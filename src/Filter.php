<?php

namespace Javaabu\Filters;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Javaabu\Filters\Exceptions\FiltersException;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Concerns\EvaluatesClosures;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Fields\TextInput;
use UnitEnum;

/**
 * One filter of an index page: the stencil fields it renders, the query
 * parameters it reads, and what it does to the query. Use a filter type
 * (TextFilter, SelectFilter, …), or this class with schema() and query()
 * for a custom filter.
 */
class Filter
{
    use EvaluatesClosures;

    protected string $name;

    protected string|Closure|null $label = null;

    protected ?string $column = null;

    protected ?Closure $query = null;

    protected mixed $default = null;

    protected bool|Closure $is_hidden = false;

    /** @var array<int, Field>|Closure|null */
    protected array|Closure|null $schema = null;

    protected ?Closure $format_chip_using = null;

    protected int|string|array|Closure|null $column_span = null;

    protected ?Filters $filters = null;

    final public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(?string $name = null): static
    {
        $filter = app(static::class, ['name' => $name ?? static::getDefaultName()]);

        $filter->setUp();

        return $filter;
    }

    /**
     * The name used when make() is called without one.
     */
    public static function getDefaultName(): string
    {
        throw FiltersException::missingName(static::class);
    }

    /**
     * Configure defaults for the filter type. Called by make().
     */
    protected function setUp(): void {}

    /*
    |--------------------------------------------------------------------------
    | Identity
    |--------------------------------------------------------------------------
    */

    public function getName(): string
    {
        return $this->name;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The label: the given one, or one from the name (category_id → Category, translated).
     */
    public function getLabel(): string
    {
        $label = $this->evaluate($this->label);

        if (filled($label)) {
            return (string) $label;
        }

        $name = Str::of($this->name)->beforeLast('_id')->camel()->snake()->replace('_', ' ')->title()->toString();

        return (string) trans($name);
    }

    /**
     * The column the filter compares. Default: the filter's name.
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
     * Qualify the column with the query's table, unless it already has one.
     */
    protected function qualifyColumn(Builder $query, ?string $column = null): string
    {
        $column ??= $this->getColumn();

        $model = static::modelOf($query);

        if (str_contains($column, '.') || $model === null) {
            return $column;
        }

        return $model->qualifyColumn($column);
    }

    /**
     * The model a query (or relationship) is for.
     */
    public static function modelOf(Builder $query): ?Model
    {
        if ($query instanceof Relation) {
            return $query->getRelated();
        }

        if (method_exists($query, 'getModel')) {
            $model = $query->getModel();

            return $model instanceof Model ? $model : null;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    /**
     * The stencil fields this filter renders. Each type has its own; set them
     * for a custom filter. Field names are the query parameters the filter reads.
     *
     * @param  array<int, Field>|Closure  $fields
     */
    public function schema(array|Closure $fields): static
    {
        $this->schema = $fields;

        return $this;
    }

    /**
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        $fields = $this->schema === null ? $this->defaultFields() : $this->evaluate($this->schema);

        return array_values(array_filter($fields));
    }

    /**
     * What the filter bar renders: the fields, or a layout around them (ranges
     * put theirs in a two-column group), with the filter's column span.
     *
     * @return array<int, Component>
     */
    public function getComponents(): array
    {
        $span = $this->evaluate($this->column_span) ?? $this->defaultColumnSpan();

        return array_map(
            fn (Component $component) => $span === null ? $component : $component->columnSpan($span),
            $this->defaultComponents(),
        );
    }

    /**
     * @return array<int, Component>
     */
    protected function defaultComponents(): array
    {
        return $this->getFields();
    }

    /**
     * @return int|string|array<string, int|string>|null
     */
    protected function defaultColumnSpan(): int|string|array|null
    {
        return null;
    }

    /**
     * @return array<int, Field>
     */
    protected function defaultFields(): array
    {
        return [
            TextInput::make($this->name)->label($this->getLabel()),
        ];
    }

    /**
     * Grid columns the filter's fields take in the filter bar.
     */
    public function columnSpan(int|string|array|Closure|null $span): static
    {
        $this->column_span = $span;

        return $this;
    }

    /**
     * Don't render the filter in the filter bar. It still applies when its
     * parameter is in the URL (from a link, say), and shows a chip.
     */
    public function hidden(bool|Closure $condition = true): static
    {
        $this->is_hidden = $condition;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->is_hidden = $condition instanceof Closure ? fn () => ! $this->evaluate($condition) : ! $condition;

        return $this;
    }

    public function isHidden(): bool
    {
        return (bool) $this->evaluate($this->is_hidden);
    }

    /*
    |--------------------------------------------------------------------------
    | Value
    |--------------------------------------------------------------------------
    */

    /**
     * The query parameters the filter reads: its name, or its fields' names.
     *
     * @return array<int, string>
     */
    public function getQueryKeys(): array
    {
        if ($this->schema === null) {
            return [$this->name];
        }

        return array_values(array_unique(array_map(
            fn (Field $field) => Str::before($field->getName(), '.'),
            $this->getFields(),
        )));
    }

    /**
     * The value when none of the filter's parameters are in the URL.
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function hasDefault(): bool
    {
        return $this->default !== null;
    }

    public function getDefault(): mixed
    {
        return $this->evaluate($this->default);
    }

    /**
     * The filter's value from the query string: its default when none of its
     * parameters are there, and null when it doesn't apply.
     *
     * @param  array<string, mixed>  $input
     */
    public function resolveValue(array $input): mixed
    {
        $keys = $this->getQueryKeys();

        if (Arr::hasAny($input, $keys)) {
            return $this->normalizeValue($this->readInput($input, $keys));
        }

        if (! $this->hasDefault()) {
            return null;
        }

        return $this->normalizeValue($this->toPlainValue($this->getDefault()));
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $keys
     */
    protected function readInput(array $input, array $keys): mixed
    {
        if ($keys === [$this->name]) {
            return $input[$this->name] ?? null;
        }

        return Arr::only($input, $keys);
    }

    /**
     * Turns enums and other objects in a default into query-string values.
     */
    protected function toPlainValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->toPlainValue($item), $value);
        }

        return $value;
    }

    /**
     * Cleans what came from the query string: trims strings and drops empty
     * values. Returns null when the filter doesn't apply. Filter types also
     * drop values that aren't allowed, so invalid input is ignored, never thrown.
     */
    public function normalizeValue(mixed $value): mixed
    {
        return static::clean($value);
    }

    protected static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) (is_bool($value) ? (int) $value : $value);
        }

        if (! is_array($value)) {
            return null;
        }

        $cleaned = array_filter(array_map(fn ($item) => static::clean($item), $value), fn ($item) => $item !== null);

        if ($cleaned === []) {
            return null;
        }

        return array_is_list($value) ? array_values($cleaned) : $cleaned;
    }

    /**
     * The value as query parameters (and form state), keyed by parameter name.
     *
     * @return array<string, mixed>
     */
    public function toQueryParameters(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if ($this->getQueryKeys() === [$this->name]) {
            return [$this->name => $value];
        }

        return (array) $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    */

    /**
     * What the filter does to the query: fn (Builder $query, $value). The
     * closure can also ask for $filter and $filters.
     */
    public function query(?Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    /**
     * Apply the filter's value to the query.
     */
    public function apply(Builder $query, mixed $value): void
    {
        if ($this->query !== null) {
            $this->evaluate($this->query, ['query' => $query, 'value' => $value], [Builder::class => $query]);

            return;
        }

        $this->applyToQuery($query, $value);
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        if ($this->getQueryKeys() !== [$this->name]) {
            throw FiltersException::missingQuery($this->name);
        }

        if (is_array($value)) {
            $query->whereIn($this->qualifyColumn($query), $value);

            return;
        }

        $query->where($this->qualifyColumn($query), $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Chips
    |--------------------------------------------------------------------------
    */

    /**
     * How the value reads in the applied-filter chip: fn ($value): ?string.
     * Return null for a chip with only the label.
     */
    public function formatChipUsing(?Closure $callback): static
    {
        $this->format_chip_using = $callback;

        return $this;
    }

    public function getChipValue(mixed $value): ?string
    {
        if ($this->format_chip_using !== null) {
            $formatted = $this->evaluate($this->format_chip_using, ['value' => $value]);

            return $formatted === null ? null : (string) $formatted;
        }

        return $this->formatChipValue($value);
    }

    protected function formatChipValue(mixed $value): ?string
    {
        if (is_array($value)) {
            return static::joinLabels(Arr::flatten($value));
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * @param  array<int, mixed>  $labels
     */
    protected static function joinLabels(array $labels): string
    {
        return implode(__('filters::strings.list_separator'), array_map('strval', $labels));
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    public function setFilters(Filters $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): ?Filters
    {
        return $this->filters;
    }

    protected function closureArgumentsByName(): array
    {
        return [
            'filter' => fn () => $this,
            'filters' => fn () => $this->filters,
            'model' => fn () => $this->filters?->getModel(),
        ];
    }

    protected function closureArgumentsByType(): array
    {
        return [
            self::class => fn () => $this,
            Filters::class => fn () => $this->filters,
        ];
    }
}
