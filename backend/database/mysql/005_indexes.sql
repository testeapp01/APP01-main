SELECT 'PERFORMANCE_INDEXES_START' AS stage;

ALTER TABLE compras
  ADD INDEX ix_compras_compra_cabecalho_id (compra_cabecalho_id),
  ADD INDEX ix_compras_produto_data (produto_id, data_compra),
  ADD INDEX ix_compras_fornecedor_data (fornecedor_id, data_compra),
  ADD INDEX ix_compras_tenant_data (tenant_id, data_compra);

ALTER TABLE vendas
  ADD INDEX ix_vendas_venda_cabecalho_id (venda_cabecalho_id),
  ADD INDEX ix_vendas_cliente_data (cliente_id, data_venda),
  ADD INDEX ix_vendas_tenant_data (tenant_id, data_venda);

ALTER TABLE compras_cabecalho
  ADD INDEX ix_compras_cab_status_data (id_statuscompra, data_entrega_prevista),
  ADD INDEX ix_compras_cab_tenant_status (tenant_id, id_statuscompra);

ALTER TABLE vendas_cabecalho
  ADD INDEX ix_vendas_cab_status_data (id_statuspedido, data_inicio_prevista),
  ADD INDEX ix_vendas_cab_tenant_status (tenant_id, id_statuspedido);

ALTER TABLE historico_status_compra
  ADD INDEX ix_hsc_cab_data (compra_cabecalho_id, confirmado_em),
  ADD INDEX ix_hsc_tenant_data (tenant_id, confirmado_em);

ALTER TABLE historico_status_pedido
  ADD INDEX ix_hsp_cab_data (venda_cabecalho_id, confirmado_em),
  ADD INDEX ix_hsp_tenant_data (tenant_id, confirmado_em);

SELECT 'PERFORMANCE_INDEXES_DONE' AS stage;
