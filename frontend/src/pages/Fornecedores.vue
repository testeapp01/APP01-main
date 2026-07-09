<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Fornecedores"
      subtitle="Centralize fornecedores, contatos e disponibilidade de forma confiável."
    >
      <template #actions>
        <div class="flex flex-col gap-3 w-full sm:flex-row sm:items-end">
          <input
            v-model="query"
            placeholder="Buscar por razão social ou CNPJ"
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
            <option value="ATIVO">
              ATIVO
            </option>
            <option value="INATIVO">
              INATIVO
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
              @click="refreshFornecedores"
            >
              Atualizar
            </BaseButton>
            <BaseButton
              class="btn-primary w-full sm:w-auto whitespace-nowrap"
              @click="openCreateFornecedor"
            >
              + Fornecedor
            </BaseButton>
          </div>
        </div>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Fornecedores
        </div>
        <div class="saas-kpi-value">
          {{ filteredFornecedores.length }}
        </div>
        <div class="saas-kpi-help">
          Registros no período
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Fornecedores Ativos
        </div>
        <div class="saas-kpi-value">
          {{ activeFornecedoresCount }}
        </div>
        <div class="saas-kpi-help">
          Base operacional
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Inativos
        </div>
        <div class="saas-kpi-value">
          {{ inactiveFornecedoresCount }}
        </div>
        <div class="saas-kpi-help">
          Suspensão de contrato
        </div>
      </article>
    </section>

    <!-- Fornecedores Table -->
    <div class="section-block">
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800 section-title">
          Lista de Fornecedores
        </h2>
      </div>
      <div
        v-if="visibleFornecedores.length > 0"
        class="panel-inner content-card"
      >
        <BaseTable
          :columns="fornecedorCols"
          :rows="paginatedFornecedores"
        >
          <template #razao_social="{ row }">
            {{ row.razao_social }}
          </template>
          <template #cnpj="{ row }">
            {{ row.cnpj || '-' }}
          </template>
          <template #endereco="{ row }">
            {{ formatAddress(row) }}
          </template>
          <template #uf="{ row }">
            {{ row.uf || '-' }}
          </template>
          <template #telefone="{ row }">
            {{ row.telefone || '-' }}
          </template>
          <template #email="{ row }">
            {{ row.email || '-' }}
          </template>
          <template #status="{ row }">
            <span
              :class="[
                'dt-badge',
                row.status ? 'success' : 'warn'
              ]"
            >
              {{ row.status ? '✓ ATIVO' : '⏱ INATIVO' }}
            </span>
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
      </div>
    </div>
    <ListState
      v-if="loading"
      :loading="loading"
      :has-data="filteredFornecedores.length > 0"
      loading-text="Carregando fornecedores..."
      empty-title="Nenhum fornecedor encontrado."
      empty-message="Adicione fornecedores para começar a gerenciar parceiros."
      action-label="Adicionar Fornecedor"
      @action="openCreateFornecedor"
    />

    <div
      v-if="!loading && filteredFornecedores.length === 0"
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
              Sem fornecedores para exibir
            </p>
            <p class="text-sm text-slate-600">
              {{ hasActiveFilter ? 'Ajuste os filtros e tente novamente.' : 'Cadastre o primeiro fornecedor para iniciar o gerenciamento.' }}
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
            @click="openCreateFornecedor"
          >
            Adicionar Fornecedor
          </BaseButton>
        </div>
      </div>
    </div>

    <div
      v-if="filteredFornecedores.length > 0"
      class="mt-6"
    >
      <div class="panel-inner content-card">
        <PaginationPremium
          :current-page.sync="currentPageFornecedores"
          :page-size.sync="pageSize"
          :total="filteredFornecedores.length"
          @update:current-page="currentPageFornecedores = $event"
          @update:page-size="pageSize = $event"
        />
      </div>
    </div>

    <SideDrawer
      :open="showCreateFornecedor"
      title="Adicionar Fornecedor"
      @close="closeCreateFornecedor"
    >
      <form
        class="drawer-form grid grid-cols-1 gap-3"
        @submit.prevent="createFornecedor"
      >
        <FormFeedback
          :message="fornecedorFeedback.message"
          :type="fornecedorFeedback.type"
        />
        <input
          v-model="novoFornecedor.razao_social"
          placeholder="Razão Social"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <input
          v-model="novoFornecedor.endereco"
          placeholder="Endereço"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input
            v-model="novoFornecedor.numero"
            placeholder="Número"
            class="p-3 border border-gray-300 rounded-xl"
          >
          <input
            v-model="novoFornecedor.complemento"
            placeholder="Complemento"
            class="p-3 border border-gray-300 rounded-xl"
          >
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input
            v-model="novoFornecedor.bairro"
            placeholder="Bairro"
            class="p-3 border border-gray-300 rounded-xl"
          >
          <input
            v-model="novoFornecedor.cep"
            placeholder="CEP"
            class="p-3 border border-gray-300 rounded-xl"
            @input="onFornecedorCepInput"
          >
        </div>
        <input
          v-model="novoFornecedor.cidade"
          placeholder="Cidade"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <input
          v-model="novoFornecedor.cnpj"
          placeholder="CNPJ"
          class="p-3 border border-gray-300 rounded-xl"
          required
          @input="onFornecedorCnpjInput"
        >
        <input
          v-model="novoFornecedor.telefone"
          placeholder="Telefone"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <input
          v-model="novoFornecedor.email"
          placeholder="Email"
          type="email"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <div class="mb-2 uf-field">
          <CustomSelect
            v-model="novoFornecedor.uf"
            :options="ufOptions"
            :placeholder="'UF'"
            class="w-full input-uf"
          />
        </div>
        <div class="switch-label mb-2">
          <label
            class="switch"
            :class="{ active: novoFornecedor.status }"
          >
            <input
              v-model="novoFornecedor.status"
              type="checkbox"
            >
            <span class="knob" />
          </label>
          <span class="text-sm status-text">{{ novoFornecedor.status ? 'ATIVO' : 'INATIVO' }}</span>
        </div>
        <div class="drawer-actions flex justify-end gap-3 mt-4">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeCreateFornecedor"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary"
            :disabled="submittingFornecedor"
            :loading="submittingFornecedor"
          >
            {{ submittingFornecedor ? 'Salvando...' : 'Adicionar' }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>

    <ConfirmDialog
      :open="confirmDeleteOpen"
      title="Excluir fornecedor"
      :message="confirmDeleteMessage"
      :loading="deletingFornecedor"
      confirm-label="Excluir"
      @cancel="cancelDeleteFornecedor"
      @confirm="confirmDeleteFornecedor"
    />
  </div>
</template>

<script>
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import CustomSelect from '../components/ui/CustomSelect.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import PaginationPremium from '../components/ui/PaginationPremium.vue'
import api from '../services/api'
import ActionDropdown from '../components/ui/ActionDropdown.vue'

export default {
  name: 'Fornecedores',
  components: {
    BaseTable,
    BaseButton,
    CustomSelect,
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
      fornecedores: [],
      loading: true,
      pageSize: 25,
      currentPageFornecedores: 1,
      totalCount: 0,
      showCreateFornecedor: false,
      submittingFornecedor: false,
      fornecedorFeedback: { message: '', type: 'info' },
      novoFornecedor: { razao_social: '', endereco: '', numero: '', complemento: '', bairro: '', cep: '', cidade: '', cnpj: '', telefone: '', email: '', uf: '', status: true },
      editingFornecedorIndex: null,
      confirmDeleteOpen: false,
      confirmDeleteMessage: '',
      deletingFornecedor: false,
      fornecedorToDelete: null,
      query: '',
      statusFilter: '',
    }
  },
  computed: {
    fornecedorCols() { return [ { key: 'razao_social', label: 'Razão Social' }, { key: 'cnpj', label: 'CNPJ' }, { key: 'endereco', label: 'Endereço' }, { key: 'uf', label: 'UF' }, { key: 'telefone', label: 'Telefone' }, { key: 'email', label: 'Email' }, { key: 'status', label: 'Status' }, { key: 'acoes', label: 'Ações' } ] },
    visibleFornecedores() { return (this.fornecedores||[]).filter(f=> f && (f.razao_social||f.cnpj||f.telefone||f.email)) },
    filteredFornecedores() {
      return this.visibleFornecedores.filter(f => {
        const matchesQuery = !this.query || f.razao_social?.toLowerCase().includes(this.query.toLowerCase()) || f.cnpj?.includes(this.query)
        const matchesStatus = !this.statusFilter || (this.statusFilter === 'ATIVO' ? f.status : !f.status)
        return matchesQuery && matchesStatus
      })
    },
    hasActiveFilter() { return this.query || this.statusFilter },
    activeFornecedoresCount() { return this.filteredFornecedores.filter(f => f.status).length },
    inactiveFornecedoresCount() { return this.filteredFornecedores.filter(f => !f.status).length },
    totalPagesFornecedores() { return Math.max(1, Math.ceil(this.filteredFornecedores.length / this.pageSize)) },
    paginatedFornecedores() { const s=(this.currentPageFornecedores-1)*this.pageSize; return this.filteredFornecedores.slice(s,s+this.pageSize) },
    ufOptions() {
      return [
        { value: '', label: 'UF' },
        ...['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'].map(uf => ({ value: uf, label: uf }))
      ]
    },
  },
  mounted() {
    this.fetchFornecedores()
  },
  methods: {
    onQuery() {
      this.currentPageFornecedores = 1
    },
    clearFilters() {
      this.query = ''
      this.statusFilter = ''
      this.currentPageFornecedores = 1
    },
    getRowActions(row) {
      return [
        { key: 'editar', label: 'Editar' },
        { key: 'excluir', label: 'Excluir', danger: true },
      ]
    },
    handleRowAction(action, row) {
      if (action === 'editar') this.editFornecedor(row)
      if (action === 'excluir') this.deleteFornecedor(row)
    },
    async fetchFornecedores() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/fornecedores')
        this.fornecedores = Array.isArray(res.data) ? res.data : (res.data.items || [])
      } catch (e) {
        console.error('Erro ao carregar fornecedores', e)
        this.fornecedores = []
      } finally {
        this.loading = false
      }
    },
    prevFornecedores() { if (this.currentPageFornecedores>1) this.currentPageFornecedores-- },
    nextFornecedores() { if (this.currentPageFornecedores < this.totalPagesFornecedores) this.currentPageFornecedores++ },
    goToFornecedores(n){ this.currentPageFornecedores = Math.min(Math.max(1,n), this.totalPagesFornecedores) },
    async refreshFornecedores() {
      this.currentPageFornecedores = 1
      await this.fetchFornecedores()
    },
    openCreateFornecedor(){ this.editingFornecedorIndex = null; this.fornecedorFeedback = { message: '', type: 'info' }; this.novoFornecedor = { razao_social: '', endereco: '', numero: '', complemento: '', bairro: '', cep: '', cidade: '', cnpj: '', telefone: '', email: '', uf: '', status: true }; this.showCreateFornecedor=true },
    closeCreateFornecedor(){ this.showCreateFornecedor=false; this.submittingFornecedor=false; this.fornecedorFeedback = { message: '', type: 'info' }; this.novoFornecedor={razao_social:'',endereco:'',numero:'',complemento:'',bairro:'',cep:'',cidade:'',cnpj:'',telefone:'',email:'',uf:'',status:true}; this.editingFornecedorIndex = null },
    async createFornecedor(){
      this.submittingFornecedor = true
      this.fornecedorFeedback = { message: 'Salvando fornecedor...', type: 'info' }
      try {
        const payload = {
          razao_social: this.novoFornecedor.razao_social,
          endereco: this.novoFornecedor.endereco,
          numero: this.novoFornecedor.numero,
          complemento: this.novoFornecedor.complemento,
          bairro: this.novoFornecedor.bairro,
          cep: this.onlyDigits(this.novoFornecedor.cep),
          cidade: this.novoFornecedor.cidade,
          cnpj: this.onlyDigits(this.novoFornecedor.cnpj),
          telefone: this.novoFornecedor.telefone,
          email: this.novoFornecedor.email,
          uf: this.novoFornecedor.uf || null,
          status: !!this.novoFornecedor.status,
        }
        const res = await api.post('/api/v1/fornecedores', payload)
        if (res.data && res.data.id) {
          this.fornecedores.unshift({ id: res.data.id, ...payload })
        }
        this.currentPageFornecedores = 1
        await this.fetchFornecedores()
        this.fornecedorFeedback = { message: 'Fornecedor salvo com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateFornecedor(), 300)
      } catch (e) {
        console.error('Erro ao criar fornecedor', e)
        const backendError = e?.response?.data?.error
        this.fornecedorFeedback = { message: backendError || 'Falha ao salvar fornecedor. Tente novamente.', type: 'error' }
      } finally {
        this.submittingFornecedor = false
      }
    },
    editFornecedor(row){ const idx = this.fornecedores.indexOf(row); if (idx!==-1) { this.editingFornecedorIndex = idx; this.novoFornecedor = { ...row, cep: this.applyCepMask(row.cep), cnpj: this.applyCnpjMask(row.cnpj), status: !!row.status }; this.showCreateFornecedor = true } },
    deleteFornecedor(row){
      if (!row?.id) return
      this.fornecedorToDelete = row
      this.confirmDeleteMessage = `Deseja excluir o fornecedor \"${row.razao_social || row.id}\"?`
      this.confirmDeleteOpen = true
    },
    cancelDeleteFornecedor() {
      this.confirmDeleteOpen = false
      this.confirmDeleteMessage = ''
      this.fornecedorToDelete = null
      this.deletingFornecedor = false
    },
    async confirmDeleteFornecedor() {
      if (!this.fornecedorToDelete?.id) return
      this.deletingFornecedor = true
      try {
        await api.delete(`/api/v1/fornecedores/${this.fornecedorToDelete.id}`)
        this.currentPageFornecedores = 1
        await this.fetchFornecedores()
      } catch (e) {
        console.error('Erro ao excluir fornecedor', e)
        this.fornecedorFeedback = { message: e?.response?.data?.error || 'Não foi possível excluir o fornecedor.', type: 'error' }
      } finally {
        this.cancelDeleteFornecedor()
      }
    },
    formatAddress(row) {
      const parts = [
        row.endereco,
        row.numero,
        row.complemento,
        row.bairro,
        row.cidade,
        row.cep ? `CEP ${row.cep}` : null,
      ].filter(Boolean)
      return parts.length ? parts.join(', ') : '-'
    },
    onlyDigits(value) {
      return String(value || '').replace(/\D+/g, '')
    },
    applyCepMask(value) {
      const digits = this.onlyDigits(value).slice(0, 8)
      return digits.replace(/(\d{5})(\d{0,3})/, (_, a, b) => (b ? `${a}-${b}` : a))
    },
    applyCnpjMask(value) {
      const digits = this.onlyDigits(value).slice(0, 14)
      return digits
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
    },
    onFornecedorCepInput() {
      this.novoFornecedor.cep = this.applyCepMask(this.novoFornecedor.cep)
    },
    onFornecedorCnpjInput() {
      this.novoFornecedor.cnpj = this.applyCnpjMask(this.novoFornecedor.cnpj)
    }
  }
}
</script>

<style scoped>
/* Add any custom styles here */
</style>
