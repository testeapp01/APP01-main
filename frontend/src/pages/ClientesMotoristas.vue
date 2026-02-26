<template>

  <div>
    <h1 class="text-2xl font-bold mb-4 text-gray-800">Clientes</h1>
    <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800">Lista de Clientes</h2>
        <BaseButton class="btn-primary" @click="openCreateClient">Adicionar Cliente</BaseButton>
      </div>
      <div v-if="visibleClients.length > 0" class="panel-inner">
        <BaseTable :columns="clientCols" :rows="paginatedClients">
          <template #nome="{ row }">{{ row.nome }}</template>
          <template #cpf_cnpj="{ row }">{{ row.cpf_cnpj || '-' }}</template>
          <template #uf="{ row }">{{ row.uf || '-' }}</template>
          <template #telefone="{ row }">{{ row.telefone || '-' }}</template>
          <template #email="{ row }">{{ row.email || '-' }}</template>
          <template #status="{ row }">{{ row.status ? 'ATIVO' : 'INATIVO' }}</template>
          <template #acoes="{ row }">
            <div class="flex items-center gap-2">
              <BaseButton variant="ghost" @click="editClient(row)">Editar</BaseButton>
              <BaseButton variant="destructive" @click="deleteClient(row)">Excluir</BaseButton>
            </div>
          </template>
        </BaseTable>
        <div class="mt-2 flex items-center justify-end gap-2">
          <BaseButton class="btn-secondary" :disabled="currentPageClients<=1" @click="prevClients">Anterior</BaseButton>
          <template v-for="p in Math.min(5, totalPagesClients)" :key="p">
            <button class="px-2 py-1 rounded text-sm" :class="{ 'bg-gray-200': currentPageClients===p }" @click="goToClients(p)">{{ p }}</button>
          </template>
          <BaseButton class="btn-secondary" :disabled="currentPageClients>=totalPagesClients" @click="nextClients">Próximo</BaseButton>
        </div>
      </div>
      <div v-else class="py-12 text-center">
        <p class="text-lg font-medium mb-4 text-gray-800">Nenhum cliente cadastrado.</p>
        <p class="text-sm muted mb-6">Adicione clientes para começar.</p>
        <div class="flex justify-center">
          <BaseButton class="btn-primary" @click="openCreateClient">Adicionar Cliente</BaseButton>
        </div>
      </div>
    </div>

    <!-- Create Client Modal -->
    <div v-if="showCreateClient" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40" @click="closeCreateClient" aria-hidden="true"></div>
      <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-md p-8">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Adicionar Cliente</h3>
        <form @submit.prevent="createClient" class="grid grid-cols-1 gap-3">
          <input v-model="novaCliente.nome" placeholder="Nome" class="p-3 border border-gray-300 rounded-xl" required />
          <input v-model="novaCliente.cpf_cnpj" placeholder="CPF ou CNPJ" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novaCliente.telefone" placeholder="Telefone" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novaCliente.email" placeholder="Email" type="email" class="p-3 border border-gray-300 rounded-xl" />
          <div class="mb-2" style="min-width:88px">
            <CustomSelect
              v-model="novaCliente.uf"
              :options="ufOptions"
              :placeholder="'UF'"
              class="w-full input-uf"
            />
          </div>
          <div class="switch-label mb-2">
            <label class="switch" :class="{ active: novaCliente.status }">
              <input type="checkbox" v-model="novaCliente.status" />
              <span class="knob"></span>
            </label>
            <span class="text-sm status-text">{{ novaCliente.status ? 'ATIVO' : 'INATIVO' }}</span>
          </div>
          <div class="flex justify-end gap-3 mt-4">
            <BaseButton type="button" class="btn-secondary" @click="closeCreateClient">Cancelar</BaseButton>
            <BaseButton type="submit" class="btn-primary">Adicionar</BaseButton>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Driver Modal -->
    <div v-if="showCreateDriver" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40" @click="closeCreateDriver" aria-hidden="true"></div>
      <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-md p-8">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Adicionar Motorista</h3>
        <form @submit.prevent="createDriver" class="grid grid-cols-1 gap-3">
          <input v-model="novoMotorista.nome" placeholder="Nome" class="p-3 border border-gray-300 rounded-xl" required />
          <input v-model="novoMotorista.placa" placeholder="Placa" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novoMotorista.veiculo" placeholder="Veículo" class="p-3 border border-gray-300 rounded-xl" />
          <input v-model="novoMotorista.telefone" placeholder="Telefone" class="p-3 border border-gray-300 rounded-xl" />
          <div class="mb-2" style="min-width:88px">
            <CustomSelect
              v-model="novoMotorista.uf"
              :options="ufOptions"
              :placeholder="'UF'"
              class="w-full input-uf"
            />
          </div>
          <div class="switch-label mb-2">
            <label class="switch" :class="{ active: novoMotorista.status }">
              <input type="checkbox" v-model="novoMotorista.status" />
              <span class="knob"></span>
            </label>
            <span class="text-sm status-text">{{ novoMotorista.status ? 'ATIVO' : 'INATIVO' }}</span>
          </div>
          <div class="flex justify-end gap-3 mt-4">
            <BaseButton type="button" class="btn-secondary" @click="closeCreateDriver">Cancelar</BaseButton>
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
  name: 'ClientsDrivers',
  components: {
    BaseTable,
    BaseButton,
    CustomSelect,
  },
  data() {
    return {
      clients: [],
      motoristas: [],
      activeTab: 'clients',
      // pagination / modal
      pageSize: 25,
      currentPageClients: 1,
      currentPageDrivers: 1,
      showCreateClient: false,
      showCreateDriver: false,
      novaCliente: { nome: '', cpf_cnpj: '', telefone: '', email: '', uf: '', status: true },
      novoMotorista: { nome: '', placa: '', veiculo: '', uf: '', telefone: '', status: true },
      editingClientIndex: null,
      editingDriverIndex: null,
    };
  },
  mounted() {
    this.fetchClients()
    this.fetchDrivers()
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
  methods: {
    async fetchClients() {
      try {
        const res = await api.get('/api/v1/clientes')
        this.clients = Array.isArray(res.data) ? res.data : (res.data.items || [])
      } catch (e) {
        console.error('Erro ao carregar clientes', e)
        this.clients = []
      }
    },
    async fetchDrivers() {
      try {
        const res = await api.get('/api/v1/motoristas')
        this.motoristas = Array.isArray(res.data) ? res.data : (res.data.items || [])
      } catch (e) {
        console.error('Erro ao carregar motoristas', e)
        this.motoristas = []
      }
    },
    prevClients() { if (this.currentPageClients>1) this.currentPageClients-- },
    nextClients() { if (this.currentPageClients < this.totalPagesClients) this.currentPageClients++ },
    goToClients(n){ this.currentPageClients = Math.min(Math.max(1,n), this.totalPagesClients) },
    prevDrivers() { if (this.currentPageDrivers>1) this.currentPageDrivers-- },
    nextDrivers() { if (this.currentPageDrivers < this.totalPagesDrivers) this.currentPageDrivers++ },
    goToDrivers(n){ this.currentPageDrivers = Math.min(Math.max(1,n), this.totalPagesDrivers) },
    openCreateClient(){ this.editingClientIndex = null; this.novaCliente = { nome: '', cpf_cnpj: '', telefone: '', email: '', status: true }; this.showCreateClient=true },
    closeCreateClient(){ this.showCreateClient=false; this.novaCliente={nome:'',cpf_cnpj:'',telefone:'',email:'',status:true}; this.editingClientIndex = null },
    async createClient(){
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
        this.closeCreateClient()
      } catch (e) {
        console.error('Erro ao criar cliente', e)
      }
    },
    editClient(row){ const idx = this.clients.indexOf(row); if (idx!==-1) { this.editingClientIndex = idx; this.novaCliente = { ...row, status: !!row.status }; this.showCreateClient = true } },
    deleteClient(row){ const idx = this.clients.indexOf(row); if (idx!==-1) this.clients.splice(idx,1) },
    openCreateDriver(){ this.editingDriverIndex = null; this.novoMotorista = { nome: '', placa: '', veiculo: '', telefone: '', status: true }; this.showCreateDriver=true },
    closeCreateDriver(){ this.showCreateDriver=false; this.novoMotorista={nome:'',placa:'',veiculo:'',telefone:'',status:true}; this.editingDriverIndex = null },
    async createDriver(){
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
        this.closeCreateDriver()
      } catch (e) {
        console.error('Erro ao criar motorista', e)
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