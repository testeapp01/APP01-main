const { test, expect } = require('@playwright/test')

const appRoutes = [
  '/',
  '/vendas',
  '/compras',
  '/produtos',
  '/fornecedores',
  '/motoristas',
  '/clientes',
  '/relatorios',
  '/configuracoes',
]

function mockApi(page) {
  page.route('**/auth/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ user: { id: 1, name: 'Admin Mobile QA' } }),
    })
  })

  page.route('**/api/v1/relatorios/compras*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        rows: [
          {
            compra_grupo_id: 101,
            compra_id: 101,
            compra_cabecalho_id: 88,
            data_compra: '2026-03-01',
            fornecedor: 'Fornecedor Alfa',
            produto: 'Tomate',
            motorista: 'Joao',
            tipo_caminhao: 'Truck',
            quantidade: 12,
            valor_unitario: 10.4,
            custo_total: 124.8,
            comissao_total: 10,
            custo_final_real: 114.8,
            status_textual: 'AGUARDANDO',
            data_envio_prevista: '2026-03-02',
            data_entrega_prevista: '2026-03-03',
            itens_count: 2,
            valor_total_agregado: 140,
            status_timeline: [],
          },
        ],
        pagination: { page: 1, per_page: 20, pages: 1, total: 1 },
        options: { fornecedores: [], produtos: [], motoristas: [], status: ['AGUARDANDO'], ufs: [] },
        kpis: {},
        charts: {
          line: { labels: ['Mar'], data: [120], datasetLabel: 'Evolucao mensal' },
          bar: { labels: ['Fornecedor Alfa'], data: [120], datasetLabel: 'Fornecedor x volume' },
          pie: { labels: ['AGUARDANDO'], data: [1] },
        },
      }),
    })
  })

  page.route('**/api/v1/**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ items: [], total: 0 }),
    })
  })
}

test.describe('Mobile UX smoke', () => {
  test.beforeEach(async ({ page }) => {
    mockApi(page)

    await page.addInitScript(() => {
      localStorage.setItem('hf_token', 'mobile-smoke-token')
    })
  })

  test('public pages render without horizontal overflow', async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.removeItem('hf_token')
    })

    await page.goto('/login')
    await expect(page.locator('text=Entrar')).toBeVisible()

    const loginOverflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
    expect(loginOverflow).toBeLessThanOrEqual(1)

    await page.goto('/sessao-expirada')
    await expect(page.locator('h2')).toContainText(/Sess.o expirada/i)

    const sessionOverflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
    expect(sessionOverflow).toBeLessThanOrEqual(1)
  })

  for (const route of appRoutes) {
    test(`internal route ${route} keeps mobile layout stable`, async ({ page }) => {
      await page.goto(route)
      await page.waitForLoadState('networkidle')

      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
      expect(overflow).toBeLessThanOrEqual(2)

      const visibleMain = await page.locator('main').first().isVisible()
      expect(visibleMain).toBeTruthy()

      const clickableButtons = page.locator('button')
      const count = await clickableButtons.count()
      if (count > 0) {
        const first = page.locator('button:visible').first()
        const box = await first.boundingBox()
        if (box) {
          expect(box.height).toBeGreaterThanOrEqual(32)
        }
      }
    })
  }
})
