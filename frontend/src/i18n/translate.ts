/** Translated labels by key, delivered by the server bootstrap payload (PHP gettext). */
export type Labels = Readonly<Record<string, string>>

/** Looks up a label by key. */
export type Translate = (key: string) => string

/**
 * Creates the label lookup. The frontend never ships user-facing text: a missing label
 * falls back to its key, which makes the gap visible instead of hiding it.
 * @param labels Translated labels from the server.
 * @returns The lookup function.
 */
export function createTranslator(labels: Labels): Translate {
  return (key) => labels[key] ?? key
}
