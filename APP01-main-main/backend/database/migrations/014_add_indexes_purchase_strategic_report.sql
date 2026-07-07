-- Migration: add performance indexes for strategic purchases report
-- Safe to run in environments where some indexes may already exist,
-- because migration runner ignores duplicate key errors.

ALTER TABLE compras
  ADD INDEX idx_compras_data_compra (data_compra),
  ADD INDEX idx_compras_produto_data (produto_id, data_compra),
  ADD INDEX idx_compras_fornecedor_data (fornecedor_id, data_compra),
  ADD INDEX idx_compras_motorista_data (motorista_id, data_compra),
  ADD INDEX idx_compras_status_data (status, data_compra);

ALTER TABLE compras_cabecalho
  ADD INDEX idx_compras_cabecalho_status_data (status, data_entrega_prevista),
  ADD INDEX idx_compras_cabecalho_fornecedor (fornecedor_id);

ALTER TABLE historico_status_compra
  ADD INDEX idx_historico_status_compra_cabecalho_data (compra_cabecalho_id, confirmado_em);
