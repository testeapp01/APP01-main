<template>
  <tr>
    <td class="p-2">
      {{ driver.name }}
    </td>
    <td class="p-2">
      {{ driver.cnh }}
    </td>
    <td class="p-2">
      {{ driver.phone }}
    </td>
  </tr>
</template>

<script>
export default {
  name: 'DriverRow',
  props: { driver: { type: Object, required: true } }
}
</script>
