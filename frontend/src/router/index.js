import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', name: 'Dashboard', component: () => import('../pages/Dashboard.vue') },
  { path: '/vendas', name: 'Vendas', component: () => import('../pages/Vendas.vue') },
  { path: '/clientes', name: 'Clientes', component: () => import('../pages/ClientesMotoristas.vue') },
  { path: '/motoristas', name: 'Motoristas', component: () => import('../pages/Motoristas.vue') },
  { path: '/compras', name: 'Compras', component: () => import('../pages/Compras.vue') },
  { path: '/estoque', name: 'Produtos', component: () => import('../pages/Produtos.vue') },
  { path: '/fornecedores', name: 'Fornecedores', component: () => import('../pages/Fornecedores.vue') },
  { path: '/relatorios', name: 'Relatorios', component: () => import('../pages/Relatorios.vue') },
  { path: '/configuracoes', name: 'Configuracoes', component: () => import('../pages/Configuracoes.vue') },
  { path: '/login', name: 'Login', component: () => import('../pages/Login.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } }
})

export default router
