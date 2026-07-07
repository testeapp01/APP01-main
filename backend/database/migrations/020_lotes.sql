CREATE TABLE IF NOT EXISTS lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    fornecedor_id INT NULL,
    compra_cabecalho_id INT NULL,
    codigo_lote VARCHAR(50) NULL,
    data_validade DATE NOT NULL,
    data_colheita DATE NULL,
    origem VARCHAR(150) NULL,
    quantidade_entrada DECIMAL(14,4) NOT NULL DEFAULT 0,
    quantidade_atual DECIMAL(14,4) NOT NULL DEFAULT 0,
    custo_unitario DECIMAL(14,4) NOT NULL DEFAULT 0,
    status ENUM('ativo','quarentena','vencido','descartado') NOT NULL DEFAULT 'ativo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lote_produto_validade (produto_id, data_validade),
    INDEX idx_lote_status (status),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
