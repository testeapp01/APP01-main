<template>
  <div class="page-shell">
    <PageHero
      title="Motoristas"
      subtitle="Coordene base logística com cadastro rápido e estado operacional claro."
    >
      <template #actions>
        <BaseButton
          class="w-full sm:w-auto"
          variant="ghost"
          @click="load"
        >
          Atualizar
        </BaseButton>
        <BaseButton
          class="w-full sm:w-auto"
          variant="primary"
          @click="openCreateModal"
        >
          Adicionar Motorista
        </BaseButton>
      </template>
    </PageHero>

    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Motoristas Ativos
        </div>
        <div class="saas-kpi-value">
          {{ visibleMotoristas.length }}
        </div>
        <div class="saas-kpi-help">
          Base operacional disponível
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
          Controle da lista
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Tipo Caminhão
        </div>
        <div class="saas-kpi-value">
          {{ caminhaoOptions.length > 1 ? 'Configurado' : 'Pendente' }}
        </div>
        <div class="saas-kpi-help">
          Parâmetro de classificação
        </div>
      </article>
    </section>
    <!-- Inline create form removed to avoid duplication; use modal 'Adicionar Motorista' -->


    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800">
          Lista de Motoristas
        </h2>
      </div>

      <div
        v-if="visibleMotoristas.length > 0"
        class="panel-inner"
      >
        <BaseTable
          :columns="tableCols"
          :rows="paginatedMotoristas"
        >
          <template #nome="{ row }">
            {{ row.nome || '-' }}
          </template>
          <template #uf="{ row }">
            {{ row.uf || '-' }}
          </template>
          <template #tipo_caminhao="{ row }">
            {{ row.tipo_caminhao || '-' }}
          </template>
          <template #status="{ row }">
            {{ row.status ? 'ATIVO' : 'INATIVO' }}
          </template>
          <template #acoes="{ row }">
            <div class="flex items-center gap-2">
              <BaseButton
                variant="ghost"
                @click="editMotorista(row)"
              >
                Editar
              </BaseButton>
              <BaseButton
                variant="destructive"
                @click="deleteMotorista(row)"
              >
                Excluir
              </BaseButton>
            </div>
          </template>
        </BaseTable>
        <div class="mt-2 page-pagination">
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

      <ListState
        :loading="loading"
        :has-data="visibleMotoristas.length > 0"
        loading-text="Carregando motoristas..."
        empty-title="Nenhum motorista cadastrado."
        empty-message="Adicione motoristas para começar."
        action-label="Adicionar Motorista"
        @action="openCreateModal"
      />
    </div>

    <DriverModal
      v-model:nome="nome"
      v-model:cpf="cpf"
      v-model:placa="placa"
      v-model:veiculo="veiculo"
      v-model:telefone="telefone"
      v-model:uf="uf"
      v-model:tp-caminhao="tpCaminhao"
      v-model:status="status"
      :show-create-modal="showCreateModal"
      :close-create-modal="closeCreateModal"
      :create="create"
      :submitting="submittingDriver"
      :feedback-message="driverFeedback.message"
      :feedback-type="driverFeedback.type"
      :uf-options="ufOptions"
      :caminhao-options="caminhaoOptions"
      :title="editingMotoristaId ? 'Editar Motorista' : 'Adicionar Motorista'"
      :submit-label="editingMotoristaId ? 'Salvar' : 'Adicionar'"
    />

    <ConfirmDialog
      :open="confirmDeleteOpen"
      title="Excluir motorista"
      :message="confirmDeleteMessage"
      :loading="deletingMotorista"
      confirm-label="Excluir"
      @cancel="cancelDeleteMotorista"
      @confirm="confirmDeleteMotorista"
    />
  </div>
</template>

<script>
import api from '../services/api'
import DriverModal from '../components/drivers/DriverModal.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable from '../components/ui/BaseTable.vue'
import PageHero from '../components/ui/PageHero.vue'
import ListState from '../components/ui/ListState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
export default {
  components: { DriverModal, BaseButton, BaseTable, PageHero, ListState, ConfirmDialog },
  data() {
    return {
      nome: '', cpf: '', placa: '', veiculo: '', uf: '', telefone: '', tpCaminhao: '', status: true,
      motoristas: [], loading: true, showCreateModal: false, pageSize: 25, currentPage: 1,
      submittingDriver: false,
      driverFeedback: { message: '', type: 'info' },
      caminhaoOptions: [ { value: '', label: 'Tipo de Caminhão' } ],
      editingMotoristaId: null,
      confirmDeleteOpen: false,
      confirmDeleteMessage: '',
      deletingMotorista: false,
      motoristaToDelete: null,
    }
  },
  computed: {
    tableCols() { return [ { key: 'nome', label: 'Nome' }, { key: 'uf', label: 'UF' }, { key: 'tipo_caminhao', label: 'Tipo Caminhão' }, { key: 'status', label: 'Status' }, { key: 'acoes', label: 'Ações' } ] },
    visibleMotoristas() { return (this.motoristas||[]).filter(m=> m && (m.nome||m.placa||m.veiculo||m.telefone)) },
    totalPages() { return Math.max(1, Math.ceil(this.visibleMotoristas.length / this.pageSize)) },
    paginatedMotoristas() { const start=(this.currentPage-1)*this.pageSize; return this.visibleMotoristas.slice(start, start+this.pageSize) },
    ufOptions() {
      return [
        { value: '', label: 'UF' },
        ...['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'].map(uf => ({ value: uf, label: uf }))
      ]
    },
  },
  async mounted() {
    this.load();
    // Carregar tipos de caminhão do backend
    try {
      const res = await api.get('/api/v1/tipos-caminhao');
      if (Array.isArray(res.data)) {
        this.caminhaoOptions = [ { value: '', label: 'Tipo de Caminhão' }, ...res.data.map(t => ({ value: t.id, label: t.nome })) ];
      }
    } catch (e) { /* ignora erro */ }
  },
  methods: {
    async load() { this.loading=true; try { const res = await api.get('/api/v1/motoristas'); this.motoristas = res.data || []; this.currentPage = 1 } catch (e) { this.motoristas = [] } finally { this.loading=false } },
    openCreateModal(){ this.editingMotoristaId = null; this.driverFeedback = { message: '', type: 'info' }; this.showCreateModal=true },
    closeCreateModal(){ this.showCreateModal=false; this.submittingDriver=false; this.driverFeedback = { message: '', type: 'info' }; this.editingMotoristaId = null; this.nome=''; this.cpf=''; this.placa=''; this.veiculo=''; this.telefone=''; this.uf=''; this.tpCaminhao=''; this.status=true },
    editMotorista(row) {
      this.editingMotoristaId = row.id
      this.driverFeedback = { message: '', type: 'info' }
      this.nome = row.nome || ''
      this.cpf = row.cpf || ''
      this.placa = row.placa || ''
      this.veiculo = row.veiculo || ''
      this.telefone = row.telefone || ''
      this.uf = row.uf || ''
      this.tpCaminhao = row.TpCaminhao || ''
      this.status = !!row.status
      this.showCreateModal = true
    },
    deleteMotorista(row) {
      if (!row?.id) return
      this.motoristaToDelete = row
      this.confirmDeleteMessage = `Deseja excluir o motorista \"${row.nome || row.id}\"?`
      this.confirmDeleteOpen = true
    },
    cancelDeleteMotorista() {
      this.confirmDeleteOpen = false
      this.confirmDeleteMessage = ''
      this.motoristaToDelete = null
      this.deletingMotorista = false
    },
    async confirmDeleteMotorista() {
      if (!this.motoristaToDelete?.id) return
      this.deletingMotorista = true
      try {
        await api.delete(`/api/v1/motoristas/${this.motoristaToDelete.id}`)
        this.currentPage = 1
        await this.load()
      } catch (e) {
        this.driverFeedback = { message: e?.response?.data?.error || 'Não foi possível excluir o motorista.', type: 'error' }
      } finally {
        this.cancelDeleteMotorista()
      }
    },
    async create() {
      this.submittingDriver = true
      this.driverFeedback = { message: this.editingMotoristaId ? 'Atualizando motorista...' : 'Salvando motorista...', type: 'info' }
      try {
        const payload = {
          nome: this.nome,
          cpf: this.cpf,
          placa: this.placa,
          veiculo: this.veiculo,
          uf: this.uf || null,
          telefone: this.telefone,
          TpCaminhao: this.tpCaminhao || null,
          status: !!this.status,
        }
        if (this.editingMotoristaId) {
          await api.put(`/api/v1/motoristas/${this.editingMotoristaId}`, payload)
        } else {
          await api.post('/api/v1/motoristas', payload)
        }
        this.driverFeedback = { message: this.editingMotoristaId ? 'Motorista atualizado com sucesso.' : 'Motorista salvo com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateModal(), 300)
        await this.load()
      } catch (e) {
        this.driverFeedback = { message: 'Falha ao salvar motorista. Tente novamente.', type: 'error' }
      } finally {
        this.submittingDriver = false
      }
    },
    prevPage(){ if(this.currentPage>1) this.currentPage-- },
    nextPage(){ if(this.currentPage < this.totalPages) this.currentPage++ },
    goToPage(n){ this.currentPage = Math.min(Math.max(1,n), this.totalPages) }
  }
}
</script>
