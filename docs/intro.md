---
title: Introduction
sidebar_position: 1.0
---

# Filters

Filters and sorts for Laravel index pages. Define each filter once, in PHP: the fields it renders (with [javaabu/stencil](https://github.com/Javaabu/stencil)) and what it does to the query. The package reads the filters from the URL, applies them and the sort to your query, and renders the filter bar, the applied-filter chips and the sort controls in Blade ([javaabu/forms](https://github.com/Javaabu/forms)) or Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms)).

```php
use App\Enums\ArticleStatuses;
use App\Models\Article;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\TextFilter;
use Javaabu\Filters\TrashedFilter;

class ArticleFilters extends Filters
{
    protected ?string $model = Article::class;

    protected ?string $default_sort = 'published_at';

    protected string $default_direction = 'desc';

    protected function filters(): array
    {
        return [
            TextFilter::make('search')->columns(['title', 'author.name'])->thaana(),
            SelectFilter::make('status')->options(ArticleStatuses::class)->multiple(),
            SelectFilter::make('category_id')->relationship('category', 'name')->searchable(),
            DateRangeFilter::make('published_at'),
            TrashedFilter::make(),
        ];
    }

    protected function sorts(): array
    {
        return [Sort::make('title'), Sort::make('published_at')->defaultDirection('desc')];
    }
}
```

```php
public function index()
{
    $filters = ArticleFilters::make();

    $articles = $filters->apply(Article::query())->paginate()->withQueryString();

    return view('articles.index', compact('articles', 'filters'));
}
```

```blade
{{ $filters }}
```

```tsx
<FilterBar filters={filters} />
```

The URL is the state: `/articles?search=ރާއްޖެ&status[]=draft&published_at_from=2026-01-01&orderby=title&order=DESC`. It's the format javaabu/forms' sortable table headings, search form and per-page select already use, so they work together. Values that aren't allowed are ignored, never thrown.

# What's in the box

- A [definition API](./basic-usage/defining-filters.md): filters classes or inline `Filters::make()`, defaults, hidden filters
- [Filter types](./basic-usage/filter-types.md): text search, select (options, enums, relationships, remote search), multi-select, boolean, yes/no, date, date and time, date range, number range, deleted rows, and [custom filters](./advanced-usage/custom-filters.md)
- [Sorting](./basic-usage/sorting.md): allowed sorts, a default sort, a sort dropdown and sortable column headings
- [Applied-filter chips](./basic-usage/chips.md) with readable values, and clearing one or all
- Rendering with [Blade](./basic-usage/blade.md) and [Inertia React](./basic-usage/react.md)
- [Reusing the filters](./advanced-usage/reusing-filters.md) in exports, "all matching" bulk actions and other packages
- English and Dhivehi, RTL layouts and Thaana search
