/** Where the user is: on a ticket form, or anywhere else. */
export type PageContext = 'ticket' | 'other'

/**
 * Reads the ticket id from the URL. GLPI 11 and 12 both serve an existing ticket at
 * `/front/ticket.form.php?id=N`.
 * @param location The page location.
 * @returns The id of the ticket on screen, or null outside the form of an existing ticket.
 */
export function ticketId(
  location: Pick<Location, 'pathname' | 'search'>
): number | null {
  if (!location.pathname.endsWith('/front/ticket.form.php')) {
    return null
  }
  const id = new URLSearchParams(location.search).get('id') ?? ''
  return /^[1-9]\d*$/.test(id) ? Number(id) : null
}

/**
 * Detects the page context from the URL.
 * @param location The page location.
 * @returns `ticket` on the form of an existing ticket, `other` everywhere else.
 */
export function detectContext(
  location: Pick<Location, 'pathname' | 'search'>
): PageContext {
  return ticketId(location) === null ? 'other' : 'ticket'
}
