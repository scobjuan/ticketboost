/** The answer forms GLPI keeps collapsed under the ticket timeline. */
export const COMPOSER_BLOCKS = {
  followup: 'new-ITILFollowup-block',
  task: 'new-TicketTask-block'
} as const

/** The form to open. */
export type ComposerKind = keyof typeof COMPOSER_BLOCKS

/** How long to wait for GLPI to load the form the first time it opens (it is lazy in GLPI 12). */
export const COMPOSER_TIMEOUT_MS = 3000

/** Longest wait for the collapse animation to end before focusing anyway. */
export const COMPOSER_SHOWN_MS = 600

/** Pause between checks while the form loads. */
export const COMPOSER_POLL_MS = 50

interface CollapseApi {
  getOrCreateInstance(
    element: Element,
    options: { toggle: boolean }
  ): { show(): void }
}

interface TinyEditor {
  readonly initialized?: boolean
  focus(): void
}

interface TinyById {
  get(id: string): TinyEditor | null | undefined
}

/**
 * Opens the followup or task form under the timeline and puts the cursor in its editor,
 * so the user can type at once and send with Ctrl+Enter. It only reveals the form through
 * Bootstrap's collapse API, the same one GLPI's own buttons use; nothing is sent.
 * @param doc The page document.
 * @param kind Which form to open.
 * @returns True when the editor has the focus; false when the page has no such form.
 */
export async function openComposer(
  doc: Document,
  kind: ComposerKind
): Promise<boolean> {
  const win = doc.defaultView
  if (win === null) {
    return false
  }
  const pause = (): Promise<void> =>
    new Promise<void>((resolve) => {
      win.setTimeout(resolve, COMPOSER_POLL_MS)
    })

  // The timeline can still be drawing when the shortcut is pressed on a slow page.
  let block = doc.getElementById(COMPOSER_BLOCKS[kind])
  for (
    let waited = 0;
    block === null && waited < COMPOSER_TIMEOUT_MS;
    waited += COMPOSER_POLL_MS
  ) {
    await pause()
    block = doc.getElementById(COMPOSER_BLOCKS[kind])
  }
  const collapse = (win as Window & { bootstrap?: { Collapse?: CollapseApi } })
    .bootstrap?.Collapse
  if (block === null || collapse === undefined) {
    return false
  }
  collapse.getOrCreateInstance(block, { toggle: false }).show()
  await shown(block, win)

  const tiny = (win as Window & { tinymce?: TinyById }).tinymce
  const find = (): {
    field: HTMLTextAreaElement | null
    editor: TinyEditor | null | undefined
    ready: boolean
  } => {
    const field = block.querySelector<HTMLTextAreaElement>(
      'textarea[name="content"]'
    )
    if (field === null) {
      return { field, editor: undefined, ready: false }
    }
    // Without TinyMCE, or for a text area it will not manage, the text area is the editor.
    if (tiny === undefined || field.id === '') {
      return { field, editor: undefined, ready: true }
    }
    const editor = tiny.get(field.id)
    return { field, editor, ready: !!editor && editor.initialized !== false }
  }

  let waited = 0
  let found = find()
  while (!found.ready && waited < COMPOSER_TIMEOUT_MS) {
    await pause()
    waited += COMPOSER_POLL_MS
    found = find()
  }
  if (found.field === null) {
    return false
  }

  // Past the timeout the plain text area still takes the focus.
  if (found.ready && found.editor) {
    found.editor.focus()
  } else {
    found.field.focus()
  }
  block.scrollIntoView({ block: 'center' })
  return true
}

/**
 * Waits until the collapse animation has ended: a field focused while the block is still
 * growing may lose the focus again, which WebKit does.
 */
function shown(block: HTMLElement, win: Window): Promise<void> {
  if (
    block.classList.contains('show') &&
    !block.classList.contains('collapsing')
  ) {
    return Promise.resolve()
  }
  return new Promise<void>((resolve) => {
    const done = (): void => {
      block.removeEventListener('shown.bs.collapse', done)
      resolve()
    }
    block.addEventListener('shown.bs.collapse', done)
    win.setTimeout(done, COMPOSER_SHOWN_MS)
  })
}
