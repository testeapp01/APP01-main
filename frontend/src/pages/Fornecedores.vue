<template>
  <div class="page-shell">
    <PageHero
      title="Fornecedores"
      subtitle="Centralize fornecedores, contatos e disponibilidade de forma confiável."
    >
      <template #actions>
        <BaseButton
          class="btn-primary"
          @click="openCreateFornecedor"
        >
          Adicionar Fornecedor
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Fornecedores
        </div>
        <div class="saas-kpi-value">
          {{ visibleFornecedores.length }}
        </div>
        <div class="saas-kpi-help">
          Cadastros ativos na listagem
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Página Atual
        </div>
        <div class="saas-kpi-value">
          {{ currentPageFornecedores }}
        </div>
        <div class="saas-kpi-help">
          Controle de navegação
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Ação Primária
        </div>
        <div class="saas-kpi-value">
          Cadastro
        </div>
        <div class="saas-kpi-help">
          Fluxo contextual em painel lateral
        </div>
      </article>
    </section>

    <!-- Fornecedores Table -->
    <div class="mb-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800">
          Lista de Fornecedores
        </h2>
      </div>
      <div
        v-if="visibleFornecedores.length > 0"
        class="panel-inner"
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
          <template #inscricao_estadual="{ row }">
            {{ row.inscricao_estadual || '-' }}
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
            {{ row.status ? 'ATIVO' : 'INATIVO' }}
          </template>
          <template #acoes="{ row }">
            <div class="flex items-center gap-2">
              <BaseButton
                variant="ghost"
                @click="editFornecedor(row)"
              >
                Editar
              </BaseButton>
              <BaseButton
                variant="destructive"
                @click="deleteFornecedor(row)"
              >
                Excluir
              </BaseButton>
            </div>
          </template>
        </BaseTable>
        <div class="mt-2 page-pagination">
          <BaseButton
            class="btn-secondary"
            :disabled="currentPageFornecedores<=1"
            @click="prevFornecedores"
          >
            Anterior
          </BaseButton>
          <template
            v-for="p in Math.min(5, totalPagesFornecedores)"
            :key="p"
          >
            <button
              type="button"
              class="page-number"
              :class="{ 'is-active': currentPageFornecedores===p }"
              @click="goToFornecedores(p)"
            >
              {{ p }}
            </button>
          </template>
          <BaseButton
            class="btn-secondary"
            :disabled="currentPageFornecedores>=totalPagesFornecedores"
            @click="nextFornecedores"
          >
            Próximo
          </BaseButton>
        </div>
      </div>
      <ListState
        :loading="loading"
        :has-data="visibleFornecedores.length > 0"
        loading-text="Carregando fornecedores..."
        empty-title="Nenhum fornecedor cadastrado."
        empty-message="Adicione fornecedores para começar."
        action-label="Adicionar Fornecedor"
        @action="openCreateFornecedor"
      />
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
          v-model="novoFornecedor.cnpj"
          placeholder="CNPJ"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novoFornecedor.inscricao_estadual"
          placeholder="Inscrição Estadual"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novoFornecedor.telefone"
          placeholder="Telefone"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novoFornecedor.email"
          placeholder="Email"
          type="email"
          class="p-3 border border-gray-300 rounded-xl"
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
import api from '../services/api'

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
  },
  data() {
    return {
      fornecedores: [],
      loading: true,
      pageSize: 25,
      currentPageFornecedores: 1,
      showCreateFornecedor: false,
      submittingFornecedor: false,
      fornecedorFeedback: { message: '', type: 'info' },
      novoFornecedor: { razao_social: '', cnpj: '', inscricao_estadual: '', telefone: '', email: '', uf: '', status: true },
      editingFornecedorIndex: null,
    }
  },
  computed: {
    fornecedorCols() { return [ { key: 'razao_social', label: 'Razão Social' }, { key: 'cnpj', label: 'CNPJ' }, { key: 'inscricao_estadual', label: 'Inscrição Estadual' }, { key: 'uf', label: 'UF' }, { key: 'telefone', label: 'Telefone' }, { key: 'email', label: 'Email' }, { key: 'status', label: 'Ativo' }, { key: 'acoes', label: 'Ações' } ] },
    visibleFornecedores() { return (this.fornecedores||[]).filter(f=> f && (f.razao_social||f.cnpj||f.telefone||f.email)) },
    totalPagesFornecedores() { return Math.max(1, Math.ceil(this.visibleFornecedores.length / this.pageSize)) },
    paginatedFornecedores() { const s=(this.currentPageFornecedores-1)*this.pageSize; return this.visibleFornecedores.slice(s,s+this.pageSize) },
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
    openCreateFornecedor(){ this.editingFornecedorIndex = null; this.fornecedorFeedback = { message: '', type: 'info' }; this.novoFornecedor = { razao_social: '', cnpj: '', inscricao_estadual: '', telefone: '', email: '', uf: '', status: true }; this.showCreateFornecedor=true },
    closeCreateFornecedor(){ this.showCreateFornecedor=false; this.submittingFornecedor=false; this.fornecedorFeedback = { message: '', type: 'info' }; this.novoFornecedor={razao_social:'',cnpj:'',inscricao_estadual:'',telefone:'',email:'',uf:'',status:true}; this.editingFornecedorIndex = null },
    async createFornecedor(){
      this.submittingFornecedor = true
      this.fornecedorFeedback = { message: 'Salvando fornecedor...', type: 'info' }
      try {
        const payload = {
          razao_social: this.novoFornecedor.razao_social,
          cnpj: this.novoFornecedor.cnpj,
          inscricao_estadual: this.novoFornecedor.inscricao_estadual,
          telefone: this.novoFornecedor.telefone,
          email: this.novoFornecedor.email,
          uf: this.novoFornecedor.uf || null,
          status: !!this.novoFornecedor.status,
        }
        const res = await api.post('/api/v1/fornecedores', payload)
        if (res.data && res.data.id) {
          this.fornecedores.unshift({ id: res.data.id, ...payload })
        }
        this.fornecedorFeedback = { message: 'Fornecedor salvo com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateFornecedor(), 300)
      } catch (e) {
        console.error('Erro ao criar fornecedor', e)
        this.fornecedorFeedback = { message: 'Falha ao salvar fornecedor. Tente novamente.', type: 'error' }
      } finally {
        this.submittingFornecedor = false
      }
    },
    editFornecedor(row){ const idx = this.fornecedores.indexOf(row); if (idx!==-1) { this.editingFornecedorIndex = idx; this.novoFornecedor = { ...row, status: !!row.status }; this.showCreateFornecedor = true } },
    deleteFornecedor(row){ const idx = this.fornecedores.indexOf(row); if (idx!==-1) this.fornecedores.splice(idx,1) }
  }
}
</script>

<style scoped>
/* Add any custom styles here */
</style>
