import { describe, expect, it } from "vitest"

import * as filters from "@javaabu/filters"

describe("@javaabu/filters", () => {
  it("loads", () => {
    expect(filters).toBeTypeOf("object")
  })
})
