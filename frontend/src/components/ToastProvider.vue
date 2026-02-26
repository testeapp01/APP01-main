<template>
  <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2">
    <div v-for="t in toasts" :key="t.id" :class="['p-3 rounded shadow', t.type === 'error' ? 'bg-red-600 text-white' : 'bg-white text-black']">
      <div class="flex items-start gap-2">
        <div class="flex-1">{{ t.message }}</div>
        <button @click="dismiss(t.id)" class="text-sm opacity-80">✕</button>
      </div>
    </div>
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
