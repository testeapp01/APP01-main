<template>
  <tr>
    <td class="border p-2">
      {{ client.nome }}
    </td>
    <td class="border p-2">
      {{ client.cpf_cnpj }}
    </td>
    <td class="border p-2">
      {{ client.uf }}
    </td>
    <td class="border p-2">
      {{ client.telefone }}
    </td>
    <td class="border p-2">
      <button class="bg-yellow-500 text-white px-2 py-1 rounded">
        Editar
      </button>
      <button class="bg-red-500 text-white px-2 py-1 rounded">
        Excluir
      </button>
    </td>
  </tr>
</template>

<script>
export default {
  props: {
    client: {
      type: Object,
      required: true,
    },
  },
};
</script>

<style scoped>
/* Add any custom styles here */
</style>