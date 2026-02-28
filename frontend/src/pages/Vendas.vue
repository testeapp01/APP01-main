<template>
  <div class="page-shell page-fade">
    <PageHero
      title="Vendas"
      subtitle="Acompanhe pedidos e operação de entrega com visão comercial em tempo real."
    >
      <template #actions>
        <input
          v-model="query"
          placeholder="Buscar por cliente ou produto"
          class="p-3 border border-gray-300 rounded-xl w-full sm:min-w-[260px]"
          @input="onQuery"
        >
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
          {{ totalCount }}
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
    </section>

    <!-- Table or empty state -->
    <div>
      <div
        v-if="visibleVendas.length > 0"
        class="panel-inner"
      >
        <BaseTable
          :columns="tableCols"
          :rows="paginatedVendas"
        >
          <template #cliente="{ row }">
            {{ row.cliente }}
          </template>
          <template #produto="{ row }">
            {{ row.produto }}
          </template>
          <template #quantidade="{ row }">
            {{ row.quantidade }}
          </template>
          <template #valor_unitario="{ row }">
            {{ row.valor_unitario !== undefined && row.valor_unitario !== null ? ('R$ ' + row.valor_unitario) : '-' }}
          </template>
          <template #status="{ row }">
            {{ row.status || '-' }}
          </template>
          <template #acoes="{ row }">
            <div>
              <BaseButton
                v-if="!(row.status && String(row.status).toLowerCase() === 'entregue')"
                variant="primary"
                @click="openConfirm(row.id)"
              >
                Entregar
              </BaseButton>
              <span
                v-else
                class="text-sm muted"
              >Entregue</span>
            </div>
          </template>
        </BaseTable>
      </div>

      <ListState
        :loading="loading"
        :has-data="visibleVendas.length > 0"
        loading-text="Carregando vendas..."
        empty-title="Nenhuma venda encontrada."
        empty-message="Você ainda não registrou vendas. Clique abaixo para adicionar a primeira venda."
        action-label="Adicionar Venda"
        @action="openCreateModal"
      />
    </div>
    <!-- Pagination controls -->
    <div
      v-if="visibleVendas.length > 0"
      class="mt-4"
    >
      <div class="panel-inner flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-sm muted">
          Mostrando {{ (currentPage-1)*pageSize + 1 }} - {{ Math.min(currentPage*pageSize, totalCount) }} de {{ totalCount }}
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
      class="panel-inner mb-4 border border-emerald-200 bg-emerald-50/60"
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
import { useToast } from '../composables/useToast'

export default {
  components: { BaseTable, BaseButton, SideDrawer, PageHero, ListState, FormFeedback, CustomSelect },
  data() {
    return {
      vendas: [],
      loading: false,
      query: '',
      novaVenda: { cliente_id: null, items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] },
      tableCols: [
        { key: 'cliente', label: 'Cliente' },
        { key: 'produto', label: 'Produto' },
        { key: 'quantidade', label: 'Quantidade' },
        { key: 'valor_unitario', label: 'Valor Unit.' },
        { key: 'status', label: 'Status' },
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
        const hasProduct = v.produto && String(v.produto).trim().length > 0
        const hasStatus = v.status && String(v.status).trim().length > 0
        const hasQuantidade = v.quantidade !== undefined && v.quantidade !== null
        const hasValor = v.valor_unitario !== undefined && v.valor_unitario !== null
        return hasClient || hasProduct || hasStatus || hasQuantidade || hasValor
      })
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.totalCount / this.pageSize))
    },
    paginatedVendas() {
      return this.visibleVendas
    },
  },
  mounted() {
    this.loadClients();
    this.loadProducts();
    this.loadVendas();
  },
  methods: {
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
        await api.patch(`/api/v1/vendas/${id}`, { status: 'entregue' })
        useToast().notify('Pedido marcado como entregue', { type: 'success' })
        this.loadVendas()
      } catch (e) {
        console.error('Erro ao atualizar status:', e)
        useToast().notify('Falha ao atualizar status', { type: 'error' })
      }
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
          items: items.map(it => ({ produto_id: it.produto_id, quantidade: it.quantidade || 1, valor_unitario: it.valor_unitario || 0 }))
        }
        await api.post('/api/v1/vendas', payload)
        this.saleFeedback = { message: 'Venda criada com sucesso.', type: 'success' }
        this.novaVenda = { cliente_id: null, items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] }
        setTimeout(() => { this.showCreateModal = false }, 350)
        this.loadVendas()
      } catch (e) {
        console.error('Erro ao criar venda:', e)
        this.saleFeedback = { message: e?.message || 'Falha ao salvar venda.', type: 'error' }
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
    closeCreateModal() { this.showCreateModal = false; this.submittingSale = false; this.saleFeedback = { message: '', type: 'info' }; this.novaVenda = { cliente_id: null, items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] } },
    addItem() { this.novaVenda.items.push({ produto_id: null, quantidade: 1, valor_unitario: null }) },
    removeItem(idx) { if (this.novaVenda.items.length > 1) this.novaVenda.items.splice(idx, 1) },
    refresh() { this.loadVendas() },
    onQuery() { clearTimeout(this.timer); this.timer = setTimeout(() => this.loadVendas(), 350) },
  },
}
</script>
