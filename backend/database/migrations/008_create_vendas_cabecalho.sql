-- Migration: create sales header table and link sales items
USE hortifrut;

CREATE TABLE IF NOT EXISTS vendas_cabecalho (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('venda','revenda') NOT NULL DEFAULT 'venda',
  cliente_id INT NOT NULL,
  valor_total DECIMAL(16,4) NOT NULL DEFAULT 0,
  data_inicio_prevista DATE NULL,
  data_fim_prevista DATE NULL,
  status ENUM('ORCAMENTO','CONFIRMADA','ENTREGUE','CANCELADA') NOT NULL DEFAULT 'ORCAMENTO',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vendas_cabecalho_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  INDEX idx_vendas_cabecalho_cliente (cliente_id),
  INDEX idx_vendas_cabecalho_status (status)
);

ALTER TABLE vendas ADD COLUMN venda_cabecalho_id INT NULL AFTER cliente_id;
ALTER TABLE vendas ADD INDEX idx_vendas_cabecalho_id (venda_cabecalho_id);
