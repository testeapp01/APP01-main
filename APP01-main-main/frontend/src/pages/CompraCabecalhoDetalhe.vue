<template>
  <div class="page-shell page-fade">
    <PageHero
      :title="`Compra #${header.id || '-'}`"
      subtitle="Detalhamento dos itens da compra."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary"
          @click="$router.push('/compras')"
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
        <div class="saas-kpi-label">
          Fornecedor
        </div>
        <div class="saas-kpi-text">
          {{ header.fornecedor || '-' }}
        </div>
        <div class="saas-kpi-help">
          Parceiro principal
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Tipo
        </div>
        <div class="saas-kpi-text">
          {{ formatTipoOperacao(header.tipo_operacao) }}
        </div>
        <div class="saas-kpi-help">
          Classificação operacional
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Valor Total
        </div>
        <div class="saas-kpi-value">
          {{ formatCurrency(header.valor_total) }}
        </div>
        <div class="saas-kpi-help">
          Soma dos itens
        </div>
      </article>
    </section>

    <div
      v-if="items.length"
      class="panel-inner"
    >
      <BaseTable
        :columns="columns"
        :rows="items"
      >
        <template #produto="{ row }">
          {{ row.produto || '-' }}
        </template>
        <template #quantidade="{ row }">
          {{ formatQuantidade(row.quantidade) }}
        </template>
        <template #valor_unitario="{ row }">
          {{ formatCurrency(row.valor_unitario) }}
        </template>
        <template #status="{ row }">
          {{ normalizeCompraStatus(row.status) }}
        </template>
        <template #data_compra="{ row }">
          {{ formatDate(row.data_compra) }}
        </template>
      </BaseTable>
    </div>

    <ListState
      :loading="loading"
      :has-data="items.length > 0"
      loading-text="Carregando itens da compra..."
      empty-title="Compra sem itens"
      empty-message="Nenhum item foi encontrado para esta compra."
    />

    <section
      id="historico-status"
      class="panel-inner mt-4"
    >
      <h3 class="text-base font-semibold text-slate-900 mb-3">
        Histórico de status
      </h3>

      <div v-if="historico.length > 0">
        <BaseTable
          :columns="historyColumns"
          :rows="historico"
        >
          <template #status="{ row }">
            {{ normalizeCompraStatus(row.status) }}
          </template>
          <template #usuario_id="{ row }">
            {{ formatHistoryUser(row) }}
          </template>
          <template #confirmado_em="{ row }">
            {{ formatDateTime(row.confirmado_em) }}
          </template>
        </BaseTable>
      </div>

      <p
        v-else
        class="text-sm text-slate-500"
      >
        Nenhum histórico de status registrado.
      </p>
    </section>
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
      historico: [],
      columns: [
        { key: 'produto', label: 'Produto' },
        { key: 'quantidade', label: 'Quantidade' },
        { key: 'valor_unitario', label: 'Valor Unit.' },
        { key: 'status', label: 'Status' },
        { key: 'data_compra', label: 'Data da Compra' },
      ],
      historyColumns: [
        { key: 'status', label: 'Status' },
        { key: 'usuario_id', label: 'Usuário' },
        { key: 'confirmado_em', label: 'Confirmado em' },
      ],
    }
  },
  mounted() {
    this.refresh()
  },
  methods: {
    async refresh() {
      this.loading = true
      try {
        const res = await api.get(`/api/v1/compras/cabecalhos/${this.$route.params.id}`)
        this.header = res.data?.header || {}
        this.items = Array.isArray(res.data?.items) ? res.data.items : []
        this.historico = Array.isArray(res.data?.historico_statuscompra) ? res.data.historico_statuscompra : []
      } catch (e) {
        this.header = {}
        this.items = []
        this.historico = []
      } finally {
        this.loading = false
      }
    },
    formatDate(value) {
      if (!value) return '-'
      const [year, month, day] = String(value).slice(0, 10).split('-')
      return year && month && day ? `${day}/${month}/${year}` : value
    },
    formatDateTime(value) {
      if (!value) return '-'
      const date = new Date(value)
      return Number.isNaN(date.getTime()) ? value : date.toLocaleString('pt-BR')
    },
    normalizeCompraStatus(value) {
      const status = String(value || '').trim().toUpperCase()
      if (status === 'RECEBIDA') return 'RECEBIDA'
      return 'AGUARDANDO'
    },
    formatCurrency(value) {
      const number = Number(value || 0)
      return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(number)
    },
    formatQuantidade(value) {
      const number = Number(value)
      if (Number.isNaN(number)) return '-'
      return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(number)
    },
    formatTipoOperacao(value) {
      const tipo = String(value || '').trim().toLowerCase()
      if (!tipo) return '-'
      return tipo.charAt(0).toUpperCase() + tipo.slice(1)
    },
    formatHistoryUser(row) {
      const id = row?.usuario_id
      const nome = String(row?.usuario_nome || '').trim()
      if (nome && id) return `${nome} (#${id})`
      if (nome) return nome
      if (id) return `Usuário removido (#${id})`
      return '-'
    }
  }
}
</script>
