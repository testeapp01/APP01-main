SELECT 'FK_CONSTRAINTS_START' AS stage;

ALTER TABLE compras
  ADD CONSTRAINT fk_compras_compra_cabecalho
  FOREIGN KEY (compra_cabecalho_id) REFERENCES compras_cabecalho(id);

ALTER TABLE vendas
  ADD CONSTRAINT fk_vendas_venda_cabecalho
  FOREIGN KEY (venda_cabecalho_id) REFERENCES vendas_cabecalho(id);

ALTER TABLE compras_cabecalho
  ADD CONSTRAINT fk_compra_cab_fornecedor
  FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id);

ALTER TABLE compras_cabecalho
  ADD CONSTRAINT fk_compra_cab_cliente
  FOREIGN KEY (cliente_id) REFERENCES clientes(id);

ALTER TABLE compras_cabecalho
  ADD CONSTRAINT fk_compra_cab_motorista
  FOREIGN KEY (motorista_id) REFERENCES motoristas(id);

ALTER TABLE compras_cabecalho
  ADD CONSTRAINT fk_compra_cab_status
  FOREIGN KEY (id_statuscompra) REFERENCES status_compra(id);

ALTER TABLE vendas_cabecalho
  ADD CONSTRAINT fk_venda_cab_status
  FOREIGN KEY (id_statuspedido) REFERENCES status_pedido(id);

ALTER TABLE historico_status_compra
  ADD CONSTRAINT fk_hsc_compra_cab
  FOREIGN KEY (compra_cabecalho_id) REFERENCES compras_cabecalho(id);

ALTER TABLE historico_status_compra
  ADD CONSTRAINT fk_hsc_user
  FOREIGN KEY (usuario_id) REFERENCES users(id);

ALTER TABLE historico_status_compra
  ADD CONSTRAINT fk_hsc_status
  FOREIGN KEY (id_statuscompra) REFERENCES status_compra(id);

ALTER TABLE historico_status_pedido
  ADD CONSTRAINT fk_hsp_venda_cab
  FOREIGN KEY (venda_cabecalho_id) REFERENCES vendas_cabecalho(id);

ALTER TABLE historico_status_pedido
  ADD CONSTRAINT fk_hsp_user
  FOREIGN KEY (usuario_id) REFERENCES users(id);

ALTER TABLE historico_status_pedido
  ADD CONSTRAINT fk_hsp_status
  FOREIGN KEY (id_statuspedido) REFERENCES status_pedido(id);

SELECT 'FK_CONSTRAINTS_DONE' AS stage;
