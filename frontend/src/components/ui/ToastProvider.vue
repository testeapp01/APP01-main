<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-[9999] max-w-sm space-y-2 pointer-events-none">
      <transition-group
        name="toast"
        tag="div"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto px-4 py-3 rounded-lg shadow-lg border text-sm font-medium flex items-center gap-3 animate-slideIn',
            {
              'bg-green-50 text-green-800 border-green-200': toast.type === 'success',
              'bg-red-50 text-red-800 border-red-200': toast.type === 'error',
              'bg-blue-50 text-blue-800 border-blue-200': toast.type === 'info',
              'bg-amber-50 text-amber-800 border-amber-200': toast.type === 'warning',
            }
          ]"
        >
          <span v-if="toast.type === 'success'">✓</span>
          <span v-else-if="toast.type === 'error'">✕</span>
          <span v-else-if="toast.type === 'warning'">⚠</span>
          <span v-else>ℹ</span>
          <span>{{ toast.message }}</span>
          <button
            class="ml-auto text-lg leading-none opacity-70 hover:opacity-100 transition-opacity"
            @click="removeToast(toast.id)"
          >
            ×
          </button>
        </div>
      </transition-group>
    </div>
  </Teleport>
</template>

<script>
import { useToast } from '../../composables/useToast'

export default {
  name: 'ToastProvider',
  setup() {
    const { toasts, removeToast } = useToast()

    return {
      toasts,
      removeToast,
    }
  },
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 200ms ease-out;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) translateY(10px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%) translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateX(0) translateY(0);
  }
}

.animate-slideIn {
  animation: slideIn 200ms ease-out;
}
</style>
