const PREFIX = '[ticketboost]'

/**
 * Reports an error without throwing, so a plugin failure never breaks the GLPI page.
 * @param error The error to report.
 */
export function report(error: unknown): void {
  console.error(PREFIX, error)
}

/**
 * Runs a function and isolates any error it throws.
 * @param fn The function to run.
 * @param fallback The value returned when the function throws.
 * @param onError Called with the error; defaults to {@link report}.
 * @returns The function result, or the fallback on error.
 */
export function safe<T>(
  fn: () => T,
  fallback: T,
  onError: (error: unknown) => void = report
): T {
  try {
    return fn()
  } catch (error) {
    onError(error)
    return fallback
  }
}
