<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Produtos"
      subtitle="Organize o catálogo com estrutura pronta para escala e operação diária."
    >
      <template #actions>
        <div class="flex flex-col gap-3 w-full sm:flex-row sm:items-end">
          <input
            v-model="query"
            placeholder="Buscar por nome ou tipo"
            class="p-3 border border-gray-300 rounded-xl w-full sm:min-w-[260px] hero-control"
            @input="onQuery"
          >
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
              @click="loadProdutos"
            >
              Atualizar
            </BaseButton>
            <BaseButton
              class="btn-primary w-full sm:w-auto whitespace-nowrap"
              @click="openCreateModal"
            >
              + Produto
            </BaseButton>
          </div>
        </div>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Produtos
        </div>
        <div class="saas-kpi-value">
          {{ filteredProdutos.length }}
        </div>
        <div class="saas-kpi-help">
          Registros exibidos
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Produtos Ativos
        </div>
        <div class="saas-kpi-value">
          {{ activeProdutosCount }}
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
          {{ inactiveProdutosCount }}
        </div>
        <div class="saas-kpi-help">
          Suspensos
        </div>
      </article>
    </section>

    <div
      v-if="visibleProdutos.length > 0"
      class="panel-inner content-card"
    >
      <BaseTable
        :columns="tableCols"
        :rows="paginatedProdutos"
      >
        <template #codigo="{ row }">
          {{ row.tipo || '-' }}
        </template>
        <template #descricao="{ row }">
          {{ row.nome || '-' }}
        </template>
        <template #unidade="{ row }">
          {{ row.unidade || '-' }}
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

    <!-- Pagination Premium -->
    <div v-if="visibleProdutos.length > 0" class="mt-6">
      <div class="panel-inner content-card">
        <PaginationPremium
          :current-page.sync="currentPage"
          :page-size.sync="pageSize"
          :total="filteredProdutos.length"
          @update:current-page="currentPage = $event"
          @update:page-size="pageSize = $event"
        />
      </div>
    </div>

    <ListState
      :loading="loading"
      :has-data="visibleProdutos.length > 0"
      loading-text="Carregando produtos..."
      empty-title="Nenhum produto encontrado."
      empty-message="Adicione produtos para começar."
      action-label="Adicionar Produto"
      @action="openCreateModal"
    />

    <!-- Old pagination removed - using PaginationPremium above -->

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
          <label class="block text-sm font-semibold text-gray-700 mb-1">Nome</label>
          <input
            v-model="produtoForm.nome"
            placeholder="Nome do produto"
            class="p-3 border border-gray-300 rounded-xl w-full focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-150"
            required
          >
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo</label>
          <input
            v-model="produtoForm.tipo"
            placeholder="Tipo"
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
import PaginationPremium from '../components/ui/PaginationPremium.vue'
import ActionDropdown from '../components/ui/ActionDropdown.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import { useToast } from '../composables/useToast'

export default {
  components: { BaseTable, BaseButton, SideDrawer, PageHero, ListState, FormFeedback, PaginationPremium, ActionDropdown, EmptyState },
  setup() {
    const produtos = ref([])
    const loading = ref(false)

    const query = ref('')
    const pageSize = ref(25)
    const currentPage = ref(1)
    const totalCount = ref(0)

    const produtoForm = ref({ id: null, nome: '', tipo: '', unidade: '', custo_medio: 0, status: 'ativo' })
    const showCreateModal = ref(false)
    const editMode = ref(false)
    const submittingProduct = ref(false)
    const productFeedback = ref({ message: '', type: 'info' })

    const tableCols = [
      { key: 'codigo', label: 'Tipo' },
      { key: 'descricao', label: 'Nome' },
      { key: 'unidade', label: 'Unidade' },
      { key: 'status', label: 'Status' },
      { key: 'acoes', label: 'Ações' }
    ]

    async function loadProdutos() {
      loading.value = true
      try {
        const res = await api.get('/api/v1/produtos', { params: { q: query.value, page: currentPage.value, per_page: pageSize } })
        const items = Array.isArray(res.data?.items) ? res.data.items : []
        produtos.value = items.map(item => ({
          id: item.id ?? null,
          nome: item.nome ?? '',
          tipo: item.tipo ?? '',
          unidade: item.unidade ?? '',
          custo_medio: Number(item.custo_medio ?? 0),
        }))
        const parsedTotal = Number(res.data?.total)
        totalCount.value = Number.isFinite(parsedTotal) ? parsedTotal : produtos.value.length
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
        const hasCodigo = p.tipo && String(p.tipo).trim().length > 0
        const hasDescricao = p.nome && String(p.nome).trim().length > 0
        const hasUnidade = p.unidade && String(p.unidade).trim().length > 0
        const hasStatus = p.id !== null && p.id !== undefined
        return hasCodigo || hasDescricao || hasUnidade || hasStatus
      })
    })

    const filteredProdutos = computed(() => {
      return visibleProdutos.value.filter(p => {
        const matchesQuery = !query.value || 
          p.nome?.toLowerCase().includes(query.value.toLowerCase()) ||
          p.tipo?.toLowerCase().includes(query.value.toLowerCase())
        return matchesQuery
      })
    })

    const hasActiveFilter = computed(() => query.value)
    const activeProdutosCount = computed(() => filteredProdutos.value.filter(p => p.status !== false).length)
    const inactiveProdutosCount = computed(() => filteredProdutos.value.filter(p => p.status === false).length)

    const totalPages = computed(() => Math.max(1, Math.ceil(filteredProdutos.value.length / pageSize.value)))
    const paginatedProdutos = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      return filteredProdutos.value.slice(start, start + pageSize.value)
    })

    function goToPage(n) { currentPage.value = Math.min(Math.max(1, n), totalPages.value); loadProdutos() }
    function nextPage() { if (currentPage.value < totalPages.value) { currentPage.value++; loadProdutos() } }
    function prevPage() { if (currentPage.value > 1) { currentPage.value--; loadProdutos() } }

    function onQuery() {
      currentPage.value = 1
    }

    function clearFilters() {
      query.value = ''
      currentPage.value = 1
    }

    function getRowActions(row) {
      return [
        { key: 'editar', label: 'Editar' },
        { key: 'excluir', label: 'Excluir', danger: true },
      ]
    }

    function handleRowAction(action, row) {
      if (action === 'editar') openEdit(row)
      if (action === 'excluir') deleteProduto(row)
    }

    function openCreateModal() { editMode.value = false; productFeedback.value = { message: '', type: 'info' }; produtoForm.value = { id: null, nome: '', tipo: '', unidade: '', custo_medio: 0, status: 'ativo' }; showCreateModal.value = true }
    function openEdit(row) { editMode.value = true; productFeedback.value = { message: '', type: 'info' }; produtoForm.value = { ...row }; showCreateModal.value = true }
    function closeCreateModal() { showCreateModal.value = false; submittingProduct.value = false; productFeedback.value = { message: '', type: 'info' }; produtoForm.value = { id: null, nome: '', tipo: '', unidade: '', custo_medio: 0, status: 'ativo' } }

    async function submitProduto() {
      submittingProduct.value = true
      productFeedback.value = { message: editMode.value ? 'Atualizando produto...' : 'Criando produto...', type: 'info' }
      try {
        if (editMode.value && produtoForm.value.id) {
          await api.put(`/api/v1/produtos/${produtoForm.value.id}`, produtoForm.value)
          useToast().notify('Produto atualizado', { type: 'success' })
        } else {
          await api.post('/api/v1/produtos', produtoForm.value)
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

    async function deleteProduto(row) {
      if (!row?.id) return
      const ok = window.confirm(`Deseja excluir o produto "${row.nome || row.id}"?`)
      if (!ok) return

      try {
        await api.delete(`/api/v1/produtos/${row.id}`)
        useToast().notify('Produto excluído com sucesso', { type: 'success' })
        await loadProdutos()
      } catch (e) {
        console.error('Erro ao excluir produto:', e)
        useToast().notify(e?.response?.data?.error || 'Falha ao excluir produto', { type: 'error' })
      }
    }

    onMounted(() => loadProdutos())

    return {
      produtos,
      loading,
      pageSize,
      currentPage,
      totalCount,
      tableCols,
      visibleProdutos,
      filteredProdutos,
      paginatedProdutos,
      totalPages,
      query,
      hasActiveFilter,
      activeProdutosCount,
      inactiveProdutosCount,
      onQuery,
      clearFilters,
      getRowActions,
      handleRowAction,
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
      openEdit,
      deleteProduto
    }
  }
}
</script>
