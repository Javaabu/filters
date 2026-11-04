# javaabu/filters API

## Filters

`Filters::make(array $filters = [], array $sorts = [])`, or a subclass with `filters()`, `sorts()`, optional `setUp()`, and properties `$model`, `$id`, `$action`, `$default_sort`, `$default_direction`, `$preserve`, `$columns`.

| Method | What |
| --- | --- |
| `withFilters([...])`, `withSorts([...])` | Add |
| `model(Article::class)`, `id('filter')`, `action(url)`, `preserve(['per_page'])`, `columns(4)`, `defaultSort('created_at', 'desc')` | Configure |
| `fromRequest($request)`, `fromQuery([...])` | Read another input (default: the current query string) |
| `getFilters()`, `getFilter(name)`, `hasFilter()`, `getVisibleFilters()` | |
| `getValues()`, `getValue(name)`, `isActive(name)`, `getActiveFilters()`, `hasActiveFilters()`, `countActiveFilters()` | |
| `getSorts()`, `hasSort()`, `getSortOptions()`, `getSortName()`, `getDirection()`, `isSortedBy(sort, ?dir)`, `getRequestedSort()` | |
| `apply($query)`, `applyFilters($query)`, `applySort($query)`; macro `$query->applyFilters($filters)` | |
| `getQueryParameters()`, `getFilterParameters()`, `getSortParameters()`, `getPreservedParameters()`, `getHiddenParameters()` | |
| `url([...])`, `urlWithout(filter)`, `clearUrl()`, `resetUrl()`, `sortUrl(sort, ?dir)` | |
| `form()` (stencil Form), `chips()`, `render(?driver)`, `toHtml()`, `toArray()` | |

## Every filter

`make(?name)`, `label()`, `column()`, `default()`, `hidden()`, `visible()`, `columnSpan()`, `query(fn (Builder $query, $value))`, `formatChipUsing(fn ($value) => ?string)`, `schema([fields])`. Closures get `$filter`, `$filters`, `$model`, `$query`, `$value`, or anything by type.

## Types

| Type | Parameters | Options |
| --- | --- | --- |
| `TextFilter` (default name `search`) | `search` | `columns(['title', 'author.name'])`, `operator(TextFilter::CONTAINS\|STARTS_WITH\|ENDS_WITH\|EXACT)`, `thaana()`, `placeholder()`, `maxLength()`; no columns → the model's `search` scope |
| `SelectFilter` | `status`, `status[]` | `options(array\|enum\|closure\|collection\|query)`, `relationship(name, title, ?fn)`, `multiple()`, `searchable()`, `ajax(url, name_field, id_field)`, `getOptionLabelsUsing(fn (array $values) => [...])`, `placeholder()` |
| `MultiSelectFilter` | `status[]` | SelectFilter, multiple |
| `BooleanFilter` | `name=1` | checked → `where(column, true)` |
| `TernaryFilter` | `name=1\|0` | `nullable()`, `trueLabel()`, `falseLabel()`, `placeholder()`, `queries(true: fn, false: fn)` |
| `DateFilter` / `DateTimeFilter` | `Y-m-d` / `Y-m-d H:i` | `operator('='\|'<'\|'<='\|'>'\|'>=')` |
| `DateRangeFilter` | `{name}_from`, `{name}_to` | `withTime()`, `parameters(from, to)` |
| `NumberRangeFilter` | `{name}_min`, `{name}_max` | `integer()`, `step()`, `parameters()` |
| `TrashedFilter` (default name `trashed`) | `with`, `only` | |
| `Filter` | its fields' names | `schema([...])` + `query()` |

## Sort

`Sort::make(name)->label()->column()->using(fn (Builder $query, string $direction))->defaultDirection('desc')`; strings in `sorts()` are columns.

## Chip

`$chip->filter`, `label`, `value` (`?string`), `url`, `text()`.

## Blade

| Component | Attributes |
| --- | --- |
| `<x-filters::bar>` | `filters`, `sort`, `chips` (true), `export`, slot (extra buttons) |
| `<x-filters::chips>` | `filters` |
| `<x-filters::sort>` | `filters` |
| `<x-filters::heading>` | `filters`, `sort`, `label`, `<th>` attributes |

## React (`@javaabu/filters`)

`<FilterBar filters sort chips export components>`, `<FilterChips filters>`, `<SortSelect filters inForm>`, `<SortableHeading filters sort label>`, types `FiltersData`, `FilterChip`, `FiltersSort`.

## Config (`config/filters.php`)

`views` (null → stencil's), `query.sort` (`orderby`), `query.direction` (`order`), `query.preserve` (`['per_page']`), `date_format`, `datetime_format`, `columns`.
