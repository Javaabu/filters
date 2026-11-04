import { defineConfig } from "vitest/config"

// @javaabu/stencil is the React renderer javaabu/stencil ships in its Composer
// package. Apps get it through javaabu/manifest; here it comes from vendor/.
export default defineConfig({
  resolve: {
    alias: {
      "@javaabu/filters": new URL("./resources/js", import.meta.url).pathname,
      "@javaabu/stencil": new URL("./vendor/javaabu/stencil/resources/js", import.meta.url).pathname,
    },
  },
  test: {
    environment: "jsdom",
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      reporter: ["text", "clover", "lcov"],
      reportsDirectory: "coverage",
    },
  },
})
