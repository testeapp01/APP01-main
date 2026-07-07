<template>
  <div class="overflow-x-auto">
    <table class="w-full table-auto">
      <thead class="text-left text-sm text-muted">
        <slot name="head" />
      </thead>
      <tbody class="text-sm">
        <slot name="body" />
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'BaseTable'
}
</script>
