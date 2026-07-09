import { useToast } from './useToast'

function extractErrorPayload(error) {
  if (!error) return null
  const data = error?.response?.data || null
  if (!data) return null

  const message = typeof data.error === 'string'
    ? data.error
    : typeof data.message === 'string'
      ? data.message
      : null

  const details = data.details || data.errors || null
  return { message, details }
}

function formatDetails(details) {
  if (!details) return null

  if (Array.isArray(details)) {
    return details.filter(Boolean).join('; ')
  }

  if (typeof details === 'string') {
    return details
  }

  if (typeof details === 'object') {
    return Object.entries(details)
      .map(([field, value]) => {
        if (Array.isArray(value)) {
          return `${field}: ${value.filter(Boolean).join(', ')}`
        }
        return `${field}: ${value}`
      })
      .join('; ')
  }

  return String(details)
}

export function useApiError() {
  function getMessage(error, fallback = 'Erro de requisição') {
    if (!error) return fallback
    if (typeof error.normalizedMessage === 'string' && error.normalizedMessage.trim() !== '') return error.normalizedMessage

    const payload = extractErrorPayload(error)
    if (payload?.message) {
      const detailText = formatDetails(payload.details)
      return detailText ? `${payload.message}: ${detailText}` : payload.message
    }

    if (typeof error.message === 'string' && error.message.trim() !== '') return error.message
    return fallback
  }

  function getDetails(error) {
    const payload = extractErrorPayload(error)
    return payload?.details || null
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

  return { getMessage, getDetails, show }
}

export default useApiError
