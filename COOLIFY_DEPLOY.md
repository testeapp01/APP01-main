# Deploy no Coolify

Este repositório já inclui um stack pronto para Coolify em `docker-compose.coolify.yml`.

## 1) Criar recurso no Coolify

1. Em Coolify, crie um novo recurso do tipo **Docker Compose**.
2. Conecte este repositório Git.
3. Selecione o arquivo `docker-compose.coolify.yml`.

## 2) Configurar variáveis de ambiente

Copie os valores de `.env.coolify.example` para as variáveis do ambiente no Coolify.

Variáveis obrigatórias:
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `VITE_API_BASE_URL`
- `CORS_ALLOWED_ORIGINS`

## 3) Domínio

No serviço `frontend`, configure o domínio principal (ex.: `app.seudominio.com`).

Observação: o Nginx do frontend já encaminha `/api/v1` internamente para o serviço `backend`, então não é obrigatório expor um domínio público separado para a API.

## 4) Deploy

Clique em **Deploy** no Coolify.

## 5) Inicialização de banco e migration

No stack `docker-compose.coolify.yml`, o serviço `backend` já executa automaticamente ao iniciar:

```bash
php tools/create_db.php
php tools/run_migrations.php
php tools/seed.php
```

Ou seja, no primeiro deploy (e nos próximos) não precisa rodar manualmente.

Para conferir no Coolify, abra os logs do serviço `backend` e valide mensagens como:
- `Database '...' created or already exists.`
- `Applied: ...sql`
- `Migrations applied.`

## 6) Checks rápidos

- Frontend: abrir o domínio configurado.
- API health básico: acessar `/api/v1/auth/login` (deve responder método/credencial inválida se chamado via GET sem payload, mas confirma roteamento).

## Problemas comuns

- Erro de CORS: ajuste `CORS_ALLOWED_ORIGINS` com os domínios finais do frontend.
- API não responde via frontend: confirme que `VITE_API_BASE_URL=/api/v1` e que o serviço `frontend` está saudável.
- Falha de conexão no DB: valide `MYSQL_*` e se o serviço `db` está saudável.
- Deploy sobe mas backend reinicia: verifique os logs do `backend` (normalmente erro de credenciais DB ou migration inválida).
