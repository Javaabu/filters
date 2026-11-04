import { Model, Select } from "@javaabu/forms"
import * as React from "react"

import { hiddenInput, submit } from "./form"
import type { FiltersData } from "./types"

export interface SortSelectProps {
  filters: FiltersData
  /**
   * Inside the filter form (`<FilterBar sort>`), the selects submit with it.
   * Outside it, a change sets the form's sort and submits it.
   */
  inForm?: boolean
  className?: string
}

/** `<x-filters::sort>`: "Sort by" and "Order" selects that apply straight away. */
export function SortSelect({ filters, inForm = false, className }: SortSelectProps) {
  const anchor = React.useRef<HTMLDivElement>(null)
  const { sort, strings } = filters

  if (!sort.options.length) return null

  const apply = (name: string, value: string) => {
    const form = inForm
      ? anchor.current?.closest("form")
      : (document.getElementById(filters.id) as HTMLFormElement | null)
    if (!(form instanceof HTMLFormElement)) return

    if (!inForm) hiddenInput(form, name).value = value
    // The select's own value is updated by the time the change handler runs.
    queueMicrotask(() => submit(form))
  }

  const sortOptions = Object.fromEntries(sort.options.map((option) => [option.value, option.label]))
  const directionOptions = { ASC: strings.ascending, DESC: strings.descending }

  return (
    <div
      ref={anchor}
      className={["grid gap-x-4 sm:grid-cols-2 lg:grid-cols-4", className].filter(Boolean).join(" ")}
      data-filter-sort=""
    >
      <Model model={{ [sort.param]: sort.current ?? "", [sort.directionParam]: sort.direction.toUpperCase() }}>
        <Select
          name={sort.param}
          label={strings.sortBy}
          options={sortOptions}
          onValueChange={(value) => apply(sort.param, String(value ?? ""))}
        />
        <Select
          name={sort.directionParam}
          label={strings.direction}
          options={directionOptions}
          onValueChange={(value) => apply(sort.directionParam, String(value ?? ""))}
        />
      </Model>
    </div>
  )
}
