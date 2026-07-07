CREATE TABLE IF NOT EXISTS historico_preco_produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    fornecedor_id INT NULL,
    valor_unitario DECIMAL(14,4) NOT NULL,
    data_referencia DATE NOT NULL,
    compra_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hpp_produto_data (produto_id, data_referencia),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
