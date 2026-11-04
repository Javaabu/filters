# Changelog

All notable changes to `javaabu/filters` are documented in this file.

## Unreleased

Initial development of v1.0:

- Filters classes (`filters()`, `sorts()`) and inline `Filters::make()`, reading flat query parameters that match javaabu/forms (`search`, `status[]`, `{name}_from`/`_to`, `orderby`/`order`, `per_page`). Invalid values are ignored, never thrown.
- Filter types: text (columns, related columns, search scope), select (options, enums, closures, relationships, remote options), multi-select, boolean, yes/no, date, date and time, date range, number range, deleted rows, and custom filters with stencil fields and a query.
- Sorts: allowed sorts, a default sort, closures, sort URLs; only registered sorts are applied.
- Applied-filter chips with option labels and translated dates; clearing one filter, all filters, or back to the defaults.
- Blade: `<x-filters::bar>`, `chips`, `sort` and `heading`, with javaabu/forms through stencil's Blade driver.
- Inertia React: `FilterBar`, `FilterChips`, `SortSelect` and `SortableHeading` in `@javaabu/filters`, shipped through javaabu/manifest, with @javaabu/forms and stencil's `StencilFields`.
- `getQueryParameters()` and `fromQuery()` to rebuild the filtered query for exports and "all matching" bulk actions.
- English and Dhivehi strings, RTL layouts and Thaana search.
- Docs, `llms.txt`, and a Laravel Boost guideline and skill.
