ALTER TABLE compras ADD COLUMN data_envio_prevista DATE NULL AFTER data_compra;
ALTER TABLE compras ADD COLUMN data_entrega_prevista DATE NULL AFTER data_envio_prevista;

ALTER TABLE vendas ADD COLUMN data_envio_prevista DATE NULL AFTER data_venda;
ALTER TABLE vendas ADD COLUMN data_entrega_prevista DATE NULL AFTER data_envio_prevista;
