<template>
  <div>
    <h1 class="text-2xl font-bold mb-4 text-gray-800">Fornecedores</h1>

    <!-- Fornecedores Table -->
    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800">Lista de Fornecedores</h2>
        <BaseButton class="btn-primary" @click="openCreateFornecedor">Adicionar Fornecedor</BaseButton>
      </div>
      <div v-if="visibleFornecedores.length > 0" class="panel-inner">
        <BaseTable :columns="fornecedorCols" :rows="paginatedFornecedores">
          <template #razao_social="{ row }">{{ row.razao_social }}</template>
          <template #cnpj="{ row }">{{ row.cnpj || '-' }}</template>
          <template #inscricao_estadual="{ row }">{{ row.inscricao_estadual || '-' }}</template>
          <template #uf="{ row }">{{ row.uf || '-' }}</template>
          <template #telefone="{ row }">{{ row.telefone || '-' }}</template>
          <template #email="{ row }">{{ row.email || '-' }}</template>
          <template #status="{ row }">{{ row.status ? 'ATIVO' : 'INATIVO' }}</template>
          <template #acoes="{ row }">
            <div class="flex items-center gap-2">
              <BaseButton variant="ghost" @click="editFornecedor(row)">Editar</BaseButton>
              <BaseButton variant="destructive" @click="deleteFornecedor(row)">Excluir</BaseButton>
            </div>
          </template>
        </BaseTable>
        <div class="mt-2 flex items-center justify-end gap-2">
          <BaseButton class="btn-secondary" :disabled="currentPageFornecedores<=1" @click="prevFornecedores">Anterior</BaseButton>
          <template v-for="p in Math.min(5, totalPagesFornecedores)" :key="p">
            <button class="px-2 py-1 rounded text-sm" :class="{ 'bg-gray-200': currentPageFornecedores===p }" @click="goToFornecedores(p)">{{ p }}</button>
          </template>
          <BaseButton class="btn-secondary" :disabled="currentPageFornecedores>=totalPagesFornecedores" @click="nextFornecedores">Próximo</BaseButton>
        </div>
      </div>
      <div v-else class="py-12 text-center">
        <p class="text-lg font-medium mb-4 text-gray-800">Nenhum fornecedor cadastrado.</p>
        <p class="text-sm muted mb-6">Adicione fornecedores para começar.</p>
        <div class="flex justify-center">
          <BaseButton class="btn-primary" @click="openCreateFornecedor">Adicionar Fornecedor</BaseButton>
        </div>
      </div>
    </div>

    <!-- Create Fornecedor Modal -->
    <div v-if="showCreateFornecedor" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40" @click="closeCreateFornecedor" aria-hidden="true"></div>
      <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-md p-8">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Adicionar Fornecedor</h3>
        <form @submit.prevent="createFornecedor" class="grid grid-cols-1 gap-3">
          <input v-model="novoFornecedor.razao_social" placeholder="Razão Social" class="p-3 border border-gray-300 rounded-xl" required />
          <input v-model="novoFornecedor.cnpj" placeholder="CNPJ" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novoFornecedor.inscricao_estadual" placeholder="Inscrição Estadual" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novoFornecedor.telefone" placeholder="Telefone" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novoFornecedor.email" placeholder="Email" type="email" class="p-3 border border-gray-300 rounded-xl" />
          <div class="mb-2" style="min-width:88px">
            <CustomSelect
              v-model="novoFornecedor.uf"
              :options="ufOptions"
              :placeholder="'UF'"
              class="w-full input-uf"
            />
          </div>
          <div class="switch-label mb-2">
            <label class="switch" :class="{ active: novoFornecedor.status }">
              <input type="checkbox" v-model="novoFornecedor.status" />
              <span class="knob"></span>
            </label>
            <span class="text-sm status-text">{{ novoFornecedor.status ? 'ATIVO' : 'INATIVO' }}</span>
          </div>
          <div class="flex justify-end gap-3 mt-4">
            <BaseButton type="button" class="btn-secondary" @click="closeCreateFornecedor">Cancelar</BaseButton>
            <BaseButton type="submit" class="btn-primary">Adicionar</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import CustomSelect from '../components/ui/CustomSelect.vue'
import api from '../services/api'

export default {
  name: 'Fornecedores',
  components: {
    BaseTable,
    BaseButton,
    CustomSelect,
  },
  data() {
    return {
      fornecedores: [],
      pageSize: 25,
      currentPageFornecedores: 1,
      showCreateFornecedor: false,
      novoFornecedor: { razao_social: '', cnpj: '', inscricao_estadual: '', telefone: '', email: '', uf: '', status: true },
      editingFornecedorIndex: null,
    }
  },
  mounted() {
    this.fetchFornecedores()
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
  methods: {
    async fetchFornecedores() {
      try {
        const res = await api.get('/api/v1/fornecedores')
        this.fornecedores = Array.isArray(res.data) ? res.data : (res.data.items || [])
      } catch (e) {
        console.error('Erro ao carregar fornecedores', e)
        this.fornecedores = []
      }
    },
    prevFornecedores() { if (this.currentPageFornecedores>1) this.currentPageFornecedores-- },
    nextFornecedores() { if (this.currentPageFornecedores < this.totalPagesFornecedores) this.currentPageFornecedores++ },
    goToFornecedores(n){ this.currentPageFornecedores = Math.min(Math.max(1,n), this.totalPagesFornecedores) },
    openCreateFornecedor(){ this.editingFornecedorIndex = null; this.novoFornecedor = { razao_social: '', cnpj: '', inscricao_estadual: '', telefone: '', email: '', uf: '', status: true }; this.showCreateFornecedor=true },
    closeCreateFornecedor(){ this.showCreateFornecedor=false; this.novoFornecedor={razao_social:'',cnpj:'',inscricao_estadual:'',telefone:'',email:'',uf:'',status:true}; this.editingFornecedorIndex = null },
    async createFornecedor(){
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
        this.closeCreateFornecedor()
      } catch (e) {
        console.error('Erro ao criar fornecedor', e)
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
