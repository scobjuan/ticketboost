import { safe } from '../core/safe'

/** The part of a TinyMCE editor the bridge uses. */
export interface TinyEditor {
  readonly initialized?: boolean
  getDoc(): Document | null | undefined
  on(event: 'init', callback: () => void): void
}

/** The part of the global TinyMCE manager the bridge uses. */
export interface TinyManager {
  get(): readonly TinyEditor[]
  on(
    event: 'AddEditor',
    callback: (event: { editor: TinyEditor }) => void
  ): void
  triggerSave?(): void
}

/**
 * Returns the TinyMCE manager GLPI loads globally, if any.
 * @param win The window to inspect.
 * @returns The manager, or undefined when TinyMCE is not on the page.
 */
export function tinymceOf(win: Window): TinyManager | undefined {
  const candidate = (win as Window & { tinymce?: unknown }).tinymce
  if (
    typeof candidate === 'object' &&
    candidate !== null &&
    typeof (candidate as Partial<TinyManager>).get === 'function' &&
    typeof (candidate as Partial<TinyManager>).on === 'function'
  ) {
    return candidate as TinyManager
  }
  return undefined
}

/**
 * Calls `onDocument` with the document of every TinyMCE editor, existing and future.
 * Editors live in iframes, so key events typed in them never reach the parent document.
 * When TinyMCE is not loaded yet, the lookup is retried once the window has loaded.
 * @param win The window that hosts the editors.
 * @param onDocument Called once per editor document.
 */
export function watchEditors(
  win: Window,
  onDocument: (doc: Document) => void
): void {
  const seen = new WeakSet<Document>()
  const attach = (editor: TinyEditor): void => {
    const run = (): void => {
      safe(() => {
        const doc = editor.getDoc()
        if (doc && !seen.has(doc)) {
          seen.add(doc)
          onDocument(doc)
        }
      }, undefined)
    }
    if (editor.initialized === true) {
      run()
    } else {
      editor.on('init', run)
    }
  }
  const hook = (): boolean =>
    safe(() => {
      const manager = tinymceOf(win)
      if (manager === undefined) {
        return false
      }
      manager.get().forEach(attach)
      manager.on('AddEditor', ({ editor }) => {
        attach(editor)
      })
      return true
    }, false)
  if (!hook()) {
    win.addEventListener('load', () => hook(), { once: true })
  }
}
