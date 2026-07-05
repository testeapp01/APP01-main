SELECT 'MULTITENANT_CORE_START' AS stage;

CREATE TABLE IF NOT EXISTS tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tenants (id, nome, ativo)
SELECT 1, 'Tenant Default', 1
WHERE NOT EXISTS (SELECT 1 FROM tenants WHERE id = 1);

ALTER TABLE users ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE fornecedores ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE motoristas ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE compras ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE compras_cabecalho ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE vendas ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE vendas_cabecalho ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE historico_status_compra ADD COLUMN IF NOT EXISTS tenant_id INT NULL;
ALTER TABLE historico_status_pedido ADD COLUMN IF NOT EXISTS tenant_id INT NULL;

UPDATE users SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE clientes SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE fornecedores SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE motoristas SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE produtos SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE compras SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE compras_cabecalho SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE vendas SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE vendas_cabecalho SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE historico_status_compra SET tenant_id = 1 WHERE tenant_id IS NULL;
UPDATE historico_status_pedido SET tenant_id = 1 WHERE tenant_id IS NULL;

ALTER TABLE users MODIFY tenant_id INT NOT NULL;
ALTER TABLE clientes MODIFY tenant_id INT NOT NULL;
ALTER TABLE fornecedores MODIFY tenant_id INT NOT NULL;
ALTER TABLE motoristas MODIFY tenant_id INT NOT NULL;
ALTER TABLE produtos MODIFY tenant_id INT NOT NULL;
ALTER TABLE compras MODIFY tenant_id INT NOT NULL;
ALTER TABLE compras_cabecalho MODIFY tenant_id INT NOT NULL;
ALTER TABLE vendas MODIFY tenant_id INT NOT NULL;
ALTER TABLE vendas_cabecalho MODIFY tenant_id INT NOT NULL;
ALTER TABLE historico_status_compra MODIFY tenant_id INT NOT NULL;
ALTER TABLE historico_status_pedido MODIFY tenant_id INT NOT NULL;

/* FKs tenant */
ALTER TABLE users ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE clientes ADD CONSTRAINT fk_clientes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE fornecedores ADD CONSTRAINT fk_fornecedores_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE motoristas ADD CONSTRAINT fk_motoristas_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE produtos ADD CONSTRAINT fk_produtos_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE compras ADD CONSTRAINT fk_compras_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE compras_cabecalho ADD CONSTRAINT fk_compras_cabecalho_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE vendas ADD CONSTRAINT fk_vendas_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE vendas_cabecalho ADD CONSTRAINT fk_vendas_cabecalho_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE historico_status_compra ADD CONSTRAINT fk_historico_status_compra_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE historico_status_pedido ADD CONSTRAINT fk_historico_status_pedido_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);

SELECT 'MULTITENANT_CORE_DONE' AS stage;
