<?php

namespace Javaabu\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Javaabu\Filters\Exceptions\FiltersException;
use Javaabu\Stencil\Form;
use JsonSerializable;
use Stringable;

/**
 * The filters and sorts of an index page, bound to the query string. Build
 * one inline with Filters::make([...], [...]), or extend it and return them
 * from filters() and sorts().
 *
 * @implements Arrayable<string, mixed>
 */
class Filters implements Arrayable, Htmlable, JsonSerializable, Stringable
{
    /** @var class-string<Model>|null */
    protected ?string $model = null;

    protected ?string $id = null;

    protected ?string $default_sort = null;

    protected string $default_direction = 'asc';

    /** @var array<int, string>|null */
    protected ?array $preserve = null;

    /** @var int|array<string, int>|null */
    protected int|array|null $columns = null;

    protected ?string $action = null;

    /** @var array<int, Filter> */
    protected array $added_filters = [];

    /** @var array<int, Sort|string> */
    protected array $added_sorts = [];

    /** @var array<string, mixed>|null */
    protected ?array $input = null;

    /** @var array<string, Filter>|null */
    private ?array $resolved_filters = null;

    /** @var array<string, Sort>|null */
    private ?array $resolved_sorts = null;

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    /**
     * @param  array<int, Filter|null>  $filters
     * @param  array<int, Sort|string>  $sorts
     */
    public static function make(array $filters = [], array $sorts = []): static
    {
        $instance = app(static::class);

        $instance->withFilters($filters)->withSorts($sorts);

        $instance->setUp();

        return $instance;
    }

    /**
     * Configure the filters. Called by make(); override it in a filters class.
     */
    protected function setUp(): void {}

    /**
     * The filters of a filters class.
     *
     * @return array<int, Filter|null>
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * The sorts of a filters class: Sort instances or column names.
     *
     * @return array<int, Sort|string>
     */
    protected function sorts(): array
    {
        return [];
    }

    /**
     * @param  array<int, Filter|null>  $filters
     */
    public function withFilters(array $filters): static
    {
        $this->added_filters = [...$this->added_filters, ...array_filter($filters)];

        return $this->flush();
    }

    /**
     * @param  array<int, Sort|string>  $sorts
     */
    public function withSorts(array $sorts): static
    {
        $this->added_sorts = [...$this->added_sorts, ...$sorts];

        return $this->flush();
    }

    protected function flush(): static
    {
        $this->resolved_filters = null;
        $this->resolved_sorts = null;
        $this->values = null;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * The model being listed: for relationship filters' options.
     *
     * @param  class-string<Model>|null  $model
     */
    public function model(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * The filter form's id. Default: filter, the form javaabu/forms' sortable
     * table headings submit.
     */
    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id ?? 'filter';
    }

    /**
     * Where the filter form submits and chips link to. Default: the current URL.
     */
    public function action(?string $url): static
    {
        $this->action = $url;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action ?? request()->url();
    }

    /**
     * Query parameters kept when filters are submitted or cleared. Default:
     * config('filters.query.preserve').
     *
     * @param  array<int, string>  $keys
     */
    public function preserve(array $keys): static
    {
        $this->preserve = $keys;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getPreservedKeys(): array
    {
        return $this->preserve ?? (array) config('filters.query.preserve', []);
    }

    /**
     * Grid columns of the filter bar: a number, or columns per breakpoint.
     *
     * @param  int|array<string, int>|null  $columns
     */
    public function columns(int|array|null $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return int|array<string, int>|null
     */
    public function getColumns(): int|array|null
    {
        return $this->columns ?? config('filters.columns');
    }

    /*
    |--------------------------------------------------------------------------
    | Input
    |--------------------------------------------------------------------------
    */

    /**
     * Read the filters from a request's query string. Default: the current request.
     */
    public function fromRequest(Request $request): static
    {
        return $this->fromQuery($request->query());
    }

    /**
     * Read the filters from query parameters: for rebuilding the same query
     * elsewhere, like a queued export or "all matching" bulk actions.
     *
     * @param  array<string, mixed>  $query
     */
    public function fromQuery(array $query): static
    {
        $this->input = $query;
        $this->values = null;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return $this->input ?? request()->query();
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, Filter>
     */
    public function getFilters(): array
    {
        if ($this->resolved_filters !== null) {
            return $this->resolved_filters;
        }

        $filters = [];

        foreach ([...$this->filters(), ...$this->added_filters] as $filter) {
            if ($filter === null) {
                continue;
            }

            if (! $filter instanceof Filter) {
                throw FiltersException::invalidFilter($filter);
            }

            if (array_key_exists($filter->getName(), $filters)) {
                throw FiltersException::duplicateFilter($filter->getName());
            }

            $filters[$filter->getName()] = $filter->setFilters($this);
        }

        return $this->resolved_filters = $filters;
    }

    public function getFilter(string $name): ?Filter
    {
        return $this->getFilters()[$name] ?? null;
    }

    public function hasFilter(string $name): bool
    {
        return $this->getFilter($name) !== null;
    }

    /**
     * The filters rendered in the filter bar.
     *
     * @return array<string, Filter>
     */
    public function getVisibleFilters(): array
    {
        return array_filter($this->getFilters(), fn (Filter $filter) => ! $filter->isHidden());
    }

    /**
     * The values of the filters that apply, by filter name.
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $input = $this->getInput();
        $values = [];

        foreach ($this->getFilters() as $name => $filter) {
            $value = $filter->resolveValue($input);

            if ($value !== null) {
                $values[$name] = $value;
            }
        }

        return $this->values = $values;
    }

    public function getValue(string $name): mixed
    {
        return $this->getValues()[$name] ?? null;
    }

    public function isActive(string $name): bool
    {
        return array_key_exists($name, $this->getValues());
    }

    /**
     * The filters that apply.
     *
     * @return array<string, Filter>
     */
    public function getActiveFilters(): array
    {
        return array_intersect_key($this->getFilters(), $this->getValues());
    }

    public function hasActiveFilters(): bool
    {
        return $this->getValues() !== [];
    }

    public function countActiveFilters(): int
    {
        return count($this->getValues());
    }

    /*
    |--------------------------------------------------------------------------
    | Sorts
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, Sort>
     */
    public function getSorts(): array
    {
        if ($this->resolved_sorts !== null) {
            return $this->resolved_sorts;
        }

        $sorts = [];

        foreach ([...$this->sorts(), ...$this->added_sorts] as $sort) {
            $sort = is_string($sort) ? Sort::make($sort) : $sort;

            $sorts[$sort->getName()] = $sort;
        }

        return $this->resolved_sorts = $sorts;
    }

    public function hasSort(string $name): bool
    {
        return array_key_exists($name, $this->getSorts());
    }

    /**
     * The sort used when the URL has none (or one that isn't allowed). It
     * doesn't have to be one of the sorts users can choose.
     */
    public function defaultSort(?string $sort, string $direction = 'asc'): static
    {
        $this->default_sort = $sort;
        $this->default_direction = Sort::normalizeDirection($direction) ?? 'asc';

        return $this;
    }

    public function getDefaultSort(): ?string
    {
        return $this->default_sort;
    }

    public function getDefaultDirection(): string
    {
        return $this->default_direction;
    }

    public function getSortParameter(): string
    {
        return (string) config('filters.query.sort', 'orderby');
    }

    public function getDirectionParameter(): string
    {
        return (string) config('filters.query.direction', 'order');
    }

    /**
     * The sort the URL asks for, when it's one of the sorts.
     */
    public function getRequestedSort(): ?string
    {
        $sort = $this->getInput()[$this->getSortParameter()] ?? null;

        return is_string($sort) && $this->hasSort($sort) ? $sort : null;
    }

    /**
     * The sort that applies: the requested one, or the default.
     */
    public function getSortName(): ?string
    {
        return $this->getRequestedSort() ?? $this->default_sort;
    }

    /**
     * The direction that applies: asc or desc.
     */
    public function getDirection(): string
    {
        $direction = Sort::normalizeDirection($this->getInput()[$this->getDirectionParameter()] ?? null);

        if ($direction !== null) {
            return $direction;
        }

        $requested = $this->getRequestedSort();

        if ($requested !== null) {
            return $this->getSorts()[$requested]->getDefaultDirection();
        }

        return $this->default_direction;
    }

    public function isSortedBy(string $sort, ?string $direction = null): bool
    {
        if ($this->getSortName() !== $sort) {
            return false;
        }

        return $direction === null || Sort::normalizeDirection($direction) === $this->getDirection();
    }

    /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    */

    /**
     * Apply the filters and the sort to the query.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function apply(Builder $query): Builder
    {
        return $this->applySort($this->applyFilters($query));
    }

    /**
     * Apply only the filters.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function applyFilters(Builder $query): Builder
    {
        $this->rememberModel($query);

        foreach ($this->getActiveFilters() as $name => $filter) {
            $filter->apply($query, $this->getValue($name));
        }

        return $query;
    }

    /**
     * Apply only the sort.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function applySort(Builder $query): Builder
    {
        $name = $this->getSortName();

        if ($name === null) {
            return $query;
        }

        $sort = $this->getSorts()[$name] ?? Sort::make($name);

        $sort->apply($query, $this->getDirection());

        return $query;
    }

    protected function rememberModel(Builder $query): void
    {
        if ($this->model !== null) {
            return;
        }

        $model = Filter::modelOf($query);

        $this->model = $model === null ? null : $model::class;
    }

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    /**
     * The query parameters of the current filters, sort and preserved keys:
     * what to carry along to rebuild the same list elsewhere.
     *
     * @return array<string, mixed>
     */
    public function getQueryParameters(): array
    {
        return [
            ...$this->getFilterParameters(),
            ...$this->getSortParameters(),
            ...$this->getPreservedParameters(),
        ];
    }

    /**
     * The applied filters as query parameters.
     *
     * @return array<string, mixed>
     */
    public function getFilterParameters(): array
    {
        $parameters = [];

        foreach ($this->getActiveFilters() as $name => $filter) {
            $parameters = [...$parameters, ...$filter->toQueryParameters($this->getValue($name))];
        }

        return $parameters;
    }

    /**
     * The requested sort as query parameters. Empty when the default sort applies.
     *
     * @return array<string, string>
     */
    public function getSortParameters(): array
    {
        $sort = $this->getRequestedSort();

        if ($sort === null) {
            return [];
        }

        return [
            $this->getSortParameter() => $sort,
            $this->getDirectionParameter() => strtoupper($this->getDirection()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreservedParameters(): array
    {
        return Arr::only($this->getInput(), $this->getPreservedKeys());
    }

    /**
     * Parameters the filter form submits as hidden inputs: the sort, preserved
     * keys, and filters that apply but aren't in the filter bar.
     *
     * @return array<string, mixed>
     */
    public function getHiddenParameters(): array
    {
        $parameters = [];

        foreach ($this->getActiveFilters() as $name => $filter) {
            if ($filter->isHidden()) {
                $parameters = [...$parameters, ...$filter->toQueryParameters($this->getValue($name))];
            }
        }

        return [...$parameters, ...$this->getSortParameters(), ...$this->getPreservedParameters()];
    }

    /**
     * The action URL with these query parameters.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function url(array $parameters = []): string
    {
        $parameters = array_filter($parameters, fn ($value) => $value !== null && $value !== []);

        $query = Arr::query($parameters);

        return $this->getAction() . ($query === '' ? '' : '?' . $query);
    }

    /**
     * The current URL without one filter (or several): the link of its chip.
     * Filters with a default are kept empty, so the default doesn't come back.
     *
     * @param  Filter|string|array<int, Filter|string>  $filters
     */
    public function urlWithout(Filter|string|array $filters): string
    {
        $parameters = $this->getQueryParameters();

        foreach (Arr::wrap($filters) as $filter) {
            $filter = is_string($filter) ? $this->getFilter($filter) : $filter;

            if ($filter === null) {
                continue;
            }

            foreach ($filter->getQueryKeys() as $key) {
                unset($parameters[$key]);

                if ($filter->hasDefault()) {
                    $parameters[$key] = '';
                }
            }
        }

        return $this->url($parameters);
    }

    /**
     * The current URL with no filters applied, keeping the sort and preserved keys.
     */
    public function clearUrl(): string
    {
        return $this->urlWithout(array_values($this->getFilters()));
    }

    /**
     * The page as it first loads: default filters and the default sort,
     * keeping preserved keys. The filter bar's Clear link.
     */
    public function resetUrl(): string
    {
        return $this->url($this->getPreservedParameters());
    }

    /**
     * The current URL sorted by a sort. Without a direction: the sort's default,
     * or the opposite of the current one when it's already sorted by it.
     */
    public function sortUrl(string $sort, ?string $direction = null): string
    {
        $direction = Sort::normalizeDirection($direction);

        if ($direction === null && $this->isSortedBy($sort)) {
            $direction = $this->getDirection() === 'asc' ? 'desc' : 'asc';
        }

        $direction ??= ($this->getSorts()[$sort] ?? null)?->getDefaultDirection() ?? 'asc';

        return $this->url([
            ...$this->getFilterParameters(),
            $this->getSortParameter() => $sort,
            $this->getDirectionParameter() => strtoupper($direction),
            ...$this->getPreservedParameters(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Chips
    |--------------------------------------------------------------------------
    */

    /**
     * The applied filters as chips, each with the URL that clears it.
     *
     * @return array<int, Chip>
     */
    public function chips(): array
    {
        $chips = [];

        foreach ($this->getActiveFilters() as $name => $filter) {
            $chips[] = new Chip(
                filter: $name,
                label: $filter->getLabel(),
                value: $filter->getChipValue($this->getValue($name)),
                url: $this->urlWithout($filter),
            );
        }

        return $chips;
    }

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    /**
     * The filter bar's fields as a stencil form: GET, embedded (the filter bar
     * adds the <form>), filled with the current values.
     */
    public function form(): Form
    {
        $fields = [];
        $state = [];

        foreach ($this->getVisibleFilters() as $name => $filter) {
            $fields = [...$fields, ...$filter->getComponents()];
            $state = [...$state, ...$filter->toQueryParameters($this->getValue($name))];
        }

        return Form::make($this->getId())
            ->method('GET')
            ->action($this->getAction())
            ->columns($this->getColumns())
            ->embedded()
            ->schema($fields)
            ->fill($state);
    }

    /**
     * The sort options for the sort dropdown: [name => label].
     *
     * @return array<string, string>
     */
    public function getSortOptions(): array
    {
        return array_map(fn (Sort $sort) => $sort->getLabel(), $this->getSorts());
    }

    /**
     * Strings for the filter bar, chips and sort controls, in the app's locale.
     *
     * @return array<string, string>
     */
    public function getStrings(): array
    {
        return [
            'filter' => __('filters::strings.filter'),
            'reset' => __('filters::strings.reset'),
            'clearAll' => __('filters::strings.clear_all'),
            'appliedFilters' => __('filters::strings.applied_filters'),
            'remove' => __('filters::strings.remove'),
            'sortBy' => __('filters::strings.sort_by'),
            'direction' => __('filters::strings.direction'),
            'ascending' => __('filters::strings.ascending'),
            'descending' => __('filters::strings.descending'),
        ];
    }

    /**
     * Render with a driver: blade (HTML) or inertia (an array for the React
     * components). Without one, config('filters.views'), then config('stencil.views').
     */
    public function render(?string $driver = null): mixed
    {
        $driver ??= config('filters.views') ?? config('stencil.views', 'blade');

        if (in_array($driver, ['inertia', 'json', 'react'], true)) {
            return $this->toArray();
        }

        return new HtmlString(view('filters::bar', ['filters' => $this])->render());
    }

    public function toHtml(): string
    {
        return (string) $this->render('blade');
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * The filters as JSON for the React components: <FilterBar filters={filters} />.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $sorts = $this->getSorts();

        return [
            'id' => $this->getId(),
            'action' => $this->getAction(),
            'form' => $this->form()->toArray(),
            'hidden' => $this->getHiddenParameters(),
            'active' => $this->countActiveFilters(),
            'chips' => array_map(fn (Chip $chip) => $chip->toArray(), $this->chips()),
            'clearUrl' => $this->clearUrl(),
            'resetUrl' => $this->resetUrl(),
            'sort' => [
                'param' => $this->getSortParameter(),
                'directionParam' => $this->getDirectionParameter(),
                'current' => $this->getSortName(),
                'direction' => $this->getDirection(),
                'default' => $this->default_sort,
                'defaultDirection' => $this->default_direction,
                'options' => array_values(array_map(fn (Sort $sort) => [
                    'value' => $sort->getName(),
                    'label' => $sort->getLabel(),
                    'defaultDirection' => $sort->getDefaultDirection(),
                ], $sorts)),
            ],
            'strings' => $this->getStrings(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
