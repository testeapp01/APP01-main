<template>
  <div class="page-shell">
    <PageHero
      title="Compras"
      subtitle="Controle entradas, parceiros e comissões em um fluxo operacional único."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary w-full sm:w-auto"
          @click="loadCompras"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          class="btn-primary w-full sm:w-auto"
          @click="openCreateModal"
        >
          Adicionar Compra
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Compras
        </div>
        <div class="saas-kpi-value">
          {{ totalCount }}
        </div>
        <div class="saas-kpi-help">
          Registros no período atual
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Página Atual
        </div>
        <div class="saas-kpi-value">
          {{ currentPage }}
        </div>
        <div class="saas-kpi-help">
          Navegação operacional
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Itens por Página
        </div>
        <div class="saas-kpi-value">
          {{ pageSize }}
        </div>
        <div class="saas-kpi-help">
          Escala de visualização
        </div>
      </article>
    </section>

    <div
      v-if="visibleCompras.length > 0"
      class="panel-inner"
    >
      <BaseTable
        :columns="tableCols"
        :rows="paginatedCompras"
      >
        <template #fornecedor="{ row }">
          {{ row.fornecedor || '-' }}
        </template>
        <template #produto="{ row }">
          {{ row.produto || '-' }}
        </template>
        <template #quantidade="{ row }">
          {{ row.quantidade !== undefined && row.quantidade !== null ? row.quantidade : '-' }}
        </template>
        <template #valor_unitario="{ row }">
          {{ row.valor_unitario !== undefined && row.valor_unitario !== null ? ('R$ ' + row.valor_unitario) : '-' }}
        </template>
        <template #status="{ row }">
          {{ row.status || '-' }}
        </template>
      </BaseTable>
    </div>
    <ListState
      :loading="loading"
      :has-data="visibleCompras.length > 0"
      loading-text="Carregando compras..."
      empty-title="Nenhuma compra encontrada."
      empty-message="Adicione compras para começar a registrar entradas."
      action-label="Adicionar Compra"
      @action="openCreateModal"
    />

    <div
      v-if="visibleCompras.length > 0"
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

    <SideDrawer
      :open="showCreateModal"
      title="Adicionar Compra"
      @close="closeCreateModal"
    >
      <form
        class="drawer-form space-y-5"
        @submit.prevent="createPurchase"
      >
        <FormFeedback
          :message="purchaseFeedback.message"
          :type="purchaseFeedback.type"
        />
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo de Compra</label>
          <div class="flex gap-3">
            <button
              type="button"
              :class="['px-4 py-2 rounded-lg border', novaCompra.tipo === 'revenda' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300']"
              @click="novaCompra.tipo = 'revenda'"
            >
              Revenda
            </button>
            <button
              type="button"
              :class="['px-4 py-2 rounded-lg border', novaCompra.tipo === 'cliente' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300']"
              @click="novaCompra.tipo = 'cliente'"
            >
              Cliente
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Fornecedor</label>
          <select
            v-model.number="novaCompra.fornecedor_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um fornecedor
            </option>
            <option
              v-for="f in fornecedores"
              :key="f.id"
              :value="f.id"
            >
              {{ f.razao_social }}
            </option>
          </select>
        </div>

        <div v-if="novaCompra.tipo === 'cliente'">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente</label>
          <select
            v-model.number="novaCompra.cliente_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um cliente
            </option>
            <option
              v-for="c in clientes"
              :key="c.id"
              :value="c.id"
            >
              {{ c.nome }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Produto</label>
          <select
            v-model.number="novaCompra.produto_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select w-full"
            required
            @change="onProdutoChange"
          >
            <option
              :value="null"
              disabled
            >
              Escolha um produto
            </option>
            <option
              v-for="p in produtos"
              :key="p.id"
              :value="p.id"
            >
              {{ p.nome }}
            </option>
          </select>
        </div>

        <div v-if="novaCompra.tipo === 'cliente'">
          <label class="text-sm text-gray-600 mb-1 block">Motorista</label>
          <select
            v-model.number="novaCompra.motorista_id"
            class="p-3 border border-gray-300 rounded-xl estilo-select"
            required
          >
            <option
              :value="null"
              disabled
            >
              Escolha um motorista
            </option>
            <option
              v-for="m in motoristas"
              :key="m.id"
              :value="m.id"
            >
              {{ m.nome }}
            </option>
          </select>
        </div>

        <!-- Quantidade e Valor Unitário lado a lado -->
        <div class="flex flex-col md:flex-row gap-4 md:gap-6 items-start w-full mb-4">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Quantidade</label>
            <input
              v-model.number="novaCompra.quantidade"
              type="number"
              placeholder="Quantidade"
              class="p-3 border border-gray-300 rounded-xl w-full"
              min="1"
              required
            >
          </div>
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Valor Unitário (R$)</label>
            <input
              v-model.number="novaCompra.valor_unitario"
              type="number"
              placeholder="Valor Unitário"
              class="p-3 border border-gray-300 rounded-xl w-full"
              step="0.01"
              required
            >
          </div>
        </div>
        <!-- Comissões centralizadas abaixo -->
        <div
          v-if="novaCompra.tipo === 'cliente'"
          class="flex flex-col lg:flex-row justify-center gap-4 lg:gap-6 w-full mb-2"
        >
          <div class="bg-gray-50 rounded-2xl p-4 border-2 border-blue-300 shadow-sm flex flex-col gap-2 w-full lg:max-w-[320px]">
            <div class="font-semibold text-gray-700 flex items-center gap-2 text-base mb-1">
              <span class="flex w-7 h-7 bg-blue-100 text-blue-600 rounded-full items-center justify-center mr-2 text-xl">💰</span>
              Comissão Intermediação
            </div>
            <div class="flex items-center gap-3 mb-1">
              <label class="text-xs text-gray-600">Tipo:</label>
              <button
                type="button"
                :class="['relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none', novaCompra.comissao_intermediador_em_dinheiro ? 'bg-green-500' : 'bg-gray-300']"
                @click="novaCompra.comissao_intermediador_em_dinheiro = !novaCompra.comissao_intermediador_em_dinheiro"
              >
                <span :class="['absolute left-0 top-0 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200', novaCompra.comissao_intermediador_em_dinheiro ? 'translate-x-6' : '']" />
                <span
                  v-if="!novaCompra.comissao_intermediador_em_dinheiro"
                  class="absolute left-1 top-1 text-xs font-bold"
                >%</span>
                <span
                  v-if="novaCompra.comissao_intermediador_em_dinheiro"
                  class="absolute right-1 top-1 text-xs font-bold"
                >R$</span>
              </button>
              <span class="text-xs text-gray-700 font-semibold">{{ novaCompra.comissao_intermediador_em_dinheiro ? 'R$' : '%' }}</span>
            </div>
            <input
              v-model.number="novaCompra.comissao_intermediador"
              type="number"
              min="0"
              :max="novaCompra.comissao_intermediador_em_dinheiro ? null : 100"
              :step="novaCompra.comissao_intermediador_em_dinheiro ? '0.01' : '0.01'"
              placeholder="Comissão"
              class="p-2 border border-blue-300 rounded-xl w-full text-base"
            >
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
          <div class="bg-gray-50 rounded-2xl p-4 border-2 border-green-300 shadow-sm flex flex-col gap-2 w-full lg:max-w-[320px]">
            <div class="font-semibold text-gray-700 flex items-center gap-2 text-base mb-1">
              <span class="flex w-7 h-7 bg-green-100 text-green-600 rounded-full items-center justify-center mr-2 text-xl">🚚</span>
              Comissão Motorista
            </div>
            <div class="flex items-center gap-3 mb-1">
              <label class="text-xs text-gray-600">Tipo:</label>
              <button
                type="button"
                :class="['relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none', novaCompra.comissao_motorista_em_dinheiro ? 'bg-green-500' : 'bg-gray-300']"
                @click="novaCompra.comissao_motorista_em_dinheiro = !novaCompra.comissao_motorista_em_dinheiro"
              >
                <span :class="['absolute left-0 top-0 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200', novaCompra.comissao_motorista_em_dinheiro ? 'translate-x-6' : '']" />
                <span
                  v-if="!novaCompra.comissao_motorista_em_dinheiro"
                  class="absolute left-1 top-1 text-xs font-bold"
                >%</span>
                <span
                  v-if="novaCompra.comissao_motorista_em_dinheiro"
                  class="absolute right-1 top-1 text-xs font-bold"
                >R$</span>
              </button>
              <span class="text-xs text-gray-700 font-semibold">{{ novaCompra.comissao_motorista_em_dinheiro ? 'R$' : '%' }}</span>
            </div>
            <input
              v-model.number="novaCompra.comissao_motorista"
              type="number"
              min="0"
              :max="novaCompra.comissao_motorista_em_dinheiro ? null : 100"
              :step="novaCompra.comissao_motorista_em_dinheiro ? '0.01' : '0.01'"
              placeholder="Comissão"
              class="p-2 border border-green-300 rounded-xl w-full text-base"
            >
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
        <div class="drawer-actions flex justify-end gap-3 mt-6">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeCreateModal"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary"
            :disabled="submittingPurchase"
            :loading="submittingPurchase"
          >
            {{ submittingPurchase ? 'Salvando...' : 'Adicionar Compra' }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>
  </div>
</template>

<script>
import api from '../services/api'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable from '../components/ui/BaseTable.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'

export default {
  components: { BaseButton, BaseTable, SideDrawer, PageHero, ListState, FormFeedback },
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
      submittingPurchase: false,
      purchaseFeedback: { message: '', type: 'info' },
      pageSize: 25,
      currentPage: 1,
      totalCount: 0,
    }
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
  mounted() {
    this.loadCompras()
    this.loadFornecedores()
    this.loadProdutos()
    this.loadMotoristas()
    this.loadClientes()
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
      this.submittingPurchase = true
      this.purchaseFeedback = { message: 'Salvando compra...', type: 'info' }
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
        this.purchaseFeedback = { message: 'Compra criada com sucesso.', type: 'success' }
        this.novaCompra = { tipo: 'revenda', fornecedor_id: null, cliente_id: null, produto_id: null, motorista_id: null, comissao_intermediador: null, comissao_motorista: null, comissao_intermediador_em_dinheiro: true, comissao_motorista_em_dinheiro: true, quantidade: 0, valor_unitario: 0 }
        setTimeout(() => { this.showCreateModal = false }, 350)
        this.loadCompras()
      } catch (e) {
        console.error('Erro ao criar compra:', e)
        this.purchaseFeedback = { message: 'Não foi possível salvar a compra. Revise os dados e tente novamente.', type: 'error' }
      } finally {
        this.submittingPurchase = false
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
    openCreateModal() { this.showCreateModal = true; this.purchaseFeedback = { message: '', type: 'info' } },
    closeCreateModal() { this.showCreateModal = false; this.submittingPurchase = false; this.purchaseFeedback = { message: '', type: 'info' }; this.novaCompra = { fornecedor: '', produto: '', quantidade: 0, valor_unitario: 0 } },
    prevPage() { if (this.currentPage > 1) { this.currentPage--; this.loadCompras() } },
    nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.loadCompras() } },
    goToPage(n) { this.currentPage = Math.min(Math.max(1, n), this.totalPages); this.loadCompras() },
  },
}
</script>