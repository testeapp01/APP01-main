<template>
  <div>
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold mb-4 text-gray-800">Motoristas</h1>
      </div>

      <div class="flex items-center gap-2">
        <BaseButton variant="ghost" @click="load">Atualizar</BaseButton>
        <BaseButton variant="primary" @click="openCreateModal">Adicionar Motorista</BaseButton>
      </div>
    </div>
    <!-- Inline create form removed to avoid duplication; use modal 'Adicionar Motorista' -->


  <div class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-xl font-semibold text-gray-800">Lista de Motoristas</h2>
      </div>

      <div v-if="visibleMotoristas.length > 0" class="panel-inner">
        <ul class="mt-2 space-y-2">
          <li v-for="m in paginatedMotoristas" :key="m.id" class="p-2 bg-white rounded shadow">
            {{ m.nome }} ({{ m.uf || '-' }})
            <span v-if="m.tipo_caminhao">- {{ m.tipo_caminhao }}</span>
          </li>
        </ul>
        <div class="mt-2 flex items-center justify-end gap-2">
          <button class="px-2 py-1 border rounded" :disabled="currentPage<=1" @click="prevPage">Anterior</button>
          <template v-for="p in Math.min(5, totalPages)" :key="p">
            <button class="px-2 py-1 rounded" :class="{ 'bg-gray-200': currentPage===p }" @click="goToPage(p)">{{ p }}</button>
          </template>
          <button class="px-2 py-1 border rounded" :disabled="currentPage>=totalPages" @click="nextPage">Próximo</button>
        </div>
      </div>

      <div v-else class="py-8 text-center">
        <p class="mb-4">Nenhum motorista cadastrado.</p>
        <BaseButton variant="primary" @click="openCreateModal">Adicionar Motorista</BaseButton>
      </div>
    </div>

    <DriverModal
      :showCreateModal="showCreateModal"
      :closeCreateModal="closeCreateModal"
      :create="create"
      v-model:nome="nome"
      v-model:cpf="cpf"
      v-model:placa="placa"
      v-model:veiculo="veiculo"
      v-model:telefone="telefone"
      v-model:uf="uf"
      v-model:TpCaminhao="TpCaminhao"
      v-model:status="status"
      :ufOptions="ufOptions"
      :caminhaoOptions="caminhaoOptions"
    />
  </div>
</template>

<script>
import api from '../services/api'
import DriverModal from '../components/drivers/DriverModal.vue'
import CustomSelect from '../components/ui/CustomSelect.vue'
import BaseButton from '../components/ui/BaseButton.vue'
export default {
  components: { DriverModal, CustomSelect, BaseButton },
  data() {
    return {
      nome: '', cpf: '', placa: '', veiculo: '', uf: '', telefone: '', TpCaminhao: '', status: true,
      motoristas: [], loading: false, showCreateModal: false, pageSize: 25, currentPage: 1,
      caminhaoOptions: [ { value: '', label: 'Tipo de Caminhão' } ],
    }
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
  computed: {
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
  methods: {
    async load() { this.loading=true; try { const res = await api.get('/api/v1/motoristas'); this.motoristas = res.data || [] } catch (e) { this.motoristas = [] } finally { this.loading=false } },
    openCreateModal(){ this.showCreateModal=true },
    closeCreateModal(){ this.showCreateModal=false; this.nome=''; this.placa=''; this.veiculo=''; this.telefone=''; this.uf=''; this.TpCaminhao=''; this.status=true },
    async create() {
      try {
        await api.post('/api/v1/motoristas', {
          nome: this.nome,
          cpf: this.cpf,
          placa: this.placa,
          veiculo: this.veiculo,
          uf: this.uf || null,
          telefone: this.telefone,
          TpCaminhao: this.TpCaminhao || null,
          status: !!this.status,
        });
        this.closeCreateModal();
        this.load();
      } catch (e) { alert('Erro') }
    },
    prevPage(){ if(this.currentPage>1) this.currentPage-- },
    nextPage(){ if(this.currentPage < this.totalPages) this.currentPage++ },
    goToPage(n){ this.currentPage = Math.min(Math.max(1,n), this.totalPages) }
  }
}
</script>
