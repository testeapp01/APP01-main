SELECT 'RBAC_START' AS stage;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  nome VARCHAR(120) NOT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(120) NOT NULL UNIQUE,
  descricao VARCHAR(300) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_user_roles (tenant_id, user_id, role_id),
  CONSTRAINT fk_user_roles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_role_permissions (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id),
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (code, nome, is_system) VALUES
('SUPERADMIN', 'SuperAdmin', 1),
('ADMIN_EMPRESA', 'AdminEmpresa', 1),
('GESTOR', 'Gestor', 1),
('OPERADOR', 'Operador', 1),
('CONSULTA', 'Consulta', 1);

INSERT IGNORE INTO permissions (code, descricao) VALUES
('tenant.create', 'Criar empresa/tenant'),
('tenant.manage', 'Gerenciar empresa/tenant'),
('user.create', 'Criar usuario'),
('user.manage', 'Editar e bloquear usuario'),
('purchase.manage', 'Gerenciar compras'),
('sale.manage', 'Gerenciar vendas'),
('report.view', 'Visualizar relatorios'),
('audit.view', 'Visualizar auditoria');

SELECT 'RBAC_DONE' AS stage;
