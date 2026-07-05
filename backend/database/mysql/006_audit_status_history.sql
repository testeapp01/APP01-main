SELECT 'AUDIT_STATUS_HISTORY_START' AS stage;

CREATE TABLE IF NOT EXISTS audit_admin_actions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  user_id INT NULL,
  action_name VARCHAR(120) NOT NULL,
  entity_name VARCHAR(120) NOT NULL,
  entity_id VARCHAR(120) NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  reason VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_audit_tenant_created (tenant_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS business_event_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  event_name VARCHAR(120) NOT NULL,
  aggregate_name VARCHAR(120) NOT NULL,
  aggregate_id VARCHAR(120) NULL,
  payload_json JSON NULL,
  correlation_id VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_business_event_tenant_created (tenant_id, created_at)
) ENGINE=InnoDB;

DROP TRIGGER IF EXISTS trg_compras_cabecalho_status_audit;
DROP TRIGGER IF EXISTS trg_vendas_cabecalho_status_audit;

DELIMITER $$

CREATE TRIGGER trg_compras_cabecalho_status_audit
AFTER UPDATE ON compras_cabecalho
FOR EACH ROW
BEGIN
  IF IFNULL(NEW.id_statuscompra, -1) <> IFNULL(OLD.id_statuscompra, -1)
     OR IFNULL(NEW.status, '') <> IFNULL(OLD.status, '') THEN
    INSERT INTO historico_status_compra
      (compra_cabecalho_id, usuario_id, id_statuscompra, confirmado_em, snapshot_json, tenant_id)
    VALUES
      (NEW.id, @app_user_id, IFNULL(NEW.id_statuscompra, 1), NOW(), NULL, NEW.tenant_id);
  END IF;
END$$

CREATE TRIGGER trg_vendas_cabecalho_status_audit
AFTER UPDATE ON vendas_cabecalho
FOR EACH ROW
BEGIN
  IF IFNULL(NEW.id_statuspedido, -1) <> IFNULL(OLD.id_statuspedido, -1)
     OR IFNULL(NEW.status, '') <> IFNULL(OLD.status, '') THEN
    INSERT INTO historico_status_pedido
      (venda_cabecalho_id, usuario_id, id_statuspedido, confirmado_em, snapshot_json, tenant_id)
    VALUES
      (NEW.id, @app_user_id, IFNULL(NEW.id_statuspedido, 1), NOW(), NULL, NEW.tenant_id);
  END IF;
END$$

DELIMITER ;

SELECT 'AUDIT_STATUS_HISTORY_DONE' AS stage;
