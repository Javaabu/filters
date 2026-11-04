# Filters

[![Tests](https://github.com/Javaabu/filters/actions/workflows/tests.yml/badge.svg)](https://github.com/Javaabu/filters/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/Javaabu/filters/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/filters)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/filters.svg)](https://packagist.org/packages/javaabu/filters)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Filters and sorts for Laravel index pages. Define each filter once: how it renders (with [javaabu/stencil](https://github.com/Javaabu/stencil) fields) and how it applies to the query. The filter state lives in the URL, and the filter bar, applied-filter chips and sort controls render in Blade ([javaabu/forms](https://github.com/Javaabu/forms)) or Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms)).

```php
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
            SelectFilter::make('category_id')->relationship('category', 'name'),
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
$filters = ArticleFilters::make();                                     // reads the query string
$articles = $filters->apply(Article::query())->paginate()->withQueryString();
```

```blade
{{ $filters }}                                                         {{-- the filter bar and chips --}}
<x-filters::heading :filters="$filters" sort="title" />               {{-- a sortable column --}}
```

```tsx
<FilterBar filters={filters} />                                        // Inertia::render('Articles/Index', ['filters' => $filters])
<SortableHeading filters={filters} sort="title" />
```

The URL holds the state in the flat parameters javaabu/forms already uses (`?search=ރާއްޖެ&status[]=draft&orderby=title&order=DESC`), and values that aren't allowed are ignored.

## Documentation

You'll find the documentation on [https://docs.javaabu.com/filters](https://docs.javaabu.com/filters), and in the [`docs`](docs) folder.

AI coding assistants can start from [`llms.txt`](llms.txt). The package ships a [Laravel Boost](https://github.com/laravel/boost) skill and guideline, which Boost finds by itself. See [AI assistants](docs/ai-assistants.md).

## Testing

```bash
composer test
pnpm test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
