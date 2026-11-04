---
title: Dhivehi and RTL
sidebar_position: 7
---

Every string the package shows is in `lang/en/strings.php` and `lang/dv/strings.php`, under `filters::strings`. Set the app locale to `dv` and the filter bar, chips, sort controls and the React components (which get their strings from PHP) are in Dhivehi. Publish the translations to change them:

```bash
php artisan vendor:publish --tag=filters-translations
```

Your filters' labels and options go through `__()` like any other string: `->label(__('Category'))`, enum labels, `trueLabel(__('Featured'))`.

# Thaana search

```php
TextFilter::make()->columns(['title', 'body'])->thaana(),
```

`thaana()` makes the search input type Thaana (`dir="rtl"`, `lang="dv"`), as javaabu/forms' fields do. Thaana searches match with `LIKE` like any text.

# Dates

Chip dates are formatted with Carbon's `translatedFormat()` in the app locale, so months are in Dhivehi with `dv`. The format itself is `config('filters.date_format')`.

# RTL

The views use logical utilities only (`ms-`, `pe-`, `ps-`, `text-start`), and chip values are wrapped in `<bdi>` so Thaana and Latin values read in their own direction inside the chip. Inputs and selects come from javaabu/forms and @javaabu/forms, which are RTL-ready.

# Translations to review

These Dhivehi strings were written without a native speaker's check. Please review them (and open a PR on `lang/dv/strings.php`):

| Key | English | Dhivehi | Note |
| --- | --- | --- | --- |
| `all` | All | ހުރިހާ | Placeholder of a select meaning "no filter"; maybe ހުރިހާ އެއްޗެއް |
| `applied_filters` | Applied filters | ބޭނުންކުރެވިފައިވާ ފިލްޓަރުތައް | |
| `clear_all` | Clear all | ހުރިހާ ފިލްޓަރެއް ފޮހެލާ | |
| `remove` | Remove filter :filter | :filter ފިލްޓަރު ފޮހެލާ | Word order |
| `trashed` | Deleted | ފޮހެލެވިފައިވާ | |
| `with_trashed` | Include deleted | ފޮހެލެވިފައިވާ ތަކާއެކު | |
| `only_trashed` | Only deleted | ހަމައެކަނި ފޮހެލެވިފައިވާ ތައް | |
| `without_trashed` | Without deleted | ފޮހެލެވިފައިވާ ތައް ނުލާ | |
| `range_from`, `range_to` | :label from / :label to | :label (އިން) / :label (އަށް) | Field labels of range filters |
| `from_value`, `until_value` | From :value / Until :value | :value އިން / :value އަށް | |
| `min_value`, `max_value` | At least :value / At most :value | މަދުވެގެން :value / ގިނަވެގެން :value | |
| `sort_by` | Sort by | ތަރުތީބުކުރާނީ | |
| `direction` | Order | ތަރުތީބު | |
| `ascending`, `descending` | Ascending / Descending | ކުޑައިން ބޮޑަށް / ބޮޑުން ކުޑައަށް | |

`filter`, `reset` (Clear), `search`, `yes` and `no` use the same Dhivehi as javaabu/forms.
