CREATE TABLE IF NOT EXISTS tabelas_preco (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('atacado','varejo','especial','padrao') NOT NULL DEFAULT 'padrao',
    desconto_percentual DECIMAL(8,4) NOT NULL DEFAULT 0,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tabelas_preco (id, nome, tipo, desconto_percentual, ativa) VALUES (1, 'Padrão', 'padrao', 0, 1);
INSERT IGNORE INTO tabelas_preco (id, nome, tipo, desconto_percentual, ativa) VALUES (2, 'Atacado', 'atacado', 10, 1);
INSERT IGNORE INTO tabelas_preco (id, nome, tipo, desconto_percentual, ativa) VALUES (3, 'Varejo', 'varejo', 0, 1);

ALTER TABLE clientes ADD COLUMN IF NOT EXISTS tabela_preco_id INT NULL;
