<template>
  <div class="page-shell page-fade">
    <PageHero
      title="Produtos"
      subtitle="Organize o catálogo com estrutura pronta para escala e operação diária."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary w-full sm:w-auto"
          @click="loadProdutos"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          class="btn-primary w-full sm:w-auto"
          @click="openCreateModal"
        >
          Adicionar Produto
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Produtos Ativos
        </div>
        <div class="saas-kpi-value">
          {{ totalCount }}
        </div>
        <div class="saas-kpi-help">
          Base de catálogo
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
          Navegação de inventário
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Ações
        </div>
        <div class="saas-kpi-value">
          {{ editMode ? 'Edição' : 'Cadastro' }}
        </div>
        <div class="saas-kpi-help">
          Modo de trabalho atual
        </div>
      </article>
    </section>

    <div
      v-if="visibleProdutos.length > 0"
      class="panel-inner"
    >
      <BaseTable
        :columns="tableCols"
        :rows="paginatedProdutos"
      >
        <template #codigo="{ row }">
          {{ row.codigo || '-' }}
        </template>
        <template #descricao="{ row }">
          {{ row.descricao || '-' }}
        </template>
        <template #unidade="{ row }">
          {{ row.unidade || '-' }}
        </template>
        <template #acoes="{ row }">
          <BaseButton
            variant="ghost"
            @click="openEdit(row)"
          >
            Atualizar
          </BaseButton>
        </template>
      </BaseTable>
    </div>

    <ListState
      :loading="loading"
      :has-data="visibleProdutos.length > 0"
      loading-text="Carregando produtos..."
      empty-title="Nenhum produto encontrado."
      empty-message="Adicione produtos ao estoque para começar."
      action-label="Adicionar Produto"
      @action="openCreateModal"
    />

    <div
      v-if="visibleProdutos.length > 0"
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
      :title="editMode ? 'Atualizar Produto' : 'Adicionar Produto'"
      @close="closeCreateModal"
    >
      <form
        class="drawer-form space-y-5"
        @submit.prevent="submitProduto"
      >
        <FormFeedback
          :message="productFeedback.message"
          :type="productFeedback.type"
        />
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Descrição</label>
          <input
            v-model="produtoForm.descricao"
            placeholder="Descrição"
            class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150"
            required
          >
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Descrição Complementar</label>
          <input
            v-model="produtoForm.descricao_complementar"
            placeholder="Descrição Complementar"
            class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150"
          >
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Unidade de Compra</label>
          <input
            v-model="produtoForm.unidade"
            placeholder="Unidade de Compra"
            class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150"
            required
          >
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
          <div class="flex items-center gap-3 mt-1">
            <button
              type="button"
              :class="['relative w-16 h-7 rounded-full transition-colors duration-200 focus:outline-none', produtoForm.status === 'ativo' ? 'bg-green-500' : 'bg-gray-300']"
              @click="produtoForm.status = produtoForm.status === 'ativo' ? 'inativo' : 'ativo'"
            >
              <span :class="['absolute left-0 top-0 w-8 h-7 bg-white rounded-full shadow transition-transform duration-200', produtoForm.status === 'ativo' ? 'translate-x-8' : '']" />
              <span
                v-if="produtoForm.status === 'inativo'"
                class="absolute left-2 top-2 text-xs font-bold"
              >Inativo</span>
            </button>
            <span
              class="text-xs font-semibold"
              :class="produtoForm.status === 'ativo' ? 'text-green-700' : 'text-gray-500'"
            >{{ produtoForm.status === 'ativo' ? 'Ativo' : 'Inativo' }}</span>
          </div>
        </div>
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
            :disabled="submittingProduct"
            :loading="submittingProduct"
          >
            {{ submittingProduct ? 'Salvando...' : (editMode ? 'Salvar' : 'Adicionar') }}
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
import { useToast } from '../composables/useToast'

export default {
  components: { BaseTable, BaseButton, SideDrawer, PageHero, ListState, FormFeedback },
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
    const submittingProduct = ref(false)
    const productFeedback = ref({ message: '', type: 'info' })

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

    function openCreateModal() { editMode.value = false; productFeedback.value = { message: '', type: 'info' }; produtoForm.value = { id: null, codigo: '', descricao: '', unidade: '', status: 'ativo' }; showCreateModal.value = true }
    function openEdit(row) { editMode.value = true; productFeedback.value = { message: '', type: 'info' }; produtoForm.value = { ...row }; showCreateModal.value = true }
    function closeCreateModal() { showCreateModal.value = false; submittingProduct.value = false; productFeedback.value = { message: '', type: 'info' }; produtoForm.value = { id: null, codigo: '', descricao: '', unidade: '', status: 'ativo' } }

    async function submitProduto() {
      submittingProduct.value = true
      productFeedback.value = { message: editMode.value ? 'Atualizando produto...' : 'Criando produto...', type: 'info' }
      try {
        if (editMode.value && produtoForm.value.id) {
          await api.put(`/produtos/${produtoForm.value.id}`, produtoForm.value)
          useToast().notify('Produto atualizado', { type: 'success' })
        } else {
          await api.post('/produtos', produtoForm.value)
          useToast().notify('Produto criado', { type: 'success' })
        }
        productFeedback.value = { message: 'Produto salvo com sucesso.', type: 'success' }
        setTimeout(() => closeCreateModal(), 300)
        loadProdutos()
      } catch (e) {
        console.error('Erro ao salvar produto:', e)
        productFeedback.value = { message: 'Falha ao salvar produto. Revise os dados.', type: 'error' }
        useToast().notify('Falha ao salvar produto', { type: 'error' })
      } finally {
        submittingProduct.value = false
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
      submittingProduct,
      productFeedback,
      editMode,
      openEdit
    }
  }
}
</script>
