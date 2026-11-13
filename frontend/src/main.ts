import './styles/ticketboost.css'

import { createApi } from './api/client'
import { boot } from './boot'

// The bundle is served from `<plugin>/js/`, so the endpoints sit next to it in `ajax/`.
boot(document, {
  api: createApi(document, new URL('../ajax/', import.meta.url).href)
})
