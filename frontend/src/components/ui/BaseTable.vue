<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
      <thead class="bg-transparent">
        <tr>
          <th v-for="col in columns" :key="col.key" class="px-4 py-2 text-left text-xs uppercase tracking-wider text-slate-500">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-transparent divide-y divide-slate-100 dark:divide-slate-800">
        <tr v-for="(row, idx) in rows" :key="row.id || idx" class="hover:bg-slate-50 dark:hover:bg-slate-800 transition">
          <td v-for="col in columns" :key="col.key + '-cell'" class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
            <slot :name="col.key" :row="row">{{ row[col.key] }}</slot>
          </td>
        </tr>
      </tbody>
    </table>
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
