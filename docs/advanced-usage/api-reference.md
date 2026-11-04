---
title: API reference
sidebar_position: 8
---

# Filters

`Javaabu\Filters\Filters`. Make one with `Filters::make(array $filters = [], array $sorts = [])`, or extend it.

## Defining

| Member | What |
| --- | --- |
| `filters(): array` (protected) | A class's filters |
| `sorts(): array` (protected) | A class's sorts: `Sort` or column names |
| `setUp(): void` (protected) | Called by `make()` |
| `withFilters(array)`, `withSorts(array)` | Add filters or sorts |
| `model(?string)`, `getModel()`, `protected ?string $model` | The listed model |
| `id(?string)`, `getId()`, `protected ?string $id` | The form id, default `filter` |
| `action(?string)`, `getAction()`, `protected ?string $action` | The form's URL, default the current URL |
| `preserve(array)`, `getPreservedKeys()`, `protected ?array $preserve` | Parameters kept when filtering |
| `columns(int\|array\|null)`, `getColumns()`, `protected int\|array\|null $columns` | The bar's grid |
| `defaultSort(?string, string $direction = 'asc')`, `protected ?string $default_sort`, `protected string $default_direction` | The default sort |

## Reading

| Method | Returns |
| --- | --- |
| `fromRequest(Request)`, `fromQuery(array)` | `static` |
| `getInput()` | The query parameters read |
| `getFilters()`, `getFilter(name)`, `hasFilter(name)`, `getVisibleFilters()` | Filters by name |
| `getValues()`, `getValue(name)`, `isActive(name)` | The values that apply |
| `getActiveFilters()`, `hasActiveFilters()`, `countActiveFilters()` | |
| `getSorts()`, `hasSort(name)`, `getSortOptions()` | `[name => Sort]`, `[name => label]` |
| `getSortName()`, `getDirection()`, `isSortedBy(sort, ?direction)`, `getRequestedSort()`, `getDefaultSort()`, `getDefaultDirection()` | |
| `getSortParameter()`, `getDirectionParameter()` | `orderby`, `order` |

## Querying

| Method | What |
| --- | --- |
| `apply(Builder)` | Filters and sort; returns the query |
| `applyFilters(Builder)`, `applySort(Builder)` | One half |
| `Builder::applyFilters(Filters)` | Eloquent macro for `apply()` |

## URLs and parameters

| Method | What |
| --- | --- |
| `getQueryParameters()` | Filters, requested sort and preserved parameters |
| `getFilterParameters()`, `getSortParameters()`, `getPreservedParameters()`, `getHiddenParameters()` | Parts |
| `url(array)` | The action with these parameters |
| `urlWithout(Filter\|string\|array)` | Without some filters |
| `clearUrl()`, `resetUrl()` | No filters; the defaults |
| `sortUrl(sort, ?direction)` | Sorted |

## Rendering

| Method | What |
| --- | --- |
| `form()` | The fields as a stencil `Form` (embedded, `GET`, filled) |
| `chips()` | `array<Chip>` |
| `getStrings()` | UI strings in the app locale |
| `render(?driver)` | `blade` → `HtmlString`, `inertia`/`json` → array; default `config('filters.views')` then `config('stencil.views')` |
| `toHtml()` | The Blade bar (`{{ $filters }}`) |
| `toArray()`, `jsonSerialize()` | The [JSON](./json-format.md) |

`Filters` is `Htmlable` but not `Stringable`, on purpose: Blade casts `Stringable` component attributes to strings.

# Filter

`Javaabu\Filters\Filter`, the base of every type, and a custom filter on its own.

| Method | What |
| --- | --- |
| `make(?name)` | |
| `getName()`, `label()`, `getLabel()` | |
| `column(?string)`, `getColumn()` | Default: the name |
| `schema(array\|Closure)`, `getFields()`, `getComponents()` | Stencil fields; what the bar renders |
| `columnSpan()` | Grid columns in the bar |
| `hidden()`, `visible()`, `isHidden()` | Not in the bar, still applies |
| `default()`, `hasDefault()`, `getDefault()` | |
| `getQueryKeys()`, `resolveValue(array)`, `normalizeValue($value)`, `toQueryParameters($value)` | |
| `query(?Closure)`, `apply(Builder, $value)` | |
| `formatChipUsing(?Closure)`, `getChipValue($value)` | |
| `getFilters()` | The filters object it belongs to |
| `Filter::modelOf(Builder)` (static) | The model of a query or relationship |

# Types

| Type | Methods |
| --- | --- |
| `TextFilter` | `columns()`, `getColumns()`, `operator(CONTAINS\|STARTS_WITH\|ENDS_WITH\|EXACT)`, `thaana()`, `placeholder()`, `maxLength()` |
| `SelectFilter` | `options()`, `getOptions()`, `getOptionLabels(array)`, `relationship(name, title = 'name', ?modify)`, `getRelationshipName()`, `multiple()`, `isMultiple()`, `searchable()`, `ajax(url, name_field = 'name', id_field = 'id')`, `isAjax()`, `getOptionLabelsUsing()`, `placeholder()`, `getPlaceholder()` |
| `MultiSelectFilter` | A `SelectFilter` with `multiple()` |
| `BooleanFilter` | |
| `TernaryFilter` | `nullable()`, `trueLabel()`, `falseLabel()`, `placeholder()`, `queries(true:, false:)`, `getOptions()` |
| `DateFilter`, `DateTimeFilter` | `operator('='\|'<'\|'<='\|'>'\|'>=')`, `getOperator()` |
| `DateRangeFilter` | `withTime()`, `parameters(from, to)`, `getFromParameter()`, `getToParameter()` |
| `NumberRangeFilter` | `integer()`, `step()`, `parameters(min, max)` |
| `TrashedFilter` | `getOptions()`; constants `WITH`, `ONLY` |

# Sort

`Javaabu\Filters\Sort::make(name)`: `label()`, `getLabel()`, `column()`, `getColumn()`, `using(fn (Builder $query, string $direction))`, `defaultDirection()`, `getDefaultDirection()`, `apply(Builder, direction)`, `Sort::normalizeDirection($value)`.

# Chip

`Javaabu\Filters\Chip`: readonly `filter`, `label`, `value` (`?string`), `url`; `text()`, `toArray()`.

# Blade

`<x-filters::bar :filters sort chips export>`, `<x-filters::chips :filters>`, `<x-filters::sort :filters>`, `<x-filters::heading :filters sort label>`.

# React (`@javaabu/filters`)

`FilterBar`, `FilterChips`, `SortSelect`, `SortableHeading`, `hiddenFields(hidden, skip?)`, `hiddenInput(form, name)`, and the types `FiltersData`, `FilterChip`, `FiltersSort`, `FiltersSortOption`, `FiltersStrings`, `SortDirection`.

# Exceptions

`Javaabu\Filters\Exceptions\FiltersException` (an `InvalidArgumentException`): a type without a name, duplicate names, a non-filter in the list, a relationship filter without a model, a custom filter with several fields and no `query()`.
