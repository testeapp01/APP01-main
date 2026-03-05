<template>
  <div class="base-table">
    <div class="md:hidden space-y-3">
      <article
        v-for="(row, idx) in rows"
        :key="`mobile-${row.id || idx}`"
        class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] backdrop-blur"
        :class="{ 'cursor-pointer hover:border-emerald-300 hover:shadow-md transition': rowClickable }"
        :title="rowClickable ? 'Clique para abrir os itens' : ''"
        @click="handleRowClick(row)"
      >
        <div
          v-for="col in columns"
          :key="`mobile-${col.key}-${idx}`"
          class="flex items-start justify-between gap-3 py-1.5"
        >
          <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">{{ col.label }}</span>
          <div class="text-sm text-slate-700 text-right">
            <slot
              :name="col.key"
              :row="row"
            >
              {{ row[col.key] }}
            </slot>
          </div>
        </div>
      </article>
    </div>

    <div class="hidden md:block overflow-x-auto overflow-y-visible table-shell">
      <table class="min-w-[720px] md:min-w-full divide-y divide-slate-200/80 dark:divide-slate-700">
        <thead class="bg-transparent">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-3 md:px-4 py-3 text-left text-[11px] md:text-xs uppercase tracking-[0.08em] text-slate-500 whitespace-nowrap"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white/75 dark:bg-transparent divide-y divide-slate-100/80 dark:divide-slate-800">
          <tr
            v-for="(row, idx) in rows"
            :key="row.id || idx"
            class="hover:bg-emerald-50/40 dark:hover:bg-slate-800 transition"
            :class="{ 'cursor-pointer row-clickable-row': rowClickable }"
            :title="rowClickable ? 'Clique para abrir os itens' : ''"
            @click="handleRowClick(row)"
          >
            <td
              v-for="col in columns"
              :key="col.key + '-cell'"
              class="px-3 md:px-4 py-2.5 md:py-3 text-xs md:text-sm text-slate-700 dark:text-slate-200 align-top"
            >
              <slot
                :name="col.key"
                :row="row"
              >
                {{ row[col.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BaseTable',
  props: {
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    rowClickable: { type: Boolean, default: false }
  },
  emits: ['row-click'],
  methods: {
    handleRowClick(row) {
      if (!this.rowClickable) return
      this.$emit('row-click', row)
    }
  }
}
</script>

<style scoped>
table { border-collapse: collapse }

.table-shell {
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 16px;
  background: linear-gradient(160deg, rgba(255,255,255,0.94), rgba(255,255,255,0.82));
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(6px);
}

.row-clickable-row td {
  transition: background-color .14s ease, box-shadow .14s ease;
}

.row-clickable-row:hover td {
  background-color: rgba(16, 185, 129, 0.06);
}
</style>
