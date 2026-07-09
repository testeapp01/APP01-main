<template>
  <div class="page-shell page-fade section-stack">
    <PageHero
      title="Usuários"
      subtitle="Gerencie acesso, permissões e atividades de usuários."
    >
      <template #actions>
        <div class="flex flex-col gap-3 w-full sm:flex-row sm:items-end">
          <input
            v-model="query"
            placeholder="Buscar por nome ou email"
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
              @click="loadUsuarios"
            >
              Atualizar
            </BaseButton>
            <BaseButton
              class="btn-primary w-full sm:w-auto whitespace-nowrap"
              @click="openCreateModal"
            >
              + Usuário
            </BaseButton>
          </div>
        </div>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Total de Usuários
        </div>
        <div class="saas-kpi-value">
          {{ filteredUsuarios.length }}
        </div>
        <div class="saas-kpi-help">
          Registros no período
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Usuários Ativos
        </div>
        <div class="saas-kpi-value">
          {{ activeUsuariosCount }}
        </div>
        <div class="saas-kpi-help">
          Base de acesso
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Inativos
        </div>
        <div class="saas-kpi-value">
          {{ inactiveUsuariosCount }}
        </div>
        <div class="saas-kpi-help">
          Contas desativadas
        </div>
      </article>
    </section>

    <div class="section-block">
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800 section-title">
          Lista de Usuários
        </h2>
      </div>
      <div
        v-if="visibleUsuarios.length > 0"
        class="panel-inner content-card"
      >
        <BaseTable
          :columns="tableCols"
          :rows="paginatedUsuarios"
        >
          <template #nome="{ row }">
            {{ row.nome || '-' }}
          </template>
          <template #email="{ row }">
            {{ row.email || '-' }}
          </template>
          <template #role="{ row }">
            {{ row.role || '-' }}
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
      <ListState
        v-if="loading"
        :loading="loading"
        :has-data="filteredUsuarios.length > 0"
        loading-text="Carregando usuários..."
        empty-title="Nenhum usuário encontrado."
        empty-message="Adicione usuários para começar a gerenciar permissões."
        action-label="Adicionar Usuário"
        @action="openCreateModal"
      />

      <div
        v-if="!loading && filteredUsuarios.length === 0"
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
                Sem usuários para exibir
              </p>
              <p class="text-sm text-slate-600">
                {{ hasActiveFilter ? 'Ajuste os filtros e tente novamente.' : 'Cadastre o primeiro usuário para iniciar.' }}
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
              @click="openCreateModal"
            >
              Adicionar Usuário
            </BaseButton>
          </div>
        </div>
      </div>

      <div
        v-if="filteredUsuarios.length > 0"
        class="mt-6"
      >
        <div class="panel-inner content-card">
          <PaginationPremium
            :current-page.sync="currentPage"
            :page-size.sync="pageSize"
            :total="filteredUsuarios.length"
            @update:current-page="currentPage = $event"
            @update:page-size="pageSize = $event"
          />
        </div>
      </div>
    </div>

    <SideDrawer
      :open="showCreateModal"
      :title="editMode ? 'Editar Usuário' : 'Adicionar Usuário'"
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
          placeholder="Nome"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
          required
        >
        <input
          v-model="form.email"
          placeholder="Email"
          type="email"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
          required
        >
        <select
          v-model="form.role"
          class="p-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
        >
          <option value="admin">Administrador</option>
          <option value="manager">Gerente</option>
          <option value="user">Usuário</option>
          <option value="viewer">Visualizador</option>
        </select>
        <div class="switch-label mb-2">
          <label
            class="switch"
            :class="{ active: form.status }"
          >
            <input
              v-model="form.status"
              type="checkbox"
            >
            <span class="knob" />
          </label>
          <span class="text-sm status-text">{{ form.status ? 'ATIVO' : 'INATIVO' }}</span>
        </div>
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
      title="Excluir usuário"
      :message="`Deseja excluir o usuário '${usuarioToDelete?.nome || 'este usuário'}'?`"
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
  name: 'Usuarios',
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
      usuarios: [],
      loading: true,
      pageSize: 25,
      currentPage: 1,
      showCreateModal: false,
      submitting: false,
      editMode: false,
      feedback: { message: '', type: 'info' },
      form: {
        nome: '',
        email: '',
        role: 'user',
        status: true,
      },
      editingId: null,
      confirmDeleteOpen: false,
      deleting: false,
      usuarioToDelete: null,
      query: '',
      statusFilter: '',
    }
  },
  computed: {
    tableCols() {
      return [
        { key: 'nome', label: 'Nome' },
        { key: 'email', label: 'Email' },
        { key: 'role', label: 'Permissão' },
        { key: 'status', label: 'Status' },
        { key: 'acoes', label: 'Ações' },
      ]
    },
    visibleUsuarios() {
      return (this.usuarios || []).filter(u => u && (u.nome || u.email))
    },
    filteredUsuarios() {
      return this.visibleUsuarios.filter(u => {
        const matchesQuery = !this.query || u.nome?.toLowerCase().includes(this.query.toLowerCase()) || u.email?.toLowerCase().includes(this.query.toLowerCase())
        const matchesStatus = !this.statusFilter || (this.statusFilter === 'ATIVO' ? u.status : !u.status)
        return matchesQuery && matchesStatus
      })
    },
    hasActiveFilter() { return this.query || this.statusFilter },
    activeUsuariosCount() { return this.filteredUsuarios.filter(u => u.status).length },
    inactiveUsuariosCount() { return this.filteredUsuarios.filter(u => !u.status).length },
    paginatedUsuarios() {
      const start = (this.currentPage - 1) * this.pageSize
      return this.filteredUsuarios.slice(start, start + this.pageSize)
    },
  },
  mounted() {
    this.loadUsuarios()
  },
  methods: {
    onQuery() {
      this.currentPage = 1
    },
    clearFilters() {
      this.query = ''
      this.statusFilter = ''
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
      if (action === 'excluir') this.deleteUsuario(row)
    },
    async loadUsuarios() {
      this.loading = true
      try {
        const res = await api.get('/api/v1/usuarios')
        this.usuarios = Array.isArray(res.data) ? res.data : (res.data.items || [])
        this.currentPage = 1
      } catch (e) {
        console.error('Erro ao carregar usuários', e)
        this.usuarios = []
      } finally {
        this.loading = false
      }
    },
    openCreateModal() {
      this.editMode = false
      this.editingId = null
      this.form = { nome: '', email: '', role: 'user', status: true }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    closeCreateModal() {
      this.showCreateModal = false
      this.submitting = false
      this.feedback = { message: '', type: 'info' }
      this.form = { nome: '', email: '', role: 'user', status: true }
      this.editMode = false
      this.editingId = null
    },
    openEdit(usuario) {
      this.editMode = true
      this.editingId = usuario.id
      this.form = {
        nome: usuario.nome || '',
        email: usuario.email || '',
        role: usuario.role || 'user',
        status: usuario.status ?? true,
      }
      this.feedback = { message: '', type: 'info' }
      this.showCreateModal = true
    },
    async submitForm() {
      this.submitting = true
      this.feedback = { message: this.editMode ? 'Atualizando...' : 'Salvando...', type: 'info' }
      try {
        if (this.editMode) {
          await api.put(`/api/v1/usuarios/${this.editingId}`, this.form)
        } else {
          await api.post('/api/v1/usuarios', this.form)
        }
        this.feedback = { message: this.editMode ? 'Usuário atualizado com sucesso.' : 'Usuário adicionado com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateModal(), 300)
        await this.loadUsuarios()
      } catch (e) {
        const { getMessage } = useApiError()
        this.feedback = { message: getMessage(e, 'Erro ao salvar usuário.'), type: 'error' }
      } finally {
        this.submitting = false
      }
    },
    deleteUsuario(usuario) {
      this.usuarioToDelete = usuario
      this.confirmDeleteOpen = true
    },
    cancelDelete() {
      this.confirmDeleteOpen = false
      this.deleting = false
      this.usuarioToDelete = null
    },
    async confirmDelete() {
      if (!this.usuarioToDelete?.id) return
      this.deleting = true
      try {
        await api.delete(`/api/v1/usuarios/${this.usuarioToDelete.id}`)
        this.currentPage = 1
        await this.loadUsuarios()
      } catch (e) {
        console.error('Erro ao excluir usuário', e)
      } finally {
        this.cancelDelete()
      }
    },
  },
}
</script>
