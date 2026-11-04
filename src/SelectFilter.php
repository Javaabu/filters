<?php

namespace Javaabu\Filters;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Javaabu\Filters\Exceptions\FiltersException;
use Javaabu\Filters\Fields\FilterSelect;
use Javaabu\Stencil\Fields\Select;

/**
 * Pick one value (or several, with multiple()) from options: an array, an
 * enum, a closure, a relationship, or a URL searched as the user types.
 * Values that aren't options are ignored.
 */
class SelectFilter extends Filter
{
    protected mixed $options = null;

    protected ?string $relationship = null;

    protected string $relationship_title = 'name';

    protected ?Closure $modify_relationship_query = null;

    protected bool $is_multiple = false;

    protected bool $is_searchable = false;

    protected string|Closure|null $ajax_url = null;

    /** @var array{0: string, 1: string} */
    protected array $ajax_fields = ['name', 'id'];

    protected ?Closure $get_option_labels_using = null;

    protected string|Closure|null $placeholder = null;

    /** @var array<int|string, string>|null */
    private ?array $resolved_options = null;

    /**
     * [value => label], an enum class, a collection, an Eloquent query, or a closure returning one.
     */
    public function options(mixed $options): static
    {
        $this->options = $options;
        $this->resolved_options = null;

        return $this;
    }

    /**
     * Options from a relationship of the filters' model. A BelongsTo filters
     * by its foreign key (name the filter category_id); other relationships
     * (BelongsToMany, HasMany, morphs) filter with whereHas (name it tags).
     */
    public function relationship(string $name, string $title_attribute = 'name', ?Closure $modify_query = null): static
    {
        $this->relationship = $name;
        $this->relationship_title = $title_attribute;
        $this->modify_relationship_query = $modify_query;
        $this->resolved_options = null;

        return $this;
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationship;
    }

    public function multiple(bool $condition = true): static
    {
        $this->is_multiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->is_multiple;
    }

    public function searchable(bool $condition = true): static
    {
        $this->is_searchable = $condition;

        return $this;
    }

    /**
     * Search options on the server as the user types, like stencil's
     * Select::ajax(). Give getOptionLabelsUsing() (or a relationship) so
     * chosen values show their labels, and so unknown values are ignored.
     */
    public function ajax(string|Closure $url, string $name_field = 'name', string $id_field = 'id'): static
    {
        $this->ajax_url = $url;
        $this->ajax_fields = [$name_field, $id_field];

        return $this;
    }

    public function isAjax(): bool
    {
        return $this->ajax_url !== null;
    }

    /**
     * The labels of chosen values, for remote (ajax) options:
     * fn (array $values): array [value => label].
     */
    public function getOptionLabelsUsing(?Closure $callback): static
    {
        $this->get_option_labels_using = $callback;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        $placeholder = $this->evaluate($this->placeholder);

        if ($placeholder !== null) {
            return (string) $placeholder;
        }

        return $this->is_multiple ? null : __('filters::strings.all');
    }

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    */

    /**
     * Every option as [value => label]. Empty for remote (ajax) options.
     *
     * @return array<int|string, string>
     */
    public function getOptions(): array
    {
        if ($this->isAjax()) {
            return [];
        }

        if ($this->resolved_options !== null) {
            return $this->resolved_options;
        }

        if ($this->relationship !== null && $this->options === null) {
            return $this->resolved_options = $this->relationshipQuery()
                ->orderBy($this->relationship_title)
                ->get()
                ->mapWithKeys(fn (Model $item) => [$item->getKey() => (string) $item->getAttribute($this->relationship_title)])
                ->all();
        }

        return $this->resolved_options = Select::normalizeOptions($this->evaluate($this->options));
    }

    /**
     * The labels of these values, [value => label], leaving out values that aren't options.
     *
     * @param  array<int, string>  $values
     * @return array<int|string, string>
     */
    public function getOptionLabels(array $values): array
    {
        if ($values === []) {
            return [];
        }

        if (! $this->isAjax()) {
            $options = $this->getOptions();
            $labels = [];

            foreach ($values as $value) {
                if (array_key_exists($value, $options)) {
                    $labels[$value] = $options[$value];
                }
            }

            return $labels;
        }

        if ($this->get_option_labels_using !== null) {
            return array_map('strval', (array) $this->evaluate($this->get_option_labels_using, ['values' => $values]));
        }

        if ($this->relationship !== null) {
            $query = $this->relationshipQuery();

            return $query->whereKey($values)
                ->get()
                ->mapWithKeys(fn (Model $item) => [$item->getKey() => (string) $item->getAttribute($this->relationship_title)])
                ->all();
        }

        return array_combine($values, $values);
    }

    protected function getRelationship(): Relation
    {
        $model = $this->filters?->getModel();

        if ($model === null) {
            throw FiltersException::missingModel($this->name);
        }

        return (new $model)->{$this->relationship}();
    }

    protected function relationshipQuery(): Builder
    {
        $query = $this->getRelationship()->getRelated()->newQuery();

        if ($this->modify_relationship_query !== null) {
            $query = $this->evaluate($this->modify_relationship_query, ['query' => $query], [Builder::class => $query]) ?? $query;
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Value
    |--------------------------------------------------------------------------
    */

    public function normalizeValue(mixed $value): mixed
    {
        $values = static::clean(is_array($value) ? array_values($value) : $value);

        if ($values === null) {
            return null;
        }

        $values = array_values(array_filter((array) $values, 'is_string'));

        if (! $this->is_multiple) {
            $values = array_slice($values, 0, 1);
        }

        $labels = $this->getOptionLabels($values);

        $values = array_values(array_filter($values, fn (string $value) => array_key_exists($value, $labels)));

        if ($values === []) {
            return null;
        }

        return $this->is_multiple ? $values : $values[0];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $values = (array) $value;

        if ($this->relationship === null) {
            $this->is_multiple
                ? $query->whereIn($this->qualifyColumn($query), $values)
                : $query->where($this->qualifyColumn($query), $value);

            return;
        }

        $relationship = static::modelOf($query)?->{$this->relationship}();

        if ($relationship instanceof BelongsTo) {
            $query->whereIn($relationship->getQualifiedForeignKeyName(), $values);

            return;
        }

        $query->whereHas($this->relationship, fn (Builder $related) => $related->whereKey($values));
    }

    protected function defaultFields(): array
    {
        $field = FilterSelect::make($this->name)
            ->label($this->getLabel())
            ->placeholder($this->getPlaceholder())
            ->multiple($this->is_multiple)
            ->searchable($this->is_searchable);

        if (! $this->isAjax()) {
            return [$field->options($this->getOptions())];
        }

        $value = $this->filters?->getValue($this->name);

        return [
            $field
                ->ajax($this->evaluate($this->ajax_url), ...$this->ajax_fields)
                ->selectedOptions($value === null ? [] : $this->getOptionLabels((array) $value)),
        ];
    }

    protected function formatChipValue(mixed $value): ?string
    {
        return static::joinLabels(array_values($this->getOptionLabels((array) $value)));
    }
}
