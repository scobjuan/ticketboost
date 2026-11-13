/**
 * Changes the URL of the test page, so code that reads `location` can be tested.
 * @param url The new absolute URL.
 */
export function setPageUrl(url: string): void {
  const win = window as unknown as { happyDOM: { setURL(url: string): void } }
  win.happyDOM.setURL(url)
}
