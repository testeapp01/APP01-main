<template>
  <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="inline-flex">
        <div class="w-16 h-16 border-4 border-slate-200 border-t-green-600 rounded-full animate-spin" />
      </div>
      <div class="text-slate-600 font-medium">{{ loadingText }}</div>
    </div>

    <!-- Empty State -->
    <div v-else class="space-y-6 max-w-sm">
      <!-- Icon -->
      <div class="text-6xl opacity-30">{{ icon }}</div>

      <!-- Title -->
      <div>
        <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
        <p class="text-slate-500 text-sm mt-1">{{ description }}</p>
      </div>

      <!-- Actions -->
      <div v-if="$slots.actions" class="pt-4">
        <slot name="actions" />
      </div>
      <button
        v-else-if="actionLabel"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors font-medium text-sm"
        @click="$emit('action')"
      >
        <span>{{ actionIcon }}</span>
        {{ actionLabel }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmptyState',
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    loadingText: {
      type: String,
      default: 'Carregando...',
    },
    icon: {
      type: String,
      default: '📭',
    },
    title: {
      type: String,
      default: 'Nenhum registro encontrado',
    },
    description: {
      type: String,
      default: 'Comece adicionando dados para começar',
    },
    actionLabel: {
      type: String,
      default: null,
    },
    actionIcon: {
      type: String,
      default: '➕',
    },
  },
  emits: ['action'],
}
</script>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
