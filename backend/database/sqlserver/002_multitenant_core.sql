SET NOCOUNT ON;

PRINT '== PHASE 2: MULTITENANT CORE ==';

IF OBJECT_ID('dbo.tenants', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tenants (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nome NVARCHAR(200) NOT NULL,
        ativo BIT NOT NULL CONSTRAINT DF_tenants_ativo DEFAULT (1),
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_tenants_created_at DEFAULT (SYSUTCDATETIME())
    );
END;

IF NOT EXISTS (SELECT 1 FROM dbo.tenants)
BEGIN
    INSERT INTO dbo.tenants (nome) VALUES ('Tenant Default');
END;

/* Add tenant_id to core tables */
DECLARE @tables TABLE (table_name SYSNAME);
INSERT INTO @tables(table_name)
VALUES
('users'), ('clientes'), ('fornecedores'), ('motoristas'), ('produtos'),
('compras'), ('compras_cabecalho'), ('vendas'), ('vendas_cabecalho'),
('historico_status_compra'), ('historico_status_pedido');

DECLARE @t SYSNAME;
DECLARE c CURSOR LOCAL FAST_FORWARD FOR SELECT table_name FROM @tables;
OPEN c;
FETCH NEXT FROM c INTO @t;

WHILE @@FETCH_STATUS = 0
BEGIN
    IF OBJECT_ID('dbo.' + @t, 'U') IS NOT NULL
    BEGIN
        IF COL_LENGTH('dbo.' + @t, 'tenant_id') IS NULL
        BEGIN
            EXEC ('ALTER TABLE dbo.' + QUOTENAME(@t) + ' ADD tenant_id INT NULL;');
            EXEC ('UPDATE dbo.' + QUOTENAME(@t) + ' SET tenant_id = 1 WHERE tenant_id IS NULL;');
            EXEC ('ALTER TABLE dbo.' + QUOTENAME(@t) + ' ALTER COLUMN tenant_id INT NOT NULL;');
        END;
    END;

    FETCH NEXT FROM c INTO @t;
END;

CLOSE c;
DEALLOCATE c;

/* FK tenant_id */
DECLARE @fkSql NVARCHAR(MAX) = N'';
SELECT @fkSql = @fkSql + N'
IF OBJECT_ID(''dbo.' + table_name + ''', ''U'') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys WHERE name = ''FK_' + table_name + '_tenant''
)
BEGIN
    ALTER TABLE dbo.' + QUOTENAME(table_name) + '
    ADD CONSTRAINT FK_' + table_name + '_tenant
    FOREIGN KEY (tenant_id) REFERENCES dbo.tenants(id);
END;'
FROM @tables;

EXEC sp_executesql @fkSql;

PRINT '== MULTITENANT CORE DONE ==';
