# MySQL SaaS Remediation Pack

Pacote de endurecimento de banco para MySQL 8.0+ focado em:

- integridade referencial
- multitenancy
- performance
- auditoria
- seguranca e governanca

## Ordem de execucao

1. `001_precheck_orphans_duplicates.sql`
2. `002_multitenant_core.sql`
3. `003_fk_constraints.sql`
4. `004_data_constraints.sql`
5. `005_indexes.sql`
6. `006_audit_status_history.sql`
7. `007_rbac_permissions.sql`
8. `008_tenant_guardrails.sql`

## Regras operacionais

- Rodar primeiro em homologacao.
- Fazer backup antes de producao.
- Corrigir inconsistencias do script 001 antes do 003.
- Este pacote assume InnoDB e MySQL 8.0+.

## Contexto de tenant e usuario na sessao

No backend, para cada conexao autenticada, setar:

```sql
SET @app_tenant_id = ?;
SET @app_user_id = ?;
```
