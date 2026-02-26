cd frontend
npm run dev
# abra http://localhost:PORT/estoque
npm run dev<template>
  <div class="page-fade">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Produtos</h2>
        <p class="mt-2 text-sm text-gray-500">Gerencie os produtos (código, descrição, unidade de compra)</p>
      </div>
      <div class="flex items-center gap-3">
        <BaseButton class="btn-secondary" @click="loadProdutos">Atualizar</BaseButton>
        <BaseButton class="btn-primary" @click="openCreateModal">Adicionar Produto</BaseButton>
      </div>
    </div>

    <div v-if="loading || visibleProdutos.length > 0" class="panel-inner">
      <BaseTable :columns="tableCols" :rows="paginatedProdutos">
      <template #codigo="{ row }">{{ row.codigo || '-' }}</template>
      <template #descricao="{ row }">{{ row.descricao || '-' }}</template>
      <template #unidade="{ row }">{{ row.unidade || '-' }}</template>
      <template #acoes="{ row }">
        <BaseButton variant="ghost" @click="openEdit(row)">Atualizar</BaseButton>
      </template>
      </BaseTable>
    </div>

    <div v-else class="py-12 text-center">
      <p class="text-lg font-medium mb-4 text-gray-800">Nenhum produto encontrado.</p>
      <p class="text-sm muted mb-6">Adicione produtos ao estoque para começar.</p>
      <div class="flex justify-center">
        <BaseButton class="btn-primary" @click="openCreateModal">Adicionar Produto</BaseButton>
      </div>
    </div>

    <div v-if="visibleProdutos.length > 0" class="mt-4">
      <div class="panel-inner flex items-center justify-between">
        <div class="text-sm muted">Mostrando {{ (currentPage-1)*pageSize + 1 }} - {{ Math.min(currentPage*pageSize, totalCount) }} de {{ totalCount }}</div>
        <div class="flex items-center gap-2">
          <BaseButton class="btn-secondary" :disabled="currentPage<=1" @click="prevPage">Anterior</BaseButton>
          <template v-for="p in Math.min(5, totalPages)" :key="p">
            <button class="px-3 py-1 rounded" :class="{ 'bg-gray-200': currentPage===p }" @click="goToPage(p)">{{ p }}</button>
          </template>
          <BaseButton class="btn-secondary" :disabled="currentPage>=totalPages" @click="nextPage">Próximo</BaseButton>
        </div>
      </div>
    </div>

    <!-- Create / Edit Product Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40" @click="closeCreateModal" aria-hidden="true"></div>
      <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-3xl p-10 flex flex-col">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">{{ editMode ? 'Atualizar Produto' : 'Adicionar Produto' }}</h3>
        <form @submit.prevent="submitProduto" class="space-y-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Descrição</label>
            <input v-model="produtoForm.descricao" placeholder="Descrição" class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150" required />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Descrição Complementar</label>
            <input v-model="produtoForm.descricao_complementar" placeholder="Descrição Complementar" class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Unidade de Compra</label>
            <input v-model="produtoForm.unidade" placeholder="Unidade de Compra" class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150" required />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
            <div class="flex items-center gap-3 mt-1">
              <button type="button" @click="produtoForm.status = produtoForm.status === 'ativo' ? 'inativo' : 'ativo'" :class="['relative w-16 h-7 rounded-full transition-colors duration-200 focus:outline-none', produtoForm.status === 'ativo' ? 'bg-green-500' : 'bg-gray-300']">
                <span :class="['absolute left-0 top-0 w-8 h-7 bg-white rounded-full shadow transition-transform duration-200', produtoForm.status === 'ativo' ? 'translate-x-8' : '']"></span>
                <span class="absolute left-2 top-2 text-xs font-bold" v-if="produtoForm.status === 'inativo'">Inativo</span>
              </button>
              <span class="text-xs font-semibold" :class="produtoForm.status === 'ativo' ? 'text-green-700' : 'text-gray-500'">{{ produtoForm.status === 'ativo' ? 'Ativo' : 'Inativo' }}</span>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <BaseButton type="button" class="btn-secondary" @click="closeCreateModal">Cancelar</BaseButton>
            <BaseButton type="submit" class="btn-primary">{{ editMode ? 'Salvar' : 'Adicionar' }}</BaseButton>
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
import { useToast } from '../composables/useToast'

export default {
  components: { BaseTable, BaseButton },
  setup() {
    const produtos = ref([])
    const loading = ref(false)

    const query = ref('')

    const pageSize = 25
    const currentPage = ref(1)
    const totalCount = ref(0)

    const produtoForm = ref({ id: null, codigo: '', descricao: '', unidade: '', status: 'ativo' })
    const showCreateModal = ref(false)
    const editMode = ref(false)

    const tableCols = [
      { key: 'codigo', label: 'Código' },
      { key: 'descricao', label: 'Descrição' },
      { key: 'unidade', label: 'Unidade' },
      { key: 'acoes', label: 'Ações' }
    ]

    async function loadProdutos() {
      loading.value = true
      try {
        const res = await api.get('/produtos', { params: { q: query.value, page: currentPage.value, per_page: pageSize } })
        produtos.value = res.data.items || []
        totalCount.value = res.data.total || 0
      } catch (e) {
        produtos.value = []
        totalCount.value = 0
        console.error('Erro ao carregar produtos:', e)
      } finally {
        loading.value = false
      }
    }

    const visibleProdutos = computed(() => {
      return (produtos.value || []).filter(p => {
        if (!p) return false
        const hasCodigo = p.codigo && String(p.codigo).trim().length > 0
        const hasDescricao = p.descricao && String(p.descricao).trim().length > 0
        const hasUnidade = p.unidade && String(p.unidade).trim().length > 0
        const hasStatus = p.status && String(p.status).trim().length > 0
        return hasCodigo || hasDescricao || hasUnidade || hasStatus
      })
    })

    const totalPages = computed(() => Math.max(1, Math.ceil(totalCount.value / pageSize)))
    const paginatedProdutos = computed(() => visibleProdutos.value)

    function goToPage(n) { currentPage.value = Math.min(Math.max(1, n), totalPages.value); loadProdutos() }
    function nextPage() { if (currentPage.value < totalPages.value) { currentPage.value++; loadProdutos() } }
    function prevPage() { if (currentPage.value > 1) { currentPage.value--; loadProdutos() } }

    function openCreateModal() { editMode.value = false; produtoForm.value = { id: null, codigo: '', descricao: '', unidade: '', status: 'ativo' }; showCreateModal.value = true }
    function openEdit(row) { editMode.value = true; produtoForm.value = { ...row }; showCreateModal.value = true }
    function closeCreateModal() { showCreateModal.value = false; produtoForm.value = { id: null, codigo: '', descricao: '', unidade: '', status: 'ativo' } }

    async function submitProduto() {
      try {
        if (editMode.value && produtoForm.value.id) {
          await api.put(`/produtos/${produtoForm.value.id}`, produtoForm.value)
          useToast().notify('Produto atualizado', { type: 'success' })
        } else {
          await api.post('/produtos', produtoForm.value)
          useToast().notify('Produto criado', { type: 'success' })
        }
        closeCreateModal()
        loadProdutos()
      } catch (e) {
        console.error('Erro ao salvar produto:', e)
        useToast().notify('Falha ao salvar produto', { type: 'error' })
      }
    }

    onMounted(() => loadProdutos())

    return {
      produtos,
      loading,
      pageSize,
      currentPage,
      tableCols,
      visibleProdutos,
      paginatedProdutos,
      totalPages,
      goToPage,
      nextPage,
      prevPage,
      loadProdutos,
      showCreateModal,
      openCreateModal,
      closeCreateModal,
      produtoForm,
      submitProduto,
      editMode,
      openEdit
    }
  }
}
</script>
