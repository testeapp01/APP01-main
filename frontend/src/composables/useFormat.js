/**
 * Composable para funções de formatação compartilhadas
 * Centraliza formatMoney, formatDate, formatPercent para reutilização
 */

export function useFormat() {
  /**
   * Formata valor numérico como moeda brasileira (R$)
   * @param {number} value - Valor a formatar
   * @returns {string} Valor formatado como "R$ 1.234,56"
   */
  const formatMoney = (value) => {
    if (!value) return 'R$ 0,00'
    const num = Number(value)
    return 'R$ ' + num.toFixed(2).replace('.', ',')
  }

  /**
   * Formata data no padrão YYYY-MM-DD para DD/MM/YYYY
   * @param {string} value - Data em formato YYYY-MM-DD
   * @returns {string} Data formatada como "DD/MM/YYYY"
   */
  const formatDate = (value) => {
    if (!value) return '-'
    const [year, month, day] = String(value).slice(0, 10).split('-')
    return year && month && day ? `${day}/${month}/${year}` : value
  }

  /**
   * Formata percentual com símbolo %
   * @param {number} value - Valor numérico (0-100)
   * @returns {string} Percentual formatado como "33%"
   */
  const formatPercent = (value) => {
    if (!value && value !== 0) return '0%'
    return Math.round(Number(value)) + '%'
  }

  /**
   * Formata tempo em minutos para formato legível
   * @param {number} minutes - Minutos
   * @returns {string} Tempo formatado
   */
  const formatTime = (minutes) => {
    if (!minutes) return '0 min'
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    if (hours > 0) return `${hours}h ${mins}m`
    return `${mins} min`
  }

  return {
    formatMoney,
    formatDate,
    formatPercent,
    formatTime
  }
}
