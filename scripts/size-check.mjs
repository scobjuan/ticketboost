// Fails when the gzipped frontend bundle exceeds the budget defined in the plan.
import { existsSync, readFileSync } from 'node:fs'
import { gzipSync } from 'node:zlib'

const BUDGET_BYTES = 15 * 1024
const FILES = ['public/js/ticketboost.js', 'public/css/ticketboost.css']

let total = 0
for (const file of FILES) {
  if (!existsSync(file)) {
    console.error(`${file} not found, run "pnpm build" first`)
    process.exit(1)
  }
  const size = gzipSync(readFileSync(file)).length
  total += size
  console.log(`${file}: ${size} B gzip`)
}

if (total > BUDGET_BYTES) {
  console.error(`Bundle is ${total} B gzip (budget ${BUDGET_BYTES} B)`)
  process.exit(1)
}
console.log(`Bundle ok: ${total}/${BUDGET_BYTES} B gzip`)
