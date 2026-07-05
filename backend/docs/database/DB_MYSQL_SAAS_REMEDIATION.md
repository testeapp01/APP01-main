# MySQL SaaS Remediation Guide

## Objetivo

Elevar o banco atual para um padrao profissional de operacao SaaS em MySQL:

- integridade de dados
- isolamento por empresa
- performance de consultas operacionais e relatorios
- auditoria e trilha de negocio
- controle de acesso por perfil

## Artefatos criados

- [backend/database/mysql/README.md](../../database/mysql/README.md)
- [backend/database/mysql/001_precheck_orphans_duplicates.sql](../../database/mysql/001_precheck_orphans_duplicates.sql)
- [backend/database/mysql/002_multitenant_core.sql](../../database/mysql/002_multitenant_core.sql)
- [backend/database/mysql/003_fk_constraints.sql](../../database/mysql/003_fk_constraints.sql)
- [backend/database/mysql/004_data_constraints.sql](../../database/mysql/004_data_constraints.sql)
- [backend/database/mysql/005_indexes.sql](../../database/mysql/005_indexes.sql)
- [backend/database/mysql/006_audit_status_history.sql](../../database/mysql/006_audit_status_history.sql)
- [backend/database/mysql/007_rbac_permissions.sql](../../database/mysql/007_rbac_permissions.sql)
- [backend/database/mysql/008_tenant_guardrails.sql](../../database/mysql/008_tenant_guardrails.sql)

## Ordem de execucao em producao

1. Executar `001` e corrigir qualquer inconsistencia encontrada.
2. Executar `002` para base multitenant.
3. Executar `003` para FKs reais.
4. Executar `004` para constraints de regra de negocio.
5. Executar `005` para desempenho.
6. Executar `006` para auditoria e historico de status.
7. Executar `007` para RBAC.
8. Executar `008` para guardrails de tenant na camada SQL.

## Requisito de backend

Em cada request autenticada, setar contexto de sessao na conexao MySQL:

```sql
SET @app_tenant_id = ?;
SET @app_user_id = ?;
```

Sem isso, os triggers de isolamento vao bloquear escrita.

## Riscos conhecidos

1. `003`, `004` e `005` podem falhar se houver dado legado inconsistente.
2. Em MySQL nao existe RLS nativa como no SQL Server, por isso o isolamento combina:
   - tenant_id + FKs
   - contexto de sessao
   - triggers de validacao
   - filtros obrigatorios no backend
