<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <!-- Info -->
    <div class="text-sm text-slate-600 font-medium">
      <span v-if="total > 0">
        Mostrando
        <span class="text-slate-900 font-semibold">{{ start }} – {{ end }}</span>
        de
        <span class="text-slate-900 font-semibold">{{ total }}</span>
        registros
      </span>
      <span v-else class="text-slate-500">Nenhum registro encontrado</span>
    </div>

    <!-- Controls -->
    <div class="flex items-center gap-4">
      <!-- Items Per Page Selector -->
      <div class="flex items-center gap-2">
        <label for="itemsPerPage" class="text-sm text-slate-600">Por página:</label>
        <select
          id="itemsPerPage"
          :value="pageSize"
          @change="$emit('update:pageSize', Number($event.target.value))"
          class="px-2 py-1.5 text-sm border border-slate-200 rounded-lg bg-white hover:border-slate-300 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>

      <!-- Navigation Buttons -->
      <div class="flex items-center gap-1">
        <button
          :disabled="currentPage <= 1"
          class="flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          @click="$emit('update:currentPage', currentPage - 1)"
          title="Página anterior"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="w-4 h-4"
          >
            <polyline points="15,18 9,12 15,6" />
          </svg>
        </button>

        <!-- Page Numbers -->
        <div class="hidden sm:flex items-center gap-1">
          <!-- First page -->
          <button
            v-if="pages[0] > 1"
            class="w-9 h-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors text-sm font-medium"
            @click="$emit('update:currentPage', 1)"
          >
            1
          </button>

          <!-- Ellipsis -->
          <span v-if="pages[0] > 2" class="text-slate-400">…</span>

          <!-- Page buttons -->
          <button
            v-for="page in pages"
            :key="page"
            :class="[
              'w-9 h-9 rounded-lg border text-sm font-medium transition-colors',
              currentPage === page
                ? 'bg-green-50 border-green-500 text-green-700'
                : 'border-slate-200 text-slate-600 hover:bg-slate-50'
            ]"
            @click="$emit('update:currentPage', page)"
          >
            {{ page }}
          </button>

          <!-- Ellipsis -->
          <span v-if="pages[pages.length - 1] < totalPages - 1" class="text-slate-400">…</span>

          <!-- Last page -->
          <button
            v-if="pages[pages.length - 1] < totalPages"
            class="w-9 h-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors text-sm font-medium"
            @click="$emit('update:currentPage', totalPages)"
          >
            {{ totalPages }}
          </button>
        </div>

        <!-- Mobile page indicator -->
        <div class="sm:hidden px-2 py-1 text-sm text-slate-600 font-medium">
          {{ currentPage }} / {{ totalPages }}
        </div>

        <button
          :disabled="currentPage >= totalPages"
          class="flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          @click="$emit('update:currentPage', currentPage + 1)"
          title="Próxima página"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="w-4 h-4"
          >
            <polyline points="9,18 15,12 9,6" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaginationPremium',
  props: {
    currentPage: {
      type: Number,
      default: 1,
    },
    pageSize: {
      type: Number,
      default: 25,
    },
    total: {
      type: Number,
      default: 0,
    },
  },
  emits: ['update:currentPage', 'update:pageSize'],
  computed: {
    totalPages() {
      return Math.max(1, Math.ceil(this.total / this.pageSize))
    },
    start() {
      if (this.total === 0) return 0
      return (this.currentPage - 1) * this.pageSize + 1
    },
    end() {
      return Math.min(this.currentPage * this.pageSize, this.total)
    },
    pages() {
      const pages = []
      const maxButtons = 5
      const half = Math.floor(maxButtons / 2)

      let start = Math.max(1, this.currentPage - half)
      let end = Math.min(this.totalPages, start + maxButtons - 1)

      if (end - start < maxButtons - 1) {
        start = Math.max(1, end - maxButtons + 1)
      }

      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      return pages
    },
  },
}
</script>

<style scoped>
button:disabled {
  pointer-events: none;
}

select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpolyline points='6 9l6 6 6-6'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  background-size: 1.25rem;
  padding-right: 2rem;
}
</style>
