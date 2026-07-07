import axios from 'axios';

const envBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').trim();
const API_BASE_URL = import.meta.env.PROD
  ? '/api/v1'
  : (envBaseUrl || 'http://localhost:8000/api/v1');

// Mock mode for development/testing without backend
const MOCK_MODE = !import.meta.env.PROD && !envBaseUrl;

const createMockAdapter = () => {
  return async (config) => {
    const url = config.url || '';
    const storedEmail = localStorage.getItem('hf_mock_email') || 'user@example.com';
    
    // Debug log all requests
    console.log('🔵 Mock adapter - URL:', url, '| Method:', config.method);

    if (url === '/auth/login') {
      const { email } = config.data ? JSON.parse(config.data) : {};
      const mockEmail = email || 'mock@example.com';
      localStorage.setItem('hf_mock_email', mockEmail);
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: {
          token: 'mock_token_' + Date.now(),
          user: { id: 1, email: mockEmail, name: mockEmail.split('@')[0] || 'Mock User', role: 'admin' }
        }
      };
    }

    if (url === '/auth/me') {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: { user: { id: 1, email: storedEmail, name: storedEmail.split('@')[0] || 'Mock User', role: 'admin' } }
      };
    }

    if (url.startsWith('/compras')) {
      console.log('✅ Matched /compras endpoint');
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: {
          items: [
            { id: 1, fornecedor_id: 1, fornecedor: 'Fornecedor A', total: 1500.50, itens_count: 2, status: 'recebida', created_at: '2026-07-01' },
            { id: 2, fornecedor_id: 2, fornecedor: 'Fornecedor B', total: 2300.75, itens_count: 1, status: 'pendente', created_at: '2026-07-02' },
            { id: 3, fornecedor_id: 1, fornecedor: 'Fornecedor A', total: 890.25, itens_count: 3, status: 'recebida', created_at: '2026-07-03' }
          ],
          total: 3
        }
      };
    }

    if (url.startsWith('/vendas') && !url.includes('/vendas/')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: {
          items: [
            { id: 1, cliente: 'Empresa A', itens_count: 3, valor_total: 3200.00, status: 'ENTREGUE', data_envio_prevista: '2026-07-01', data_entrega_prevista: '2026-07-05' },
            { id: 2, cliente: 'Empresa B', itens_count: 2, valor_total: 1850.50, status: 'AGUARDANDO', data_envio_prevista: '2026-07-02', data_entrega_prevista: '2026-07-06' },
            { id: 3, cliente: 'Empresa C', itens_count: 1, valor_total: 2100.75, status: 'AGUARDANDO', data_envio_prevista: '2026-07-03', data_entrega_prevista: '2026-07-07' }
          ],
          total: 3
        }
      };
    }

    if (url.includes('/clientes')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, nome: 'Empresa A' },
          { id: 2, nome: 'Empresa B' },
          { id: 3, nome: 'Empresa C' }
        ]
      };
    }

    if (url.includes('/produtos')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: {
          items: [
            { id: 1, nome: 'Produto A', custo_medio: 100.00, custo: 95.00 },
            { id: 2, nome: 'Produto B', custo_medio: 250.50, custo: 240.00 },
            { id: 3, nome: 'Produto C', custo_medio: 50.75, custo: 48.00 }
          ],
          total: 3
        }
      };
    }

    if (url.includes('/fornecedores')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, nome: 'Fornecedor A', cnpj: '12.345.678/0001-90', contato: 'contato@fornecedora.com' },
          { id: 2, nome: 'Fornecedor B', cnpj: '98.765.432/0001-10', contato: 'vendas@fornecedorb.com' },
          { id: 3, nome: 'Fornecedor C', cnpj: '11.222.333/0001-55', contato: 'info@fornecedorc.com' }
        ]
      };
    }

    if (url.includes('/motoristas')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, nome: 'João Silva', cpf: '123.456.789-00', telefone: '11 99999-0001' },
          { id: 2, nome: 'Maria Santos', cpf: '987.654.321-00', telefone: '11 99999-0002' },
          { id: 3, nome: 'Pedro Oliveira', cpf: '456.789.123-00', telefone: '11 99999-0003' }
        ]
      };
    }

    if (url.includes('/relatorios/dashboard')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: {
          cards: { sales_total: 7150.25, purchases_total: 4690.50, estimated_profit: 2459.75 },
          chart_data: [
            { label: 'Dia 1', value: 450 }, { label: 'Dia 2', value: 620 }, { label: 'Dia 3', value: 800 },
            { label: 'Dia 4', value: 720 }, { label: 'Dia 5', value: 950 }, { label: 'Dia 6', value: 1100 },
            { label: 'Dia 7', value: 1480 }
          ]
        }
      };
    }

    if (url.includes('/usuarios')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, nome: 'Admin User', email: 'admin@empresa.com', role: 'admin', status: true },
          { id: 2, nome: 'Gerente Vendas', email: 'gerente@empresa.com', role: 'manager', status: true },
          { id: 3, nome: 'Operador', email: 'operador@empresa.com', role: 'user', status: true }
        ]
      };
    }

    if (url.includes('/estoque')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, produto: 'Produto A', quantidade: 150, tipo_movimento: 'entrada', data: '2026-07-01' },
          { id: 2, produto: 'Produto B', quantidade: 50, tipo_movimento: 'saida', data: '2026-07-02' },
          { id: 3, produto: 'Produto C', quantidade: 200, tipo_movimento: 'entrada', data: '2026-07-03' }
        ]
      };
    }

    if (url.includes('/usuarios-empresas')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, usuario_id: 1, usuario_nome: 'Admin User', empresa_id: 1, empresa_nome: 'Empresa Matriz', role_empresa: 'admin', status: true },
          { id: 2, usuario_id: 2, usuario_nome: 'Gerente Vendas', empresa_id: 1, empresa_nome: 'Empresa Matriz', role_empresa: 'manager', status: true },
          { id: 3, usuario_id: 3, usuario_nome: 'Operador', empresa_id: 2, empresa_nome: 'Filial SP', role_empresa: 'user', status: true }
        ]
      };
    }

    if (url.includes('/integracoes')) {
      return {
        status: 200,
        statusText: 'OK',
        headers: config.headers,
        config,
        data: [
          { id: 1, nome: 'Integração Stripe', tipo: 'API', status: 'ativo', ultimo_sincronismo: '2026-07-03 10:30' },
          { id: 2, nome: 'Integração Shopify', tipo: 'Webhook', status: 'ativo', ultimo_sincronismo: '2026-07-03 11:15' },
          { id: 3, nome: 'Integração Contábil', tipo: 'OAuth', status: 'inativo', ultimo_sincronismo: 'Nunca' }
        ]
      };
    }

    throw new Error('Mock endpoint not found: ' + url);
  };
};

const api = axios.create({
  baseURL: MOCK_MODE ? 'http://mock-api' : API_BASE_URL,
  timeout: 15000,
  withCredentials: true,
});

if (MOCK_MODE) {
  api.defaults.adapter = createMockAdapter();
}

api.interceptors.request.use(config => {
  if (typeof config.url === 'string') {
    if (config.url.startsWith('/api/v1/')) {
      config.url = config.url.replace('/api/v1', '')
    } else if (config.url.startsWith('api/v1/')) {
      config.url = config.url.replace('api/v1', '')
    }
  }
  return config;
}, error => Promise.reject(error));

api.interceptors.response.use(response => response, error => {
  if (error.response && error.response.status === 401) {
    if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
      window.location.assign('/login');
    }
  }
  return Promise.reject(error);
});

export default api;
