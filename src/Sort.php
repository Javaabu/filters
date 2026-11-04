<?php

namespace Javaabu\Filters;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Javaabu\Stencil\Concerns\EvaluatesClosures;

/**
 * A sort users can choose: a column (or a closure) and a label for the sort
 * dropdown. Only registered sorts are ever applied to the query.
 */
class Sort
{
    use EvaluatesClosures;

    protected string|Closure|null $label = null;

    protected ?string $column = null;

    protected ?Closure $using = null;

    protected string $default_direction = 'asc';

    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return app(static::class, ['name' => $name]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        $label = $this->evaluate($this->label);

        if (filled($label)) {
            return (string) $label;
        }

        return (string) trans(Str::of($this->name)->camel()->snake()->replace('_', ' ')->title()->toString());
    }

    /**
     * The column to order by. Default: the sort's name.
     */
    public function column(?string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): string
    {
        return $this->column ?? $this->name;
    }

    /**
     * Sort with a closure instead of a column: fn (Builder $query, string $direction).
     */
    public function using(?Closure $callback): static
    {
        $this->using = $callback;

        return $this;
    }

    /**
     * The direction used when the URL picks this sort without one.
     */
    public function defaultDirection(string $direction): static
    {
        $this->default_direction = static::normalizeDirection($direction) ?? 'asc';

        return $this;
    }

    public function getDefaultDirection(): string
    {
        return $this->default_direction;
    }

    public function apply(Builder $query, string $direction): void
    {
        if ($this->using !== null) {
            $this->evaluate($this->using, ['query' => $query, 'direction' => $direction], [Builder::class => $query]);

            return;
        }

        $column = $this->getColumn();

        if (! str_contains($column, '.') && method_exists($query, 'qualifyColumn')) {
            $column = $query->qualifyColumn($column);
        }

        $query->orderBy($column, $direction);
    }

    /**
     * asc or desc, from ASC, desc, … Anything else is null.
     */
    public static function normalizeDirection(mixed $direction): ?string
    {
        if (! is_string($direction)) {
            return null;
        }

        $direction = strtolower(trim($direction));

        return in_array($direction, ['asc', 'desc'], true) ? $direction : null;
    }
}
