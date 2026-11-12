/** Where the user is: on a ticket form, or anywhere else. */
export type PageContext = 'ticket' | 'other'

/**
 * Detects the page context from the URL. GLPI 11 and 12 both serve an existing ticket at
 * `/front/ticket.form.php?id=N`.
 * @param location The page location.
 * @returns `ticket` on the form of an existing ticket, `other` everywhere else.
 */
export function detectContext(
  location: Pick<Location, 'pathname' | 'search'>
): PageContext {
  if (!location.pathname.endsWith('/front/ticket.form.php')) {
    return 'other'
  }
  const id = new URLSearchParams(location.search).get('id') ?? ''
  return /^[1-9]\d*$/.test(id) ? 'ticket' : 'other'
}
