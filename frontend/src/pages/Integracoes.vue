<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Integrações"
      subtitle="Conecte aplicações terceiras e sincronize dados automaticamente."
    >
      <template #actions>
        <BaseButton
          class="btn-secondary w-full sm:w-auto"
          @click="loadIntegracoes"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          class="btn-primary w-full sm:w-auto"
          @click="openCreateModal"
        >
          Adicionar Integração
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Integrações Ativas
        </div>
        <div class="saas-kpi-value">
          {{ visibleIntegracoes.length }}
        </div>
        <div class="saas-kpi-help">
          Aplicações conectadas
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
          Navegação de integrações
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Ações
        </div>
        <div class="saas-kpi-value">
          {{ editMode ? 'Edição' : 'Novo' }}
        </div>
        <div class="saas-kpi-help">
          Modo operacional
        </div>
      </article>
    </section>

    <div class="section-block">
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800 section-title">
          Integrações Configuradas
        </h2>
      </div>
      <div
        v-if="visibleIntegracoes.length > 0"
        class="panel-inner content-card"
      >
        <BaseTable
          :columns="tableCols"
          :rows="paginatedIntegracoes"
        >
          <template #nome="{ row }">
            {{ row.nome || '-' }}
          </template>
          <template #tipo="{ row }">
            {{ row.tipo || '-' }}
          </template>
          <template #status="{ row }">
            <span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="row.status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
              {{ row.status === 'ativo' ? 'Ativo' : 'Inativo' }}
            </span>
          </template>
          <template #ultimo_sincronismo="{ row }">
            {{ row.ultimo_sincronismo || 'Nunca' }}
          </template>
          <template #acoes="{ row }">
            <div class="flex items-center gap-2">
              <BaseButton
                variant="ghost"
                @click="openEdit(row)"
              >
                Editar
              </BaseButton>
              <BaseButton
                variant="destructive"
                @click="deleteIntegracao(row)"
              >
                Excluir
              </BaseButton>
            </div>
          </template>
        </BaseTable>
        <PaginationPremium
          :current-page.sync="currentPage"
          :page-size.sync="pageSize"
          :total="visibleIntegracoes.length"
        />
      </div>
      <ListState
        :loading="loading"
        :has-data="visibleIntegracoes.length > 0"
        loading-text="Carregando integrações..."
        empty-title="Nenhuma integração configurada."
        empty-message="Adicione integrações para começar."
        action-label="Adicionar Integração"
        @action="openCreateModal"
      />
    </div>

    <SideDrawer
      :open="showCreateModal"
      :title="editMode ? 'Editar Integração' : 'Adicionar Integração'"
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
          v-model="form.nome"
          placeholder="Nome da Integração"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
          required
        >
        <input
          v-model="form.tipo"
          placeholder="Tipo (ex: API, Webhook, OAuth)"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
          required
        >
        <input
          v-model="form.chave_api"
          placeholder="Chave API"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
        >
        <select
          v-model="form.status"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
        >
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
        </select>
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
            {{ submitting ? 'Salvando...' : (editMode ? 'Atualizar' : 'Adicionar') }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>

    <ConfirmDialog
      :open="confirmDeleteOpen"
      title="Excluir integração"
      :message="`Deseja excluir a integração '${integracaoToDelete?.nome || 'esta integração'}'?`"
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

export default {
  name: 'Integracoes',
  components: {
    BaseTable,
    BaseButton,
    SideDrawer,
    PageHero,
    ListState,
    FormFeedback,
    ConfirmDialog,
    PaginationPremium,
  },
  data() {
    return {
      integracoes: [],
      loading: true,
      pageSize: 25,
      currentPage: 1,
      showCreateModal: false,
      submitting: false,
      editMode: false,
      feedback: { message: '', type: 'info' },
      form: {
        nome: '',
        tipo: '',
        chave_api: '',
        status: 'ativo',
      },
      editingId: null,
      confirmDeleteOpen: false,
      deleting: false,
      integracaoToDelete: null,
    }
  },
  computed: {
    tableCols() {
      return [
        { key: 'nome', label: 'Nome' },
        { key: 'tipo', label: 'Tipo' },
        { key: 'status', label: 'Status' },
        { key: 'ultimo_sincronismo', label: 'Último Sincronismo' },
        { key: 'acoes', label: 'Ações' },
      ]
    },
    visibleIntegracoes() {
      return (this.integracoes || []).filter(i => i && (i.nome || i.tipo))
    },
    paginatedIntegracoes() {
      const start = (this.currentPage - 1) * this.pageSize
      return this.visibleIntegracoes.slice(start, start + this.pageSize)
    },
  },
  mounted() {
    this.loadIntegracoes()
  },
  methods: {
    async loadIntegracoes() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/integracoes')
        this.integracoes = Array.isArray(res.data) ? res.data : (res.data.items || [])
        this.currentPage = 1
      } catch (e) {
        console.error('Erro ao carregar integrações', e)
        this.integracoes = []
      } finally {
        this.loading = false
      }
    },
    openCreateModal() {
      this.editMode = false
      this.editingId = null
      this.form = { nome: '', tipo: '', chave_api: '', status: 'ativo' }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    closeCreateModal() {
      this.showCreateModal = false
      this.submitting = false
      this.feedback = { message: '', type: 'info' }
      this.form = { nome: '', tipo: '', chave_api: '', status: 'ativo' }
      this.editMode = false
      this.editingId = null
    },
    openEdit(integracao) {
      this.editMode = true
      this.editingId = integracao.id
      this.form = {
        nome: integracao.nome || '',
        tipo: integracao.tipo || '',
        chave_api: integracao.chave_api || '',
        status: integracao.status || 'ativo',
      }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    async submitForm() {
      this.submitting = true
      this.feedback = { message: this.editMode ? 'Atualizando...' : 'Salvando...', type: 'info' }
      try {
        if (this.editMode) {
          await api.put(`/api/v1/integracoes/${this.editingId}`, this.form)
        } else {
          await api.post('/api/v1/integracoes', this.form)
        }
        this.feedback = { message: this.editMode ? 'Integração atualizada com sucesso.' : 'Integração adicionada com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateModal(), 300)
        await this.loadIntegracoes()
      } catch (e) {
        const { getMessage } = useApiError()
        this.feedback = { message: getMessage(e, 'Erro ao salvar integração.'), type: 'error' }
      } finally {
        this.submitting = false
      }
    },
    deleteIntegracao(integracao) {
      this.integracaoToDelete = integracao
      this.confirmDeleteOpen = true
    },
    cancelDelete() {
      this.confirmDeleteOpen = false
      this.deleting = false
      this.integracaoToDelete = null
    },
    async confirmDelete() {
      if (!this.integracaoToDelete?.id) return
      this.deleting = true
      try {
        await api.delete(`/api/v1/integracoes/${this.integracaoToDelete.id}`)
        this.currentPage = 1
        await this.loadIntegracoes()
      } catch (e) {
        console.error('Erro ao excluir integração', e)
      } finally {
        this.cancelDelete()
      }
    },
  },
}
</script>
