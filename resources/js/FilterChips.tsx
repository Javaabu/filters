import { useUi } from "@javaabu/forms"

import { replace } from "./form"
import type { FiltersData } from "./types"

export interface FilterChipsProps {
  filters: FiltersData
  className?: string
}

/**
 * `<x-filters::chips>`: a chip for each applied filter, with a link that
 * removes it, and "Clear all" when more than one applies.
 */
export function FilterChips({ filters, className }: FilterChipsProps) {
  const ui = useUi()
  const { chips, strings } = filters

  if (!chips.length) return null

  return (
    <div
      className={["flex flex-wrap items-center gap-2", className].filter(Boolean).join(" ")}
      role="list"
      aria-label={strings.appliedFilters}
      data-filter-chips=""
    >
      {chips.map((chip) => (
        <span
          key={chip.filter}
          className="inline-flex max-w-full items-center gap-1 rounded-full border bg-muted ps-2.5 pe-1 py-0.5 text-sm text-foreground"
          role="listitem"
          data-filter-chip={chip.filter}
        >
          <span className="truncate">
            <span className="text-muted-foreground">{chip.label}</span>
            {chip.value ? (
              <>
                <span className="text-muted-foreground">:</span> <bdi>{chip.value}</bdi>
              </>
            ) : null}
          </span>
          <ui.Link
            href={chip.url}
            className="inline-flex size-5 shrink-0 items-center justify-center rounded-full text-muted-foreground hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-ring"
            aria-label={replace(strings.remove, { filter: chip.text })}
            data-filter-chip-remove=""
          >
            <ui.Icon name="close" size="sm" />
          </ui.Link>
        </span>
      ))}

      {chips.length > 1 ? (
        <ui.Link
          href={filters.clearUrl}
          className="text-sm font-medium text-primary underline-offset-4 hover:underline"
          data-filter-chips-clear=""
        >
          {strings.clearAll}
        </ui.Link>
      ) : null}
    </div>
  )
}
