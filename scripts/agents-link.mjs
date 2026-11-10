// Creates a local, never committed, symlink to AGENTS.md under the filename a given
// coding tool expects, and excludes it through .git/info/exclude (not versioned).
//
// Usage: node scripts/agents-link.mjs <FILENAME>   (or: pnpm agents:link <FILENAME>)
import { execFileSync } from 'node:child_process'
import {
  appendFileSync,
  existsSync,
  lstatSync,
  mkdirSync,
  readFileSync,
  symlinkSync,
  unlinkSync
} from 'node:fs'
import { dirname, join, relative } from 'node:path'

const name = process.argv[2]
if (!name || name.startsWith('/') || name.includes('..')) {
  console.error(
    'Usage: node scripts/agents-link.mjs <FILENAME relative to the repository root>'
  )
  process.exit(2)
}

const root = execFileSync('git', ['rev-parse', '--show-toplevel'], {
  encoding: 'utf8'
}).trim()
const target = join(root, name)
const gitDir = execFileSync('git', ['rev-parse', '--absolute-git-dir'], {
  encoding: 'utf8'
}).trim()

if (existsSync(target) && !lstatSync(target).isSymbolicLink()) {
  console.error(`${name} exists and is not a symlink; refusing to overwrite it`)
  process.exit(1)
}
mkdirSync(dirname(target), { recursive: true })
if (existsSync(target) || lstatSync(target, { throwIfNoEntry: false }))
  unlinkSync(target)
symlinkSync(relative(dirname(target), join(root, 'AGENTS.md')), target)

const exclude = join(gitDir, 'info', 'exclude')
mkdirSync(dirname(exclude), { recursive: true })
const entry = `/${name}`
const current = existsSync(exclude) ? readFileSync(exclude, 'utf8') : ''
if (!current.split('\n').includes(entry)) {
  appendFileSync(
    exclude,
    (current.endsWith('\n') || current === '' ? '' : '\n') + entry + '\n'
  )
}
console.log(`${name} -> AGENTS.md (local only, excluded from git)`)
