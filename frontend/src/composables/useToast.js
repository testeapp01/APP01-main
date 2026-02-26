import { reactive } from 'vue'

const state = reactive({ toasts: [] })
let id = 1

export function useToast() {
  function notify(message, opts = {}) {
    const t = { id: id++, message, type: opts.type || 'info', timeout: opts.timeout || 4000 }
    state.toasts.push(t)
    if (t.timeout) setTimeout(() => dismiss(t.id), t.timeout)
    return t.id
  }
  function dismiss(id) { const i = state.toasts.findIndex(t => t.id === id); if (i !== -1) state.toasts.splice(i,1) }
  return { toasts: state.toasts, notify, dismiss }
}

export default useToast
