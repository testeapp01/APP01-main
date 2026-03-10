CREATE TABLE IF NOT EXISTS status_compra (
  id INT PRIMARY KEY,
  nome VARCHAR(30) NOT NULL UNIQUE
);

INSERT IGNORE INTO status_compra (id, nome)
VALUES
  (1, 'AGUARDANDO'),
  (2, 'RECEBIDA');

ALTER TABLE compras_cabecalho
  ADD COLUMN id_statuscompra INT NULL AFTER status;

CREATE INDEX idx_compras_cabecalho_statuscompra
  ON compras_cabecalho (id_statuscompra);

UPDATE compras_cabecalho
SET id_statuscompra = CASE
  WHEN UPPER(IFNULL(status, '')) = 'RECEBIDA' THEN 2
  ELSE 1
END
WHERE id_statuscompra IS NULL;

CREATE TABLE IF NOT EXISTS historico_status_compra (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_cabecalho_id INT NOT NULL,
  usuario_id INT NULL,
  id_statuscompra INT NOT NULL,
  confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hsc_compra (compra_cabecalho_id),
  INDEX idx_hsc_usuario (usuario_id),
  INDEX idx_hsc_status (id_statuscompra),
  INDEX idx_hsc_data (confirmado_em)
);
