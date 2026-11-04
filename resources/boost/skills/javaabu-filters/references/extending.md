# Extending and reusing javaabu/filters

## Custom filters

A type with your own query (the type still cleans the value and renders the field):

```php
SelectFilter::make('age')->options(['new' => __('This week'), 'old' => __('Older')])
    ->query(fn (Builder $query, string $value) => $value === 'new'
        ? $query->where('created_at', '>=', now()->subWeek())
        : $query->where('created_at', '<', now()->subWeek())),
```

Fields of your own: the field names are the query parameters; with several fields the value is an array keyed by name, and `query()` is required.

```php
Filter::make('words')->label(__('Length'))
    ->schema([TextInput::make('min_words')->integer(), Checkbox::make('featured_only')])
    ->query(fn (Builder $query, array $value) => $query->where('word_count', '>=', (int) ($value['min_words'] ?? 0)))
    ->columnSpan(2),
```

A filter class extends a type and overrides `setUp()`, `getDefaultName()`, `getQueryKeys()`, `normalizeValue()` (return null when it doesn't apply), `defaultFields()`, `defaultComponents()`, `applyToQuery()`, `toQueryParameters()`, `formatChipValue()`.

## Query string

Flat parameters, as javaabu/forms writes them: `search`, `status[]`, `published_at_from`/`_to`, `word_count_min`/`_max`, `trashed`, `orderby`, `order` (`ASC`/`DESC`), `per_page` (preserved), `page` (dropped on change). Empty values don't apply; `?status=` turns off a default. Invalid values and unregistered sorts are ignored. One filter set per page.

## Reusing the query

```php
$parameters = $filters->getQueryParameters();                       // filters + requested sort + preserved
$query = ArticleFilters::make()->fromQuery($parameters)->apply(Article::query());
```

"All matching" bulk actions: post `$filters->getFilterParameters()` as `filters[...]`, then `ArticleFilters::make()->fromQuery($request->array('filters'))->applyFilters($query)`. Values are cleaned again. Queued exports store the parameters on the job. Filters classes used in jobs must not depend on `request()` in `filters()`.

Packages render either stack: `$filters->render()` returns the Blade bar or the array by `config('filters.views')` (fallback `config('stencil.views')`).

## JSON (`$filters->toArray()`, typed `FiltersData`)

`id`, `action`, `form` (stencil schema, embedded, GET, `state`), `hidden` (hidden filters, requested sort, preserved), `active`, `chips [{filter, label, value, text, url}]`, `clearUrl`, `resetUrl`, `sort {param, directionParam, current, direction, default, defaultDirection, options [{value, label, defaultDirection, url}]}`, `strings`. URLs are built on the server.

## Views

`php artisan vendor:publish --tag=filters-views` (Blade: `components/bar`, `chips`, `sort`, `heading`). React files are overridden through manifest: `php artisan manifest:override filters js/FilterChips.tsx`. Keep the form id (`filter`).
