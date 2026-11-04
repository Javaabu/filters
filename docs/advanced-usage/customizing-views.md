---
title: Customizing views
sidebar_position: 5
---

# Blade

```bash
php artisan vendor:publish --tag=filters-views
```

| View | Component |
| --- | --- |
| `resources/views/vendor/filters/components/bar.blade.php` | `<x-filters::bar>` |
| `…/components/chips.blade.php` | `<x-filters::chips>` |
| `…/components/sort.blade.php` | `<x-filters::sort>` |
| `…/components/heading.blade.php` | `<x-filters::heading>` |
| `…/render.blade.php` | `{{ $filters }}` / `$filters->render('blade')` |

Delete the ones you don't change. The fields themselves are rendered by stencil's Blade driver with javaabu/forms components; customize those in stencil or javaabu/forms.

Keep the form's `id` (`$filters->getId()`): sortable headings and the React `SortSelect` find the form by it.

# React

The components ship in the Composer package and are built by your app's Vite through javaabu/manifest, so you override a file by putting your own at the same path:

```
resources/vendor/filters/js/FilterChips.tsx    overrides vendor/javaabu/filters/resources/js/FilterChips.tsx
```

`php artisan manifest:override filters js/FilterChips.tsx` copies it there for you. The override wins for `@javaabu/filters` imports and for the package's own relative imports, so `FilterBar` uses your chips. Restart the dev server after adding or removing an override.

Or compose your own bar from the exported parts: `@javaabu/forms`' `Form`, stencil's `StencilFields` with `filters.form`, `hiddenFields(filters.hidden)`, `FilterChips` and `SortSelect`.
