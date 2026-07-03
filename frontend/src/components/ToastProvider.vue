<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-3 max-w-sm">
    <transition-group name="toast" tag="div">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="[
          'rounded-lg px-4 py-3.5 shadow-lg flex items-start gap-3 border',
          t.type === 'error' ? 'bg-red-50 border-red-200 text-red-900' : 
          t.type === 'success' ? 'bg-green-50 border-green-200 text-green-900' :
          t.type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-900' :
          'bg-blue-50 border-blue-200 text-blue-900'
        ]"
      >
        <div class="flex-1 text-sm font-medium leading-relaxed">
          {{ t.message }}
        </div>
        <button
          class="ml-2 flex-none opacity-70 hover:opacity-100 transition-opacity"
          aria-label="Descartar"
          @click="dismiss(t.id)"
        >
          ✕
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script>
import { useToast } from '../composables/useToast'
export default {
  name: 'ToastProvider',
  setup() {
    const { toasts, dismiss } = useToast()
    return { toasts, dismiss }
  }
}
</script>
