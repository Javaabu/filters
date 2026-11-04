import { TableHeading, useFormsTranslation, useUi } from "@javaabu/forms"
import type * as React from "react"

import type { FiltersData } from "./types"

export interface SortableHeadingProps extends Omit<React.ComponentProps<"th">, "children"> {
  filters: FiltersData
  /** The sort's name. A heading for a sort that isn't registered isn't sortable. */
  sort: string
  /** Default: the sort's label. */
  label?: React.ReactNode
  children?: React.ReactNode
}

/**
 * `<x-filters::heading>`: a table heading that links to the list sorted by
 * its sort (the other direction when it already is), with `aria-sort`.
 */
export function SortableHeading({ filters, sort, label, children, ...props }: SortableHeadingProps) {
  const ui = useUi()
  const { t } = useFormsTranslation()
  const option = filters.sort.options.find((candidate) => candidate.value === sort)
  const content = children ?? label ?? option?.label ?? sort

  if (!option) return <TableHeading {...props}>{content}</TableHeading>

  const active = filters.sort.current === sort
  const descending = filters.sort.direction === "desc"
  const text = typeof (label ?? option.label) === "string" ? String(label ?? option.label) : option.label

  return (
    <TableHeading
      aria-sort={active ? (descending ? "descending" : "ascending") : "none"}
      data-filter-sort-heading={sort}
      {...props}
    >
      <ui.Link
        href={option.url}
        className="inline-flex items-center gap-1 font-medium hover:text-foreground"
        aria-label={t("sort_by", { label: text })}
      >
        {content}
        <ui.Icon
          name={active ? (descending ? "chevron-down" : "chevron-up") : "chevrons-up-down"}
          size="sm"
          className={active ? undefined : "opacity-40"}
        />
      </ui.Link>
    </TableHeading>
  )
}
