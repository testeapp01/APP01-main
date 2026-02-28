<template>
  <div class="page-shell">
    <PageHero
      title="Clientes & Motoristas"
      subtitle="Gerencie dados de relacionamento e operação logística no mesmo ambiente."
    >
      <template #actions>
        <BaseButton
          class="btn-primary"
          @click="openCreateClient"
        >
          Adicionar Cliente
        </BaseButton>
      </template>
    </PageHero>
    <section class="saas-context-grid">
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Clientes
        </div>
        <div class="saas-kpi-value">
          {{ visibleClients.length }}
        </div>
        <div class="saas-kpi-help">
          Cadastros disponíveis
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Motoristas
        </div>
        <div class="saas-kpi-value">
          {{ visibleDrivers.length }}
        </div>
        <div class="saas-kpi-help">
          Base operacional
        </div>
      </article>
      <article class="saas-kpi-card">
        <div class="saas-kpi-label">
          Página Clientes
        </div>
        <div class="saas-kpi-value">
          {{ currentPageClients }}
        </div>
        <div class="saas-kpi-help">
          Navegação ativa
        </div>
      </article>
    </section>
    <div class="mb-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800">
          Lista de Clientes
        </h2>
      </div>
      <div
        v-if="visibleClients.length > 0"
        class="panel-inner"
      >
        <BaseTable
          :columns="clientCols"
          :rows="paginatedClients"
        >
          <template #nome="{ row }">
            {{ row.nome }}
          </template>
          <template #cpf_cnpj="{ row }">
            {{ row.cpf_cnpj || '-' }}
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
                @click="editClient(row)"
              >
                Editar
              </BaseButton>
              <BaseButton
                variant="destructive"
                @click="deleteClient(row)"
              >
                Excluir
              </BaseButton>
            </div>
          </template>
        </BaseTable>
        <div class="mt-2 page-pagination">
          <BaseButton
            class="btn-secondary"
            :disabled="currentPageClients<=1"
            @click="prevClients"
          >
            Anterior
          </BaseButton>
          <template
            v-for="p in Math.min(5, totalPagesClients)"
            :key="p"
          >
            <button
              type="button"
              class="page-number"
              :class="{ 'is-active': currentPageClients===p }"
              @click="goToClients(p)"
            >
              {{ p }}
            </button>
          </template>
          <BaseButton
            class="btn-secondary"
            :disabled="currentPageClients>=totalPagesClients"
            @click="nextClients"
          >
            Próximo
          </BaseButton>
        </div>
      </div>
      <ListState
        :loading="loadingClients"
        :has-data="visibleClients.length > 0"
        loading-text="Carregando clientes..."
        empty-title="Nenhum cliente cadastrado."
        empty-message="Adicione clientes para começar."
        action-label="Adicionar Cliente"
        @action="openCreateClient"
      />
    </div>

    <SideDrawer
      :open="showCreateClient"
      title="Adicionar Cliente"
      @close="closeCreateClient"
    >
      <form
        class="drawer-form grid grid-cols-1 gap-3"
        @submit.prevent="createClient"
      >
        <FormFeedback
          :message="clientFeedback.message"
          :type="clientFeedback.type"
        />
        <input
          v-model="novaCliente.nome"
          placeholder="Nome"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <input
          v-model="novaCliente.cpf_cnpj"
          placeholder="CPF ou CNPJ"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novaCliente.telefone"
          placeholder="Telefone"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novaCliente.email"
          placeholder="Email"
          type="email"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <div class="mb-2 uf-field">
          <CustomSelect
            v-model="novaCliente.uf"
            :options="ufOptions"
            :placeholder="'UF'"
            class="w-full input-uf"
          />
        </div>
        <div class="switch-label mb-2">
          <label
            class="switch"
            :class="{ active: novaCliente.status }"
          >
            <input
              v-model="novaCliente.status"
              type="checkbox"
            >
            <span class="knob" />
          </label>
          <span class="text-sm status-text">{{ novaCliente.status ? 'ATIVO' : 'INATIVO' }}</span>
        </div>
        <div class="drawer-actions flex justify-end gap-3 mt-4">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeCreateClient"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary"
            :disabled="submittingClient"
            :loading="submittingClient"
          >
            {{ submittingClient ? 'Salvando...' : 'Adicionar' }}
          </BaseButton>
        </div>
      </form>
    </SideDrawer>

    <SideDrawer
      :open="showCreateDriver"
      title="Adicionar Motorista"
      @close="closeCreateDriver"
    >
      <form
        class="drawer-form grid grid-cols-1 gap-3"
        @submit.prevent="createDriver"
      >
        <FormFeedback
          :message="driverFeedback.message"
          :type="driverFeedback.type"
        />
        <input
          v-model="novoMotorista.nome"
          placeholder="Nome"
          class="p-3 border border-gray-300 rounded-xl"
          required
        >
        <input
          v-model="novoMotorista.placa"
          placeholder="Placa"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novoMotorista.veiculo"
          placeholder="Veículo"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <input
          v-model="novoMotorista.telefone"
          placeholder="Telefone"
          class="p-3 border border-gray-300 rounded-xl"
        >
        <div class="mb-2 uf-field">
          <CustomSelect
            v-model="novoMotorista.uf"
            :options="ufOptions"
            :placeholder="'UF'"
            class="w-full input-uf"
          />
        </div>
        <div class="switch-label mb-2">
          <label
            class="switch"
            :class="{ active: novoMotorista.status }"
          >
            <input
              v-model="novoMotorista.status"
              type="checkbox"
            >
            <span class="knob" />
          </label>
          <span class="text-sm status-text">{{ novoMotorista.status ? 'ATIVO' : 'INATIVO' }}</span>
        </div>
        <div class="drawer-actions flex justify-end gap-3 mt-4">
          <BaseButton
            type="button"
            class="btn-secondary"
            @click="closeCreateDriver"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            class="btn-primary"
            :disabled="submittingDriver"
            :loading="submittingDriver"
          >
            {{ submittingDriver ? 'Salvando...' : 'Adicionar' }}
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
  name: 'ClientsDrivers',
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
      clients: [],
      motoristas: [],
      loadingClients: true,
      loadingDrivers: true,
      activeTab: 'clients',
      // pagination / modal
      pageSize: 25,
      currentPageClients: 1,
      currentPageDrivers: 1,
      showCreateClient: false,
      showCreateDriver: false,
      submittingClient: false,
      submittingDriver: false,
      clientFeedback: { message: '', type: 'info' },
      driverFeedback: { message: '', type: 'info' },
      novaCliente: { nome: '', cpf_cnpj: '', telefone: '', email: '', uf: '', status: true },
      novoMotorista: { nome: '', placa: '', veiculo: '', uf: '', telefone: '', status: true },
      editingClientIndex: null,
      editingDriverIndex: null,
    };
  },
  computed: {
    clientCols() { return [ { key: 'nome', label: 'Nome' }, { key: 'cpf_cnpj', label: 'CPF / CNPJ' }, { key: 'uf', label: 'UF' }, { key: 'telefone', label: 'Telefone' }, { key: 'email', label: 'Email' }, { key: 'status', label: 'Ativo' }, { key: 'acoes', label: 'Ações' } ] },
    driverCols() { return [ { key: 'nome', label: 'Nome' }, { key: 'placa', label: 'Placa' }, { key: 'veiculo', label: 'Veículo' }, { key: 'uf', label: 'UF' }, { key: 'telefone', label: 'Telefone' }, { key: 'status', label: 'Ativo' }, { key: 'acoes', label: 'Ações' } ] },
    visibleClients() { return (this.clients||[]).filter(c=> c && (c.nome||c.cpf_cnpj||c.telefone||c.email)) },
    totalPagesClients() { return Math.max(1, Math.ceil(this.visibleClients.length / this.pageSize)) },
    paginatedClients() { const s=(this.currentPageClients-1)*this.pageSize; return this.visibleClients.slice(s,s+this.pageSize) },
    visibleDrivers() { return (this.motoristas||[]).filter(m=> m && (m.nome||m.placa||m.veiculo||m.telefone)) },
    totalPagesDrivers() { return Math.max(1, Math.ceil(this.visibleDrivers.length / this.pageSize)) },
    paginatedDrivers() { const s=(this.currentPageDrivers-1)*this.pageSize; return this.visibleDrivers.slice(s,s+this.pageSize) },
    ufOptions() {
      return [
        { value: '', label: 'UF' },
        ...['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'].map(uf => ({ value: uf, label: uf }))
      ]
    },
  },
  mounted() {
    this.fetchClients()
    this.fetchDrivers()
  },
  methods: {
    async fetchClients() {
      this.loadingClients = true
      try {
        const res = await api.get('/api/v1/clientes')
        this.clients = Array.isArray(res.data) ? res.data : (res.data.items || [])
      } catch (e) {
        console.error('Erro ao carregar clientes', e)
        this.clients = []
      } finally {
        this.loadingClients = false
      }
    },
    async fetchDrivers() {
      this.loadingDrivers = true
      try {
        const res = await api.get('/api/v1/motoristas')
        this.motoristas = Array.isArray(res.data) ? res.data : (res.data.items || [])
      } catch (e) {
        console.error('Erro ao carregar motoristas', e)
        this.motoristas = []
      } finally {
        this.loadingDrivers = false
      }
    },
    prevClients() { if (this.currentPageClients>1) this.currentPageClients-- },
    nextClients() { if (this.currentPageClients < this.totalPagesClients) this.currentPageClients++ },
    goToClients(n){ this.currentPageClients = Math.min(Math.max(1,n), this.totalPagesClients) },
    prevDrivers() { if (this.currentPageDrivers>1) this.currentPageDrivers-- },
    nextDrivers() { if (this.currentPageDrivers < this.totalPagesDrivers) this.currentPageDrivers++ },
    goToDrivers(n){ this.currentPageDrivers = Math.min(Math.max(1,n), this.totalPagesDrivers) },
    openCreateClient(){ this.editingClientIndex = null; this.clientFeedback = { message: '', type: 'info' }; this.novaCliente = { nome: '', cpf_cnpj: '', telefone: '', email: '', status: true }; this.showCreateClient=true },
    closeCreateClient(){ this.showCreateClient=false; this.submittingClient=false; this.clientFeedback = { message: '', type: 'info' }; this.novaCliente={nome:'',cpf_cnpj:'',telefone:'',email:'',status:true}; this.editingClientIndex = null },
    async createClient(){
      this.submittingClient = true
      this.clientFeedback = { message: 'Salvando cliente...', type: 'info' }
      try {
        const payload = {
          nome: this.novaCliente.nome,
          cpf_cnpj: this.novaCliente.cpf_cnpj,
          telefone: this.novaCliente.telefone,
          email: this.novaCliente.email,
          uf: this.novaCliente.uf || null,
          status: !!this.novaCliente.status,
        }
        const res = await api.post('/api/v1/clientes', payload)
        if (res.data && res.data.id) {
          this.clients.unshift({ id: res.data.id, ...payload })
        }
        this.clientFeedback = { message: 'Cliente salvo com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateClient(), 300)
      } catch (e) {
        console.error('Erro ao criar cliente', e)
        this.clientFeedback = { message: 'Falha ao salvar cliente. Tente novamente.', type: 'error' }
      } finally {
        this.submittingClient = false
      }
    },
    editClient(row){ const idx = this.clients.indexOf(row); if (idx!==-1) { this.editingClientIndex = idx; this.novaCliente = { ...row, status: !!row.status }; this.showCreateClient = true } },
    deleteClient(row){ const idx = this.clients.indexOf(row); if (idx!==-1) this.clients.splice(idx,1) },
    openCreateDriver(){ this.editingDriverIndex = null; this.driverFeedback = { message: '', type: 'info' }; this.novoMotorista = { nome: '', placa: '', veiculo: '', telefone: '', status: true }; this.showCreateDriver=true },
    closeCreateDriver(){ this.showCreateDriver=false; this.submittingDriver=false; this.driverFeedback = { message: '', type: 'info' }; this.novoMotorista={nome:'',placa:'',veiculo:'',telefone:'',status:true}; this.editingDriverIndex = null },
    async createDriver(){
      this.submittingDriver = true
      this.driverFeedback = { message: 'Salvando motorista...', type: 'info' }
      try {
        const payload = {
          nome: this.novoMotorista.nome,
          placa: this.novoMotorista.placa,
          veiculo: this.novoMotorista.veiculo,
          uf: this.novoMotorista.uf || null,
          telefone: this.novoMotorista.telefone,
          status: !!this.novoMotorista.status,
        }
        const res = await api.post('/api/v1/motoristas', payload)
        if (res.data && res.data.id) {
          this.motoristas.unshift({ id: res.data.id, ...payload })
        }
        this.driverFeedback = { message: 'Motorista salvo com sucesso.', type: 'success' }
        setTimeout(() => this.closeCreateDriver(), 300)
      } catch (e) {
        console.error('Erro ao criar motorista', e)
        this.driverFeedback = { message: 'Falha ao salvar motorista. Tente novamente.', type: 'error' }
      } finally {
        this.submittingDriver = false
      }
    },
    editDriver(row){ const idx = this.motoristas.indexOf(row); if (idx!==-1) { this.editingDriverIndex = idx; this.novoMotorista = { ...row, status: !!row.status }; this.showCreateDriver = true } },
    deleteDriver(row){ const idx = this.motoristas.indexOf(row); if (idx!==-1) this.motoristas.splice(idx,1) }

  }
}
</script>

<style scoped>
/* Add any custom styles here */
</style>