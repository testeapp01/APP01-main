# Diagnostico e Plano de Correcao SQL Server (SaaS)

## Escopo

Documento pratico para elevar o banco para padrao profissional em:

- Integridade relacional
- Performance
- Governanca e auditoria
- Seguranca
- Arquitetura SaaS multitenant

## Entregaveis implementados no repositorio

- [backend/database/sqlserver/001_precheck_orphans_duplicates.sql](../../database/sqlserver/001_precheck_orphans_duplicates.sql)
- [backend/database/sqlserver/002_multitenant_core.sql](../../database/sqlserver/002_multitenant_core.sql)
- [backend/database/sqlserver/003_fk_constraints.sql](../../database/sqlserver/003_fk_constraints.sql)
- [backend/database/sqlserver/004_data_constraints.sql](../../database/sqlserver/004_data_constraints.sql)
- [backend/database/sqlserver/005_indexes.sql](../../database/sqlserver/005_indexes.sql)
- [backend/database/sqlserver/006_audit_status_history.sql](../../database/sqlserver/006_audit_status_history.sql)
- [backend/database/sqlserver/007_rbac_permissions.sql](../../database/sqlserver/007_rbac_permissions.sql)
- [backend/database/sqlserver/008_rls_tenant_policy.sql](../../database/sqlserver/008_rls_tenant_policy.sql)

## Problemas criticos identificados

1. Ausencia de multitenancy no schema e na aplicacao.
2. FKs ausentes entre cabecalho, itens e historicos.
3. Status redundante e regra de transicao muito dependente do backend.
4. Exclusao fisica em entidades de negocio sensiveis.
5. Forte acoplamento em sintaxe MySQL, inviabilizando portabilidade imediata para SQL Server.
6. Seguranca operacional fraca com credenciais padrao em scripts de utilitario.

## Ordem de execucao recomendada

1. Rodar `001_precheck_orphans_duplicates.sql` e tratar inconsistencias encontradas.
2. Rodar `002_multitenant_core.sql` para criar base de isolamento.
3. Rodar `003_fk_constraints.sql` para garantir integridade referencial.
4. Rodar `004_data_constraints.sql` para regras de negocio obrigatorias.
5. Rodar `005_indexes.sql` para performance das consultas principais.
6. Rodar `006_audit_status_history.sql` para trilha de auditoria e historico.
7. Rodar `007_rbac_permissions.sql` para perfis e permissoes profissionais.
8. Rodar `008_rls_tenant_policy.sql` para isolamento no banco por tenant.

## Regras de aplicacao (backend)

1. Antes de cada request autenticada, setar contexto:

```sql
EXEC sp_set_session_context @key=N'tenant_id', @value=@TenantId;
EXEC sp_set_session_context @key=N'user_id', @value=@UserId;
```

2. Bloquear criacao de usuarios fora do tenant do AdminEmpresa.
3. Somente SuperAdmin cria novos tenants.
4. Toda alteracao sensivel deve registrar evento em `audit_admin_actions`.

## Recomendacoes adicionais para SQL Server

1. Trocar query MySQL-specific no backend (`IFNULL`, `DATE_FORMAT`, `GROUP_CONCAT`, `SHOW COLUMNS`, `SHOW TABLES`).
2. Implementar paginação por keyset nas tabelas com maior volume.
3. Criar rotina de arquivamento para historicos/logs com janela de retencao.
4. Revisar plano de backup/restore e DR testado.
