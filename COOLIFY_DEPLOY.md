# Deploy no Coolify

Este repositório já inclui um stack pronto para Coolify em `docker-compose.coolify.yml`.

## 1) Criar recurso no Coolify

1. Em Coolify, crie um novo recurso do tipo **Docker Compose**.
2. Conecte este repositório Git.
3. Selecione o arquivo `docker-compose.coolify.yml`.

## 2) Configurar variáveis de ambiente

Copie os valores de `.env.coolify.example` para as variáveis do ambiente no Coolify.

Variáveis obrigatórias:
- `DB_HOST` ou `MYSQL_HOST`
- `DB_PORT` ou `MYSQL_PORT`
- `DB_NAME` ou `DB_DATABASE` ou `MYSQL_DATABASE`
- `DB_USER` ou `DB_USERNAME` ou `MYSQL_USER`
- `DB_PASS` ou `DB_PASSWORD` ou `MYSQL_PASSWORD`
- `VITE_API_BASE_URL`
- `CORS_ALLOWED_ORIGINS`

Observacao importante:
- O backend agora aceita tanto variaveis `DB_*` quanto `MYSQL_*`.
- Se voce estiver usando um recurso MySQL gerenciado pelo Coolify, o mais seguro e preencher explicitamente `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` com os dados exibidos no recurso do banco.

## 3) Domínio

No serviço `frontend`, configure o domínio principal (ex.: `app.seudominio.com`).

Observação: o Nginx do frontend já encaminha `/api/v1` internamente para o serviço `backend`, então não é obrigatório expor um domínio público separado para a API.

## 4) Deploy

Clique em **Deploy** no Coolify.

## 5) Inicialização de banco e migration

No stack `docker-compose.coolify.yml`, o serviço `backend` já executa automaticamente ao iniciar:

```bash
php tools/bootstrap_production.php
```

Esse bootstrap de producao executa:

```bash
php tools/create_db.php
php tools/run_migrations.php
php tools/ensure_admin_user.php   # somente se ADMIN_PASSWORD estiver configurada
```

Ou seja, no primeiro deploy (e nos proximos) nao precisa rodar manualmente.
Dados ficticios nao sao inseridos automaticamente em producao.

Se o banco estiver vazio apos o deploy, quase sempre o problema e um destes:
- variaveis do banco preenchidas com nome errado
- `DB_HOST` apontando para host interno incorreto
- senha/usuario do recurso MySQL divergentes
- o container do backend nao conseguiu alcançar o recurso MySQL

Para conferir no Coolify, abra os logs do serviço `backend` e valide mensagens como:
- `Database '...' created or already exists.`
- `Applied: ...sql`
- `Skipped: ...sql (already applied)`
- `Migrations applied.`

Se essas mensagens nao aparecerem, o deploy automatico nao concluiu a etapa de banco.

## 6) Checks rápidos

- Frontend: abrir o domínio configurado.
- API health básico: acessar `/api/v1/auth/login` (deve responder método/credencial inválida se chamado via GET sem payload, mas confirma roteamento).

## Problemas comuns

- Erro de CORS: ajuste `CORS_ALLOWED_ORIGINS` com os domínios finais do frontend.
- API não responde via frontend: confirme que `VITE_API_BASE_URL=/api/v1` e que o serviço `frontend` está saudável.
- Falha de conexão no DB: valide `MYSQL_*` e se o serviço `db` está saudável.
- Deploy sobe mas backend reinicia: verifique os logs do `backend` (normalmente erro de credenciais DB ou migration inválida).
