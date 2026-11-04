---
title: Defining filters
sidebar_position: 1
---

A `Filters` object holds the filters and sorts of one index page and reads their values from the query string. Define it as a class when it's reused (the index page, its export, its bulk actions), or inline for a one-off.

# A filters class

```php
<?php

namespace App\Filters;

use App\Enums\ArticleStatuses;
use App\Models\Article;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\TernaryFilter;
use Javaabu\Filters\TextFilter;

class ArticleFilters extends Filters
{
    // The listed model, for relationship filters' options. Taken from the query in apply() when missing.
    protected ?string $model = Article::class;

    protected ?string $default_sort = 'published_at';

    protected string $default_direction = 'desc';

    protected function filters(): array
    {
        return [
            TextFilter::make('search')->columns(['title', 'author.name'])->thaana(),
            SelectFilter::make('status')->options(ArticleStatuses::class)->multiple(),
            SelectFilter::make('category_id')->relationship('category', 'name'),
            TernaryFilter::make('is_featured')->label(__('Featured')),
            DateRangeFilter::make('published_at')->label(__('Published')),
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

Other properties a class can set: `$id` (the form id, default `filter`), `$columns` (the filter bar's grid), `$preserve` (query parameters kept when filtering, default `['per_page']`) and `$action` (the URL the form submits to, default the current URL). Override `setUp()` to configure anything else; `make()` calls it.

# Inline

```php
use Javaabu\Filters\Filters;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\TextFilter;

$filters = Filters::make([
    TextFilter::make('search')->columns(['name', 'email']),
    SelectFilter::make('status')->options(UserStatuses::class),
], ['name', 'created_at'])
    ->model(User::class)
    ->defaultSort('created_at', 'desc');
```

The second argument is the sorts: `Sort` objects or column names. Add more later with `withFilters([...])` and `withSorts([...])`. `null` entries are skipped, so `$can_see_trashed ? TrashedFilter::make() : null` works.

# In the controller

```php
use App\Filters\ArticleFilters;
use App\Models\Article;

public function index()
{
    $filters = ArticleFilters::make();

    $articles = $filters->apply(Article::query()->with('category'))
        ->paginate(request()->integer('per_page', 20))
        ->withQueryString();

    return view('admin.articles.index', compact('articles', 'filters'));
}
```

`apply()` applies the filters that have a value, then the sort. It works on any Eloquent query or relationship (`$category->articles()`), and returns it. There's also a builder macro, and both halves on their own:

```php
Article::query()->applyFilters($filters);   // the same as $filters->apply(Article::query())
$filters->applyFilters($query);             // only the filters
$filters->applySort($query);                // only the sort
```

Use `withQueryString()` on the paginator so the page links keep the filters.

# Where values come from

Filters read `request()->query()` unless you give them something else:

```php
ArticleFilters::make()->fromRequest($request);
ArticleFilters::make()->fromQuery(['status' => ['draft'], 'orderby' => 'title']);
```

Each filter reads its own [query parameters](../advanced-usage/query-string.md) (a select named `status` reads `status`, a date range `published_at_from` and `published_at_to`), cleans them, and drops what isn't allowed. Empty values don't apply. Nothing invalid ever throws: a stale bookmark just shows the unfiltered list.

```php
$filters->getValues();           // ['status' => ['draft']]: the filters that apply, by name
$filters->getValue('status');    // ['draft']
$filters->isActive('status');    // true
$filters->getActiveFilters();    // ['status' => SelectFilter]
$filters->hasActiveFilters();
$filters->countActiveFilters();
```

# Labels

A filter's label comes from its name like javaabu/forms does (`category_id` → `Category`, through `__()`), or set it with `->label(__('Category'))`. Labels are used for the field and the chip, so translate them.

# Defaults

```php
SelectFilter::make('status')->options(ArticleStatuses::class)->default(ArticleStatuses::PUBLISHED),
```

A default applies when none of the filter's parameters are in the URL. Submitting the filter empty (`?status=`) turns it off, and clearing its chip keeps it empty, so the default doesn't come back. The Clear button goes back to the defaults.

# Hidden filters

```php
SelectFilter::make('author_id')->relationship('author', 'name')->hidden(),
```

A hidden filter isn't in the filter bar, but it applies when its parameter is in the URL (a "View this author's articles" link, say), shows a chip, and is kept as a hidden input when the form is submitted. `->visible(fn () => …)` hides it on a condition.

:::warning
Hiding is not authorization: a hidden filter still applies from the URL. To keep a filter from some users, leave it out: `$user->can('restore', Article::class) ? TrashedFilter::make() : null`. Scope what a user may see in the query you pass to `apply()`.
:::

# Closures

Labels, options, defaults, `hidden()` and `query()` take closures. Ask for what you need by name or type:

| Argument | What |
| --- | --- |
| `$filter`, `Filter` | The filter |
| `$filters`, `Filters` | The filters object |
| `$model` | The listed model class |
| `$query`, `Builder` | In `query()` and `using()` |
| `$value` | In `query()` and `formatChipUsing()` |
| Any class | Resolved from the container |
