ALTER TABLE historico_status_pedido
    ADD COLUMN snapshot_json JSON NULL AFTER id_statuspedido;

ALTER TABLE historico_status_compra
    ADD COLUMN snapshot_json JSON NULL AFTER id_statuscompra;
