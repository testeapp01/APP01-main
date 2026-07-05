SET NOCOUNT ON;

PRINT '== PHASE 4/5: RBAC ==';

IF OBJECT_ID('dbo.roles', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.roles (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        code NVARCHAR(60) NOT NULL,
        nome NVARCHAR(120) NOT NULL,
        is_system BIT NOT NULL CONSTRAINT DF_roles_is_system DEFAULT (0),
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_roles_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT UQ_roles_code UNIQUE (code)
    );
END;

IF OBJECT_ID('dbo.permissions', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.permissions (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        code NVARCHAR(120) NOT NULL,
        descricao NVARCHAR(300) NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_permissions_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT UQ_permissions_code UNIQUE (code)
    );
END;

IF OBJECT_ID('dbo.user_roles', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.user_roles (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        tenant_id INT NOT NULL,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_user_roles_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT UQ_user_roles UNIQUE (tenant_id, user_id, role_id)
    );
END;

IF OBJECT_ID('dbo.role_permissions', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.role_permissions (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_role_permissions_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT UQ_role_permissions UNIQUE (role_id, permission_id)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_user_roles_tenant')
    ALTER TABLE dbo.user_roles ADD CONSTRAINT FK_user_roles_tenant FOREIGN KEY (tenant_id) REFERENCES dbo.tenants(id);
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_user_roles_user')
    ALTER TABLE dbo.user_roles ADD CONSTRAINT FK_user_roles_user FOREIGN KEY (user_id) REFERENCES dbo.users(id);
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_user_roles_role')
    ALTER TABLE dbo.user_roles ADD CONSTRAINT FK_user_roles_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id);
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_role_permissions_role')
    ALTER TABLE dbo.role_permissions ADD CONSTRAINT FK_role_permissions_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id);
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_role_permissions_permission')
    ALTER TABLE dbo.role_permissions ADD CONSTRAINT FK_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES dbo.permissions(id);

/* Baseline SaaS roles */
MERGE dbo.roles AS t
USING (
    VALUES
    ('SUPERADMIN', 'SuperAdmin', 1),
    ('ADMIN_EMPRESA', 'AdminEmpresa', 1),
    ('GESTOR', 'Gestor', 1),
    ('OPERADOR', 'Operador', 1),
    ('CONSULTA', 'Consulta', 1)
) AS s(code, nome, is_system)
ON t.code = s.code
WHEN NOT MATCHED THEN
    INSERT (code, nome, is_system) VALUES (s.code, s.nome, s.is_system);

/* Baseline permissions */
MERGE dbo.permissions AS t
USING (
    VALUES
    ('tenant.create', 'Criar empresa/tenant'),
    ('tenant.manage', 'Gerenciar empresa/tenant'),
    ('user.create', 'Criar usuario'),
    ('user.manage', 'Editar/bloquear usuario'),
    ('purchase.manage', 'Gerenciar compras'),
    ('sale.manage', 'Gerenciar vendas'),
    ('report.view', 'Visualizar relatorios'),
    ('audit.view', 'Visualizar auditoria')
) AS s(code, descricao)
ON t.code = s.code
WHEN NOT MATCHED THEN
    INSERT (code, descricao) VALUES (s.code, s.descricao);

PRINT '== RBAC DONE ==';
