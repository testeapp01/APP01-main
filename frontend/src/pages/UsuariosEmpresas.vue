<template>
  <div class="page-shell page-fade section-stack">
    <PageHero title="Usuários por Empresa" subtitle="Vincule usuários às empresas para acessar o ambiente SaaS">
      <template #actions>
        <BaseButton class="btn-secondary" @click="loadUsuariosEmpresas">Atualizar</BaseButton>
        <BaseButton class="btn-primary" @click="openCreateModal">Vincular Usuário</BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">Vínculos Ativos</div>
        <div class="saas-kpi-value">{{ visibleUsuariosEmpresas.length }}</div>
        <div class="saas-kpi-help">Usuários vinculados a empresas</div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">Página Atual</div>
        <div class="saas-kpi-value">{{ currentPage }}</div>
        <div class="saas-kpi-help">Navegação de vínculos</div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">Ações</div>
        <div class="saas-kpi-value">{{ editMode ? 'Edição' : 'Novo' }}</div>
        <div class="saas-kpi-help">Modo operacional</div>
      </article>
    </section>

    <div class="section-block">
      <h2>Lista de Vínculos</h2>
      <div class="table-wrapper">
        <BaseTable :columns="tableCols" :rows="paginatedUsuariosEmpresas">
          <template #usuario_nome="{ row }">
            <span class="font-medium">{{ row.usuario_nome }}</span>
          </template>
          <template #empresa_nome="{ row }">
            <span class="text-blue-600">{{ row.empresa_nome }}</span>
          </template>
          <template #role_empresa="{ row }">
            <span :class="`badge badge-${row.role_empresa === 'admin' ? 'red' : row.role_empresa === 'manager' ? 'yellow' : 'blue'}`">
              {{ (row.role_empresa || '').toUpperCase() }}
            </span>
          </template>
          <template #status="{ row }">
            <span :class="`badge ${row.status ? 'badge-green' : 'badge-gray'}`">
              {{ row.status ? 'ATIVO' : 'INATIVO' }}
            </span>
          </template>
          <template #acoes="{ row }">
            <div class="flex gap-2">
              <BaseButton variant="ghost" size="sm" @click="editRow(row)">Editar</BaseButton>
              <BaseButton variant="ghost" size="sm" class="text-red-500" @click="deleteRow(row)">Excluir</BaseButton>
            </div>
          </template>
        </BaseTable>
        <PaginationPremium :current-page.sync="currentPage" :page-size.sync="pageSize" :total="visibleUsuariosEmpresas.length" />
      </div>
      <p class="text-gray-500 mt-4">{{ loading ? 'Carregando vínculos...' : '' }}</p>
    </div>

    <SideDrawer :open="showCreateModal" @close="closeCreateModal">
      <div class="space-y-4">
        <h3 class="text-lg font-semibold">{{ editMode ? 'Editar Vínculo' : 'Novo Vínculo' }}</h3>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Usuário *</label>
            <select v-model="form.usuario_id" class="input w-full" required>
              <option value="">Selecione um usuário</option>
              <option value="1">Admin User (admin@empresa.com)</option>
              <option value="2">Gerente Vendas (gerente@empresa.com)</option>
              <option value="3">Operador (operador@empresa.com)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Empresa *</label>
            <select v-model="form.empresa_id" class="input w-full" required>
              <option value="">Selecione uma empresa</option>
              <option value="1">Empresa Matriz</option>
              <option value="2">Filial SP</option>
              <option value="3">Filial RJ</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Permissão na Empresa *</label>
            <select v-model="form.role_empresa" class="input w-full" required>
              <option value="">Selecione uma permissão</option>
              <option value="admin">Administrador</option>
              <option value="manager">Gerente</option>
              <option value="user">Operador</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="form.status" id="status" class="checkbox" />
            <label for="status" class="text-sm">Vínculo ativo</label>
          </div>

          <FormFeedback :message="feedback.message" :type="feedback.type" v-if="feedback.message" />

          <div class="flex gap-2 justify-end pt-4">
            <BaseButton variant="secondary" @click="closeCreateModal">Cancelar</BaseButton>
            <BaseButton class="btn-primary" :loading="submitting" @click="submitForm">
              {{ editMode ? 'Atualizar' : 'Vincular' }}
            </BaseButton>
          </div>
        </form>
      </div>
    </SideDrawer>

    <ConfirmDialog :open="confirmDeleteOpen" title="Confirmar exclusão" message="Deseja remover este vínculo?" @confirm="confirmDelete" @cancel="confirmDeleteOpen = false" />
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import api, { showApiError } from '../services/api'
import PageHero from '../components/ui/PageHero.vue'
import BaseTable from '../components/ui/BaseTable.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import SideDrawer from '../components/ui/SideDrawer.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import FormFeedback from '../components/ui/FormFeedback.vue'
import PaginationPremium from '../components/ui/PaginationPremium.vue'

export default {
  name: 'UsuariosEmpresas',
  components: { PageHero, BaseTable, BaseButton, SideDrawer, ConfirmDialog, FormFeedback, PaginationPremium },
  
  setup() {
    const usuariosEmpresas = ref([])
    const currentPage = ref(1)
    const pageSize = ref(25)
    const showCreateModal = ref(false)
    const editMode = ref(false)
    const loading = ref(false)
    const submitting = ref(false)
    const confirmDeleteOpen = ref(false)
    const selectedId = ref(null)
    const feedback = ref({ message: '', type: '' })

    const form = ref({
      usuario_id: '',
      empresa_id: '',
      role_empresa: '',
      status: true
    })

    const tableCols = [
      { key: 'usuario_nome', label: 'Usuário' },
      { key: 'empresa_nome', label: 'Empresa' },
      { key: 'role_empresa', label: 'Permissão' },
      { key: 'status', label: 'Status' },
      { key: 'acoes', label: 'Ações' }
    ]

    const visibleUsuariosEmpresas = computed(() => usuariosEmpresas.value)
    const paginatedUsuariosEmpresas = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      return visibleUsuariosEmpresas.value.slice(start, start + pageSize.value)
    })

    const loadUsuariosEmpresas = async () => {
      loading.value = true
      try {
        const res = await api.get('/usuarios-empresas')
        usuariosEmpresas.value = res.data || res.data.data || []
      } catch (err) {
        const message = showApiError(err, 'Erro ao carregar vínculos', { type: 'error' })
        feedback.value = { message, type: 'error' }
      } finally {
        loading.value = false
      }
    }

    const openCreateModal = () => {
      editMode.value = false
      form.value = { usuario_id: '', empresa_id: '', role_empresa: '', status: true }
      feedback.value = { message: '', type: '' }
      showCreateModal.value = true
    }

    const closeCreateModal = () => {
      showCreateModal.value = false
    }

    const editRow = (row) => {
      editMode.value = true
      selectedId.value = row.id
      form.value = { ...row, usuario_id: String(row.usuario_id), empresa_id: String(row.empresa_id) }
      showCreateModal.value = true
    }

    const submitForm = async () => {
      submitting.value = true
      feedback.value = { message: '', type: '' }

      try {
        if (editMode.value) {
          await api.put(`/usuarios-empresas/${selectedId.value}`, form.value)
          usuariosEmpresas.value = usuariosEmpresas.value.map(u => u.id === selectedId.value ? { ...form.value, id: selectedId.value } : u)
          feedback.value = { message: 'Vínculo atualizado com sucesso!', type: 'success' }
        } else {
          const newItem = { ...form.value, id: Math.max(...usuariosEmpresas.value.map(u => u.id), 0) + 1 }
          await api.post('/usuarios-empresas', newItem)
          usuariosEmpresas.value.push(newItem)
          feedback.value = { message: 'Vínculo criado com sucesso!', type: 'success' }
        }
        setTimeout(() => closeCreateModal(), 1500)
      } catch (err) {
        const message = showApiError(err, editMode.value ? 'Erro ao atualizar vínculo' : 'Erro ao salvar vínculo', { type: 'error' })
        feedback.value = { message, type: 'error' }
      } finally {
        submitting.value = false
      }
    }

    const deleteRow = (row) => {
      selectedId.value = row.id
      confirmDeleteOpen.value = true
    }

    const confirmDelete = async () => {
      try {
        await api.delete(`/usuarios-empresas/${selectedId.value}`)
        usuariosEmpresas.value = usuariosEmpresas.value.filter(u => u.id !== selectedId.value)
        confirmDeleteOpen.value = false
        feedback.value = { message: 'Vínculo removido!', type: 'success' }
      } catch (err) {
        const message = showApiError(err, 'Erro ao remover vínculo', { type: 'error' })
        feedback.value = { message, type: 'error' }
      }
    }

    loadUsuariosEmpresas()

    return {
      usuariosEmpresas,
      currentPage,
      pageSize,
      showCreateModal,
      editMode,
      loading,
      submitting,
      confirmDeleteOpen,
      form,
      feedback,
      tableCols,
      visibleUsuariosEmpresas,
      paginatedUsuariosEmpresas,
      loadUsuariosEmpresas,
      openCreateModal,
      closeCreateModal,
      editRow,
      submitForm,
      deleteRow,
      confirmDelete
    }
  }
}
</script>
