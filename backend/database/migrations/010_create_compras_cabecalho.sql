-- Migration: create purchase header table and link items

CREATE TABLE IF NOT EXISTS compras_cabecalho (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_operacao VARCHAR(20) NOT NULL DEFAULT 'revenda',
  fornecedor_id INT NOT NULL,
  cliente_id INT NULL,
  motorista_id INT NULL,
  valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  data_envio_prevista DATE NULL,
  data_entrega_prevista DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'AGUARDANDO',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_compra_cab_fornecedor (fornecedor_id),
  INDEX idx_compra_cab_cliente (cliente_id),
  INDEX idx_compra_cab_motorista (motorista_id),
  INDEX idx_compra_cab_status (status)
);

ALTER TABLE compras ADD COLUMN IF NOT EXISTS compra_cabecalho_id INT NULL AFTER id;
ALTER TABLE compras ADD INDEX idx_compras_cabecalho_id (compra_cabecalho_id);
