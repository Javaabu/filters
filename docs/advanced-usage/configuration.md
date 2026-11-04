---
title: Configuration
sidebar_position: 4
---

Publish the config only to change it:

```bash
php artisan vendor:publish --tag=filters-config
```

```php
// config/filters.php
return [
    // What $filters->render() returns: 'blade' (HTML) or 'inertia' (an array for React).
    // null follows config('stencil.views').
    'views' => env('FILTERS_VIEWS'),

    'query' => [
        // The sort parameters, as javaabu/forms' sortable headings write them.
        'sort' => 'orderby',
        'direction' => 'order',

        // Kept when filters are submitted or cleared.
        'preserve' => ['per_page'],
    ],

    // Dates in chips, through Carbon's translatedFormat() in the app locale.
    'date_format' => 'j M Y',
    'datetime_format' => 'j M Y H:i',

    // Grid columns of the filter bar, per breakpoint.
    'columns' => ['default' => 1, 'sm' => 2, 'lg' => 4],
];
```

| Key | Default | Per filters object |
| --- | --- | --- |
| `views` | `null` (stencil's) | `render('blade')`, `render('inertia')` |
| `query.sort`, `query.direction` | `orderby`, `order` | |
| `query.preserve` | `['per_page']` | `->preserve([...])`, `protected ?array $preserve` |
| `date_format`, `datetime_format` | `j M Y`, `j M Y H:i` | `formatChipUsing()` per filter |
| `columns` | 1 / 2 / 4 | `->columns(3)`, `->columns(['default' => 1, 'md' => 3])`, `protected int\|array\|null $columns` |

If you change `query.sort` or `query.direction`, javaabu/forms' own sortable headings won't match any more; use [`x-filters::heading` / `SortableHeading`](../basic-usage/sorting.md#sortable-column-headings), which follow the config.
