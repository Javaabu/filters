---
title: The JSON format
sidebar_position: 6
---

`$filters->toArray()` (also `jsonSerialize()`, an Inertia prop, and `render('inertia')`) is what the React components read. It's typed as `FiltersData` in `resources/js/types.ts`, exported from `@javaabu/filters`.

```json
{
  "id": "filter",
  "action": "/articles",
  "form": { "stencil": 1, "id": "filter", "method": "GET", "embedded": true, "state": { "search": "ވާރޭ" }, "components": [ … ] },
  "hidden": { "author_id": "1", "orderby": "title", "order": "DESC", "per_page": "50" },
  "active": 2,
  "chips": [
    { "filter": "search", "label": "Search", "value": "ވާރޭ", "text": "Search: ވާރޭ", "url": "/articles?author_id=1&orderby=title&order=DESC&per_page=50" }
  ],
  "clearUrl": "/articles?orderby=title&order=DESC&per_page=50",
  "resetUrl": "/articles?per_page=50",
  "sort": {
    "param": "orderby",
    "directionParam": "order",
    "current": "title",
    "direction": "desc",
    "default": "published_at",
    "defaultDirection": "desc",
    "options": [
      { "value": "title", "label": "Title", "defaultDirection": "asc", "url": "/articles?…&orderby=title&order=ASC" }
    ]
  },
  "strings": { "filters": "Filters", "filter": "Filter", "reset": "Clear", "clearAll": "Clear all", "appliedFilters": "Applied filters", "remove": "Remove filter :filter", "sortBy": "Sort by", "direction": "Order", "ascending": "Ascending", "descending": "Descending" }
}
```

| Key | What |
| --- | --- |
| `id` | The filter form's id |
| `action` | Where the form submits and links point |
| `form` | The filter bar's fields: a [stencil schema](https://github.com/Javaabu/stencil/blob/main/docs/advanced-usage/json-format.md), embedded and `GET`, with the current values as `state` |
| `hidden` | Parameters the form keeps as hidden inputs: hidden filters, the requested sort, preserved parameters. Lists are arrays |
| `active` | How many filters apply |
| `chips` | The [chips](../basic-usage/chips.md), with the URL that clears each |
| `clearUrl`, `resetUrl` | Clear all; back to the defaults |
| `sort.current`, `sort.direction` | The sort that applies, requested or default; `asc` or `desc` |
| `sort.options[].url` | The current page sorted by that sort (the other direction when it already is) |
| `strings` | UI strings in the app's locale. `remove` has a `:filter` placeholder |

URLs are built on the server, so the components never build query strings. A change to the format changes `resources/js/types.ts`, this page and the components together; `JsFixtureTest` keeps the fixtures the React tests render in step with PHP.
