CREATE TABLE IF NOT EXISTS quebras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    lote_id INT NULL,
    quantidade DECIMAL(14,4) NOT NULL,
    valor_unitario DECIMAL(14,4) NOT NULL DEFAULT 0,
    valor_total DECIMAL(16,4) NOT NULL DEFAULT 0,
    tipo ENUM('deterioracao','acidente','roubo','vencimento','qualidade','outro') NOT NULL DEFAULT 'outro',
    observacao TEXT NULL,
    usuario_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_quebra_produto (produto_id, created_at),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
