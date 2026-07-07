<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Vendas"
      subtitle="Acompanhe pedidos e operação de entrega com visão comercial em tempo real."
    >
      <template #actions>
        <input
          v-model="query"
          placeholder="Buscar por cliente ou produto"
          class="p-3 border border-gray-300 rounded-xl w-full sm:min-w-[260px] hero-control"
          @input="onQuery"
        >
        <select
          v-model="statusFilter"
          class="p-3 border border-gray-300 rounded-xl w-full sm:w-auto hero-control"
        >
          <option value="">
            Todos status
          </option>
          <option value="AGUARDANDO">
            AGUARDANDO
          </option>
          <option value="ENTREGUE">
            ENTREGUE
          </option>
        </select>
        <BaseButton
          class="btn-secondary w-full sm:w-auto"
          @click="refresh"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          class="btn-primary w-full sm:w-auto whitespace-nowrap"
          @click="openCreateModal"
        >
          Adicionar Venda
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Vendas
        </div>
        <div class="saas-kpi-value">
          {{ filteredVendas.length }}
        </div>
        <div class="saas-kpi-help">
          Pedidos rastreados
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Busca Atual
        </div>
        <div class="saas-kpi-value">
          {{ query ? 'Filtrada' : 'Geral' }}
        </div>
        <div class="saas-kpi-help">
          Contexto operacional
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Página
        </div>
        <div class="saas-kpi-value">
          {{ currentPage }}
        </div>
        <div class="saas-kpi-help">
          Navegação ativa
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Filtro de Status
        </div>
        <div class="saas-kpi-value">
          {{ statusFilter || 'TODOS' }}
        </div>
        <div class="saas-kpi-help">
          Visão operacional
        </div>
      </article>
    </section>

    <!-- Table or empty state -->
    <div>
      <div
        v-if="filteredVendas.length > 0"
        class="panel-inner content-card"
      >
        <BaseTable
          :columns="tableCols"
          :rows="paginatedVendas"
          :row-clickable="true"
          @row-click="openItemsFromRow"
        >
          <template #numero_pedido="{ row }">
            #{{ row.id }}
          </template>
          <template #cliente="{ row }">
            {{ row.cliente }}
          </template>
          <template #itens_count="{ row }">
            {{ row.itens_count ?? 0 }}
          </template>
          <template #valor_total="{ row }">
            {{ row.valor_total !== undefined && row.valor_total !== null ? ('R$ ' + Number(row.valor_total).toFixed(2)) : '-' }}
          </template>
          <template #status="{ row }">
            {{ normalizeVendaStatus(row.status) }}
          </template>
          <template #data_envio_prevista="{ row }">
            {{ formatDate(row.data_envio_prevista) }}
          </template>
          <template #data_entrega_prevista="{ row }">
            {{ formatDate(row.data_entrega_prevista) }}
          </template>
          <template #acoes="{ row }">
            <div @click.stop>
              <ActionDropdown
                :items="getRowActions(row)"
                :menu-height="220"
                @select="handleRowAction($event, row)"
              />
            </div>
          </template>
        </BaseTable>
      </div>

      <ListState
        v-if="loading"
        :loading="loading"
        :has-data="filteredVendas.length > 0"
        loading-text="Carregando vendas..."
        empty-title="Nenhuma venda encontrada."
        empty-message="Você ainda não registrou vendas. Clique abaixo para adicionar a primeira venda."
        action-label="Adicionar Venda"
        @action="openCreateModal"
      />

      <div
        v-if="!loading && filteredVendas.length === 0"
        class="panel-inner content-card mt-4"
      >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-3">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                class="h-4 w-4"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"
                />
              </svg>
            </span>
            <div>
              <p class="text-sm font-semibold text-slate-700">
                Sem vendas para exibir
              </p>
              <p class="text-sm text-slate-600">
                {{ hasActiveFilter ? 'Ajuste os filtros e tente novamente.' : 'Cadastre a primeira venda para iniciar o acompanhamento.' }}
              </p>
            </div>
          </div>
          <div class="flex w-full sm:w-auto gap-2">
            <BaseButton
              v-if="hasActiveFilter"
              variant="secondary"
              class="w-full sm:w-auto"
              @click="clearFilters"
            >
              Limpar filtros
            </BaseButton>
            <BaseButton
              variant="primary"
              class="w-full sm:w-auto"
              @click="openCreateModal"
            >
              Adicionar Venda
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
    <!-- Pagination controls -->
    <div
      v-if="filteredVendas.length > 0"
      class="mt-4"
    >
      <div class="panel-inner content-card flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-sm muted">
          Mostrando {{ filteredVendas.length > 0 ? ((currentPage-1)*pageSize + 1) : 0 }} - {{ Math.min(currentPage*pageSize, filteredVendas.length) }} de {{ filteredVendas.length }}
        </div>
        <div class="page-pagination">
          <BaseButton
            class="btn-secondary"
            :disabled="currentPage<=1"
            @click="prevPage"
          >
            Anterior
          </BaseButton>
          <template
            v-for="p in Math.min(5, totalPages)"
            :key="p"
          >
            <button
              type="button"
              class="page-number"
              :class="{ 'is-active': currentPage===p }"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
          </template>
          <BaseButton
            class="btn-secondary"
            :disabled="currentPage>=totalPages"
            @click="nextPage"
          >
            Próximo
          </BaseButton>
        </div>
      </div>
    </div>

    <div
      v-if="confirming"
      class="panel-inner content-card mb-4 border border-emerald-200 bg-emerald-50/60"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="text-sm text-slate-700">
          Confirmar entrega do pedido selecionado?
        </div>
        <div class="flex gap-2">
          <BaseButton
            variant="secondary"
            @click="cancelConfirm"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="confirmUpdate"
          >
            Confirmar entrega
          </BaseButton>
        </div>
      </div>
    </div>

    <SideDrawer
      :open="showCreateModal"
      title="Adicionar Venda"
      @close="closeCreateModal"
    >
      <form
        class="drawer-form grid grid-cols-1 gap-3"
        @submit.prevent="createSale"
      >
        <FormFeedback
          :message="saleFeedback.message"
          :type="saleFeedback.type"
        />
        <label class="text-sm text-gray-600">Cliente</label>
        <select
          v-model="novaVenda.cliente_id"
          required
          class="p-3 border border-gray-300 rounded-xl estilo-select"
        >
          <option
            value=""
            disabled
          >
            Selecione um cliente
          </option>
          <option
            v-for="c in clients"
            :key="c.id"
            :value="c.id"
          >
            {{ c.nome }}
          </option>
        </select>

        <div class="space-y-2">
          <div
            v-for="(item, idx) in novaVenda.items"
            :key="idx"
            class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end"
          >
            <div class="md:col-span-6">
              <label class="text-sm text-gray-600">Produto</label>
              <CustomSelect
                v-model="item.produto_id"
                :options="productOptions"
                :placeholder="'Selecione um produto'"
                class="w-full"
                @update:model-value="onProductChange(idx)"
              />
            </div>
            <div class="md:col-span-2">
              <label class="text-sm text-gray-600">Qtd</label>
              <input
                v-model.number="item.quantidade"
                type="number"
                placeholder="Qtd"
                class="p-3 border border-gray-300 rounded-xl w-full"
                min="1"
                required
              >
            </div>
            <div class="md:col-span-3">
              <label class="text-sm text-gray-600">Valor Unit.</label>
              <input
                v-model.number="item.valor_unitario"
                type="number"
                placeholder="Valor Unitário"
                class="p-3 border border-gray-300 rounded-xl w-full"
                step="0.01"
                required
              >
            </div>
            <div class="md:col-span-1">
              <BaseButton
                type="button"
                class="btn-secondary w-full"
                @click="removeItem(idx)"
              >
                -
              </BaseButton>
            </div>
          </div>
          <div>
            <BaseButton
              type="button"
              class="btn-secondary"
              @click="addItem"
            >
              + Adicionar produto
            </BaseButton>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-gray-600">Data de Envio</label>
            <input
              v-model="novaVenda.data_envio_prevista"
              type="date"
              class="p-3 border border-gray-300 rounded-xl w-full"
            >
          </div>
          <div>
            <label class="text-sm text-gray-600">Data de Entrega</label>
            <input
              v-model="novaVenda.data_entrega_prevista"
              type="date"
              class="p-3 border border-gray-300 rounded-xl w-full"
            >
          </div>
        </div>
        <div class="drawer-actions flex justify-end gap-3 mt-4">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeCreateModal"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary whitespace-nowrap"
            :disabled="submittingSale"
            :loading="submittingSale"
          >
            {{ submittingSale ? 'Salvando...' : 'Adicionar Venda' }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>
  </div>
</template>

<script>

import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'
import CustomSelect from '../components/ui/CustomSelect.vue'
import ActionDropdown from '../components/ui/ActionDropdown.vue'
import { useToast } from '../composables/useToast'

export default {
  components: { BaseTable, BaseButton, SideDrawer, PageHero, ListState, FormFeedback, CustomSelect, ActionDropdown },
  data() {
    return {
      vendas: [],
      loading: false,
      query: '',
      statusFilter: '',
      novaVenda: { cliente_id: null, data_envio_prevista: '', data_entrega_prevista: '', items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] },
      tableCols: [
        { key: 'numero_pedido', label: 'Pedido' },
        { key: 'cliente', label: 'Cliente' },
        { key: 'itens_count', label: 'Itens' },
        { key: 'valor_total', label: 'Valor Total' },
        { key: 'status', label: 'Status' },
        { key: 'data_envio_prevista', label: 'Envio Previsto' },
        { key: 'data_entrega_prevista', label: 'Entrega Prevista' },
        { key: 'acoes', label: 'Ações' },
      ],
      totalCount: 0,
      clients: [],
      products: [],
      showCreateModal: false,
      submittingSale: false,
      saleFeedback: { message: '', type: 'info' },
      confirmPayload: null,
      confirming: false,
      pageSize: 25,
      currentPage: 1,
      timer: null,
    }
  },
  computed: {
    productOptions() {
      return [
        { value: '', label: 'Selecione um produto' },
        ...this.products.map(p => ({ value: p.id, label: p.nome }))
      ]
    },
    visibleVendas() {
      return (this.vendas || []).filter(v => {
        if (!v) return false
        const hasClient = v.cliente && String(v.cliente).trim().length > 0
        const hasStatus = v.status && String(v.status).trim().length > 0
        const hasItens = v.itens_count !== undefined && v.itens_count !== null
        const hasValor = v.valor_total !== undefined && v.valor_total !== null
        return hasClient || hasStatus || hasItens || hasValor
      })
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.filteredVendas.length / this.pageSize))
    },
    paginatedVendas() {
      return this.filteredVendas
    },
    filteredVendas() {
      if (!this.statusFilter) return this.visibleVendas
      return this.visibleVendas.filter(v => this.normalizeVendaStatus(v.status) === this.statusFilter)
    },
    hasActiveFilter() {
      return String(this.statusFilter || '').trim() !== '' || String(this.query || '').trim() !== ''
    },
  },
  mounted() {
    this.loadClients();
    this.loadProducts();
    this.loadVendas();
  },
  methods: {
    getRowActions(row) {
      return [
        { key: 'itens', label: 'Itens' },
        { key: 'historico', label: 'Histórico status' },
        { key: 'imprimir', label: 'Imprimir' },
        { key: 'confirmar', label: 'Confirmar entrega', hidden: this.normalizeVendaStatus(row.status) === 'ENTREGUE' },
        { key: 'excluir', label: 'Excluir', danger: true },
      ]
    },
    normalizeVendaStatus(value) {
      const status = String(value || '').trim().toUpperCase()
      if (status === 'ENTREGUE') return 'ENTREGUE'
      return 'AGUARDANDO'
    },
    handleRowAction(action, row) {
      if (action === 'itens') this.openItems(row.id)
      if (action === 'historico') this.openHistory(row.id)
      if (action === 'imprimir') this.printOrder(row)
      if (action === 'confirmar') this.openConfirm(row.id)
      if (action === 'excluir') this.deleteSale(row)
    },
    openItemsFromRow(row) {
      if (!row?.id) return
      this.openItems(row.id)
    },
    async loadVendas() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/vendas', { params: { q: this.query, page: this.currentPage, per_page: this.pageSize } })
        this.vendas = res.data.items || []
        this.totalCount = res.data.total || 0
      } catch (e) {
        console.error('Erro ao carregar vendas:', e)
        this.vendas = []
        this.totalCount = 0
      } finally {
        this.loading = false
      }
    },
    async loadClients() {
      try {
        const res = await api.get('/api/v1/clientes')
        this.clients = res.data || []
      } catch (e) {
        this.clients = []
      }
    },
    async loadProducts() {
      try {
        const res = await api.get('/api/v1/produtos', { params: { per_page: 100 } })
        this.products = res.data.items || []
      } catch (e) {
        this.products = []
      }
    },
    goToPage(n) { this.currentPage = Math.min(Math.max(1, n), this.totalPages); this.loadVendas() },
    nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.loadVendas() } },
    prevPage() { if (this.currentPage > 1) { this.currentPage--; this.loadVendas() } },
    openConfirm(id) { this.confirmPayload = id; this.confirming = true },
    cancelConfirm() { this.confirming = false; this.confirmPayload = null },
    async confirmUpdate() {
      const id = this.confirmPayload
      this.confirming = false
      try {
        await api.post('/api/v1/vendas/deliver', { venda_cabecalho_id: id })
        useToast().notify('Pedido marcado como entregue', { type: 'success' })
        this.loadVendas()
      } catch (e) {
        console.error('Erro ao atualizar status:', e)
        useToast().notify('Falha ao atualizar status', { type: 'error' })
      }
    },
    async deleteSale(row) {
      const ok = window.confirm(`Deseja realmente excluir o pedido #${row.id}?`)
      if (!ok) return

      try {
        await api.delete(`/api/v1/vendas/cabecalhos/${row.id}`)
        useToast().notify('Pedido excluído com sucesso', { type: 'success' })
        this.loadVendas()
      } catch (e) {
        useToast().notify(e?.response?.data?.error || 'Não foi possível excluir o pedido', { type: 'error' })
      }
    },
    openItems(id) {
      this.$router.push(`/vendas/cabecalho/${id}`)
    },
    openHistory(id) {
      this.$router.push(`/vendas/cabecalho/${id}#historico-status`)
    },
    async createSale() {
      this.submittingSale = true
      this.saleFeedback = { message: 'Salvando venda...', type: 'info' }
      try {
        if (!this.novaVenda.cliente_id) throw new Error('Cliente obrigatório')
        const items = (this.novaVenda.items || []).filter(it => it && it.produto_id)
        if (items.length === 0) throw new Error('Adicione pelo menos um produto')
        const payload = {
          cliente_id: this.novaVenda.cliente_id,
          data_envio_prevista: this.novaVenda.data_envio_prevista || null,
          data_entrega_prevista: this.novaVenda.data_entrega_prevista || null,
          items: items.map(it => ({ produto_id: it.produto_id, quantidade: it.quantidade || 1, valor_unitario: it.valor_unitario || 0 }))
        }
        await api.post('/api/v1/vendas', payload)
        this.saleFeedback = { message: 'Venda criada com sucesso.', type: 'success' }
        this.novaVenda = { cliente_id: null, data_envio_prevista: '', data_entrega_prevista: '', items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] }
        setTimeout(() => { this.showCreateModal = false }, 350)
        this.loadVendas()
      } catch (e) {
        console.error('Erro ao criar venda:', e)
        this.saleFeedback = { message: e?.response?.data?.error || e?.message || 'Falha ao salvar venda.', type: 'error' }
      } finally {
        this.submittingSale = false
      }
    },
    onProductChange(idx) {
      const pid = this.novaVenda.items[idx].produto_id
      const p = this.products.find(x => x.id === pid)
      if (p) {
        this.novaVenda.items[idx].valor_unitario = p.custo_medio || p.custo || null
      } else {
        this.novaVenda.items[idx].valor_unitario = null
      }
    },
    openCreateModal() { this.showCreateModal = true; this.saleFeedback = { message: '', type: 'info' } },
    clearFilters() { this.query = ''; this.statusFilter = ''; this.currentPage = 1; this.loadVendas() },
    closeCreateModal() { this.showCreateModal = false; this.submittingSale = false; this.saleFeedback = { message: '', type: 'info' }; this.novaVenda = { cliente_id: null, data_envio_prevista: '', data_entrega_prevista: '', items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] } },
    addItem() { this.novaVenda.items.push({ produto_id: null, quantidade: 1, valor_unitario: null }) },
    removeItem(idx) { if (this.novaVenda.items.length > 1) this.novaVenda.items.splice(idx, 1) },
    refresh() { this.loadVendas() },
    onQuery() { clearTimeout(this.timer); this.timer = setTimeout(() => this.loadVendas(), 350) },
    formatDate(value) {
      if (!value) return '-'
      const [year, month, day] = String(value).slice(0, 10).split('-')
      return year && month && day ? `${day}/${month}/${year}` : value
    },
    printOrder(row) {
      api.get(`/api/v1/vendas/cabecalhos/${row.id}/pdf`, {
        responseType: 'blob',
      }).then((response) => {
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const printWindow = window.open(url, '_blank')
        if (!printWindow) {
          window.URL.revokeObjectURL(url)
          return
        }
        setTimeout(() => window.URL.revokeObjectURL(url), 8000)
      }).catch((err) => {
        alert(err?.response?.data?.error || 'Não foi possível gerar o PDF de venda.')
      })
    },
  },
}
</script>
