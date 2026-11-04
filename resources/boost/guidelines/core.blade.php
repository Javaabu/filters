{{--
    javaabu/filters guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/filters

- Index pages filter and sort with `javaabu/filters`: a class extending `Javaabu\Filters\Filters` with `filters()` and `sorts()`, applied with `$filters->apply($query)` and rendered with `@{{ $filters }}` (Blade) or `<FilterBar filters={filters} />` (React). Don't read filter values from the request or hand-write filter forms.
- The URL is the state, in flat parameters (`search`, `status[]`, `published_at_from`, `orderby`, `order`); invalid values are ignored and only registered sorts apply, so don't validate filter input. Paginate with `->withQueryString()`.
- `hidden()` filters still apply from the URL: leave filters out of the array for users who mustn't use them.
- Follow Javaabu codestyle: imports for every filter type, `__()` for every label and option, `->thaana()` on text filters that search Dhivehi.
- Always activate the `javaabu-filters` skill when adding filters, sorts or sortable columns to an index page, writing custom filters, or acting on "all matching" records.
