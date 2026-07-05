SET NOCOUNT ON;

PRINT '== PHASE 3: PERFORMANCE INDEXES ==';

IF OBJECT_ID('dbo.compras', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.compras', 'compra_cabecalho_id') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_compras_compra_cabecalho_id')
        CREATE INDEX IX_compras_compra_cabecalho_id ON dbo.compras(compra_cabecalho_id);

    IF COL_LENGTH('dbo.compras', 'produto_id') IS NOT NULL
    AND COL_LENGTH('dbo.compras', 'data_compra') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_compras_produto_data')
        CREATE INDEX IX_compras_produto_data ON dbo.compras(produto_id, data_compra DESC);

    IF COL_LENGTH('dbo.compras', 'fornecedor_id') IS NOT NULL
    AND COL_LENGTH('dbo.compras', 'data_compra') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_compras_fornecedor_data')
        CREATE INDEX IX_compras_fornecedor_data ON dbo.compras(fornecedor_id, data_compra DESC);
END;

IF OBJECT_ID('dbo.vendas', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.vendas', 'venda_cabecalho_id') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_vendas_venda_cabecalho_id')
        CREATE INDEX IX_vendas_venda_cabecalho_id ON dbo.vendas(venda_cabecalho_id);

    IF COL_LENGTH('dbo.vendas', 'cliente_id') IS NOT NULL
    AND COL_LENGTH('dbo.vendas', 'data_venda') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_vendas_cliente_data')
        CREATE INDEX IX_vendas_cliente_data ON dbo.vendas(cliente_id, data_venda DESC);
END;

IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.compras_cabecalho', 'id_statuscompra') IS NOT NULL
    AND COL_LENGTH('dbo.compras_cabecalho', 'data_entrega_prevista') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_compras_cab_status_data')
        CREATE INDEX IX_compras_cab_status_data ON dbo.compras_cabecalho(id_statuscompra, data_entrega_prevista DESC);
END;

IF OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.vendas_cabecalho', 'id_statuspedido') IS NOT NULL
    AND COL_LENGTH('dbo.vendas_cabecalho', 'data_inicio_prevista') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_vendas_cab_status_data')
        CREATE INDEX IX_vendas_cab_status_data ON dbo.vendas_cabecalho(id_statuspedido, data_inicio_prevista DESC);
END;

IF OBJECT_ID('dbo.historico_status_compra', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_compra', 'compra_cabecalho_id') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_compra', 'confirmado_em') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_hsc_cab_data')
BEGIN
    CREATE INDEX IX_hsc_cab_data ON dbo.historico_status_compra(compra_cabecalho_id, confirmado_em DESC);
END;

IF OBJECT_ID('dbo.historico_status_pedido', 'U') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_pedido', 'venda_cabecalho_id') IS NOT NULL
AND COL_LENGTH('dbo.historico_status_pedido', 'confirmado_em') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_hsp_cab_data')
BEGIN
    CREATE INDEX IX_hsp_cab_data ON dbo.historico_status_pedido(venda_cabecalho_id, confirmado_em DESC);
END;

PRINT '== PERFORMANCE INDEXES DONE ==';
