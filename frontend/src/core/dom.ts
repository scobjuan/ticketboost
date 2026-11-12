/** Attributes accepted by {@link h}; `false` and `undefined` skip the attribute. */
export type Attributes = Readonly<Record<string, string | boolean | undefined>>

/** A child accepted by {@link h}: strings become text nodes, never HTML. */
export type Child = Node | string | null | undefined | false

/**
 * Creates an element without ever parsing HTML, so dynamic text cannot inject markup.
 * @param doc The document that owns the element.
 * @param tag The tag name.
 * @param attributes Attributes to set; `true` sets an empty attribute.
 * @param children Child nodes or text.
 * @returns The new element.
 */
export function h<K extends keyof HTMLElementTagNameMap>(
  doc: Document,
  tag: K,
  attributes: Attributes = {},
  children: readonly Child[] = []
): HTMLElementTagNameMap[K] {
  const element = doc.createElement(tag)
  for (const [name, value] of Object.entries(attributes)) {
    if (value === true) {
      element.setAttribute(name, '')
    } else if (typeof value === 'string') {
      element.setAttribute(name, value)
    }
  }
  for (const child of children) {
    if (typeof child === 'string') {
      element.appendChild(doc.createTextNode(child))
    } else if (child) {
      element.appendChild(child)
    }
  }
  return element
}

/**
 * Replaces all children of an element.
 * @param element The element to fill.
 * @param children The new children.
 */
export function replaceChildren(
  element: Element,
  children: readonly Node[]
): void {
  while (element.firstChild) {
    element.removeChild(element.firstChild)
  }
  for (const child of children) {
    element.appendChild(child)
  }
}
