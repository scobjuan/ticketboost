import { safe } from '../core/safe'
import { startConfigForm } from './form'

// Entry point of `ticketboost-config.js`, loaded only by the plugin configuration pages.
safe(() => startConfigForm(document), null)
