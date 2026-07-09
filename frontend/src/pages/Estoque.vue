<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Estoque"
      subtitle="Monitore inventário, movimentações e disponibilidade em tempo real."
    >
      <template #actions>
        <div class="flex flex-col gap-3 w-full sm:flex-row sm:items-end">
          <input
            v-model="query"
            placeholder="Buscar por produto"
            class="p-3 border border-gray-300 rounded-xl w-full sm:min-w-[260px] hero-control"
            @input="onQuery"
          >
          <select
            v-model="tipoMovimento"
            class="p-3 border border-gray-300 rounded-xl w-full sm:w-auto hero-control"
          >
            <option value="">
              Todos movimentos
            </option>
            <option value="entrada">
              Entrada (+)
            </option>
            <option value="saida">
              Saída (-)
            </option>
          </select>
          <div class="flex gap-2">
            <BaseButton
              v-if="hasActiveFilter"
              variant="secondary"
              class="w-full sm:w-auto whitespace-nowrap"
              @click="clearFilters"
            >
              Limpar
            </BaseButton>
            <BaseButton
              class="btn-secondary w-full sm:w-auto whitespace-nowrap"
              @click="loadEstoque"
            >
              Atualizar
            </BaseButton>
            <BaseButton
              class="btn-secondary w-full sm:w-auto whitespace-nowrap"
              @click="activeTab = activeTab === 'saldos' ? 'movimentos' : 'saldos'"
            >
              {{ activeTab === 'saldos' ? 'Ver Movimentos' : 'Ver Saldos' }}
            </BaseButton>
            <BaseButton
              class="btn-primary w-full sm:w-auto whitespace-nowrap"
              @click="openCreateModal"
            >
              + Movimentação
            </BaseButton>
          </div>
        </div>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Movimentações
        </div>
        <div class="saas-kpi-value">
          {{ filteredEstoque.length }}
        </div>
        <div class="saas-kpi-help">
          Registros no período
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Entradas (+)
        </div>
        <div class="saas-kpi-value">
          {{ entradasCount }}
        </div>
        <div class="saas-kpi-help">
          Recebimentos
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Saídas (-)
        </div>
        <div class="saas-kpi-value">
          {{ saidasCount }}
        </div>
        <div class="saas-kpi-help">
          Vendás/Descartes
        </div>
      </article>
    </section>

    <div class="section-block">
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800 section-title">
          {{ activeTab === 'saldos' ? 'Saldo Atual por Produto' : 'Movimentação de Estoque' }}
        </h2>
      </div>

      <!-- Saldos tab -->
      <div v-if="activeTab === 'saldos'" class="panel-inner content-card">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-2 px-3 text-gray-500 font-semibold">Produto</th>
              <th class="text-right py-2 px-3 text-gray-500 font-semibold">Estoque</th>
              <th class="text-right py-2 px-3 text-gray-500 font-semibold">Reservado</th>
              <th class="text-right py-2 px-3 text-gray-500 font-semibold">Disponível</th>
              <th class="text-right py-2 px-3 text-gray-500 font-semibold">Custo Médio</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in saldos" :key="s.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="py-2 px-3 font-medium">{{ s.nome }}</td>
              <td class="py-2 px-3 text-right">{{ s.estoque_atual }} {{ s.unidade }}</td>
              <td class="py-2 px-3 text-right text-amber-600">{{ s.estoque_reservado }}</td>
              <td class="py-2 px-3 text-right" :class="parseFloat(s.disponivel) <= 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold'">
                {{ s.disponivel }}
              </td>
              <td class="py-2 px-3 text-right text-gray-500">R$ {{ parseFloat(s.custo_medio || 0).toFixed(2) }}</td>
            </tr>
            <tr v-if="saldos.length === 0">
              <td colspan="5" class="py-4 text-center text-gray-400">Nenhum produto cadastrado</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Movimentações tab -->
      <div
        v-if="activeTab === 'movimentos' && visibleEstoque.length > 0"
        class="panel-inner content-card"
      >
        <BaseTable
          :columns="tableCols"
          :rows="paginatedEstoque"
        >
          <template #produto="{ row }">
            {{ row.produto || '-' }}
          </template>
          <template #quantidade="{ row }">
            {{ row.quantidade || 0 }}
          </template>
          <template #tipo_movimento="{ row }">
            <span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="row.tipo_movimento === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ row.tipo_movimento === 'entrada' ? 'Entrada' : 'Saída' }}
            </span>
          </template>
          <template #data="{ row }">
            {{ row.data || '-' }}
          </template>
          <template #acoes="{ row }">
            <div class="text-sm text-gray-500">-</div>
          </template>
        </BaseTable>
        <PaginationPremium
          :current-page.sync="currentPage"
          :page-size.sync="pageSize"
          :total="visibleEstoque.length"
        />
      </div>
      <ListState
        :loading="loading"
        :has-data="visibleEstoque.length > 0"
        loading-text="Carregando estoque..."
        empty-title="Nenhuma movimentação registrada."
        empty-message="Registre movimentações para começar."
        action-label="Registrar Movimentação"
        @action="openCreateModal"
      />
    </div>

    <SideDrawer
      :open="showCreateModal"
      :title="editMode ? 'Editar Movimentação' : 'Registrar Movimentação'"
      @close="closeCreateModal"
    >
      <form
        class="drawer-form grid grid-cols-1 gap-3"
        @submit.prevent="submitForm"
      >
        <FormFeedback
          :message="feedback.message"
          :type="feedback.type"
        />
        <select v-model.number="form.produto_id" class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500" required>
          <option :value="null">Selecione um produto</option>
          <option v-for="p in produtos" :key="p.id" :value="p.id">{{ p.nome }}</option>
        </select>
        <div v-if="form.tipo_movimento === 'saida' && form.produto_id" class="p-3 bg-yellow-50 border border-yellow-300 rounded-xl text-sm">
          <strong>Quantidade disponível:</strong> {{ getQuantidadeDisponivelById(form.produto_id) }}
        </div>
        <input
          v-model.number="form.quantidade"
          placeholder="Quantidade"
          type="number"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
          required
        >
        <select
          v-model="form.tipo_movimento"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
        >
          <option value="entrada">Entrada (+)</option>
          <option value="saida">Saída (-)</option>
        </select>
        <input
          v-model="form.data"
          placeholder="Data"
          type="date"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
        >
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
            class="btn-primary"
            :disabled="submitting"
            :loading="submitting"
          >
            {{ submitting ? 'Salvando...' : (editMode ? 'Atualizar' : 'Registrar') }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>

  </div>
</template>

<script>
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'
import PaginationPremium from '../components/ui/PaginationPremium.vue'
import api from '../services/api'

export default {
  name: 'Estoque',
  components: {
    BaseTable,
    BaseButton,
    SideDrawer,
    PageHero,
    ListState,
    FormFeedback,
    PaginationPremium,
  },
  data() {
    return {
      estoque: [],
      saldos: [],
      activeTab: 'movimentos',
      loading: true,
      pageSize: 25,
      currentPage: 1,
      showCreateModal: false,
      submitting: false,
      editMode: false,
      feedback: { message: '', type: 'info' },
      form: {
        produto_id: null,
        quantidade: 0,
        tipo_movimento: 'entrada',
        data: new Date().toISOString().split('T')[0],
      },
      editingId: null,
      // deletions/edits are not supported by backend for automatic movements
      confirmDeleteOpen: false,
      deleting: false,
      movimentoToDelete: null,
      produtos: [],
      query: '',
      tipoMovimento: '',
    }
  },
  computed: {
    tableCols() {
      return [
        { key: 'produto', label: 'Produto' },
        { key: 'quantidade', label: 'Quantidade' },
        { key: 'tipo_movimento', label: 'Tipo' },
        { key: 'data', label: 'Data' },
        { key: 'acoes', label: 'Ações' },
      ]
    },
    visibleEstoque() {
      return (this.estoque || []).filter(e => e && (e.produto || e.quantidade))
    },
    filteredEstoque() {
      return this.visibleEstoque.filter(e => {
        const matchesQuery = !this.query || e.produto?.toLowerCase().includes(this.query.toLowerCase())
        const matchesType = !this.tipoMovimento || e.tipo_movimento === this.tipoMovimento
        return matchesQuery && matchesType
      })
    },
    hasActiveFilter() { return this.query || this.tipoMovimento },
    entradasCount() { return this.filteredEstoque.filter(e => ['entrada_compra','ajuste_manual','entrada'].includes(e.tipo_movimento)).length },
    saidasCount() { return this.filteredEstoque.filter(e => ['saida_venda','quebra','saida'].includes(e.tipo_movimento)).length },
    paginatedEstoque() {
      const start = (this.currentPage - 1) * this.pageSize
      return this.filteredEstoque.slice(start, start + this.pageSize)
    },
  },
  mounted() {
    this.loadEstoque()
    this.loadSaldos()
    this.loadProdutos()
  },
  methods: {
    onQuery() {
      this.currentPage = 1
    },
    clearFilters() {
      this.query = ''
      this.tipoMovimento = ''
      this.currentPage = 1
    },
    // Row actions removed: backend only supports manual adjustments via POST
    async loadSaldos() {
      try {
        const res = await api.get('/api/v1/estoque/saldos')
        this.saldos = Array.isArray(res.data) ? res.data : []
      } catch { this.saldos = [] }
    },
    async loadProdutos() {
      try {
        const res = await api.get('/api/v1/produtos', { params: { page: 1, per_page: 1000 } })
        const items = Array.isArray(res.data) ? res.data : (res.data.items || [])
        this.produtos = items.map(p => ({ id: p.id, nome: p.nome }))
      } catch (e) { this.produtos = [] }
    },
    async loadEstoque() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/estoque', { params: { q: this.query, per_page: 100 } })
        this.estoque = Array.isArray(res.data) ? res.data : (res.data.items || [])
        this.currentPage = 1
      } catch (e) {
        console.error('Erro ao carregar estoque', e)
        this.estoque = []
      } finally {
        this.loading = false
      }
    },
    openCreateModal() {
      this.editMode = false
      this.editingId = null
      this.form = { produto_id: null, quantidade: 0, tipo_movimento: 'entrada', data: new Date().toISOString().split('T')[0] }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    closeCreateModal() {
      this.showCreateModal = false
      this.submitting = false
      this.feedback = { message: '', type: 'info' }
      this.form = { produto_id: null, quantidade: 0, tipo_movimento: 'entrada', data: new Date().toISOString().split('T')[0] }
      this.editMode = false
      this.editingId = null
    },
    openEdit(movimento) {
      // Editing existing movimentações is not supported by backend for automatic movements.
      this.feedback = { message: 'Edição de movimentações não suportada. Registre um ajuste manual.', type: 'warning' }
    },
    async submitForm() {
      // Basic client-side checks
      if (!this.form.produto_id) {
        this.feedback = { message: 'Selecione um produto.', type: 'error' }
        return
      }
      if (this.form.quantidade <= 0) {
        this.feedback = { message: 'Quantidade deve ser maior que zero.', type: 'error' }
        return
      }

      this.submitting = true
      this.feedback = { message: this.editMode ? 'Atualizando...' : 'Salvando...', type: 'info' }
      try {
        const payload = {
          produto_id: Number(this.form.produto_id),
          quantidade: Number(this.form.quantidade),
          direcao: this.form.tipo_movimento === 'entrada' ? 'entrada' : 'saida',
          tipo_movimento: 'ajuste_manual'
        }
        await api.post('/api/v1/estoque', payload)
        this.feedback = { message: 'Movimentação registrada com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateModal(), 300)
        await this.loadEstoque()
      } catch (e) {
        const { getMessage } = useApiError()
        this.feedback = { message: getMessage(e, 'Erro ao salvar movimentação.'), type: 'error' }
      } finally {
        this.submitting = false
      }
    },
    getQuantidadeDisponivelById(produtoId) {
      const s = this.saldos.find(x => Number(x.id) === Number(produtoId))
      return s ? Number(s.disponivel || 0) : 0
    },
  },
}
</script>
