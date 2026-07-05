# SQL Server SaaS Remediation Pack

Este pacote implementa uma base minima profissional para SQL Server em ambiente SaaS.

## Ordem recomendada

1. `001_precheck_orphans_duplicates.sql`
2. `002_multitenant_core.sql`
3. `003_fk_constraints.sql`
4. `004_data_constraints.sql`
5. `005_indexes.sql`
6. `006_audit_status_history.sql`
7. `007_rbac_permissions.sql`
8. `008_rls_tenant_policy.sql`

## Regras operacionais

- Execute primeiro em homologacao.
- Tire backup antes de rodar em producao.
- Scripts usam validacoes `IF NOT EXISTS` para reduzir erro de reexecucao.
- Se houver dados inconsistentes, corrija com os SELECTs do script 001 antes do script 003.

## Premissas

- Schema alvo: `dbo`
- Tabelas atuais: `users`, `clientes`, `fornecedores`, `motoristas`, `produtos`, `compras`, `compras_cabecalho`, `vendas`, `vendas_cabecalho`, `historico_status_compra`, `historico_status_pedido`.
- Engine: Microsoft SQL Server 2019+.
