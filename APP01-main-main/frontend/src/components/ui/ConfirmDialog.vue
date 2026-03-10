<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center px-4"
    role="dialog"
    aria-modal="true"
  >
    <div
      class="absolute inset-0 bg-black/40"
      @click="$emit('cancel')"
    />

    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
      <h3 class="text-lg font-semibold text-slate-900">
        {{ title }}
      </h3>
      <p class="mt-2 text-sm text-slate-600">
        {{ message }}
      </p>
      <div class="mt-5 flex justify-end gap-2">
        <BaseButton
          variant="secondary"
          @click="$emit('cancel')"
        >
          Cancelar
        </BaseButton>
        <BaseButton
          variant="destructive"
          :loading="loading"
          :disabled="loading"
          @click="$emit('confirm')"
        >
          {{ confirmLabel }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from './BaseButton.vue'

export default {
  name: 'ConfirmDialog',
  components: { BaseButton },
  props: {
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar ação' },
    message: { type: String, default: 'Tem certeza que deseja continuar?' },
    confirmLabel: { type: String, default: 'Excluir' },
    loading: { type: Boolean, default: false },
  },
  emits: ['confirm', 'cancel'],
}
</script>
