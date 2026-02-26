<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Compras</h2>
        <p class="mt-2 text-sm text-gray-500">Gerencie suas compras.</p>
      </div>
      <div class="flex items-center gap-3">
        <BaseButton class="btn-secondary" @click="loadCompras">Atualizar</BaseButton>
        <BaseButton class="btn-primary" @click="openCreateModal">Adicionar Compra</BaseButton>
      </div>
    </div>

    <div v-if="loading || visibleCompras.length > 0" class="panel-inner">
      <BaseTable :columns="tableCols" :rows="paginatedCompras">
      <template #fornecedor="{ row }">{{ row.fornecedor || '-' }}</template>
      <template #produto="{ row }">{{ row.produto || '-' }}</template>
      <template #quantidade="{ row }">{{ row.quantidade !== undefined && row.quantidade !== null ? row.quantidade : '-' }}</template>
      <template #valor_unitario="{ row }">{{ row.valor_unitario !== undefined && row.valor_unitario !== null ? ('R$ ' + row.valor_unitario) : '-' }}</template>
      <template #status="{ row }">{{ row.status || '-' }}</template>
      </BaseTable>
    </div>
    <div v-if="visibleCompras.length === 0 && !loading" class="py-12 text-center">
      <p class="text-lg font-medium mb-4 text-gray-800">Nenhuma compra encontrada.</p>
      <p class="text-sm muted mb-6">Adicione compras para começar a registrar entradas.</p>
      <div class="flex justify-center">
        <BaseButton class="btn-primary" @click="openCreateModal">Adicionar Compra</BaseButton>
      </div>
    </div>

    <div v-if="visibleCompras.length > 0" class="mt-4">
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

    <!-- Create purchase modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40" @click="closeCreateModal" aria-hidden="true"></div>
      <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-3xl p-10">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Adicionar Compra</h3>
        <form @submit.prevent="createPurchase" class="space-y-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Compra</label>
            <div class="flex gap-3">
              <button type="button" @click="novaCompra.tipo = 'revenda'" :class="['px-4 py-2 rounded-lg border', novaCompra.tipo === 'revenda' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300']">Revenda</button>
              <button type="button" @click="novaCompra.tipo = 'cliente'" :class="['px-4 py-2 rounded-lg border', novaCompra.tipo === 'cliente' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300']">Cliente</button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Fornecedor</label>
            <select v-model.number="novaCompra.fornecedor_id" class="p-3 border border-gray-300 rounded-xl estilo-select w-full" required>
              <option :value="null" disabled>Escolha um fornecedor</option>
              <option v-for="f in fornecedores" :key="f.id" :value="f.id">{{ f.razao_social }}</option>
            </select>
          </div>

          <div v-if="novaCompra.tipo === 'cliente'">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente</label>
            <select v-model.number="novaCompra.cliente_id" class="p-3 border border-gray-300 rounded-xl estilo-select w-full" required>
              <option :value="null" disabled>Escolha um cliente</option>
              <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nome }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Produto</label>
            <select v-model.number="novaCompra.produto_id" @change="onProdutoChange" class="p-3 border border-gray-300 rounded-xl estilo-select w-full" required>
              <option :value="null" disabled>Escolha um produto</option>
              <option v-for="p in produtos" :key="p.id" :value="p.id">{{ p.nome }}</option>
            </select>
          </div>

          <div v-if="novaCompra.tipo === 'cliente'">
            <label class="text-sm text-gray-600 mb-1 block">Motorista</label>
            <select v-model.number="novaCompra.motorista_id" class="p-3 border border-gray-300 rounded-xl estilo-select" required>
              <option :value="null" disabled>Escolha um motorista</option>
              <option v-for="m in motoristas" :key="m.id" :value="m.id">{{ m.nome }}</option>
            </select>
          </div>

          <!-- Quantidade e Valor Unitário lado a lado -->
          <div class="flex flex-row gap-6 items-start w-full mb-4">
            <div class="flex-1">
              <label class="block text-sm font-semibold text-gray-700 mb-1">Quantidade</label>
              <input v-model.number="novaCompra.quantidade" type="number" placeholder="Quantidade" class="p-3 border border-gray-300 rounded-xl w-full" min="1" required />
            </div>
            <div class="flex-1">
              <label class="block text-sm font-semibold text-gray-700 mb-1">Valor Unitário (R$)</label>
              <input v-model.number="novaCompra.valor_unitario" type="number" placeholder="Valor Unitário" class="p-3 border border-gray-300 rounded-xl w-full" step="0.01" required />
            </div>
          </div>
          <!-- Comissões centralizadas abaixo -->
          <div v-if="novaCompra.tipo === 'cliente'" class="flex flex-row justify-center gap-6 w-full mb-2">
            <div class="bg-gray-50 rounded-2xl p-4 border-2 border-blue-300 shadow-sm flex flex-col gap-2 min-w-[260px] max-w-[320px]">
              <div class="font-semibold text-gray-700 flex items-center gap-2 text-base mb-1">
                <span class="flex w-7 h-7 bg-blue-100 text-blue-600 rounded-full items-center justify-center mr-2 text-xl">💰</span>
                Comissão Intermediação
              </div>
              <div class="flex items-center gap-3 mb-1">
                <label class="text-xs text-gray-600">Tipo:</label>
                <button type="button" @click="novaCompra.comissao_intermediador_em_dinheiro = !novaCompra.comissao_intermediador_em_dinheiro" :class="['relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none', novaCompra.comissao_intermediador_em_dinheiro ? 'bg-green-500' : 'bg-gray-300']">
                  <span :class="['absolute left-0 top-0 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200', novaCompra.comissao_intermediador_em_dinheiro ? 'translate-x-6' : '']"></span>
                  <span class="absolute left-1 top-1 text-xs font-bold" v-if="!novaCompra.comissao_intermediador_em_dinheiro">%</span>
                  <span class="absolute right-1 top-1 text-xs font-bold" v-if="novaCompra.comissao_intermediador_em_dinheiro">R$</span>
                </button>
                <span class="text-xs text-gray-700 font-semibold">{{ novaCompra.comissao_intermediador_em_dinheiro ? 'R$' : '%' }}</span>
              </div>
              <input v-model.number="novaCompra.comissao_intermediador" type="number" min="0" :max="novaCompra.comissao_intermediador_em_dinheiro ? null : 100" :step="novaCompra.comissao_intermediador_em_dinheiro ? '0.01' : '0.01'" placeholder="Comissão" class="p-2 border border-blue-300 rounded-xl w-full text-base" />
              <div class="text-xs text-gray-600 mt-1">
                <span v-if="novaCompra.comissao_intermediador_em_dinheiro">
                  Ganho fixo: <span class="font-bold text-green-700">R$ {{ (novaCompra.comissao_intermediador || 0).toFixed(2) }}</span>
                </span>
                <span v-else>
                  Comissão: <span class="font-bold">{{ novaCompra.comissao_intermediador || 0 }}%</span> <br>
                  Valor: <span class="font-bold text-green-700">R$ {{ ((novaCompra.quantidade * novaCompra.valor_unitario) * (novaCompra.comissao_intermediador || 0) / 100).toFixed(2) }}</span>
                </span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Total para o cliente: <span class="font-bold">R$ {{ (novaCompra.quantidade * novaCompra.valor_unitario).toFixed(2) }}</span>
              </div>
            </div>
            <div class="bg-gray-50 rounded-2xl p-4 border-2 border-green-300 shadow-sm flex flex-col gap-2 min-w-[260px] max-w-[320px]">
              <div class="font-semibold text-gray-700 flex items-center gap-2 text-base mb-1">
                <span class="flex w-7 h-7 bg-green-100 text-green-600 rounded-full items-center justify-center mr-2 text-xl">🚚</span>
                Comissão Motorista
              </div>
              <div class="flex items-center gap-3 mb-1">
                <label class="text-xs text-gray-600">Tipo:</label>
                <button type="button" @click="novaCompra.comissao_motorista_em_dinheiro = !novaCompra.comissao_motorista_em_dinheiro" :class="['relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none', novaCompra.comissao_motorista_em_dinheiro ? 'bg-green-500' : 'bg-gray-300']">
                  <span :class="['absolute left-0 top-0 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200', novaCompra.comissao_motorista_em_dinheiro ? 'translate-x-6' : '']"></span>
                  <span class="absolute left-1 top-1 text-xs font-bold" v-if="!novaCompra.comissao_motorista_em_dinheiro">%</span>
                  <span class="absolute right-1 top-1 text-xs font-bold" v-if="novaCompra.comissao_motorista_em_dinheiro">R$</span>
                </button>
                <span class="text-xs text-gray-700 font-semibold">{{ novaCompra.comissao_motorista_em_dinheiro ? 'R$' : '%' }}</span>
              </div>
              <input v-model.number="novaCompra.comissao_motorista" type="number" min="0" :max="novaCompra.comissao_motorista_em_dinheiro ? null : 100" :step="novaCompra.comissao_motorista_em_dinheiro ? '0.01' : '0.01'" placeholder="Comissão" class="p-2 border border-green-300 rounded-xl w-full text-base" />
              <div class="text-xs text-gray-600 mt-1">
                <span v-if="novaCompra.comissao_motorista_em_dinheiro">
                  Ganho fixo: <span class="font-bold text-green-700">R$ {{ (novaCompra.comissao_motorista || 0).toFixed(2) }}</span>
                </span>
                <span v-else>
                  Comissão: <span class="font-bold">{{ novaCompra.comissao_motorista || 0 }}%</span> <br>
                  Valor: <span class="font-bold text-green-700">R$ {{ ((novaCompra.quantidade * novaCompra.valor_unitario) * (novaCompra.comissao_motorista || 0) / 100).toFixed(2) }}</span>
                </span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Total para o cliente: <span class="font-bold">R$ {{ (novaCompra.quantidade * novaCompra.valor_unitario).toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- Duplicates and unused fields removed. Only lateralized box remains. -->
          <div class="flex justify-end gap-3 mt-6">
            <BaseButton type="button" class="btn-secondary" @click="closeCreateModal">Cancelar</BaseButton>
            <BaseButton type="submit" class="btn-primary">Adicionar Compra</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable from '../components/ui/BaseTable.vue'

export default {
  components: { BaseButton, BaseTable },
  data() {
    return {
      compras: [],
      fornecedores: [],
      produtos: [],
      motoristas: [],
      clientes: [],
      novaCompra: {
        tipo: 'revenda',
        fornecedor_id: null,
        cliente_id: null,
        produto_id: null,
        motorista_id: null,
        comissao_intermediador: null,
        comissao_motorista: null,
        comissao_intermediador_em_dinheiro: true,
        comissao_motorista_em_dinheiro: true,
        quantidade: 0,
        valor_unitario: 0,
      },
      loading: false,
      showCreateModal: false,
      pageSize: 25,
      currentPage: 1,
      totalCount: 0,
    }
  },
  mounted() {
    this.loadCompras()
    this.loadFornecedores()
    this.loadProdutos()
    this.loadMotoristas()
    this.loadClientes()
  },
  computed: {
    tableCols() { return [
      { key: 'fornecedor', label: 'Fornecedor' },
      { key: 'produto', label: 'Produto' },
      { key: 'quantidade', label: 'Quantidade' },
      { key: 'valor_unitario', label: 'Valor Unit.' },
      { key: 'status', label: 'Status' }
    ] },
    visibleCompras() {
      return (this.compras || []).filter(c => {
        if (!c) return false
        const hasFornecedor = c.fornecedor && String(c.fornecedor).trim().length > 0
        const hasProduto = c.produto && String(c.produto).trim().length > 0
        const hasQuantidade = c.quantidade !== undefined && c.quantidade !== null
        const hasValor = c.valor_unitario !== undefined && c.valor_unitario !== null
        return hasFornecedor || hasProduto || hasQuantidade || hasValor
      })
    },
    totalPages() { return Math.max(1, Math.ceil(this.totalCount / this.pageSize)) },
    paginatedCompras() { return this.visibleCompras },
  },
  methods: {
    async loadCompras() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/compras', { params: { page: this.currentPage, per_page: this.pageSize } })
        this.compras = res.data.items || []
        this.totalCount = res.data.total || 0
      } catch (e) {
        console.error('Erro ao carregar compras:', e)
        this.compras = []
        this.totalCount = 0
      } finally {
        this.loading = false
      }
    },
    async createPurchase() {
      try {
        let payload = {
          produto_id: this.novaCompra.produto_id,
          quantidade: this.novaCompra.quantidade,
          valor_unitario: this.novaCompra.valor_unitario,
          fornecedor_id: this.novaCompra.fornecedor_id,
        }
        if (this.novaCompra.tipo === 'cliente') {
          payload = {
            ...payload,
            cliente_id: this.novaCompra.cliente_id,
            motorista_id: this.novaCompra.motorista_id,
            comissao_intermediador: this.novaCompra.comissao_intermediador,
            comissao_motorista: this.novaCompra.comissao_motorista,
            comissao_intermediador_em_dinheiro: this.novaCompra.comissao_intermediador_em_dinheiro,
            comissao_motorista_em_dinheiro: this.novaCompra.comissao_motorista_em_dinheiro,
          }
        }
        await api.post('/api/v1/compras', payload)
        this.novaCompra = { tipo: 'revenda', fornecedor_id: null, cliente_id: null, produto_id: null, motorista_id: null, comissao_intermediador: null, comissao_motorista: null, comissao_intermediador_em_dinheiro: true, comissao_motorista_em_dinheiro: true, quantidade: 0, valor_unitario: 0 }
        this.showCreateModal = false
        this.loadCompras()
      } catch (e) {
        console.error('Erro ao criar compra:', e)
      }
    },
    async loadClientes() {
      try {
        const res = await api.get('/api/v1/clientes')
        this.clientes = res.data.items || res.data || []
      } catch (e) {
        console.error('Erro ao carregar clientes', e)
        this.clientes = []
      }
    },

    async loadFornecedores() {
      try {
        const res = await api.get('/api/v1/fornecedores')
        this.fornecedores = res.data || []
      } catch (e) {
        console.error('Erro ao carregar fornecedores', e)
        this.fornecedores = []
      }
    },

    async loadProdutos() {
      try {
        const res = await api.get('/api/v1/produtos', { params: { page: 1, per_page: 1000 } })
        this.produtos = res.data.items || []
      } catch (e) {
        console.error('Erro ao carregar produtos', e)
        this.produtos = []
      }
    },

    async loadMotoristas() {
      try {
        const res = await api.get('/api/v1/motoristas')
        this.motoristas = res.data || []
      } catch (e) {
        console.error('Erro ao carregar motoristas', e)
        this.motoristas = []
      }
    },

    onProdutoChange() {
      const pid = this.novaCompra.produto_id
      const prod = this.produtos.find(p => p.id === pid)
      if (prod && prod.custo_medio !== undefined) {
        this.novaCompra.valor_unitario = parseFloat(prod.custo_medio)
      }
    },
    openCreateModal() { this.showCreateModal = true },
    closeCreateModal() { this.showCreateModal = false; this.novaCompra = { fornecedor: '', produto: '', quantidade: 0, valor_unitario: 0 } },
    prevPage() { if (this.currentPage > 1) { this.currentPage--; this.loadCompras() } },
    nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.loadCompras() } },
    goToPage(n) { this.currentPage = Math.min(Math.max(1, n), this.totalPages); this.loadCompras() },
  },
}
</script>