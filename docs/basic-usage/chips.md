---
title: Applied filters and clearing
sidebar_position: 4
---

The filter bar shows a chip for each filter that applies, hidden ones too: "Status: Draft, Published", "Published: 1 Jan 2026 – 31 Mar 2026", "Featured: Yes". Each chip links to the page without that filter, and "Clear all" appears when more than one applies.

```blade
<x-filters::chips :filters="$filters" />     {{-- on their own: <x-filters::bar :chips="false"> leaves them out of the bar --}}
```

```tsx
<FilterChips filters={filters} />           // <FilterBar chips={false}> leaves them out
```

# Values

| Filter | Chip value |
| --- | --- |
| Text | The search |
| Select | The option labels, joined: `Draft, Published` (`، ` in Dhivehi) |
| Remote select | The labels from the relationship or `getOptionLabelsUsing()` |
| Yes or no | Yes / No, or `trueLabel()` / `falseLabel()` |
| Boolean | None: the chip is the label |
| Date | `1 Jan 2026` (`date_format`), `From …` / `Until …` with an operator |
| Date range | `1 Jan 2026 – 31 Mar 2026`, `From …`, `Until …` |
| Number range | `100 – 500`, `At least 100`, `At most 500` |
| Deleted rows | Include deleted / Only deleted |

Dates use `config('filters.date_format')` and `datetime_format`, translated to the app's locale with Carbon. Change one chip with `formatChipUsing()`; return `null` for a chip with only the label:

```php
NumberRangeFilter::make('price')->formatChipUsing(fn (array $value) => Number::currency($value['from'] ?? 0, 'MVR') . '+'),
```

# In PHP

```php
foreach ($filters->chips() as $chip) {
    $chip->filter;     // status
    $chip->label;      // Status
    $chip->value;      // Draft, Published (or null)
    $chip->text();     // Status: Draft, Published
    $chip->url;        // the page without this filter
}
```

# Clearing

| URL | What it keeps | Used by |
| --- | --- | --- |
| `$chip->url`, `$filters->urlWithout('status')` | The other filters, the sort, preserved parameters | A chip's ✕ |
| `$filters->clearUrl()` | The sort and preserved parameters; no filters | Clear all |
| `$filters->resetUrl()` | Preserved parameters; the default filters and sort come back | The bar's Clear button |

All of them drop `page`, so you land on the first page. Preserved parameters are `config('filters.query.preserve')` (`['per_page']`), or `->preserve(['per_page', 'tab'])`. A cleared filter that has a default is kept empty (`?status=`) so the default doesn't come back.
