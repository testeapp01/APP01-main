<template>
  <transition
    name="state-fade"
    mode="out-in"
  >
    <div
      v-if="loading"
      key="loading"
      class="panel-inner py-8 px-5"
    >
      <div class="space-y-3 animate-pulse">
        <div class="h-4 w-40 rounded bg-slate-200 mx-auto" />
        <div class="h-3 w-56 rounded bg-slate-100 mx-auto" />
        <div class="h-3 w-48 rounded bg-slate-100 mx-auto" />
      </div>
      <p class="text-xs muted text-center mt-5 tracking-wide uppercase">
        {{ loadingText }}
      </p>
    </div>

    <div
      v-else-if="!hasData"
      key="empty"
      class="py-12 text-center"
    >
      <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600">
        ⊕
      </div>
      <p class="text-lg font-semibold mb-2 text-gray-800">
        {{ emptyTitle }}
      </p>
      <p class="text-sm muted mb-6 max-w-md mx-auto">
        {{ emptyMessage }}
      </p>
      <div
        v-if="actionLabel"
        class="flex justify-center"
      >
        <button
          class="btn-primary"
          @click="$emit('action')"
        >
          {{ actionLabel }}
        </button>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'ListState',
  props: {
    loading: { type: Boolean, default: false },
    hasData: { type: Boolean, default: false },
    loadingText: { type: String, default: 'Carregando...' },
    emptyTitle: { type: String, default: 'Nenhum registro encontrado.' },
    emptyMessage: { type: String, default: 'Adicione dados para começar.' },
    actionLabel: { type: String, default: '' }
  },
  emits: ['action']
}
</script>

<style scoped>
.state-fade-enter-active,
.state-fade-leave-active {
  transition: opacity .22s ease, transform .22s ease;
}

.state-fade-enter-from,
.state-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>
