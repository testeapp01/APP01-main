<template>
  <BaseModal
    :show="visible"
    :title="title"
    @update:show="onClose"
  >
    <div>
      <p class="text-slate-700 text-base leading-relaxed mb-6">{{ message }}</p>
      <div class="flex justify-end gap-3">
        <BaseButton
          variant="secondary"
          @click="cancel"
        >
          Cancelar
        </BaseButton>
        <BaseButton
          variant="primary"
          @click="confirm"
        >
          Confirmar
        </BaseButton>
      </div>
    </div>
  </BaseModal>
</template>

<script>
import BaseModal from './BaseModal.vue'
import BaseButton from './BaseButton.vue'

export default {
  name: 'ConfirmDialog',
  components: { BaseModal, BaseButton },
  props: { modelValue: { type: Boolean, default: false }, title: { type: String, default: 'Confirmação' }, message: { type: String, default: '' } },
  emits: ['update:modelValue', 'confirm', 'cancel'],
  computed: {
    visible: {
      get() { return this.modelValue },
      set(v) { this.$emit('update:modelValue', v) }
    }
  },
  methods: {
    confirm() { this.$emit('confirm'); this.visible = false },
    cancel() { this.$emit('cancel'); this.visible = false },
    onClose() { this.visible = false }
  }
}
</script>
