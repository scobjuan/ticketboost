import { safe } from '../core/safe'
import { watchConfigForms } from './watch'

// Entry point of `ticketboost-config.js`, loaded only by the plugin configuration pages.
safe(() => watchConfigForms(document), null)
