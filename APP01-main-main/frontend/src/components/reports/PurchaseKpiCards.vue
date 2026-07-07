<template>
  <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Custo total
      </div>
      <div class="saas-kpi-value">
        {{ currency(kpis.soma_custo_total) }}
      </div>
      <div class="saas-kpi-help">
        Soma de custo_total
      </div>
    </article>

    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Comissão total
      </div>
      <div class="saas-kpi-value">
        {{ currency(kpis.soma_comissao_total) }}
      </div>
      <div class="saas-kpi-help">
        Soma de comissao_total
      </div>
    </article>

    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Custo final real
      </div>
      <div class="saas-kpi-value">
        {{ currency(kpis.soma_custo_final_real) }}
      </div>
      <div class="saas-kpi-help">
        Soma consolidada por compra
      </div>
    </article>

    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Ticket médio
      </div>
      <div class="saas-kpi-value">
        {{ currency(kpis.ticket_medio) }}
      </div>
      <div class="saas-kpi-help">
        Média por compra consolidada
      </div>
    </article>

    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Produto mais comprado
      </div>
      <div class="saas-kpi-text">
        {{ kpis.produto_mais_comprado?.nome || 'Sem dados' }}
      </div>
      <div class="saas-kpi-help">
        {{ number(kpis.produto_mais_comprado?.quantidade || 0) }} unidades no período
      </div>
    </article>

    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Fornecedor líder
      </div>
      <div class="saas-kpi-text">
        {{ kpis.fornecedor_maior_volume?.nome || 'Sem dados' }}
      </div>
      <div class="saas-kpi-help">
        {{ currency(kpis.fornecedor_maior_volume?.total_financeiro || 0) }} de volume financeiro
      </div>
    </article>

    <article class="saas-kpi-card">
      <div class="saas-kpi-label">
        Prazo médio
      </div>
      <div class="saas-kpi-value">
        {{ number(kpis.prazo_medio_dias || 0) }} dias
      </div>
      <div class="saas-kpi-help">
        Data compra até entrega prevista
      </div>
    </article>
  </section>
</template>

<script>
export default {
  name: 'PurchaseKpiCards',
  props: {
    kpis: {
      type: Object,
      default: () => ({})
    }
  },
  methods: {
    currency(value) {
      return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0))
    },
    number(value) {
      return new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 }).format(Number(value || 0))
    }
  }
}
</script>
