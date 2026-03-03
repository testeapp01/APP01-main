-- Migration: add purchase header fields for operation type and references
ALTER TABLE compras ADD COLUMN tipo_operacao VARCHAR(20) NULL AFTER motorista_id;
ALTER TABLE compras ADD COLUMN cliente_id INT NULL AFTER tipo_operacao;
ALTER TABLE compras ADD INDEX idx_compras_cliente_id (cliente_id);
