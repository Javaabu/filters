/**
 * The filter form's hidden input called `name`, added when the form doesn't
 * have one. Like javaabu/forms' sortable headings, so they work together.
 */
export function hiddenInput(form: HTMLFormElement, name: string): HTMLInputElement {
  const existing = Array.from(form.elements).find(
    (element): element is HTMLInputElement => element instanceof HTMLInputElement && element.name === name,
  )
  if (existing) return existing

  const input = document.createElement("input")
  input.type = "hidden"
  input.name = name
  form.appendChild(input)

  return input
}

/** Submits a form like a click on its submit button, so `onSubmit` handlers (Inertia visits) run. */
export function submit(form: HTMLFormElement): void {
  if (typeof form.requestSubmit === "function") form.requestSubmit()
  else form.submit()
}

/** The `[name]=value` pairs of the hidden parameters, with `name[]` for lists. */
export function hiddenFields(hidden: Record<string, string | string[]>, skip: string[] = []): [string, string][] {
  return Object.entries(hidden).flatMap(([name, value]): [string, string][] => {
    if (skip.includes(name)) return []
    if (Array.isArray(value)) return value.map((item): [string, string] => [`${name}[]`, item])
    return [[name, value]]
  })
}

/** Replaces `:key` placeholders in a translated string. */
export function replace(text: string, replacements: Record<string, string>): string {
  return Object.entries(replacements).reduce((result, [key, value]) => result.split(`:${key}`).join(value), text)
}
