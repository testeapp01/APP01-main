<template>
  <div class="page-shell page-fade">
    <PageHero
      :title="`Pedido #${header.id || '-'}`"
      subtitle="Detalhamento dos itens do pedido."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary"
          @click="$router.push('/vendas')"
        >
          Voltar
        </BaseButton>
        <BaseButton
          class="btn-primary"
          @click="refresh"
        >
          Atualizar
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">Cliente</div>
        <div class="saas-kpi-value">{{ header.cliente || '-' }}</div>
        <div class="saas-kpi-help">Relacionamento do pedido</div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">Valor Total</div>
        <div class="saas-kpi-value">R$ {{ Number(header.valor_total || 0).toFixed(2) }}</div>
        <div class="saas-kpi-help">Soma dos itens</div>
      </article>
    </section>

    <div v-if="items.length" class="panel-inner">
      <BaseTable :columns="columns" :rows="items">
        <template #produto="{ row }">{{ row.produto || '-' }}</template>
        <template #quantidade="{ row }">{{ row.quantidade ?? '-' }}</template>
        <template #valor_unitario="{ row }">R$ {{ Number(row.valor_unitario || 0).toFixed(2) }}</template>
        <template #status="{ row }">{{ row.status || '-' }}</template>
        <template #data_venda="{ row }">{{ formatDate(row.data_venda) }}</template>
      </BaseTable>
    </div>

    <ListState
      :loading="loading"
      :has-data="items.length > 0"
      loading-text="Carregando itens do pedido..."
      empty-title="Pedido sem itens"
      empty-message="Nenhum item foi encontrado para este pedido."
    />
  </div>
</template>

<script>
import PageHero from '../components/ui/PageHero.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable from '../components/ui/BaseTable.vue'
import ListState from '../components/ui/ListState.vue'
import api from '../services/api'

export default {
  components: { PageHero, BaseButton, BaseTable, ListState },
  data() {
    return {
      loading: false,
      header: {},
      items: [],
      columns: [
        { key: 'produto', label: 'Produto' },
        { key: 'quantidade', label: 'Quantidade' },
        { key: 'valor_unitario', label: 'Valor Unit.' },
        { key: 'status', label: 'Status' },
        { key: 'data_venda', label: 'Data da Venda' },
      ]
    }
  },
  mounted() {
    this.refresh()
  },
  methods: {
    async refresh() {
      this.loading = true
      try {
        const res = await api.get(`/api/v1/vendas/cabecalhos/${this.$route.params.id}`)
        this.header = res.data?.header || {}
        this.items = Array.isArray(res.data?.items) ? res.data.items : []
      } catch (e) {
        this.header = {}
        this.items = []
      } finally {
        this.loading = false
      }
    },
    formatDate(value) {
      if (!value) return '-'
      const [year, month, day] = String(value).slice(0, 10).split('-')
      return year && month && day ? `${day}/${month}/${year}` : value
    }
  }
}
</script>
