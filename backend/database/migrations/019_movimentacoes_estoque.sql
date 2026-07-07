CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('entrada_compra','saida_venda','ajuste_manual','quebra','reserva','cancelamento_reserva') NOT NULL,
    quantidade DECIMAL(14,4) NOT NULL,
    valor_unitario DECIMAL(14,4) NOT NULL DEFAULT 0,
    saldo_antes DECIMAL(14,4) NOT NULL DEFAULT 0,
    saldo_depois DECIMAL(14,4) NOT NULL DEFAULT 0,
    referencia_id INT NULL,
    referencia_tipo VARCHAR(50) NULL,
    observacao TEXT NULL,
    usuario_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_me_produto_created (produto_id, created_at),
    INDEX idx_me_created (created_at),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS estoque_reservado DECIMAL(14,4) NOT NULL DEFAULT 0;
