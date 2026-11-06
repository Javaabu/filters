---
title: Reusing filters elsewhere
sidebar_position: 3
---

A filters class describes a list, so the same class rebuilds the same query anywhere: a queued export, "all matching" bulk actions, a count on a dashboard, or another package's page.

# Carry the filters along

`getQueryParameters()` is what to carry: the applied filters (cleaned), the requested sort and the preserved parameters. `fromQuery()` reads them back:

```php
// on the index page: a form or link that acts on "all matching"
$parameters = $filters->getQueryParameters();
// ['search' => 'ވާރޭ', 'status' => ['draft'], 'orderby' => 'title', 'order' => 'ASC']

// in the action, the job or the export
$query = ArticleFilters::make()->fromQuery($parameters)->apply(Article::query());
```

The parts on their own: `getFilterParameters()` (filters only), `getSortParameters()`, `getPreservedParameters()`, and `getHiddenParameters()` (what the filter form keeps as hidden inputs).

# "All matching" bulk actions

Submit the current filters with the action, as a nested field, and rebuild the query from them:

```blade
<form method="POST" action="{{ route('admin.articles.bulk') }}">
    @csrf
    @foreach(\Illuminate\Support\Arr::dot($filters->getFilterParameters()) as $name => $value)
        <input type="hidden" name="filters[{{ str_replace('.', '][', $name) }}]" value="{{ $value }}">
    @endforeach
    {{-- action select, "all matching" checkbox, … --}}
</form>
```

```php
public function bulk(Request $request)
{
    $query = $request->boolean('all_matching')
        ? ArticleFilters::make()->fromQuery($request->array('filters'))->applyFilters(Article::query())
        : Article::whereKey($request->array('articles'));

    $this->authorize('updateAny', Article::class);

    $query->each(fn (Article $article) => $article->update(['status' => $request->input('status')]));

    return back();
}
```

Values are cleaned again, so a tampered field can't widen the query beyond what the filters allow. Scope what the user may act on in the query you pass in.

# Queued exports

Store the parameters with the export and rebuild in the job:

```php
ExportArticles::dispatch($request->user(), $filters->getQueryParameters());

// in the job
$query = ArticleFilters::make()->fromQuery($this->parameters)->apply(Article::query());
```

A job has no request. `fromQuery()` covers the query string, but a filters class that calls `request()` or `auth()` in `filters()` (to hide a filter from some users, say) gets nothing there. Give such a class a setter for what it needs, and call it in both places.

# For package authors

Packages that list models (activity logs, post types, generated admin pages) take a `Filters` class or instance from the app, and render it with whichever stack the app uses:

```php
// in your package's controller
$filters = config('activitylog.filters', ActivityFilters::class)::make();

$activities = $filters->apply(Activity::query())->paginate()->withQueryString();

if (config('activitylog.views') === 'inertia') {
    return Inertia::render('activitylog::Activities/Index', compact('filters', 'activities'));
}

return view('activitylog::activities.index', compact('filters', 'activities'));
```

The Blade view renders `{{ $filters }}` or `<x-filters::bar>`; the React page (shipped through manifest) imports `FilterBar` from `@javaabu/filters` and gets the filters as JSON. `$filters->render()` returns either one by `config('filters.views')`, falling back to `config('stencil.views')`, for code that doesn't know the stack.

Let apps change the filters by binding a class in your config, and document its filter names as part of your URLs.

A React page that imports `@javaabu/filters` needs `@javaabu/forms` in the app. Declare it in your manifest registration for pages only, as filters does, so Blade-only apps aren't asked to install it:

```php
$package->js()->pages()->npm(
    ['@javaabu/forms' => 'github:Javaabu/react-forms#next-gen'],
    peer_dependencies: ['react' => '^19.0', 'react-dom' => '^19.0'],
    for: 'pages',
);
```
