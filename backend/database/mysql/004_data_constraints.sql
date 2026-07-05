SELECT 'DATA_CONSTRAINTS_START' AS stage;

/* checks (MySQL 8.0+) */
ALTER TABLE compras
  ADD CONSTRAINT ck_compras_quantidade CHECK (quantidade > 0),
  ADD CONSTRAINT ck_compras_valor_unitario CHECK (valor_unitario > 0);

ALTER TABLE vendas
  ADD CONSTRAINT ck_vendas_quantidade CHECK (quantidade > 0),
  ADD CONSTRAINT ck_vendas_valor_unitario CHECK (valor_unitario > 0);

ALTER TABLE compras_cabecalho
  ADD CONSTRAINT ck_compra_cab_datas
  CHECK (
    data_entrega_prevista IS NULL
    OR data_envio_prevista IS NULL
    OR data_entrega_prevista >= data_envio_prevista
  );

/* soft delete baseline */
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

ALTER TABLE fornecedores ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE fornecedores ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

ALTER TABLE motoristas ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE motoristas ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

ALTER TABLE compras_cabecalho ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE compras_cabecalho ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

ALTER TABLE vendas_cabecalho ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE vendas_cabecalho ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

/* unique por tenant */
CREATE UNIQUE INDEX ux_users_tenant_email ON users(tenant_id, email);
CREATE UNIQUE INDEX ux_clientes_tenant_doc ON clientes(tenant_id, cpf_cnpj);

SELECT 'DATA_CONSTRAINTS_DONE' AS stage;
