<template>
  <SideDrawer
    :open="showCreateModal"
    :title="title"
    @close="closeCreateModal"
  >
    <form
      class="drawer-form grid grid-cols-1 gap-3"
      @submit.prevent="emitCreate"
    >
      <FormFeedback
        :message="feedbackMessage"
        :type="feedbackType"
      />
      <input
        v-model="local.nome"
        placeholder="Nome"
        class="p-3 border border-gray-300 rounded-xl"
        required
      >
      <input
        v-model="local.cpf"
        placeholder="CPF"
        class="p-3 border border-gray-300 rounded-xl"
      >
      <input
        v-model="local.placa"
        placeholder="Placa"
        class="p-3 border border-gray-300 rounded-xl"
      >
      <input
        v-model="local.veiculo"
        placeholder="Veículo"
        class="p-3 border border-gray-300 rounded-xl"
      >
      <input
        v-model="local.telefone"
        placeholder="Telefone"
        class="p-3 border border-gray-300 rounded-xl"
      >
      <div class="mb-2 uf-field">
        <CustomSelect
          v-model="local.uf"
          :options="ufOptions"
          :placeholder="'UF'"
          class="w-full input-uf"
        />
      </div>
      <div class="mb-2">
        <CustomSelect
          v-model="local.tpCaminhao"
          :options="caminhaoOptions"
          :placeholder="'Tipo de Caminhão'"
          class="w-full input-uf"
        />
      </div>
      <div class="switch-label mb-2">
        <label
          class="switch"
          :class="{ active: local.status }"
        >
          <input
            v-model="local.status"
            type="checkbox"
          >
          <span class="knob" />
        </label>
        <span class="text-sm status-text">{{ local.status ? 'ATIVO' : 'INATIVO' }}</span>
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
          {{ submitting ? 'Salvando...' : submitLabel }}
        </BaseButton>
      </div>
    </form>
  </SideDrawer>
</template>

<script>
import { reactive, watch } from 'vue'
import CustomSelect from '../ui/CustomSelect.vue'
import BaseButton from '../ui/BaseButton.vue'
import SideDrawer from '../ui/SideDrawer.vue'
import FormFeedback from '../ui/FormFeedback.vue'
export default {
  components: { CustomSelect, BaseButton, SideDrawer, FormFeedback },
  props: {
    showCreateModal: { type: Boolean, default: false },
    closeCreateModal: { type: Function, default: () => {} },
    create: { type: Function, default: () => {} },
    nome: { type: String, default: '' },
    cpf: { type: String, default: '' },
    placa: { type: String, default: '' },
    veiculo: { type: String, default: '' },
    telefone: { type: String, default: '' },
    uf: { type: String, default: '' },
    tpCaminhao: { type: [String, Number], default: '' },
    status: { type: Boolean, default: true },
    ufOptions: { type: Array, default: () => [] },
    caminhaoOptions: { type: Array, default: () => [] },
    submitting: { type: Boolean, default: false },
    feedbackMessage: { type: String, default: '' },
    feedbackType: { type: String, default: 'info' },
    title: { type: String, default: 'Adicionar Motorista' },
    submitLabel: { type: String, default: 'Adicionar' }
  },
  emits: ['update:nome', 'update:cpf', 'update:placa', 'update:veiculo', 'update:telefone', 'update:uf', 'update:tpCaminhao', 'update:status'],
  setup(props, { emit }) {
    const local = reactive({
      nome: props.nome || '',
      cpf: props.cpf || '',
      placa: props.placa || '',
      veiculo: props.veiculo || '',
      telefone: props.telefone || '',
      uf: props.uf || '',
      tpCaminhao: props.tpCaminhao || '',
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
        local.tpCaminhao = props.tpCaminhao || '';
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
      emit('update:tpCaminhao', local.tpCaminhao)
      emit('update:status', local.status)
      props.create()
    }
    return { local, emitCreate }
  }
}
</script>
