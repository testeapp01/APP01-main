<template>
  <div class="base-table">
    <div class="md:hidden space-y-3">
      <article
        v-for="(row, idx) in rows"
        :key="`mobile-${row.id || idx}`"
        class="rounded-lg border border-slate-200 bg-white p-4 shadow-[0_2px_8px_rgba(0,0,0,0.06)]"
        :class="{ 'cursor-pointer hover:border-slate-300 hover:shadow-[0_6px_16px_rgba(0,0,0,0.1)] transition duration-150' : rowClickable }"
        :title="rowClickable ? 'Clique para abrir' : ''"
        @click="handleRowClick(row)"
      >
        <div
          v-for="col in columns"
          :key="`mobile-${col.key}-${idx}`"
          class="flex items-start justify-between gap-3 py-2.5 border-b border-slate-100 last:border-b-0"
        >
          <span class="text-xs uppercase tracking-wider text-slate-500 font-bold">{{ col.label }}</span>
          <div class="text-sm text-slate-900 text-right font-medium flex-shrink-0">
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
      <table class="min-w-[720px] md:min-w-full divide-y divide-slate-200 dark:divide-slate-700 w-full">
        <thead class="bg-slate-50 dark:bg-slate-800/50">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3.5 text-left text-xs uppercase tracking-[0.08em] text-slate-600 font-bold whitespace-nowrap"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-transparent divide-y divide-slate-100 dark:divide-slate-800">
          <tr
            v-for="(row, idx) in rows"
            :key="row.id || idx"
            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition duration-150"
            :class="{ 'cursor-pointer': rowClickable }"
            :title="rowClickable ? 'Clique para abrir' : ''"
            @click="handleRowClick(row)"
          >
            <td
              v-for="col in columns"
              :key="col.key + '-cell'"
              class="px-4 py-3.5 text-sm text-slate-800 dark:text-slate-200 align-middle"
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
table { 
  border-collapse: collapse;
}

.table-shell {
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  background: #FFFFFF;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

thead {
  background: #F8FAFC;
  border-bottom: 1px solid #E2E8F0;
}

th {
  padding: 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748B;
  background: #F8FAFC;
}

tbody tr {
  border-bottom: 1px solid #F1F5F9;
  transition: background-color 0.2s ease;
}

tbody tr:last-child {
  border-bottom: none;
}

td {
  padding: 1rem;
  font-size: 0.95rem;
  color: #0F172A;
  font-weight: 500;
}

.row-clickable-row {
  cursor: pointer;
}

.row-clickable-row:hover {
  background-color: #F8FAFC;
}

.row-clickable-row:hover td {
  background-color: transparent;
}
</style>
