/**
 * Scores how well a query matches a text as a case-insensitive subsequence. Consecutive
 * characters and matches at word starts score higher.
 * @param query What the user typed.
 * @param text The candidate text.
 * @returns A score above zero on a match, zero when the query is not a subsequence.
 */
export function fuzzyScore(query: string, text: string): number {
  const needle = normalize(query)
  const haystack = normalize(text)
  if (needle === '') {
    return 1
  }
  let score = 0
  let position = 0
  let previous = -2
  for (const char of needle) {
    const found = haystack.indexOf(char, position)
    if (found < 0) {
      return 0
    }
    score += 1
    if (found === previous + 1) {
      score += 2
    }
    if (found === 0 || /[\s\-_.@(]/.test(haystack.charAt(found - 1))) {
      score += 3
    }
    previous = found
    position = found + 1
  }
  return score
}

/**
 * Filters and sorts items by fuzzy score; ties keep their original order.
 * @param query What the user typed.
 * @param items The candidates.
 * @param text Extracts the searchable text of an item.
 * @returns Matching items, best first.
 */
export function fuzzyFilter<T>(
  query: string,
  items: readonly T[],
  text: (item: T) => string
): T[] {
  return items
    .map((item, index) => ({
      item,
      index,
      score: fuzzyScore(query, text(item))
    }))
    .filter((entry) => entry.score > 0)
    .sort((a, b) => b.score - a.score || a.index - b.index)
    .map((entry) => entry.item)
}

function normalize(value: string): string {
  return value.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase()
}
