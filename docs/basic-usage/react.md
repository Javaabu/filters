---
title: Inertia React
sidebar_position: 6
---

Pass the filters as a prop; they serialize to the [JSON](../advanced-usage/json-format.md) the React components read:

```php
use Inertia\Inertia;

public function index()
{
    $filters = ArticleFilters::make();

    return Inertia::render('Admin/Articles/Index', [
        'articles' => ArticleResource::collection($filters->apply(Article::query())->paginate()->withQueryString()),
        'filters' => $filters,
    ]);
}
```

```tsx
// resources/js/pages/Admin/Articles/Index.tsx
import { Pagination, Table, TableCell, TableEmptyRow, TableHeading, TableRow } from "@javaabu/forms"
import { FilterBar, SortableHeading, type FiltersData } from "@javaabu/filters"

interface Props {
  filters: FiltersData
  articles: { data: Article[]; meta: { last_page: number } }
}

export default function Index({ filters, articles }: Props) {
  return (
    <div className="flex flex-col gap-6">
      <h1 className="text-2xl font-semibold">Articles</h1>

      <FilterBar filters={filters} />

      <Table
        model="articles"
        noCheckbox
        headers={
          <>
            <SortableHeading filters={filters} sort="title" />
            <TableHeading label="Status" />
            <SortableHeading filters={filters} sort="published_at" label="Published" />
          </>
        }
        pagination={articles.meta.last_page > 1 ? <Pagination paginator={articles} /> : null}
      >
        {articles.data.length ? (
          articles.data.map((article) => (
            <TableRow key={article.id} model={article} noCheckbox>
              <TableCell name="title" />
              <TableCell name="status" />
              <TableCell name="published_at" />
            </TableRow>
          ))
        ) : (
          <TableEmptyRow columns={3} noCheckbox>
            {filters.active ? "No articles match these filters." : "No articles yet."}
          </TableEmptyRow>
        )}
      </Table>
    </div>
  )
}
```

With `FormsProvider router={inertia}` (see [installation](../installation-and-setup.md#inertia-react)), submitting the filter bar is an Inertia `GET` visit, and with `inertiaLinks` the chips and sortable headings navigate without a reload. Without them, they work as a normal form and links.

# Components

| Component | Props | Blade |
| --- | --- | --- |
| `FilterBar` | `filters`, `sort`, `chips` (default `true`), `export`, `components` (stencil components for custom field types), `children` (extra buttons), `className` | `<x-filters::bar>` |
| `FilterChips` | `filters`, `className` | `<x-filters::chips>` |
| `SortSelect` | `filters`, `inForm`, `className` | `<x-filters::sort>` |
| `SortableHeading` | `filters`, `sort`, `label`, `children`, and `<th>` props | `<x-filters::heading>` |

`FilterBar` renders `@javaabu/forms`' `Form` (`GET`, id `filter`) with a `Card`, stencil's `<StencilFields>` for the fields, hidden inputs, `FilterSubmit` and `FilterChips`. Every string comes translated from PHP in `filters.strings`, so the components need no locale setup.

The types (`FiltersData`, `FilterChip`, `FiltersSort`, …) are exported from `@javaabu/filters`.

# Custom field types

A [custom filter](../advanced-usage/custom-filters.md) can use a custom stencil field type. Register its React component as for any stencil form, with `StencilProvider` around the page, or pass it to the bar:

```tsx
<FilterBar filters={filters} components={{ rating: RatingField }} />
```
