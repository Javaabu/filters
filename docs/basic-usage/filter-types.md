---
title: Filter types
sidebar_position: 2
---

Every type is in the `Javaabu\Filters` namespace and made with `Type::make('name')`. They all have `label()`, `column()` (default: the name), `default()`, `hidden()`, `visible()`, `columnSpan()`, `query()` (to replace what the type does to the query) and `formatChipUsing()`.

| Type | Parameters | Field |
| --- | --- | --- |
| [`TextFilter`](#text) | `search` | Text input |
| [`SelectFilter`](#select) | `status` or `status[]` | Select |
| [`MultiSelectFilter`](#select) | `status[]` | Multiple select |
| [`BooleanFilter`](#boolean) | `long` | Checkbox |
| [`TernaryFilter`](#yes-or-no) | `is_featured` (`1`/`0`) | Select: All, Yes, No |
| [`DateFilter`](#dates) | `published_at` (`Y-m-d`) | Date picker |
| [`DateTimeFilter`](#dates) | `published_at` (`Y-m-d H:i`) | Date and time picker |
| [`DateRangeFilter`](#date-ranges) | `published_at_from`, `published_at_to` | Two date pickers |
| [`NumberRangeFilter`](#number-ranges) | `price_min`, `price_max` | Two number inputs |
| [`TrashedFilter`](#deleted-rows) | `trashed` (`with`/`only`) | Select |
| [`Filter`](../advanced-usage/custom-filters.md) | Your fields' names | Your fields |

# Text

```php
TextFilter::make()                                       // named search
    ->columns(['title', 'slug', 'author.name'])          // relation.column searches related models
    ->thaana()                                           // the input types Thaana
    ->placeholder(__('Search articles...'))
    ->operator(TextFilter::STARTS_WITH);                 // contains (default), starts_with, ends_with, exact
```

The columns are searched with `LIKE`, any of them matching, with `%` and `_` in the search escaped. Without `columns()`, it uses the model's `search` scope when it has one (the usual Javaabu `scopeSearch`), else the filter's column. The search is trimmed and cut at 255 characters (`maxLength()`).

# Select

```php
SelectFilter::make('status')->options(ArticleStatuses::class);                  // an enum: getLabel() or labels() for the labels
SelectFilter::make('type')->options(['news' => __('News'), 'blog' => __('Blog')]);
SelectFilter::make('year')->options(fn () => Article::query()->distinct()->pluck('year', 'year'));
SelectFilter::make('status')->options(ArticleStatuses::class)->multiple();      // or MultiSelectFilter::make('status')
```

Only values that are options apply; anything else in the URL is ignored. A single select's placeholder is "All" (`placeholder()` to change it); `searchable()` turns on search in the dropdown.

## Relationships

```php
SelectFilter::make('category_id')->relationship('category', 'name');             // BelongsTo: where category_id in (…)
SelectFilter::make('tags')->relationship('tags', 'name')->multiple();            // BelongsToMany: whereHas('tags', …)
SelectFilter::make('category_id')->relationship('category', 'name', fn (Builder $query) => $query->where('is_active', true));
```

Name a `BelongsTo` filter by its foreign key and any other relationship (`BelongsToMany`, `HasMany`, morphs) by the relationship, as stencil's selects. Relationship options need the [filters' model](./defining-filters.md).

## Options searched on the server

For tables too big to list, search a URL as the user types, like stencil's `Select::ajax()`:

```php
SelectFilter::make('author_id')
    ->relationship('author', 'name')
    ->ajax(route('api.authors.index'), 'name', 'id');

SelectFilter::make('customer')
    ->ajax(route('api.customers.index'))
    ->getOptionLabelsUsing(fn (array $values) => Customer::whereKey($values)->pluck('name', 'id')->all());
```

With a relationship or `getOptionLabelsUsing()`, chosen values show their labels in the select and the chip, and values that don't exist are ignored. Without either, any value is accepted and shown as it is.

# Boolean

```php
BooleanFilter::make('is_featured');                      // checked: where is_featured = true
BooleanFilter::make('mine')->label(__('Only mine'))
    ->query(fn (Builder $query) => $query->whereBelongsTo(auth()->user(), 'author'));
```

A checkbox that applies when it's ticked (`1`, `true`, `on`, `yes`). Its chip shows the label alone.

# Yes or no

```php
TernaryFilter::make('is_featured')->label(__('Featured'));                       // 1: true, 0: false
TernaryFilter::make('verified')->column('email_verified_at')->nullable();        // 1: whereNotNull, 0: whereNull
TernaryFilter::make('long')
    ->trueLabel(__('Long reads'))
    ->falseLabel(__('Short reads'))
    ->placeholder(__('Any length'))
    ->queries(
        true: fn (Builder $query) => $query->where('word_count', '>=', 1000),
        false: fn (Builder $query) => $query->where('word_count', '<', 1000),
    );
```

# Dates

```php
DateFilter::make('published_at');                        // that day
DateFilter::make('published_at')->operator('>=');        // on or after: =, <, <=, >, >=
DateTimeFilter::make('starts_at')->operator('<');        // Y-m-d H:i, to the minute
```

Dates are parsed strictly (`2026-02-30` and `yesterday` are ignored), and compared against the whole day (or minute): `=` is between the start and the end of the day, `<=` up to its end.

# Date ranges

```php
DateRangeFilter::make('published_at');                   // published_at_from, published_at_to
DateRangeFilter::make('created_at')->withTime();         // Y-m-d H:i
DateRangeFilter::make('created_at')->parameters('date_from', 'date_to');
```

Either end alone applies; each day is included. The two pickers sit side by side and take two columns of the filter bar (`columnSpan()` to change it).

# Number ranges

```php
NumberRangeFilter::make('word_count')->integer();        // word_count_min, word_count_max
NumberRangeFilter::make('price')->step(0.01)->parameters('min_price', 'max_price');
```

# Deleted rows

```php
TrashedFilter::make();                                   // named trashed
```

For models with `SoftDeletes`: without deleted rows (the default), with them (`trashed=with`), or only them (`trashed=only`). To offer it only to some users, leave it out of the array for the others; don't just hide it, since hidden filters still apply from the URL:

```php
auth()->user()->can('restore', Article::class) ? TrashedFilter::make() : null,
```
