import { Card, FilterSubmit, Form, type FilterSubmitProps } from "@javaabu/forms"
import { StencilFields, type StencilComponents } from "@javaabu/stencil"
import type * as React from "react"

import { FilterChips } from "./FilterChips"
import { hiddenFields } from "./form"
import { SortSelect } from "./SortSelect"
import type { FiltersData } from "./types"

export interface FilterBarProps {
  /** `$filters` from PHP: an Inertia prop, or `$filters->toArray()`. */
  filters: FiltersData
  /** Show the sort dropdown in the bar. */
  sort?: boolean
  /** Show the chips under the bar. Default `true`. */
  chips?: boolean
  /** Export button(s), as `FilterSubmit`'s `export`. */
  export?: FilterSubmitProps["export"]
  /** Stencil components for custom field types, merged over the registry. */
  components?: StencilComponents
  /** Extra buttons, after the Filter and Export buttons. */
  children?: React.ReactNode
  className?: string
}

/**
 * `<x-filters::bar>`: the filter form (GET, `#filter`), with the stencil fields,
 * the Filter, Export and Clear buttons, and the applied-filter chips. With
 * `FormsProvider router={inertia}`, submitting is an Inertia visit.
 */
export function FilterBar({
  filters,
  sort = false,
  chips = true,
  export: exportProp,
  components,
  children,
  className,
}: FilterBarProps) {
  const skip = sort ? [filters.sort.param, filters.sort.directionParam] : []

  return (
    <div className={["flex flex-col gap-3", className].filter(Boolean).join(" ")} data-filters={filters.id}>
      <Form
        id={filters.id}
        action={filters.action}
        method="GET"
        summary={false}
        role="search"
        aria-label={filters.strings.filters}
        data-filters-form=""
      >
        <Card>
          <StencilFields schema={filters.form} components={components} />

          {sort ? <SortSelect filters={filters} inForm /> : null}

          {hiddenFields(filters.hidden, skip).map(([name, value], index) => (
            <input key={`${name}-${index}`} type="hidden" name={name} value={value} />
          ))}

          <FilterSubmit cancelUrl={filters.resetUrl} export={exportProp} after={children} />
        </Card>
      </Form>

      {chips ? <FilterChips filters={filters} /> : null}
    </div>
  )
}
