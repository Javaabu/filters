---
title: Sorting
sidebar_position: 3
---

# Allowed sorts

```php
protected function sorts(): array
{
    return [
        'name',                                                  // a column, labelled "Name"
        Sort::make('created_at')->label(__('Created'))->defaultDirection('desc'),
        Sort::make('newest')->label(__('Newest'))->column('created_at'),
        Sort::make('author')->label(__('Author'))->using(fn (Builder $query, string $direction) => $query->orderBy(
            Author::select('name')->whereColumn('authors.id', 'articles.author_id'),
            $direction,
        )),
    ];
}
```

The URL picks a sort with `orderby` and `order` (`ASC` or `DESC`, any case). Only these sorts are ever applied: an `orderby` that isn't one of them is ignored and the default sort applies, so a URL can't order by a column you didn't allow. `defaultDirection()` is used when the URL names the sort without a direction.

# The default sort

```php
protected ?string $default_sort = 'created_at';

protected string $default_direction = 'desc';
```

or `->defaultSort('created_at', 'desc')`. It applies when the URL has no allowed sort. It doesn't have to be one users can choose (`defaultSort('position')` for a manually ordered list).

```php
$filters->getSortName();          // the sort that applies: requested or default
$filters->getDirection();         // asc or desc
$filters->isSortedBy('name');
$filters->isSortedBy('name', 'desc');
$filters->getRequestedSort();     // the sort in the URL, when it's allowed
$filters->sortUrl('name');        // this page sorted by name (the other direction when it already is)
$filters->sortUrl('name', 'desc');
```

# Sortable column headings

**Blade:**

```blade
<x-forms::table model="articles" no-checkbox>
    <x-slot:headers>
        <x-filters::heading :filters="$filters" sort="title" />
        <x-filters::heading :filters="$filters" sort="published_at" :label="__('Published')" class="w-40" />
        <x-forms::table.heading :label="__('Status')" />
    </x-slot:headers>
    {{-- … --}}
</x-forms::table>
```

**React:**

```tsx
<Table
  model="articles"
  noCheckbox
  headers={
    <>
      <SortableHeading filters={filters} sort="title" />
      <SortableHeading filters={filters} sort="published_at" label="Published" className="w-40" />
      <TableHeading label="Status" />
    </>
  }
>
  {/* … */}
</Table>
```

A sortable heading is a link to the list sorted by its sort, keeping the filters, with `aria-sort` and an arrow on the current sort (the default sort too). Its label defaults to the sort's label. A heading for a sort that isn't allowed renders as a plain heading. With Inertia links (`inertiaLinks`), it navigates without a reload.

javaabu/forms' own sortable headings (`<x-forms::table.heading sortable="title">`, `TableHeading sortable="title"`) also work, since they write the same `orderby` and `order` into the `#filter` form. Prefer `x-filters::heading`, which only offers allowed sorts and shows the default sort.

# A sort dropdown

```blade
<x-filters::bar :filters="$filters" sort />
```

```tsx
<FilterBar filters={filters} sort />
```

`sort` adds "Sort by" and "Order" selects to the filter bar, submitted with the filters. In React, changing them applies straight away. On its own, outside the bar, `<SortSelect filters={filters} />` sets the filter form's sort and submits it; in Blade, `<x-filters::sort :filters="$filters" />` renders the two selects for a form of your own.
