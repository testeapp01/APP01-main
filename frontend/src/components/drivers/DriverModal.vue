<template>
  <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40" @click="closeCreateModal" aria-hidden="true"></div>
    <div class="bg-white rounded-2xl shadow-md z-50 w-full max-w-md p-8">
      <h3 class="text-xl font-semibold mb-4 text-gray-800">Adicionar Motorista</h3>
      <form @submit.prevent="emitCreate" class="grid grid-cols-1 gap-3">
        <input v-model="local.nome" placeholder="Nome" class="p-3 border border-gray-300 rounded-xl" required />
        <input v-model="local.cpf" placeholder="CPF" class="p-3 border border-gray-300 rounded-xl" />
        <input v-model="local.placa" placeholder="Placa" class="p-3 border border-gray-300 rounded-xl" />
        <input v-model="local.veiculo" placeholder="Veículo" class="p-3 border border-gray-300 rounded-xl" />
        <input v-model="local.telefone" placeholder="Telefone" class="p-3 border border-gray-300 rounded-xl" />
        <div class="mb-2" style="min-width:88px">
          <CustomSelect
            v-model="local.uf"
            :options="ufOptions"
            :placeholder="'UF'"
            class="w-full input-uf"
          />
        </div>
        <div class="mb-2">
          <CustomSelect
            v-model="local.TpCaminhao"
            :options="caminhaoOptions"
            :placeholder="'Tipo de Caminhão'"
            class="w-full input-uf"
          />
        </div>
        <div class="switch-label mb-2">
          <label class="switch" :class="{ active: local.status }">
            <input type="checkbox" v-model="local.status" />
            <span class="knob"></span>
          </label>
          <span class="text-sm status-text">{{ local.status ? 'ATIVO' : 'INATIVO' }}</span>
        </div>
        <div class="flex justify-end gap-3 mt-4">
          <BaseButton type="button" class="btn-secondary" @click="closeCreateModal">Cancelar</BaseButton>
          <BaseButton type="submit" class="btn-primary">Adicionar</BaseButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { reactive, watch, toRefs } from 'vue'
import CustomSelect from '../ui/CustomSelect.vue'
import BaseButton from '../ui/BaseButton.vue'
export default {
  components: { CustomSelect, BaseButton },
  props: ['showCreateModal', 'closeCreateModal', 'create', 'nome', 'cpf', 'placa', 'veiculo', 'telefone', 'uf', 'TpCaminhao', 'status', 'ufOptions', 'caminhaoOptions'],
  setup(props, { emit }) {
    const local = reactive({
      nome: props.nome || '',
      cpf: props.cpf || '',
      placa: props.placa || '',
      veiculo: props.veiculo || '',
      telefone: props.telefone || '',
      uf: props.uf || '',
      TpCaminhao: props.TpCaminhao || '',
      status: props.status ?? true,
    })
    watch(() => props.showCreateModal, (val) => {
      if (val) {
        local.nome = props.nome || '';
        local.cpf = props.cpf || '';
        local.placa = props.placa || '';
        local.veiculo = props.veiculo || '';
        local.telefone = props.telefone || '';
        local.uf = props.uf || '';
        local.TpCaminhao = props.TpCaminhao || '';
        local.status = props.status ?? true;
      }
    });
    function emitCreate() {
      emit('update:nome', local.nome)
      emit('update:cpf', local.cpf)
      emit('update:placa', local.placa)
      emit('update:veiculo', local.veiculo)
      emit('update:telefone', local.telefone)
      emit('update:uf', local.uf)
      emit('update:TpCaminhao', local.TpCaminhao)
      emit('update:status', local.status)
      props.create()
    }
    return { local, emitCreate }
  }
}
</script>
