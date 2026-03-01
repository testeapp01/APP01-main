ALTER TABLE compras
    ADD COLUMN IF NOT EXISTS data_envio_prevista DATE NULL AFTER data_compra,
    ADD COLUMN IF NOT EXISTS data_entrega_prevista DATE NULL AFTER data_envio_prevista;

ALTER TABLE vendas
    ADD COLUMN IF NOT EXISTS data_envio_prevista DATE NULL AFTER data_venda,
    ADD COLUMN IF NOT EXISTS data_entrega_prevista DATE NULL AFTER data_envio_prevista;
