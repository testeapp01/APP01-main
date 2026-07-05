SET NOCOUNT ON;

PRINT '== PHASE 4: AUDIT AND STATUS HISTORY ==';

IF OBJECT_ID('dbo.audit_admin_actions', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.audit_admin_actions (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        tenant_id INT NOT NULL,
        user_id INT NULL,
        action_name NVARCHAR(120) NOT NULL,
        entity_name NVARCHAR(120) NOT NULL,
        entity_id NVARCHAR(120) NULL,
        before_json NVARCHAR(MAX) NULL,
        after_json NVARCHAR(MAX) NULL,
        reason NVARCHAR(500) NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_audit_admin_actions_created_at DEFAULT (SYSUTCDATETIME())
    );
END;

IF OBJECT_ID('dbo.business_event_log', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.business_event_log (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        tenant_id INT NOT NULL,
        event_name NVARCHAR(120) NOT NULL,
        aggregate_name NVARCHAR(120) NOT NULL,
        aggregate_id NVARCHAR(120) NULL,
        payload_json NVARCHAR(MAX) NULL,
        correlation_id NVARCHAR(120) NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_business_event_log_created_at DEFAULT (SYSUTCDATETIME())
    );
END;

/* Trigger: status change tracking for compras_cabecalho */
IF OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.historico_status_compra', 'U') IS NOT NULL
BEGIN
    EXEC('CREATE OR ALTER TRIGGER dbo.TRG_compras_cabecalho_status_audit
ON dbo.compras_cabecalho
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    IF UPDATE(id_statuscompra) OR UPDATE(status)
    BEGIN
        INSERT INTO dbo.historico_status_compra
        (
            compra_cabecalho_id,
            usuario_id,
            id_statuscompra,
            confirmado_em,
            snapshot_json,
            tenant_id
        )
        SELECT i.id,
               TRY_CAST(SESSION_CONTEXT(N''user_id'') AS INT),
               ISNULL(i.id_statuscompra, 1),
               SYSUTCDATETIME(),
               NULL,
               i.tenant_id
        FROM inserted i
        INNER JOIN deleted d ON d.id = i.id
        WHERE ISNULL(i.id_statuscompra, -1) <> ISNULL(d.id_statuscompra, -1)
           OR ISNULL(i.status, '''') <> ISNULL(d.status, '''');
    END;
END;');
END;

/* Trigger: status change tracking for vendas_cabecalho */
IF OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
AND OBJECT_ID('dbo.historico_status_pedido', 'U') IS NOT NULL
BEGIN
    EXEC('CREATE OR ALTER TRIGGER dbo.TRG_vendas_cabecalho_status_audit
ON dbo.vendas_cabecalho
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    IF UPDATE(id_statuspedido) OR UPDATE(status)
    BEGIN
        INSERT INTO dbo.historico_status_pedido
        (
            venda_cabecalho_id,
            usuario_id,
            id_statuspedido,
            confirmado_em,
            snapshot_json,
            tenant_id
        )
        SELECT i.id,
               TRY_CAST(SESSION_CONTEXT(N''user_id'') AS INT),
               ISNULL(i.id_statuspedido, 1),
               SYSUTCDATETIME(),
               NULL,
               i.tenant_id
        FROM inserted i
        INNER JOIN deleted d ON d.id = i.id
        WHERE ISNULL(i.id_statuspedido, -1) <> ISNULL(d.id_statuspedido, -1)
           OR ISNULL(i.status, '''') <> ISNULL(d.status, '''');
    END;
END;');
END;

PRINT '== AUDIT AND STATUS HISTORY DONE ==';
