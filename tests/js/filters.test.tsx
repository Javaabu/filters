import { FormsProvider, type FormsRouter } from "@javaabu/forms"
import { fireEvent, render, screen, within } from "@testing-library/react"
import type * as React from "react"
import { afterEach, describe, expect, it, vi } from "vitest"

import { FilterBar, FilterChips, SortableHeading, SortSelect, hiddenFields, type FiltersData } from "@javaabu/filters"

import articlesDv from "./fixtures/articles-dv.json"
import articlesEmpty from "./fixtures/articles-empty.json"
import articles from "./fixtures/articles.json"

const filters = articles as unknown as FiltersData
const empty = articlesEmpty as unknown as FiltersData
const dhivehi = articlesDv as unknown as FiltersData

function clone(data: FiltersData = filters): FiltersData {
  return structuredClone(data)
}

function values(form: HTMLFormElement): URLSearchParams {
  return new URLSearchParams(Array.from(new FormData(form).entries()).map(([key, value]) => [key, String(value)]))
}

afterEach(() => {
  vi.restoreAllMocks()
})

describe("FilterBar", () => {
  it("renders a GET form with the stencil fields, bound to the filter values", () => {
    const { container } = render(<FilterBar filters={filters} />)

    const form = container.querySelector("form")!

    expect(form).toHaveAttribute("id", "filter")
    expect(form).toHaveAttribute("method", "GET")
    expect(form).toHaveAttribute("action", "/articles")
    expect(form).toHaveAttribute("role", "search")
    expect(form).toHaveAccessibleName("Filters")

    const search = container.querySelector<HTMLInputElement>('input[name="search"]')!
    expect(search.value).toBe("ވާރޭ")
    expect(search).toHaveAttribute("dir", "rtl")

    const data = values(form)
    expect(data.getAll("status[]")).toEqual(["draft", "published"])
    expect(data.get("published_at_from")).toBe("2026-01-01")
    expect(Array.from(data.keys()).some((key) => key.startsWith("sync_"))).toBe(false)
  })

  it("keeps the sort, preserved keys and hidden filters as hidden inputs", () => {
    const { container } = render(<FilterBar filters={filters} />)

    const data = values(container.querySelector("form")!)

    expect(data.get("author_id")).toBe("1")
    expect(data.get("orderby")).toBe("title")
    expect(data.get("order")).toBe("DESC")
    expect(data.get("per_page")).toBe("50")
  })

  it("has Filter, Clear and the chips", () => {
    render(<FilterBar filters={filters} />)

    expect(screen.getByRole("button", { name: "Filter" })).toHaveAttribute("type", "submit")
    expect(screen.getByRole("link", { name: "Clear" })).toHaveAttribute("href", "/articles?per_page=50")
    expect(screen.getByRole("list", { name: "Applied filters" })).toBeInTheDocument()
  })

  it("renders the sort dropdown instead of the hidden sort inputs", () => {
    const { container } = render(<FilterBar filters={filters} sort chips={false} />)

    const form = container.querySelector("form")!

    expect(form.querySelectorAll('input[type="hidden"][name="orderby"]')).toHaveLength(0)
    expect(form.querySelector<HTMLSelectElement>('select[name="orderby"]')!.value).toBe("title")
    expect(form.querySelector<HTMLSelectElement>('select[name="order"]')!.value).toBe("DESC")
    expect(screen.queryByRole("list", { name: "Applied filters" })).toBeNull()
  })

  it("adds extra buttons and export buttons", () => {
    render(
      <FilterBar filters={filters} export>
        <button type="button">Extra</button>
      </FilterBar>,
    )

    expect(screen.getByRole("button", { name: "Extra" })).toBeInTheDocument()
    expect(container().querySelector('button[name="download"]')).not.toBeNull()
  })

  it("submits through the forms router as a GET visit", () => {
    const submit = vi.fn()
    const router: FormsRouter = { name: "test", submit }

    const { container } = render(
      <FormsProvider router={router}>
        <FilterBar filters={filters} />
      </FormsProvider>,
    )

    fireEvent.submit(container.querySelector("form")!)

    expect(submit).toHaveBeenCalledTimes(1)
    const [request] = submit.mock.calls[0]!
    expect(request.method).toBe("get")
    expect(request.url).toBe("/articles")
    expect((request.data as FormData).get("search")).toBe("ވާރޭ")
  })
})

function container(): HTMLElement {
  return document.body
}

describe("FilterChips", () => {
  it("shows each applied filter with a link that removes it", () => {
    render(<FilterChips filters={filters} />)

    const list = screen.getByRole("list", { name: "Applied filters" })
    const chips = within(list).getAllByRole("listitem")

    expect(chips).toHaveLength(4)
    expect(chips[1]).toHaveTextContent("Status: Draft, Published")

    const remove = screen.getByRole("link", { name: "Remove filter Search: ވާރޭ" })
    expect(remove).toHaveAttribute("href", filters.chips[0]!.url)
  })

  it("clears all when more than one applies", () => {
    render(<FilterChips filters={filters} />)

    expect(screen.getByRole("link", { name: "Clear all" })).toHaveAttribute("href", filters.clearUrl)
  })

  it("shows the label alone for a chip without a value, and no clear all for one chip", () => {
    const data = clone()
    data.chips = [{ filter: "long", label: "Long reads", value: null, text: "Long reads", url: "/articles" }]

    render(<FilterChips filters={data} />)

    expect(screen.getByRole("listitem")).toHaveTextContent(/^Long reads$/)
    expect(screen.queryByRole("link", { name: "Clear all" })).toBeNull()
  })

  it("renders nothing without applied filters", () => {
    const { container } = render(<FilterChips filters={empty} />)

    expect(container).toBeEmptyDOMElement()
  })

  it("renders in Dhivehi", () => {
    render(<FilterChips filters={dhivehi} />)

    expect(screen.getByRole("list", { name: "ބޭނުންކުރެވިފައިވާ ފިލްޓަރުތައް" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ހުރިހާ ފިލްޓަރެއް ފޮހެލާ" })).toBeInTheDocument()
    expect(screen.getByText("ހަމައެކަނި ފޮހެލެވިފައިވާ ތައް")).toBeInTheDocument()
  })
})

describe("SortableHeading", () => {
  function table(children: React.ReactNode) {
    return render(
      <table>
        <thead>
          <tr>{children}</tr>
        </thead>
      </table>,
    )
  }

  it("links to the list sorted by its sort, with aria-sort", () => {
    table(
      <>
        <SortableHeading filters={filters} sort="title" />
        <SortableHeading filters={filters} sort="word_count" label="Words" className="w-40" />
      </>,
    )

    const [title, words] = screen.getAllByRole("columnheader")

    expect(title).toHaveAttribute("aria-sort", "descending")
    expect(within(title!).getByRole("link")).toHaveAttribute("href", filters.sort.options[0]!.url)
    expect(words).toHaveAttribute("aria-sort", "none")
    expect(words).toHaveClass("w-40")
    expect(within(words!).getByRole("link", { name: "Sort by Words" })).toBeInTheDocument()
  })

  it("marks the default sort", () => {
    table(<SortableHeading filters={empty} sort="published_at" />)

    expect(screen.getByRole("columnheader")).toHaveAttribute("aria-sort", "descending")
  })

  it("isn't sortable for a sort that isn't registered", () => {
    table(<SortableHeading filters={filters} sort="status" label="Status" />)

    expect(screen.getByRole("columnheader")).not.toHaveAttribute("aria-sort")
    expect(screen.queryByRole("link")).toBeNull()
    expect(screen.getByRole("columnheader")).toHaveTextContent("Status")
  })
})

describe("SortSelect", () => {
  it("sets the filter form's sort and submits it", async () => {
    const requestSubmit = vi.spyOn(HTMLFormElement.prototype, "requestSubmit").mockImplementation(() => {})

    render(
      <>
        <FilterBar filters={filters} chips={false} />
        <SortSelect filters={filters} />
      </>,
    )

    const select = document.querySelector<HTMLSelectElement>('[data-filter-sort] select[name="orderby"]')!
    fireEvent.change(select, { target: { value: "word_count" } })
    await Promise.resolve()

    const form = document.getElementById("filter") as HTMLFormElement
    expect(form.querySelector<HTMLInputElement>('input[type="hidden"][name="orderby"]')!.value).toBe("word_count")
    expect(requestSubmit).toHaveBeenCalledTimes(1)
  })

  it("submits its own form when it's in the filter bar", async () => {
    const requestSubmit = vi.spyOn(HTMLFormElement.prototype, "requestSubmit").mockImplementation(() => {})

    render(<FilterBar filters={filters} sort chips={false} />)

    fireEvent.change(document.querySelector<HTMLSelectElement>('select[name="order"]')!, { target: { value: "ASC" } })
    await Promise.resolve()

    expect(requestSubmit).toHaveBeenCalledTimes(1)
    expect(values(document.getElementById("filter") as HTMLFormElement).get("order")).toBe("ASC")
  })

  it("does nothing without a filter form, and renders nothing without sorts", async () => {
    const requestSubmit = vi.spyOn(HTMLFormElement.prototype, "requestSubmit").mockImplementation(() => {})

    render(<SortSelect filters={filters} />)

    fireEvent.change(document.querySelector<HTMLSelectElement>('select[name="orderby"]')!, {
      target: { value: "author" },
    })
    await Promise.resolve()

    expect(requestSubmit).not.toHaveBeenCalled()

    const data = clone()
    data.sort.options = []
    const { container } = render(<SortSelect filters={data} />)
    expect(container).toBeEmptyDOMElement()
  })
})

describe("hiddenFields", () => {
  it("flattens lists and skips names", () => {
    expect(hiddenFields({ a: "1", b: ["x", "y"], c: "2" }, ["c"])).toEqual([
      ["a", "1"],
      ["b[]", "x"],
      ["b[]", "y"],
    ])
  })
})
