SET NOCOUNT ON;

PRINT '== PHASE 2: FK CONSTRAINTS ==';

/* compras -> compras_cabecalho */
IF OBJECT_ID('dbo.compras', 'U') IS NOT NULL
AND OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND COL_LENGTH('dbo.compras', 'compra_cabecalho_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_compras_compra_cabecalho')
BEGIN
    ALTER TABLE dbo.compras WITH CHECK
    ADD CONSTRAINT FK_compras_compra_cabecalho
    FOREIGN KEY (compra_cabecalho_id) REFERENCES dbo.compras_cabecalho(id);
END;

/* vendas -> vendas_cabecalho */
IF OBJECT_ID('dbo.vendas', 'U') IS NOT NULL
AND OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
AND COL_LENGTH('dbo.vendas', 'venda_cabecalho_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_vendas_venda_cabecalho')
BEGIN
    ALTER TABLE dbo.vendas WITH CHECK
    ADD CONSTRAINT FK_vendas_venda_cabecalho
    FOREIGN KEY (venda_cabecalho_id) REFERENCES dbo.vendas_cabecalho(id);
END;

/* compras_cabecalho references */
IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.fornecedores', 'U') IS NOT NULL
AND COL_LENGTH('dbo.compras_cabecalho', 'fornecedor_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_compra_cab_fornecedor')
BEGIN
    ALTER TABLE dbo.compras_cabecalho WITH CHECK
    ADD CONSTRAINT FK_compra_cab_fornecedor
    FOREIGN KEY (fornecedor_id) REFERENCES dbo.fornecedores(id);
END;

IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.clientes', 'U') IS NOT NULL
AND COL_LENGTH('dbo.compras_cabecalho', 'cliente_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_compra_cab_cliente')
BEGIN
    ALTER TABLE dbo.compras_cabecalho WITH CHECK
    ADD CONSTRAINT FK_compra_cab_cliente
    FOREIGN KEY (cliente_id) REFERENCES dbo.clientes(id);
END;

IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.motoristas', 'U') IS NOT NULL
AND COL_LENGTH('dbo.compras_cabecalho', 'motorista_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_compra_cab_motorista')
BEGIN
    ALTER TABLE dbo.compras_cabecalho WITH CHECK
    ADD CONSTRAINT FK_compra_cab_motorista
    FOREIGN KEY (motorista_id) REFERENCES dbo.motoristas(id);
END;

/* status refs */
IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.status_compra', 'U') IS NOT NULL
AND COL_LENGTH('dbo.compras_cabecalho', 'id_statuscompra') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_compra_cab_status')
BEGIN
    ALTER TABLE dbo.compras_cabecalho WITH CHECK
    ADD CONSTRAINT FK_compra_cab_status
    FOREIGN KEY (id_statuscompra) REFERENCES dbo.status_compra(id);
END;

IF OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.status_pedido', 'U') IS NOT NULL
AND COL_LENGTH('dbo.vendas_cabecalho', 'id_statuspedido') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_venda_cab_status')
BEGIN
    ALTER TABLE dbo.vendas_cabecalho WITH CHECK
    ADD CONSTRAINT FK_venda_cab_status
    FOREIGN KEY (id_statuspedido) REFERENCES dbo.status_pedido(id);
END;

/* historico refs */
IF OBJECT_ID('dbo.historico_status_compra', 'U') IS NOT NULL
AND OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_compra', 'compra_cabecalho_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hsc_compra_cab')
BEGIN
    ALTER TABLE dbo.historico_status_compra WITH CHECK
    ADD CONSTRAINT FK_hsc_compra_cab
    FOREIGN KEY (compra_cabecalho_id) REFERENCES dbo.compras_cabecalho(id);
END;

IF OBJECT_ID('dbo.historico_status_compra', 'U') IS NOT NULL
AND OBJECT_ID('dbo.users', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_compra', 'usuario_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hsc_user')
BEGIN
    ALTER TABLE dbo.historico_status_compra WITH CHECK
    ADD CONSTRAINT FK_hsc_user
    FOREIGN KEY (usuario_id) REFERENCES dbo.users(id);
END;

IF OBJECT_ID('dbo.historico_status_compra', 'U') IS NOT NULL
AND OBJECT_ID('dbo.status_compra', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_compra', 'id_statuscompra') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hsc_status')
BEGIN
    ALTER TABLE dbo.historico_status_compra WITH CHECK
    ADD CONSTRAINT FK_hsc_status
    FOREIGN KEY (id_statuscompra) REFERENCES dbo.status_compra(id);
END;

IF OBJECT_ID('dbo.historico_status_pedido', 'U') IS NOT NULL
AND OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_pedido', 'venda_cabecalho_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hsp_venda_cab')
BEGIN
    ALTER TABLE dbo.historico_status_pedido WITH CHECK
    ADD CONSTRAINT FK_hsp_venda_cab
    FOREIGN KEY (venda_cabecalho_id) REFERENCES dbo.vendas_cabecalho(id);
END;

IF OBJECT_ID('dbo.historico_status_pedido', 'U') IS NOT NULL
AND OBJECT_ID('dbo.users', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_pedido', 'usuario_id') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hsp_user')
BEGIN
    ALTER TABLE dbo.historico_status_pedido WITH CHECK
    ADD CONSTRAINT FK_hsp_user
    FOREIGN KEY (usuario_id) REFERENCES dbo.users(id);
END;

IF OBJECT_ID('dbo.historico_status_pedido', 'U') IS NOT NULL
AND OBJECT_ID('dbo.status_pedido', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_pedido', 'id_statuspedido') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_hsp_status')
BEGIN
    ALTER TABLE dbo.historico_status_pedido WITH CHECK
    ADD CONSTRAINT FK_hsp_status
    FOREIGN KEY (id_statuspedido) REFERENCES dbo.status_pedido(id);
END;

PRINT '== FK CONSTRAINTS DONE ==';
