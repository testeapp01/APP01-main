<template>
  <div>
    <div class="md:hidden space-y-3">
      <article
        v-for="(row, idx) in rows"
        :key="`mobile-${row.id || idx}`"
        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
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

    <div class="hidden md:block overflow-x-auto">
      <table class="min-w-[720px] md:min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-transparent">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-3 md:px-4 py-2 text-left text-[11px] md:text-xs uppercase tracking-wider text-slate-500 whitespace-nowrap"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-transparent divide-y divide-slate-100 dark:divide-slate-800">
          <tr
            v-for="(row, idx) in rows"
            :key="row.id || idx"
            class="hover:bg-slate-50 dark:hover:bg-slate-800 transition"
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
    rows: { type: Array, default: () => [] }
  }
}
</script>

<style scoped>
table { border-collapse: collapse }
</style>
