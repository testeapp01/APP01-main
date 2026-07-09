import { useToast } from './useToast'

export function useApiError() {
  function getMessage(error, fallback = 'Erro de requisição') {
    if (!error) return fallback
    if (typeof error.normalizedMessage === 'string' && error.normalizedMessage.trim() !== '') return error.normalizedMessage
    const backendError = error?.response?.data?.error || error?.response?.data?.message || null
    if (backendError) return backendError
    if (typeof error.message === 'string' && error.message.trim() !== '') return error.message
    return fallback
  }

  function show(error, fallback = 'Erro de requisição', opts = {}) {
    const msg = getMessage(error, fallback)
    try {
      const { notify } = useToast()
      notify(msg, { type: opts.type || 'error', timeout: opts.timeout })
    } catch (err) {
      // fail silently if toast provider unavailable
      // eslint-disable-next-line no-console
      console.error('useApiError.show: erro ao notificar toast', err)
    }
    return msg
  }

  return { getMessage, show }
}

export default useApiError
