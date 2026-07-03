<template>
  <transition
    name="state-fade"
    mode="out-in"
  >
    <div
      v-if="loading"
      key="loading"
      class="panel-inner py-12 px-5"
    >
      <div class="space-y-4 animate-pulse">
        <div class="h-4 w-32 rounded bg-slate-200 mx-auto" />
        <div class="h-3 w-48 rounded bg-slate-100 mx-auto" />
        <div class="h-3 w-40 rounded bg-slate-100 mx-auto" />
      </div>
      <p class="text-xs text-slate-500 text-center mt-6 tracking-wide uppercase font-semibold">
        {{ loadingText }}
      </p>
    </div>

    <div
      v-else-if="!hasData"
      key="empty"
      class="py-16 px-6 text-center"
    >
      <div class="mx-auto mb-6 h-16 w-16 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-2xl">
        ∅
      </div>
      <p class="text-xl font-bold mb-3 text-slate-900">
        {{ emptyTitle }}
      </p>
      <p class="text-sm text-slate-600 mb-8 max-w-sm mx-auto leading-relaxed">
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
