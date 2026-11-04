---
title: Requirements
sidebar_position: 1.1
---

This package requires:

- PHP 8.4 or higher
- Laravel 12 or 13
- [javaabu/stencil](https://github.com/Javaabu/stencil), which defines the filter bar's fields and renders them (installed with filters)
- [javaabu/forms](https://github.com/Javaabu/forms) v2, for the Blade components (installed with filters)
- [javaabu/manifest](https://github.com/Javaabu/manifest), which ships the React components to your app (installed with filters)

For Inertia React pages:

- React 19
- [@javaabu/forms](https://github.com/Javaabu/react-forms) and its peer dependencies (`php artisan manifest:check` lists what's missing)
- manifest's Vite plugin, which lets your app import `@javaabu/filters` and `@javaabu/stencil`
