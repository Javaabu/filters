---
title: The query string
sidebar_position: 2
---

The URL is the filters' state. It uses flat parameters, the ones javaabu/forms' index-page components already write:

```
/articles?search=ވާރޭ&status[]=draft&status[]=published&category_id=3&is_featured=1
         &published_at_from=2026-01-01&published_at_to=2026-03-31&word_count_min=100
         &trashed=with&orderby=title&order=DESC&per_page=50&page=2
```

| Parameter | From |
| --- | --- |
| `{name}` | Text, select, boolean, yes/no, date and deleted-rows filters |
| `{name}[]` | Multiple selects |
| `{name}_from`, `{name}_to` | Date ranges (`parameters()` to rename) |
| `{name}_min`, `{name}_max` | Number ranges |
| your fields' names | Custom filters with `schema()` |
| `orderby`, `order` | The sort (`config('filters.query.sort')`, `config('filters.query.direction')`) |
| `per_page` | Kept by the filters (`config('filters.query.preserve')`); the page size is `<x-forms::per-page>`'s |
| `page` | The paginator; dropped when the filters change |

# Rules

- Empty values don't apply. `?status=` turns off a filter that has a default.
- Values that aren't allowed are ignored, never thrown: an option that isn't one, an unparseable date, a number that isn't one, a sort that isn't registered. The page shows the list without that filter.
- Parameters no filter reads are left alone (and kept only if they're preserved).
- Directions are `ASC` or `DESC` in any case; links write them in capitals, like javaabu/forms.

# Why not spatie/laravel-query-builder

The package owns its query-string format instead of building on [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) or [Javaabu/query-builder](https://github.com/Javaabu/query-builder), which uses it:

- Its `filter[status]=draft&sort=-title` format is designed for JSON APIs. javaabu/forms' sortable headings, search form and filter card already write `orderby`, `order` and `search` on index pages; using the same names keeps them working together.
- It throws on unknown filters and sorts. That's right for an API, wrong for an admin page opened from an old bookmark.
- It doesn't know labels, fields or chips, so every filter would still need a second definition here.

Keep using javaabu/query-builder for API endpoints. Filters is for the pages people use.

# Several filter sets on one page

Not supported in v1: every filter bar on a page reads the same parameters, and sortable headings always write `orderby` and `order`. Give the second list its own page, or a tab with its own URL.
