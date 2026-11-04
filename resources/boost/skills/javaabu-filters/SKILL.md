---
name: javaabu-filters
description: Filter and sort Laravel index pages with javaabu/filters. Use when adding filters or sorts to an index or listing page with Javaabu\Filters\Filters (a filters class with filters() and sorts(), or Filters::make()), its types (TextFilter, SelectFilter, MultiSelectFilter, BooleanFilter, TernaryFilter, DateFilter, DateTimeFilter, DateRangeFilter, NumberRangeFilter, TrashedFilter, custom Filter with schema() and query()), Sort, applying them with $filters->apply($query), rendering the filter bar, chips and sort controls in Blade (<x-filters::bar>, <x-filters::heading>) or Inertia React (FilterBar, FilterChips, SortableHeading from @javaabu/filters), or rebuilding the filtered query for exports and "all matching" bulk actions with getQueryParameters() and fromQuery().
license: MIT
metadata:
  author: Javaabu
---

# Index page filters with javaabu/filters

The docs ship with the package: `vendor/javaabu/filters/llms.txt` lists every page in `vendor/javaabu/filters/docs/`.

| Task | Read |
| --- | --- |
| Define, apply and render filters | This file |
| Every type, method and component | [references/api.md](references/api.md) |
| Custom filters, the query string, reusing filters, the JSON | [references/extending.md](references/extending.md) |

## A filters class

```php
<?php

namespace App\Filters;

use App\Enums\ArticleStatuses;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Filters\BooleanFilter;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\TernaryFilter;
use Javaabu\Filters\TextFilter;
use Javaabu\Filters\TrashedFilter;

class ArticleFilters extends Filters
{
    protected ?string $model = Article::class;

    protected ?string $default_sort = 'published_at';

    protected string $default_direction = 'desc';

    protected function filters(): array
    {
        $user = auth()->user();

        return [
            TextFilter::make('search')->columns(['title', 'author.name'])->thaana(),
            SelectFilter::make('status')->label(__('Status'))->options(ArticleStatuses::class)->multiple(),
            SelectFilter::make('category_id')->label(__('Category'))->relationship('category', 'name')->searchable(),
            SelectFilter::make('tags')->label(__('Tags'))->relationship('tags', 'name')->multiple(),
            TernaryFilter::make('is_featured')->label(__('Featured')),
            BooleanFilter::make('mine')->label(__('Only mine'))
                ->query(fn (Builder $query) => $query->whereBelongsTo($user, 'author')),
            DateRangeFilter::make('published_at')->label(__('Published')),
            SelectFilter::make('author_id')->label(__('Author'))->relationship('author', 'name')->hidden(),
            $user?->can('restore', Article::class) ? TrashedFilter::make() : null,
        ];
    }

    protected function sorts(): array
    {
        return [
            Sort::make('title')->label(__('Title')),
            Sort::make('published_at')->label(__('Published'))->defaultDirection('desc'),
        ];
    }
}
```

## The controller

```php
public function index()
{
    $this->authorize('viewAny', Article::class);

    $filters = ArticleFilters::make();

    $articles = $filters->apply(Article::query()->with('category'))
        ->paginate(request()->integer('per_page', 20))
        ->withQueryString();

    return view('admin.articles.index', compact('articles', 'filters'));
    // or: Inertia::render('Admin/Articles/Index', ['articles' => ArticleResource::collection($articles), 'filters' => $filters]);
}
```

## Rendering

```blade
{{ $filters }}   {{-- or <x-filters::bar :filters="$filters" sort export /> --}}

<x-forms::table model="articles" no-checkbox>
    <x-slot:headers>
        <x-filters::heading :filters="$filters" sort="title" />
        <x-forms::table.heading :label="__('Status')" />
        <x-filters::heading :filters="$filters" sort="published_at" :label="__('Published')" />
    </x-slot:headers>
    {{-- rows --}}
</x-forms::table>
```

```tsx
import { FilterBar, SortableHeading, type FiltersData } from "@javaabu/filters"

<FilterBar filters={filters} />
<Table headers={<><SortableHeading filters={filters} sort="title" /><TableHeading label="Status" /></>}>…</Table>
```

React needs `FormsProvider ui={mergeAdapters(onu, inertiaLinks)} router={inertia}` for Inertia visits and links.

## Rules

- Use a filters class for any list that's also exported, bulk-edited or reused; inline `Filters::make([...], [...])` for one-offs.
- Apply with `$filters->apply($query)` (or `$query->applyFilters($filters)`); paginate with `->withQueryString()`. Don't read `request('status')` yourself.
- Flat query parameters: `search`, `status[]`, `{name}_from`/`_to`, `{name}_min`/`_max`, `orderby`/`order`, `per_page`. They match javaabu/forms' table headings and search form.
- Invalid values are ignored and only registered sorts apply: don't validate filter input, don't whitelist sorts yourself.
- Name `BelongsTo` select filters by the foreign key (`category_id`) and other relationship filters by the relationship (`tags`). Relationship filters need `$model` (or `->model()`).
- `->query(fn (Builder $query, $value) => …)` changes what a type does; the type still cleans the value and renders the field.
- `hidden()` keeps a filter out of the bar but it still applies from the URL. Return `null` in `filters()` for users who mustn't use one.
- For "all matching" bulk actions and queued exports, carry `$filters->getQueryParameters()` and rebuild with `ArticleFilters::make()->fromQuery($parameters)->apply($query)`.
- Every label and option through `__()`; `->thaana()` on text filters that search Dhivehi.

## Common mistakes

- `SelectFilter::make('category')->relationship('category', 'name')`: name it `category_id`.
- A relationship filter in `Filters::make([...])` without `->model(Article::class)`: options can't load ("needs a model").
- Forgetting `->withQueryString()`: page 2 loses the filters.
- `TrashedFilter::make()->hidden()` to restrict it: it still applies with `?trashed=with`. Leave it out instead.
- `Filter::make('x')->schema([TextInput::make('a'), TextInput::make('b')])` without `->query()`: throws, since several fields can't be applied to one column.
- `(string) $filters` or `"{$filters}"`: `Filters` isn't `Stringable`. Use `{{ $filters }}`, `<x-filters::bar>` or `$filters->toHtml()`.
- Writing the filter form by hand in Blade or React: the bar renders the stencil fields and keeps the sort, `per_page` and hidden filters.
- Using spatie/laravel-query-builder's `filter[status]` format on an admin page: use javaabu/query-builder for APIs and filters for pages.
