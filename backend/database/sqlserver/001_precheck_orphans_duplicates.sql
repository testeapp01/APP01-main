SET NOCOUNT ON;

PRINT '== PRECHECK: ORPHANS, DUPLICATES, INCONSISTENCIES ==';

/* 1) Orfaos de cabecalho/item */
IF OBJECT_ID('dbo.compras', 'U') IS NOT NULL AND OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
BEGIN
    SELECT c.id AS compra_orfa
    FROM dbo.compras c
    LEFT JOIN dbo.compras_cabecalho h ON h.id = c.compra_cabecalho_id
    WHERE c.compra_cabecalho_id IS NOT NULL
      AND h.id IS NULL;
END;

IF OBJECT_ID('dbo.vendas', 'U') IS NOT NULL AND OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
BEGIN
    SELECT v.id AS venda_orfa
    FROM dbo.vendas v
    LEFT JOIN dbo.vendas_cabecalho h ON h.id = v.venda_cabecalho_id
    WHERE v.venda_cabecalho_id IS NOT NULL
      AND h.id IS NULL;
END;

/* 2) Orfaos de historico */
IF OBJECT_ID('dbo.historico_status_compra', 'U') IS NOT NULL AND OBJECT_ID('dbo.compras_cabecalho', 'U') IS NOT NULL
BEGIN
    SELECT h.id AS historico_compra_orfao
    FROM dbo.historico_status_compra h
    LEFT JOIN dbo.compras_cabecalho c ON c.id = h.compra_cabecalho_id
    WHERE c.id IS NULL;
END;

IF OBJECT_ID('dbo.historico_status_pedido', 'U') IS NOT NULL AND OBJECT_ID('dbo.vendas_cabecalho', 'U') IS NOT NULL
BEGIN
    SELECT h.id AS historico_pedido_orfao
    FROM dbo.historico_status_pedido h
    LEFT JOIN dbo.vendas_cabecalho v ON v.id = h.venda_cabecalho_id
    WHERE v.id IS NULL;
END;

/* 3) Duplicidade de documento de cliente */
IF OBJECT_ID('dbo.clientes', 'U') IS NOT NULL AND COL_LENGTH('dbo.clientes', 'cpf_cnpj') IS NOT NULL
BEGIN
    SELECT REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), '(', ''), ')', '') AS doc_norm,
           COUNT(*) AS qtd
    FROM dbo.clientes
    WHERE cpf_cnpj IS NOT NULL AND LTRIM(RTRIM(cpf_cnpj)) <> ''
    GROUP BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), '(', ''), ')', '')
    HAVING COUNT(*) > 1;
END;

/* 4) Duplicidade de email de usuario */
IF OBJECT_ID('dbo.users', 'U') IS NOT NULL AND COL_LENGTH('dbo.users', 'email') IS NOT NULL
BEGIN
    SELECT LOWER(LTRIM(RTRIM(email))) AS email_norm,
           COUNT(*) AS qtd
    FROM dbo.users
    WHERE email IS NOT NULL AND LTRIM(RTRIM(email)) <> ''
    GROUP BY LOWER(LTRIM(RTRIM(email)))
    HAVING COUNT(*) > 1;
END;

/* 5) Campos financeiros invalidos */
IF OBJECT_ID('dbo.compras', 'U') IS NOT NULL
BEGIN
    SELECT id, quantidade, valor_unitario
    FROM dbo.compras
    WHERE ISNULL(quantidade, 0) <= 0 OR ISNULL(valor_unitario, 0) <= 0;
END;

IF OBJECT_ID('dbo.vendas', 'U') IS NOT NULL
BEGIN
    SELECT id, quantidade, valor_unitario
    FROM dbo.vendas
    WHERE ISNULL(quantidade, 0) <= 0 OR ISNULL(valor_unitario, 0) <= 0;
END;

PRINT '== PRECHECK DONE ==';
