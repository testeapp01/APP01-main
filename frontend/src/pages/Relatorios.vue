<template>
  <div>
    <h1 class="text-2xl font-bold mb-4 text-gray-800">Relatórios</h1>

    <!-- Report Filters -->
    <div class="mb-6 grid grid-cols-4 gap-4">
      <select class="p-3 border border-gray-300 rounded-xl">
        <option>Tipo de Relatório</option>
        <option value="faturamento">Faturamento</option>
        <option value="lucro">Lucro</option>
        <option value="compras">Compras</option>
        <option value="vendas">Vendas</option>
      </select>
      <input type="date" placeholder="Data Inicial" class="p-3 border border-gray-300 rounded-xl" />
      <input type="date" placeholder="Data Final" class="p-3 border border-gray-300 rounded-xl" />
      <input type="text" placeholder="Produto/Cliente/Motorista" class="p-3 border border-gray-300 rounded-xl" />
    </div>

    <!-- Chart -->
    <div class="panel-inner">
      <h2 class="text-xl font-semibold mb-4 text-gray-800">Gráfico</h2>
      <div class="w-full h-60">
        <canvas id="report-chart" class="w-full h-full"></canvas>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  name: 'Reports',
  mounted() {
    const ctx = document.getElementById('report-chart');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr'],
        datasets: [
          {
            label: 'Faturamento',
            data: [10000, 15000, 20000, 25000],
            backgroundColor: 'rgba(37, 99, 235, 0.7)',
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          },
        },
      },
    });
  },
};
</script>

<style scoped>
/* Add any custom styles here */
</style>