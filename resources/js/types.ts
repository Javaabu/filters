import type { StencilSchema } from "@javaabu/stencil"

/**
 * The JSON of javaabu/filters: what `$filters->toArray()` returns and Inertia
 * sends as a prop. Documented in docs/advanced-usage/json-format.md.
 */

export type SortDirection = "asc" | "desc"

/** An applied filter, with the URL that clears it. */
export interface FilterChip {
  /** The filter's name. */
  filter: string
  label: string
  /** The value as text ("Draft, Published"), or `null` for a chip with only the label. */
  value: string | null
  /** "Label: value", translated. */
  text: string
  url: string
}

export interface FiltersSortOption {
  /** The sort's name: the `orderby` value. */
  value: string
  label: string
  defaultDirection: SortDirection
  /** The current URL sorted by this sort (the opposite direction when it already is). */
  url: string
}

export interface FiltersSort {
  /** The query parameter of the sort: `orderby`. */
  param: string
  /** The query parameter of the direction: `order`. */
  directionParam: string
  /** The sort that applies (requested or default), or `null`. */
  current: string | null
  direction: SortDirection
  default: string | null
  defaultDirection: SortDirection
  options: FiltersSortOption[]
}

/** Strings in the app's locale. */
export interface FiltersStrings {
  /** The filter form's accessible name. */
  filters: string
  filter: string
  reset: string
  clearAll: string
  appliedFilters: string
  /** "Remove filter :filter". */
  remove: string
  sortBy: string
  direction: string
  ascending: string
  descending: string
}

export interface FiltersData {
  /** The filter form's id: `filter`. */
  id: string
  /** Where the form submits and links point. */
  action: string
  /** The filter bar's fields: an embedded, GET stencil form. */
  form: StencilSchema
  /** Parameters the form keeps as hidden inputs: the sort, preserved keys and hidden filters. */
  hidden: Record<string, string | string[]>
  /** How many filters apply. */
  active: number
  chips: FilterChip[]
  /** The URL with no filters (keeps the sort and preserved keys). */
  clearUrl: string
  /** The page with its default filters and sort: the Clear link. */
  resetUrl: string
  sort: FiltersSort
  strings: FiltersStrings
}
