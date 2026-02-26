<template>
  <div class="page-fade">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Vendas</h2>
        <p class="mt-2 text-sm text-gray-500">Gerencie suas vendas e pedidos</p>
      </div>

      <div class="flex items-center gap-3">
        <input v-model="query" @input="onQuery" placeholder="Buscar por cliente ou produto" class="p-3 border border-gray-300 rounded-xl w-full max-w-md" />
        <BaseButton class="btn-secondary" @click="refresh">Atualizar</BaseButton>
        <BaseButton class="btn-primary ml-2 whitespace-nowrap" @click="openCreateModal">Adicionar Venda</BaseButton>
      </div>
    </div>

    <!-- Table or empty state -->
    <div>
      <div v-if="loading || visibleVendas.length > 0" class="panel-inner">
        <BaseTable :columns="tableCols" :rows="paginatedVendas">
        <template #cliente="{ row }">{{ row.cliente }}</template>
        <template #produto="{ row }">{{ row.produto }}</template>
        <template #quantidade="{ row }">{{ row.quantidade }}</template>
        <template #valor_unitario="{ row }">{{ row.valor_unitario !== undefined && row.valor_unitario !== null ? ('R$ ' + row.valor_unitario) : '-' }}</template>
        <template #status="{ row }">{{ row.status || '-' }}</template>
        <template #acoes="{ row }">
          <div>
            <BaseButton v-if="!(row.status && String(row.status).toLowerCase() === 'entregue')" variant="primary" @click="openConfirm(row.id)">Entregar</BaseButton>
            <span v-else class="text-sm muted">Entregue</span>
          </div>
        </template>
        </BaseTable>
      </div>

      <div v-else class="py-12 text-center">
        <p class="text-lg font-medium mb-4 text-gray-800">Nenhuma venda encontrada.</p>
        <p class="text-sm muted mb-6">Você ainda não registrou vendas. Clique abaixo para adicionar a primeira venda.</p>
        <div class="flex justify-center">
          <BaseButton class="btn-primary whitespace-nowrap" @click="openCreateModal">Adicionar Venda</BaseButton>
        </div>
      </div>
    </div>
    <!-- Pagination controls -->
    <div v-if="visibleVendas.length > 0" class="mt-4">
      <div class="panel-inner flex items-center justify-between">
          <div class="text-sm muted">Mostrando {{ (currentPage-1)*pageSize + 1 }} - {{ Math.min(currentPage*pageSize, totalCount) }} de {{ totalCount }}</div>
        <div class="flex items-center gap-2">
          <BaseButton class="btn-secondary" :disabled="currentPage<=1" @click="prevPage">Anterior</BaseButton>
          <template v-for="p in Math.min(5, totalPages)" :key="p">
            <button class="px-3 py-1 rounded text-sm" :class="{ 'bg-gray-200': currentPage===p }" @click="goToPage(p)">{{ p }}</button>
          </template>
          <BaseButton class="btn-secondary" :disabled="currentPage>=totalPages" @click="nextPage">Próximo</BaseButton>
        </div>
      </div>
    </div>

    <ConfirmDialog :modelValue="confirming" title="Confirmar entrega" message="Deseja marcar este pedido como entregue?" @confirm="confirmUpdate" @cancel="cancelConfirm" />

    <!-- Create Sale Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40" @click="closeCreateModal" aria-hidden="true"></div>
      <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-3xl p-10 md:p-12">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Adicionar Venda</h3>
        <form @submit.prevent="createSale" class="grid grid-cols-1 gap-3">
          <label class="text-sm text-gray-600">Cliente</label>
          <select v-model="novaVenda.cliente_id" required class="p-3 border border-gray-300 rounded-xl estilo-select">
            <option value="" disabled>Selecione um cliente</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nome }}</option>
          </select>

          <div class="space-y-2">
            <div v-for="(item, idx) in novaVenda.items" :key="idx" class="grid grid-cols-12 gap-2 items-end">
              <div class="col-span-6">
                <label class="text-sm text-gray-600">Produto</label>
                <CustomSelect
                  v-model="item.produto_id"
                  :options="productOptions"
                  :placeholder="'Selecione um produto'"
                  @update:modelValue="onProductChange(idx)"
                  class="w-full"
                />
              </div>
              <div class="col-span-2">
                <label class="text-sm text-gray-600">Qtd</label>
                <input v-model.number="item.quantidade" type="number" placeholder="Qtd" class="p-3 border border-gray-300 rounded-xl w-full" min="1" required />
              </div>
              <div class="col-span-3">
                <label class="text-sm text-gray-600">Valor Unit.</label>
                <input v-model.number="item.valor_unitario" type="number" placeholder="Valor Unitário" class="p-3 border border-gray-300 rounded-xl w-full" step="0.01" required />
              </div>
              <div class="col-span-1">
                <button type="button" class="btn-secondary w-full" @click="removeItem(idx)">-</button>
              </div>
            </div>
            <div>
              <button type="button" class="btn-secondary" @click="addItem">+ Adicionar produto</button>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-4">
            <BaseButton type="button" class="btn-secondary" @click="closeCreateModal">Cancelar</BaseButton>
            <BaseButton type="submit" class="btn-primary whitespace-nowrap">Adicionar Venda</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>

import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import CustomSelect from '../components/ui/CustomSelect.vue'
import { useToast } from '../composables/useToast'

export default {
  components: { BaseTable, BaseButton, ConfirmDialog, CustomSelect },
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
      try {
        if (!this.novaVenda.cliente_id) throw new Error('Cliente obrigatório')
        const items = (this.novaVenda.items || []).filter(it => it && it.produto_id)
        if (items.length === 0) throw new Error('Adicione pelo menos um produto')
        const payload = {
          cliente_id: this.novaVenda.cliente_id,
          items: items.map(it => ({ produto_id: it.produto_id, quantidade: it.quantidade || 1, valor_unitario: it.valor_unitario || 0 }))
        }
        await api.post('/api/v1/vendas', payload)
        this.novaVenda = { cliente_id: null, items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] }
        this.showCreateModal = false
        this.loadVendas()
      } catch (e) {
        console.error('Erro ao criar venda:', e)
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
    openCreateModal() { this.showCreateModal = true },
    closeCreateModal() { this.showCreateModal = false; this.novaVenda = { cliente_id: null, items: [{ produto_id: null, quantidade: 1, valor_unitario: null }] } },
    addItem() { this.novaVenda.items.push({ produto_id: null, quantidade: 1, valor_unitario: null }) },
    removeItem(idx) { if (this.novaVenda.items.length > 1) this.novaVenda.items.splice(idx, 1) },
    refresh() { this.loadVendas() },
    onQuery() { clearTimeout(this.timer); this.timer = setTimeout(() => this.loadVendas(), 350) },
  },
  mounted() {
    this.loadClients();
    this.loadProducts();
    this.loadVendas();
  },
}
</script>
