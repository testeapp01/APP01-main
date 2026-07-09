import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', name: 'Dashboard', component: () => import('../pages/Dashboard.vue'), meta: { requiresAuth: true } },
  { path: '/vendas', name: 'Vendas', component: () => import('../pages/Vendas.vue'), meta: { requiresAuth: true } },
  { path: '/vendas/cabecalho/:id', name: 'VendasCabecalhoDetalhe', component: () => import('../pages/VendaCabecalhoDetalhe.vue'), meta: { requiresAuth: true } },
  { path: '/clientes', name: 'Clientes', component: () => import('../pages/ClientesMotoristas.vue'), meta: { requiresAuth: true } },
  { path: '/motoristas', name: 'Motoristas', component: () => import('../pages/Motoristas.vue'), meta: { requiresAuth: true } },
  { path: '/compras', name: 'Compras', component: () => import('../pages/Compras.vue'), meta: { requiresAuth: true } },
  { path: '/compras/cabecalho/:id', name: 'ComprasCabecalhoDetalhe', component: () => import('../pages/CompraCabecalhoDetalhe.vue'), meta: { requiresAuth: true } },
  { path: '/produtos', name: 'Produtos', component: () => import('../pages/Produtos.vue'), meta: { requiresAuth: true } },
  { path: '/fornecedores', name: 'Fornecedores', component: () => import('../pages/Fornecedores.vue'), meta: { requiresAuth: true } },
  { path: '/usuarios', name: 'Usuarios', component: () => import('../pages/Usuarios.vue'), meta: { requiresAuth: true } },
  { path: '/usuarios-empresas', name: 'UsuariosEmpresas', component: () => import('../pages/UsuariosEmpresas.vue'), meta: { requiresAuth: true, requiresSystem: true } },
  { path: '/estoque', name: 'Estoque', component: () => import('../pages/Estoque.vue'), meta: { requiresAuth: true } },
  { path: '/integracoes', name: 'Integracoes', component: () => import('../pages/Integracoes.vue'), meta: { requiresAuth: true, requiresSystem: true } },
  { path: '/relatorios', name: 'Relatorios', component: () => import('../pages/Relatorios.vue'), meta: { requiresAuth: true } },
  { path: '/configuracoes', name: 'Configuracoes', component: () => import('../pages/Configuracoes.vue'), meta: { requiresAuth: true } },
  { path: '/login', name: 'Login', component: () => import('../pages/Login.vue'), meta: { publicOnly: true } },
  { path: '/sessao-expirada', name: 'SessaoExpirada', component: () => import('../pages/SessaoExpirada.vue'), meta: { publicOnly: true } },
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } }
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.hydrated) {
    await auth.hydrateSession()
  }

  const isAuthenticated = auth.isAuthenticated

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresSystem && !auth.isSystemUser) {
    return { path: '/' }
  }

  if (to.meta.publicOnly && isAuthenticated) {
    return { path: '/' }
  }

  return true
})

export default router
