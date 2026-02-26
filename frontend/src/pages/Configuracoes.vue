<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Configurações</h1>

    <!-- Commissions Settings -->
    <div class="mb-6">
      <div class="panel-inner">
        <h2 class="text-xl font-semibold mb-4">Comissões</h2>
        <form class="grid grid-cols-2 gap-4">
          <input type="number" placeholder="Percentual Padrão (%)" class="p-2 border rounded" />
          <input type="number" placeholder="Comissão Fixa (R$)" class="p-2 border rounded" />
          <input type="number" placeholder="Extra por Saco (R$)" class="p-2 border rounded" />
          <button type="submit" class="col-span-2 bg-blue-500 text-white px-4 py-2 rounded">Salvar</button>
        </form>
      </div>
    </div>

    <!-- Layout Settings -->
    <div>
      <div class="panel-inner">
        <h2 class="text-xl font-semibold mb-4">Layout</h2>
        <form>
          <label class="flex items-center mb-4">
            <input type="checkbox" class="mr-2" />
            Ativar Modo Escuro
          </label>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Salvar</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Settings',
};
</script>

<style scoped>
/* Add any custom styles here */
</style>