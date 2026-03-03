CREATE TABLE IF NOT EXISTS status_pedido (
  id INT PRIMARY KEY,
  nome VARCHAR(30) NOT NULL UNIQUE
);

INSERT IGNORE INTO status_pedido (id, nome)
VALUES
  (1, 'AGUARDANDO'),
  (2, 'ENTREGUE');

ALTER TABLE vendas_cabecalho
  ADD COLUMN id_statuspedido INT NULL AFTER status;

CREATE INDEX idx_vendas_cabecalho_statuspedido
  ON vendas_cabecalho (id_statuspedido);

UPDATE vendas_cabecalho
SET id_statuspedido = CASE
  WHEN UPPER(IFNULL(status, '')) = 'ENTREGUE' THEN 2
  ELSE 1
END
WHERE id_statuspedido IS NULL;

CREATE TABLE IF NOT EXISTS historico_status_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venda_cabecalho_id INT NOT NULL,
  usuario_id INT NULL,
  id_statuspedido INT NOT NULL,
  confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hsp_venda (venda_cabecalho_id),
  INDEX idx_hsp_usuario (usuario_id),
  INDEX idx_hsp_status (id_statuspedido),
  INDEX idx_hsp_data (confirmado_em)
);
