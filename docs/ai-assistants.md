---
title: AI assistants
sidebar_position: 1.35
---

The docs are plain Markdown, written for people and AI coding assistants alike. The package ships them, so they always match the version you have installed.

| File | What |
| --- | --- |
| `vendor/javaabu/filters/llms.txt` | The rules for using the package, and every page |
| `vendor/javaabu/filters/docs/` | The pages |
| `vendor/javaabu/filters/resources/boost/` | The Laravel Boost skill and guideline |

# Laravel Boost

[Laravel Boost](https://github.com/laravel/boost) finds the skill and guideline in the Composer package by itself. Run this after installing or updating:

```bash
php artisan boost:update
```

The guideline gives the assistant the rules for index-page filters; the skill (`javaabu-filters`) has the API, examples and common mistakes, and loads when it works on filters, sorting or index pages.

# Other assistants

Point them at `vendor/javaabu/filters/llms.txt`.
