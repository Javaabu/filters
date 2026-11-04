<?php

namespace Javaabu\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Javaabu\Stencil\Fields\TextInput;

/**
 * A text search over one or more columns, including columns of related
 * models (author.name). Without columns, it uses the model's search scope
 * when it has one, else the filter's column.
 */
class TextFilter extends Filter
{
    public const string CONTAINS = 'contains';

    public const string STARTS_WITH = 'starts_with';

    public const string ENDS_WITH = 'ends_with';

    public const string EXACT = 'exact';

    /** @var array<int, string> */
    protected array $columns = [];

    protected string $operator = self::CONTAINS;

    protected bool $is_thaana = false;

    protected ?string $placeholder = null;

    protected int $max_length = 255;

    public static function getDefaultName(): string
    {
        return 'search';
    }

    public function getLabel(): string
    {
        if ($this->name !== 'search' || filled($this->evaluate($this->label))) {
            return parent::getLabel();
        }

        return __('filters::strings.search');
    }

    /**
     * The columns to search. Use relation.column for related models.
     *
     * @param  array<int, string>  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * How a column matches: contains (default), starts_with, ends_with or exact.
     */
    public function operator(string $operator): static
    {
        if (! in_array($operator, [self::CONTAINS, self::STARTS_WITH, self::ENDS_WITH, self::EXACT], true)) {
            throw new InvalidArgumentException("Unknown text filter operator [{$operator}].");
        }

        $this->operator = $operator;

        return $this;
    }

    /**
     * The input types Thaana (for searching Dhivehi text).
     */
    public function thaana(bool $condition = true): static
    {
        $this->is_thaana = $condition;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function maxLength(int $length): static
    {
        $this->max_length = $length;

        return $this;
    }

    public function normalizeValue(mixed $value): mixed
    {
        $value = static::clean($value);

        if (! is_string($value)) {
            return null;
        }

        return mb_substr($value, 0, $this->max_length);
    }

    protected function defaultFields(): array
    {
        $placeholder = $this->placeholder ?? ($this->name === 'search' ? __('filters::strings.search_placeholder') : null);

        return [
            TextInput::make($this->name)
                ->label($this->getLabel())
                ->placeholder($placeholder)
                ->maxLength($this->max_length)
                ->thaana($this->is_thaana),
        ];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        if ($this->columns === [] && $this->column === null && $this->hasSearchScope($query)) {
            $query->search($value);

            return;
        }

        $columns = $this->columns ?: [$this->getColumn()];

        $query->where(function (Builder $query) use ($columns, $value) {
            foreach ($columns as $column) {
                if (! str_contains($column, '.')) {
                    $this->whereMatches($query, $this->qualifyColumn($query, $column), $value, 'or');

                    continue;
                }

                $relation = Str::beforeLast($column, '.');
                $related_column = Str::afterLast($column, '.');

                $query->orWhereHas($relation, fn (Builder $related) => $this->whereMatches(
                    $related,
                    $this->qualifyColumn($related, $related_column),
                    $value,
                ));
            }
        });
    }

    protected function hasSearchScope(Builder $query): bool
    {
        return static::modelOf($query)?->hasNamedScope('search') ?? false;
    }

    protected function whereMatches(Builder $query, string $column, string $value, string $boolean = 'and'): void
    {
        if ($this->operator === self::EXACT) {
            $query->where($column, '=', $value, $boolean);

            return;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);

        $pattern = match ($this->operator) {
            self::STARTS_WITH => "{$escaped}%",
            self::ENDS_WITH => "%{$escaped}",
            default => "%{$escaped}%",
        };

        $base = $query->getQuery();

        if ($base->getConnection()->getDriverName() !== 'sqlite') {
            $query->where($column, 'like', $pattern, $boolean);

            return;
        }

        // SQLite has no default escape character for LIKE.
        $query->whereRaw($base->getGrammar()->wrap($column) . " like ? escape '\\'", [$pattern], $boolean);
    }
}
