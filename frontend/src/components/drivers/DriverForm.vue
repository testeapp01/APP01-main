<template>
  <form @submit.prevent="handleSubmit" class="grid grid-cols-1 gap-2">
    <BaseInput v-model="form.name" label="Nome" />
    <BaseInput v-model="form.cnh" label="CNH" />
    <BaseInput v-model="form.phone" label="Telefone" />
    <div class="flex gap-2">
      <BaseButton type="submit">Salvar</BaseButton>
      <BaseButton variant="secondary" @click="$emit('cancel')">Cancelar</BaseButton>
    </div>
  </form>
</template>

<script>
import BaseInput from '../BaseInput.vue'
import BaseButton from '../BaseButton.vue'

export default {
  name: 'DriverForm',
  components: { BaseInput, BaseButton },
  data() {
    return { form: { name: '', cnh: '', phone: '' } }
  },
  methods: {
    handleSubmit() { this.$emit('submit', this.form) }
  }
}
</script>
