SET NOCOUNT ON;

PRINT '== PHASE 2: DATA CONSTRAINTS ==';

/* Financial checks */
IF OBJECT_ID('dbo.compras', 'U') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_compras_quantidade')
BEGIN
    ALTER TABLE dbo.compras ADD CONSTRAINT CK_compras_quantidade CHECK (quantidade > 0);
END;

IF OBJECT_ID('dbo.compras', 'U') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_compras_valor_unitario')
BEGIN
    ALTER TABLE dbo.compras ADD CONSTRAINT CK_compras_valor_unitario CHECK (valor_unitario > 0);
END;

IF OBJECT_ID('dbo.vendas', 'U') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_vendas_quantidade')
BEGIN
    ALTER TABLE dbo.vendas ADD CONSTRAINT CK_vendas_quantidade CHECK (quantidade > 0);
END;

IF OBJECT_ID('dbo.vendas', 'U') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_vendas_valor_unitario')
BEGIN
    ALTER TABLE dbo.vendas ADD CONSTRAINT CK_vendas_valor_unitario CHECK (valor_unitario > 0);
END;

/* Date coherence */
IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND COL_LENGTH('dbo.compras_cabecalho', 'data_envio_prevista') IS NOT NULL
AND COL_LENGTH('dbo.compras_cabecalho', 'data_entrega_prevista') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_compra_cab_datas')
BEGIN
    ALTER TABLE dbo.compras_cabecalho
    ADD CONSTRAINT CK_compra_cab_datas CHECK (
        data_entrega_prevista IS NULL OR data_envio_prevista IS NULL OR data_entrega_prevista >= data_envio_prevista
    );
END;

/* Default timestamps */
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL
AND COL_LENGTH('dbo.users', 'created_at') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.default_constraints WHERE name = 'DF_users_created_at')
BEGIN
    ALTER TABLE dbo.users ADD CONSTRAINT DF_users_created_at DEFAULT (SYSUTCDATETIME()) FOR created_at;
END;

/* Soft delete baseline */
DECLARE @soft TABLE (table_name SYSNAME);
INSERT INTO @soft(table_name)
VALUES ('clientes'), ('fornecedores'), ('motoristas'), ('produtos'), ('compras_cabecalho'), ('vendas_cabecalho');

DECLARE @st SYSNAME;
DECLARE cur CURSOR LOCAL FAST_FORWARD FOR SELECT table_name FROM @soft;
OPEN cur;
FETCH NEXT FROM cur INTO @st;

WHILE @@FETCH_STATUS = 0
BEGIN
    IF OBJECT_ID('dbo.' + @st, 'U') IS NOT NULL
    BEGIN
        IF COL_LENGTH('dbo.' + @st, 'is_deleted') IS NULL
            EXEC ('ALTER TABLE dbo.' + QUOTENAME(@st) + ' ADD is_deleted BIT NOT NULL CONSTRAINT DF_' + @st + '_is_deleted DEFAULT (0);');

        IF COL_LENGTH('dbo.' + @st, 'deleted_at') IS NULL
            EXEC ('ALTER TABLE dbo.' + QUOTENAME(@st) + ' ADD deleted_at DATETIME2(0) NULL;');
    END;

    FETCH NEXT FROM cur INTO @st;
END;

CLOSE cur;
DEALLOCATE cur;

/* Uniques */
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL
AND COL_LENGTH('dbo.users', 'tenant_id') IS NOT NULL
AND COL_LENGTH('dbo.users', 'email') IS NOT NULL
AND COL_LENGTH('dbo.users', 'is_deleted') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_users_tenant_email')
BEGIN
    CREATE UNIQUE INDEX UX_users_tenant_email
    ON dbo.users(tenant_id, email)
    WHERE is_deleted = 0 AND email IS NOT NULL;
END;

IF OBJECT_ID('dbo.clientes', 'U') IS NOT NULL
AND COL_LENGTH('dbo.clientes', 'tenant_id') IS NOT NULL
AND COL_LENGTH('dbo.clientes', 'cpf_cnpj') IS NOT NULL
AND COL_LENGTH('dbo.clientes', 'is_deleted') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_clientes_tenant_doc')
BEGIN
    CREATE UNIQUE INDEX UX_clientes_tenant_doc
    ON dbo.clientes(tenant_id, cpf_cnpj)
    WHERE is_deleted = 0 AND cpf_cnpj IS NOT NULL;
END;

PRINT '== DATA CONSTRAINTS DONE ==';
