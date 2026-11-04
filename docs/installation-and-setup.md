---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

Filters and its Javaabu dependencies aren't on Packagist yet. Add their repositories, then require the package:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Javaabu/filters" },
    { "type": "vcs", "url": "https://github.com/Javaabu/stencil" },
    { "type": "vcs", "url": "https://github.com/Javaabu/forms" },
    { "type": "vcs", "url": "https://github.com/Javaabu/manifest" }
]
```

```bash
composer require javaabu/filters:^1.0@dev
```

The service provider registers itself. There's nothing to configure for the common case: filters read the current request's query string, and `{{ $filters }}` renders the filter bar with javaabu/forms.

# Inertia React

The React components are in the Composer package, under `resources/js`, and your app builds them with its own Vite through [javaabu/manifest](https://github.com/Javaabu/manifest), the same way as stencil's renderer. If your app already renders stencil forms in React, there's nothing more to do. Otherwise add manifest's Vite plugin:

```js
// vite.config.js
import { defineConfig } from "vite"
import laravel from "laravel-vite-plugin"
import react from "@vitejs/plugin-react"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/js/app.tsx"] }), react(), manifest()],
})
```

Install what the components import (`php artisan manifest:check` lists it, with the command for your package manager), and set up `FormsProvider` once with the Inertia router, so the filter form submits as an Inertia visit and links navigate without a reload:

```bash
npm install github:Javaabu/react-forms#next-gen react react-dom
php artisan manifest:check
php artisan manifest:types     # TypeScript paths for @javaabu/filters and @javaabu/stencil
```

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { createRoot } from "react-dom/client"
import { FormsProvider, mergeAdapters } from "@javaabu/forms"
import { inertia, inertiaLinks } from "@javaabu/forms/inertia"
import { onu } from "@javaabu/forms/onu"

const ui = mergeAdapters(onu, inertiaLinks)

createInertiaApp({
  resolve: (name) => import(`./pages/${name}.tsx`),
  setup({ el, App, props }) {
    createRoot(el).render(
      <FormsProvider ui={ui} router={inertia}>
        <App {...props} />
      </FormsProvider>,
    )
  },
})
```

Then import the components:

```tsx
import { FilterBar, SortableHeading, type FiltersData } from "@javaabu/filters"
```

# Publishing

Everything has a default; publish only what you change:

```bash
php artisan vendor:publish --tag=filters-config         # config/filters.php
php artisan vendor:publish --tag=filters-translations   # lang/vendor/filters
php artisan vendor:publish --tag=filters-views          # resources/views/vendor/filters
```

React components are overridden through manifest: see [Customizing views](./advanced-usage/customizing-views.md).
