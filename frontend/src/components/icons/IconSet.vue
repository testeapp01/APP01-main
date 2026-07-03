<template>
  <!-- Renderiza SVG icon baseado em 'name' prop -->
  <svg
    :class="['icon', `icon-${name}`, iconClass]"
    :viewBox="icons[name]?.viewBox || '0 0 24 24'"
    :width="size"
    :height="size"
    fill="currentColor"
    v-bind="$attrs"
  >
    <g v-html="icons[name]?.svg || ''" />
  </svg>
</template>

<script>
export default {
  name: 'IconSet',
  props: {
    name: {
      type: String,
      required: true
    },
    size: {
      type: Number,
      default: 24
    },
    iconClass: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      icons: {
        // NAVIGATION
        dashboard: {
          viewBox: '0 0 24 24',
          svg: `<rect x="3" y="3" width="7" height="7" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <rect x="14" y="3" width="7" height="7" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <rect x="3" y="14" width="7" height="7" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <rect x="14" y="14" width="7" height="7" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>`
        },
        
        // CADASTROS
        empresas: {
          viewBox: '0 0 24 24',
          svg: `<path d="M3 9h18M3 9l1-6h16l1 6M5 9v8c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V9M10 13h4M10 17h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        clientes: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        fornecedores: {
          viewBox: '0 0 24 24',
          svg: `<path d="M12 2c4.4 0 8 3.6 8 8s-3.6 8-8 8-8-3.6-8-8 3.6-8 8-8M9 10h6M9 14h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        produtos: {
          viewBox: '0 0 24 24',
          svg: `<path d="M6 9l6-4 6 4v8c0 1-1 2-2 2H8c-1 0-2-1-2-2V9M12 5v10M6 13h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        categorias: {
          viewBox: '0 0 24 24',
          svg: `<rect x="3" y="3" width="8" height="8" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <rect x="13" y="3" width="8" height="8" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <rect x="3" y="13" width="8" height="8" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <rect x="13" y="13" width="8" height="8" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>`
        },
        usuarios: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="9" cy="7" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M5 22c0-3 1.8-4 4-4s4 1 4 4" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="18" cy="9" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M15 22c0-2.5 1.3-3.5 3-3.5s3 1 3 3.5" fill="none" stroke="currentColor" stroke-width="2"/>`
        },
        
        // OPERAÇÕES
        pedidos: {
          viewBox: '0 0 24 24',
          svg: `<rect x="3" y="4" width="18" height="16" fill="none" stroke="currentColor" stroke-width="2" rx="2"/>
                <path d="M9 9h6M9 14h3M6 9h1M6 14h1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        compras: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="9" cy="20" r="1" fill="currentColor"/>
                <circle cx="19" cy="20" r="1" fill="currentColor"/>
                <path d="M1 1h4l2 11h13l1-7H8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        estoque: {
          viewBox: '0 0 24 24',
          svg: `<rect x="2" y="7" width="20" height="12" fill="none" stroke="currentColor" stroke-width="2" rx="1"/>
                <path d="M6 7V5c0-1 1-2 2-2h8c1 0 2 1 2 2v2M6 11h12M6 15h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        financeiro: {
          viewBox: '0 0 24 24',
          svg: `<path d="M12 2c5.5 0 10 4.5 10 10s-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2M12 7v10M9 10h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        motoristas: {
          viewBox: '0 0 24 24',
          svg: `<path d="M5 11l7-7 7 7M12 4v12M7 16h10v4H7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        
        // ANALYTICS & SYSTEM
        relatorios: {
          viewBox: '0 0 24 24',
          svg: `<path d="M3 12h4v8H3zM9 8h4v12H9zM15 5h4v15h-4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        integracoes: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="6" cy="12" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="18" cy="12" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M8 12h8M6 9v6M18 9v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        configuracoes: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M12 2v3m0 14v3m10-5h-3m-14 0H2m15.4-10.4l-2.1 2.1m-6.6 6.6l-2.1 2.1m0-14.8l2.1 2.1m6.6 6.6l2.1 2.1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        
        // ACTIONS
        search: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="11" cy="11" r="8" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        notificacoes: {
          viewBox: '0 0 24 24',
          svg: `<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13 21a2 2 0 1 1-4 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        perfil: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="12" cy="9" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M4 20c0-4 3-7 8-7s8 3 8 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        tema: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="12" cy="12" r="5" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6m-16.78 7.78l4.24-4.24m4.24-4.24l4.24-4.24" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        logout: {
          viewBox: '0 0 24 24',
          svg: `<path d="M9 21H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2h4M16 8l4 4m-4 4l4-4M9 12h7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        
        // NAVIGATION ARROWS
        chevron_left: {
          viewBox: '0 0 24 24',
          svg: `<polyline points="15 18 9 12 15 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        chevron_right: {
          viewBox: '0 0 24 24',
          svg: `<polyline points="9 18 15 12 9 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        chevron_down: {
          viewBox: '0 0 24 24',
          svg: `<polyline points="6 9 12 15 18 9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        
        // ADDITIONAL
        adicionar: {
          viewBox: '0 0 24 24',
          svg: `<line x1="12" y1="5" x2="12" y2="19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        editar: {
          viewBox: '0 0 24 24',
          svg: `<path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        deletar: {
          viewBox: '0 0 24 24',
          svg: `<polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="10" y1="11" x2="10" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="14" y1="11" x2="14" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        },
        atualizar: {
          viewBox: '0 0 24 24',
          svg: `<polyline points="23 4 23 10 17 10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20.49 15a9 9 0 1 1-2-8.83" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        sucesso: {
          viewBox: '0 0 24 24',
          svg: `<polyline points="20 6 9 17 4 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
        },
        erro: {
          viewBox: '0 0 24 24',
          svg: `<circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                <line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`
        }
      }
    }
  }
}
</script>

<style scoped>
.icon {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}
</style>
