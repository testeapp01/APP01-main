SET NOCOUNT ON;

PRINT '== PHASE 5: ROW LEVEL SECURITY ==';

/* Session context expected:
   EXEC sp_set_session_context @key=N''tenant_id'', @value=@TenantId;
*/

IF SCHEMA_ID('rls') IS NULL
    EXEC('CREATE SCHEMA rls');

IF OBJECT_ID('rls.fn_tenantAccessPredicate', 'IF') IS NULL
BEGIN
    EXEC('CREATE FUNCTION rls.fn_tenantAccessPredicate(@tenant_id INT)
          RETURNS TABLE
          WITH SCHEMABINDING
          AS
          RETURN SELECT 1 AS fn_result
          WHERE @tenant_id = TRY_CAST(SESSION_CONTEXT(N''tenant_id'') AS INT);');
END;

/* Apply policy only if table has tenant_id */
DECLARE @rls TABLE (table_name SYSNAME);
INSERT INTO @rls(table_name)
VALUES
('users'), ('clientes'), ('fornecedores'), ('motoristas'), ('produtos'),
('compras'), ('compras_cabecalho'), ('vendas'), ('vendas_cabecalho'),
('historico_status_compra'), ('historico_status_pedido'),
('audit_admin_actions'), ('business_event_log'), ('user_roles');

IF NOT EXISTS (SELECT 1 FROM sys.security_policies WHERE name = 'TenantIsolationPolicy')
BEGIN
    DECLARE @sql NVARCHAR(MAX) = N'CREATE SECURITY POLICY dbo.TenantIsolationPolicy ';
    DECLARE @first BIT = 1;
    DECLARE @t SYSNAME;

    DECLARE c CURSOR LOCAL FAST_FORWARD FOR SELECT table_name FROM @rls;
    OPEN c;
    FETCH NEXT FROM c INTO @t;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        IF OBJECT_ID('dbo.' + @t, 'U') IS NOT NULL AND COL_LENGTH('dbo.' + @t, 'tenant_id') IS NOT NULL
        BEGIN
            IF @first = 0 SET @sql = @sql + N', ';
            SET @sql = @sql + N'ADD FILTER PREDICATE rls.fn_tenantAccessPredicate(tenant_id) ON dbo.' + QUOTENAME(@t);
            SET @first = 0;
        END;
        FETCH NEXT FROM c INTO @t;
    END;

    CLOSE c;
    DEALLOCATE c;

    SET @sql = @sql + N' WITH (STATE = ON);';
    EXEC sp_executesql @sql;
END;

PRINT '== RLS DONE ==';
