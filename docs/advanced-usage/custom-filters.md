---
title: Custom filters
sidebar_position: 1
---

# A filter with a query

The simplest custom filter is a type with your own `query()`:

```php
use Illuminate\Database\Eloquent\Builder;

BooleanFilter::make('mine')->label(__('Only mine'))
    ->query(fn (Builder $query) => $query->whereBelongsTo(auth()->user(), 'author')),

SelectFilter::make('age')->label(__('Age'))
    ->options(['new' => __('This week'), 'old' => __('Older')])
    ->query(fn (Builder $query, string $value) => $value === 'new'
        ? $query->where('created_at', '>=', now()->subWeek())
        : $query->where('created_at', '<', now()->subWeek())),
```

The type still reads, cleans and validates the value (only options apply), renders its field and formats its chip. The closure only replaces what it does to the query.

# A filter with fields of your own

`Filter::make()` with `schema()` takes any stencil fields. Their names are the query parameters the filter reads. With one field named like the filter, the value is that field's; with several, it's an array keyed by field name, of the fields that have a value:

```php
use Javaabu\Filters\Filter;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

Filter::make('words')
    ->label(__('Length'))
    ->schema([
        TextInput::make('min_words')->label(__('At least'))->integer(),
        Checkbox::make('featured_only')->label(__('Featured only')),
    ])
    ->query(function (Builder $query, array $value) {
        $query->where('word_count', '>=', (int) ($value['min_words'] ?? 0));

        if (isset($value['featured_only'])) {
            $query->where('is_featured', true);
        }
    })
    ->formatChipUsing(fn (array $value) => __('At least :count words', ['count' => $value['min_words'] ?? 0]))
    ->columnSpan(2),
```

A filter with several fields must have a `query()`. One field without a `query()` compares its column: `where(column, value)`, or `whereIn` for an array. Values are only trimmed and emptied; check them in your closure.

# A filter class

For a filter you reuse, extend a type (or `Filter`) and override what changes. The hooks:

| Method | What |
| --- | --- |
| `setUp()` | Defaults for the type, called by `make()` |
| `getDefaultName()` (static) | The name for `make()` without one |
| `getQueryKeys()` | The query parameters it reads |
| `normalizeValue($value)` | Clean the raw input; return `null` when it doesn't apply |
| `defaultFields()` | The stencil fields |
| `defaultComponents()` | What the bar renders: the fields, or a layout around them |
| `applyToQuery($query, $value)` | What it does to the query |
| `toQueryParameters($value)` | The value as query parameters (also the form's state) |
| `formatChipValue($value)` | The chip's value |

```php
<?php

namespace App\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Filters\SelectFilter;

/**
 * Atolls of the Maldives, by code: K, AA, ADh, …
 */
class AtollFilter extends SelectFilter
{
    public static function getDefaultName(): string
    {
        return 'atoll';
    }

    protected function setUp(): void
    {
        $this->label(__('Atoll'))
            ->options(fn () => Atoll::query()->orderBy('order')->pluck('name', 'code'))
            ->multiple()
            ->searchable();
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $query->whereHas('island.atoll', fn (Builder $atolls) => $atolls->whereIn('code', $value));
    }
}
```

```php
AtollFilter::make(),
```

A custom stencil field type works in `schema()` like any field; register its React component as shown in [Inertia React](../basic-usage/react.md#custom-field-types).
