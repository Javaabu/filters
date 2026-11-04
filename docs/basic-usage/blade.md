---
title: Blade
sidebar_position: 5
---

```blade
{{-- resources/views/admin/articles/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold">{{ __('Articles') }}</h1>

        {{ $filters }}

        <x-forms::table model="articles" no-checkbox>
            <x-slot:headers>
                <x-filters::heading :filters="$filters" sort="title" />
                <x-forms::table.heading :label="__('Status')" />
                <x-filters::heading :filters="$filters" sort="published_at" :label="__('Published')" />
            </x-slot:headers>

            <x-slot:rows>
                @forelse($articles as $article)
                    <x-forms::table.row :model="$article" no-checkbox>
                        <x-forms::table.cell name="title" />
                        <x-forms::table.cell name="status" />
                        <x-forms::table.cell name="published_at" />
                    </x-forms::table.row>
                @empty
                    <x-forms::table.empty-row :columns="3" no-checkbox>
                        {{ $filters->hasActiveFilters() ? __('No articles match these filters.') : __('No articles yet.') }}
                    </x-forms::table.empty-row>
                @endforelse
            </x-slot:rows>

            @if($articles->hasPages())
                <x-slot:pagination>
                    <x-forms::pagination :paginator="$articles" />
                </x-slot:pagination>
            @endif
        </x-forms::table>
    </div>
@endsection
```

# The filter bar

`{{ $filters }}` is `<x-filters::bar :filters="$filters" />`. Use the component for options:

```blade
<x-filters::bar :filters="$filters" sort :chips="false" export>
    <x-forms::link-button :url="route('admin.articles.create')" color="secondary">{{ __('New article') }}</x-forms::link-button>
</x-filters::bar>
```

| Attribute | Default | What |
| --- | --- | --- |
| `filters` | | The `Filters` object |
| `sort` | `false` | Add the sort dropdown |
| `chips` | `true` | Show the applied-filter chips under the bar |
| `export` | `false` | Export button(s), as `<x-forms::filter-submit export>`: `true`, a format, or format → label |
| default slot | | Extra buttons after Filter and Export |

It renders a `GET` form with the id `filter` and `role="search"`, in a javaabu/forms card:

1. the filters' fields, rendered by stencil's Blade driver in a grid (`config('filters.columns')`, or `->columns()`)
2. hidden inputs for the current sort, the preserved parameters and [hidden filters](./defining-filters.md#hidden-filters)
3. `<x-forms::filter-submit>`: Filter, Export and Clear (back to the defaults)
4. the chips

Other attributes (`class`, `data-…`) go on the wrapper. javaabu/forms adds the assets its fields need (date pickers, selects) through manifest; there's nothing to include.

# The parts

| Component | What |
| --- | --- |
| `<x-filters::bar :filters>` | The whole bar |
| `<x-filters::chips :filters>` | The [chips](./chips.md) |
| `<x-filters::sort :filters>` | "Sort by" and "Order" selects, for inside a form |
| `<x-filters::heading :filters sort label>` | A [sortable table heading](./sorting.md#sortable-column-headings) |

For a layout of your own, render the fields with `{{ $filters->form() }}` (a stencil form: embedded, `GET`) inside your own `<form id="filter" method="GET">`, and add the hidden inputs from `$filters->getHiddenParameters()`.

# Exports

The bar's Export button submits the filter form with `download`. Handle it in the same action, reusing the filters:

```php
public function index(Request $request)
{
    $filters = ArticleFilters::make();
    $query = $filters->apply(Article::query());

    if ($request->filled('download')) {
        return (new ArticlesExport($query))->download('articles.xlsx');
    }

    return view('admin.articles.index', ['articles' => $query->paginate()->withQueryString(), 'filters' => $filters]);
}
```
