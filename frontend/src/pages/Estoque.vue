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
          Movimentação de Estoque
        </h2>
      </div>
      <div
        v-if="visibleEstoque.length > 0"
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
            <div @click.stop>
              <ActionDropdown
                :items="getRowActions(row)"
                :menu-height="180"
                @select="handleRowAction($event, row)"
              />
            </div>
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
        <input
          v-model="form.produto"
          placeholder="Produto"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
          required
        >
        <div v-if="form.tipo_movimento === 'saida' && form.produto" class="p-3 bg-yellow-50 border border-yellow-300 rounded-xl text-sm">
          <strong>Quantidade disponível:</strong> {{ getQuantidadeDisponivel(form.produto) }}
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

    <ConfirmDialog
      :open="confirmDeleteOpen"
      title="Excluir movimentação"
      message="Deseja excluir esta movimentação de estoque?"
      :loading="deleting"
      confirm-label="Excluir"
      @cancel="cancelDelete"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script>
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import PaginationPremium from '../components/ui/PaginationPremium.vue'
import api from '../services/api'
import ActionDropdown from '../components/ui/ActionDropdown.vue'

export default {
  name: 'Estoque',
  components: {
    BaseTable,
    BaseButton,
    SideDrawer,
    PageHero,
    ListState,
    FormFeedback,
    ConfirmDialog,
    PaginationPremium,
    ActionDropdown,
  },
  data() {
    return {
      estoque: [],
      loading: true,
      pageSize: 25,
      currentPage: 1,
      showCreateModal: false,
      submitting: false,
      editMode: false,
      feedback: { message: '', type: 'info' },
      form: {
        produto: '',
        quantidade: 0,
        tipo_movimento: 'entrada',
        data: new Date().toISOString().split('T')[0],
      },
      editingId: null,
      confirmDeleteOpen: false,
      deleting: false,
      movimentoToDelete: null,
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
    entradasCount() { return this.filteredEstoque.filter(e => e.tipo_movimento === 'entrada').length },
    saidasCount() { return this.filteredEstoque.filter(e => e.tipo_movimento === 'saida').length },
    paginatedEstoque() {
      const start = (this.currentPage - 1) * this.pageSize
      return this.filteredEstoque.slice(start, start + this.pageSize)
    },
  },
  mounted() {
    this.loadEstoque()
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
    getRowActions(row) {
      return [
        { key: 'editar', label: 'Editar' },
        { key: 'excluir', label: 'Excluir', danger: true },
      ]
    },
    handleRowAction(action, row) {
      if (action === 'editar') this.openEdit(row)
      if (action === 'excluir') this.deleteMovimento(row)
    },
    async loadEstoque() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/estoque')
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
      this.form = { produto: '', quantidade: 0, tipo_movimento: 'entrada', data: new Date().toISOString().split('T')[0] }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    closeCreateModal() {
      this.showCreateModal = false
      this.submitting = false
      this.feedback = { message: '', type: 'info' }
      this.form = { produto: '', quantidade: 0, tipo_movimento: 'entrada', data: new Date().toISOString().split('T')[0] }
      this.editMode = false
      this.editingId = null
    },
    openEdit(movimento) {
      this.editMode = true
      this.editingId = movimento.id
      this.form = {
        produto: movimento.produto || '',
        quantidade: movimento.quantidade || 0,
        tipo_movimento: movimento.tipo_movimento || 'entrada',
        data: movimento.data || new Date().toISOString().split('T')[0],
      }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    async submitForm() {
      // Validação: Saída só é permitida se houver quantidade em estoque
      if (this.form.tipo_movimento === 'saida') {
        const produtoEmEstoque = this.estoque.find(e => e.produto === this.form.produto && e.tipo_movimento === 'entrada')
        const quantidadeTotalEntrada = this.estoque
          .filter(e => e.produto === this.form.produto && e.tipo_movimento === 'entrada')
          .reduce((sum, e) => sum + (e.quantidade || 0), 0)
        const quantidadeTotalSaida = this.estoque
          .filter(e => e.produto === this.form.produto && e.tipo_movimento === 'saida')
          .reduce((sum, e) => sum + (e.quantidade || 0), 0)
        const quantidadeDisponivel = quantidadeTotalEntrada - quantidadeTotalSaida

        if (quantidadeDisponivel <= 0) {
          this.feedback = { 
            message: `Sem estoque de "${this.form.produto}". Adicione quantidade em entrada primeiro.`, 
            type: 'error' 
          }
          return
        }

        if (this.form.quantidade > quantidadeDisponivel) {
          this.feedback = { 
            message: `Quantidade insuficiente. Disponível: ${quantidadeDisponivel}. Deseja adicionar mais estoque?`, 
            type: 'warning' 
          }
          return
        }
      }

      this.submitting = true
      this.feedback = { message: this.editMode ? 'Atualizando...' : 'Salvando...', type: 'info' }
      try {
        if (this.editMode) {
          await api.put(`/api/v1/estoque/${this.editingId}`, this.form)
        } else {
          await api.post('/api/v1/estoque', this.form)
        }
        this.feedback = { message: this.editMode ? 'Movimentação atualizada com sucesso.' : 'Movimentação registrada com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateModal(), 300)
        await this.loadEstoque()
      } catch (e) {
        this.feedback = { message: e?.response?.data?.error || 'Erro ao salvar movimentação.', type: 'error' }
      } finally {
        this.submitting = false
      }
    },
    deleteMovimento(movimento) {
      this.movimentoToDelete = movimento
      this.confirmDeleteOpen = true
    },
    cancelDelete() {
      this.confirmDeleteOpen = false
      this.deleting = false
      this.movimentoToDelete = null
    },
    async confirmDelete() {
      if (!this.movimentoToDelete?.id) return
      this.deleting = true
      try {
        await api.delete(`/api/v1/estoque/${this.movimentoToDelete.id}`)
        this.currentPage = 1
        await this.loadEstoque()
      } catch (e) {
        console.error('Erro ao excluir movimento', e)
      } finally {
        this.cancelDelete()
      }
    },
    getQuantidadeDisponivel(produto) {
      const quantidadeTotalEntrada = this.estoque
        .filter(e => e.produto === produto && e.tipo_movimento === 'entrada')
        .reduce((sum, e) => sum + (e.quantidade || 0), 0)
      const quantidadeTotalSaida = this.estoque
        .filter(e => e.produto === produto && e.tipo_movimento === 'saida')
        .reduce((sum, e) => sum + (e.quantidade || 0), 0)
      return Math.max(0, quantidadeTotalEntrada - quantidadeTotalSaida)
    },
  },
}
</script>
