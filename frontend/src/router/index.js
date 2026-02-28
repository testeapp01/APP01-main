import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', name: 'Dashboard', component: () => import('../pages/Dashboard.vue'), meta: { requiresAuth: true } },
  { path: '/vendas', name: 'Vendas', component: () => import('../pages/Vendas.vue'), meta: { requiresAuth: true } },
  { path: '/clientes', name: 'Clientes', component: () => import('../pages/ClientesMotoristas.vue'), meta: { requiresAuth: true } },
  { path: '/motoristas', name: 'Motoristas', component: () => import('../pages/Motoristas.vue'), meta: { requiresAuth: true } },
  { path: '/compras', name: 'Compras', component: () => import('../pages/Compras.vue'), meta: { requiresAuth: true } },
  { path: '/estoque', name: 'Produtos', component: () => import('../pages/Produtos.vue'), meta: { requiresAuth: true } },
  { path: '/fornecedores', name: 'Fornecedores', component: () => import('../pages/Fornecedores.vue'), meta: { requiresAuth: true } },
  { path: '/relatorios', name: 'Relatorios', component: () => import('../pages/Relatorios.vue'), meta: { requiresAuth: true } },
  { path: '/configuracoes', name: 'Configuracoes', component: () => import('../pages/Configuracoes.vue'), meta: { requiresAuth: true } },
  { path: '/login', name: 'Login', component: () => import('../pages/Login.vue'), meta: { publicOnly: true } },
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } }
})

router.beforeEach((to) => {
  const token = (typeof localStorage !== 'undefined') ? localStorage.getItem('hf_token') : null
  const isAuthenticated = !!token

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.publicOnly && isAuthenticated) {
    return { path: '/' }
  }

  return true
})

export default router
