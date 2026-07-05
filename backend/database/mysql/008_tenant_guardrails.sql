SELECT 'TENANT_GUARDRAILS_START' AS stage;

DROP FUNCTION IF EXISTS fn_current_tenant_id;
DELIMITER $$
CREATE FUNCTION fn_current_tenant_id()
RETURNS INT
DETERMINISTIC
BEGIN
  RETURN IFNULL(@app_tenant_id, 0);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_set_app_context;
DELIMITER $$
CREATE PROCEDURE sp_set_app_context(IN p_tenant_id INT, IN p_user_id INT)
BEGIN
  SET @app_tenant_id = p_tenant_id;
  SET @app_user_id = p_user_id;
END$$
DELIMITER ;

/* Trigger helpers: reject cross-tenant writes */
DROP TRIGGER IF EXISTS trg_compras_tenant_guard_bi;
DROP TRIGGER IF EXISTS trg_compras_tenant_guard_bu;
DROP TRIGGER IF EXISTS trg_vendas_tenant_guard_bi;
DROP TRIGGER IF EXISTS trg_vendas_tenant_guard_bu;

DELIMITER $$
CREATE TRIGGER trg_compras_tenant_guard_bi
BEFORE INSERT ON compras
FOR EACH ROW
BEGIN
  IF fn_current_tenant_id() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tenant context nao definido (@app_tenant_id).';
  END IF;

  IF NEW.tenant_id IS NULL THEN
    SET NEW.tenant_id = fn_current_tenant_id();
  END IF;

  IF NEW.tenant_id <> fn_current_tenant_id() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Violacao de isolamento de tenant em compras.';
  END IF;
END$$

CREATE TRIGGER trg_compras_tenant_guard_bu
BEFORE UPDATE ON compras
FOR EACH ROW
BEGIN
  IF fn_current_tenant_id() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tenant context nao definido (@app_tenant_id).';
  END IF;

  IF OLD.tenant_id <> fn_current_tenant_id() OR NEW.tenant_id <> fn_current_tenant_id() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Violacao de isolamento de tenant em compras (update).';
  END IF;
END$$

CREATE TRIGGER trg_vendas_tenant_guard_bi
BEFORE INSERT ON vendas
FOR EACH ROW
BEGIN
  IF fn_current_tenant_id() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tenant context nao definido (@app_tenant_id).';
  END IF;

  IF NEW.tenant_id IS NULL THEN
    SET NEW.tenant_id = fn_current_tenant_id();
  END IF;

  IF NEW.tenant_id <> fn_current_tenant_id() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Violacao de isolamento de tenant em vendas.';
  END IF;
END$$

CREATE TRIGGER trg_vendas_tenant_guard_bu
BEFORE UPDATE ON vendas
FOR EACH ROW
BEGIN
  IF fn_current_tenant_id() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tenant context nao definido (@app_tenant_id).';
  END IF;

  IF OLD.tenant_id <> fn_current_tenant_id() OR NEW.tenant_id <> fn_current_tenant_id() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Violacao de isolamento de tenant em vendas (update).';
  END IF;
END$$
DELIMITER ;

SELECT 'TENANT_GUARDRAILS_DONE' AS stage;
