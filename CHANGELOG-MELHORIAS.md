# Melhorias aplicadas — Hortifrut Box Management

Este documento resume tudo que foi revisado e alterado no projeto. Priorizei
correções **reais e verificadas** (com testes passando) em vez de mudanças
cosméticas espalhadas por todo o código — o projeto já estava bem estruturado
(middlewares, observabilidade, correlation-id, rate limit, etc.), então o
maior valor estava em achar e consertar problemas concretos.

## 🐛 Bug crítico de negócio: estoque nunca era atualizado

**Este era o problema mais sério encontrado.** O README do backend afirma que
o sistema faz "atualização de estoque por média ponderada", mas isso nunca
acontecia de verdade:

- **Ao confirmar recebimento de uma compra** (`PurchaseService::receivePurchase`),
  o sistema só mudava o status para `RECEBIDA` — nunca somava a quantidade
  recebida ao estoque do produto, nem recalculava o custo médio ponderado.
- **Ao confirmar entrega de uma venda** (`SalesService::confirmDelivery`), o
  sistema só mudava o status para `ENTREGUE` — nunca conferia se havia
  estoque suficiente, nem descontava a quantidade vendida.

Ou seja: era possível vender mais do que existia em estoque, e o estoque
nunca refletia compras recebidas. Os testes automatizados existentes já
cobriam esse comportamento esperado, mas falhavam silenciosamente porque o
código de verdade nunca tinha sido implementado.

**Correção:**
- `ProductRepository` ganhou um método `updateStock()` para persistir estoque
  e custo médio.
- `PurchaseService::receivePurchase()` agora calcula a média ponderada
  (estoque atual × custo médio atual + custo desta compra) ÷ novo estoque, e
  grava o novo estoque/custo no produto.
- `SalesService::confirmDelivery()` agora valida se há estoque suficiente
  (lança erro 409 se não houver, cancelando a transação) e desconta a
  quantidade vendida ao confirmar a entrega.
- Os 18 testes do backend (incluindo os testes de integração de
  compra/venda/rollback) agora passam com asserts reais — antes, 3 deles
  falhavam.

## 🔒 Segurança

### `AuthController` simplificado e endurecido
O controller de login tinha uma lógica extensa de "descoberta automática" de
tabela/colunas de usuários (tentava `users`, `usuarios`, `tb_users`, etc., e
no pior caso escaneava **todas as tabelas do banco** cujo nome batesse com
`/user|usuario|acesso|login|conta/i`, linha por linha, até achar algo que
parecesse uma credencial válida). Isso trazia dois problemas reais:

1. **Aceitava senha em texto puro ou hash MD5/SHA1 como válidos** — se uma
   linha de qualquer tabela candidata tivesse uma coluna chamada `senha`,
   `passwd` etc. com o valor batendo, o login passava.
2. **Performance/DoS**: no pior caso, fazia `SELECT * FROM tabela LIMIT 5000`
   e comparava senha contra cada linha, para várias tabelas, em toda
   tentativa de login que não batesse de primeira.

Como o schema real do projeto é conhecido e fixo (`users` com
`id, name, email, password, role` — confirmado em `database/migrations` e
`tools/seed.php`, que já usa `password_hash(..., PASSWORD_BCRYPT)`), essa
complexidade não tinha utilidade real e só aumentava a superfície de ataque.

**Correção:** `AuthController` agora consulta diretamente a tabela `users`
com uma query parametrizada, aceita **apenas** senhas com hash bcrypt válido
(`password_verify`), e continua fazendo upgrade automático do hash quando o
custo do bcrypt está desatualizado. Também roda `password_verify` mesmo
quando o e-mail não existe, para não vazar por tempo de resposta se o e-mail
está cadastrado.

> ⚠️ Os testes antigos (`AuthControllerTest.php`) validavam explicitamente o
> comportamento antigo (aceitar senha em texto puro, MD5, tabelas alternativas
> como `usuarios`/`tb_users` etc.). Reescrevi os testes para validar o novo
> comportamento seguro, incluindo um teste de regressão que garante que uma
> senha gravada em texto puro **não** é mais aceita.

### `AuthMiddleware` não vaza mais detalhes internos
Antes, um token inválido retornava `{"error": "Token inválido", "detail": "<mensagem da exception>"}`
— isso pode expor detalhes da biblioteca JWT/implementação. Agora a mensagem
de erro é genérica para o cliente, o detalhe vai só para o log do servidor, e
tokens expirados retornam uma mensagem específica ("Sessão expirada") em vez
de serem tratados como token inválido genérico.

### Dados de entrada não são mais corrompidos
O middleware global `InputSanitizer` rodava `htmlspecialchars()` em **todo**
campo de texto recebido pela API, antes mesmo de salvar no banco. Isso
convertia, por exemplo, `"Fornecedor A & B Ltda"` em
`"Fornecedor A &amp; B Ltda"` **permanentemente no banco de dados** —
qualquer nome com `&`, `'`, `<`, `>` ou aspas ficava corrompido.

Essa proteção também era desnecessária: a API só retorna JSON (não HTML), o
acesso ao banco já usa queries parametrizadas (proteção real contra SQL
injection), o frontend Vue já escapa automaticamente tudo que é interpolado
com `{{ }}` (não há nenhum uso de `v-html` no projeto), e o gerador de PDF
(`OrderPdfService`) já faz seu próprio `htmlspecialchars` no momento de
montar o HTML. Ou seja, o dado era escapado sem necessidade e nunca mais
"desescapado" depois.

**Correção:** o sanitizador agora só remove espaços em branco (`trim`), sem
alterar o conteúdo do texto.

## 🧹 Limpeza de código morto

- Removida a pasta `backend/src/Controllers/ProductController.php` — um
  controller órfão, fora do autoload PSR-4 (`composer.json` só mapeia
  `App\` para `app/`), inacessível pela aplicação, e que ainda referenciava
  uma tabela `products` que nem existe no schema (a tabela real é
  `produtos`). Puro ruído para quem for entender o projeto.

## ✅ Como validar

Rodei a suíte de testes PHPUnit completa depois de cada mudança:

```bash
cd backend
composer install
vendor/bin/phpunit
```

Resultado final: **18 testes, 55 assertions, 100% passando** (antes de
começar, 3 testes já falhavam por causa do bug de estoque).

## O que eu recomendaria olhar em seguida

Não mexi nestes pontos porque são decisões de arquitetura/produto, não bugs,
mas vale registrar:

1. **Token JWT em `localStorage`** (`frontend/src/stores/auth.js`) — funciona,
   mas é vulnerável a XSS (qualquer script malicioso injetado no front pode
   ler o token). O ideal a médio prazo é migrar para cookie `httpOnly`, o que
   exige mudanças no backend (CORS/CSRF) e no frontend.
2. **Rate limit de login** — hoje o login está sob o rate limit genérico da
   API (120 req/60s por IP). Para reduzir ainda mais o risco de força bruta,
   vale um limite mais agressivo específico para `/auth/login`.
3. **`.env` local com `JWT_SECRET` real commitado no zip** — o arquivo já
   está no `.gitignore`, mas como veio dentro do `.zip` enviado, recomendo
   trocar esse segredo antes de ir para produção (o `docker-compose.yml` já
   usa variáveis de ambiente próprias para produção, então isso afeta só o
   ambiente local).
