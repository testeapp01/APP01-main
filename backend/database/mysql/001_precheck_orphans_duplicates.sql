SELECT 'PRECHECK_ORPHANS_DUPLICATES_START' AS stage;

/* Orfaos de item->cabecalho */
SELECT c.id AS compra_orfa
FROM compras c
LEFT JOIN compras_cabecalho h ON h.id = c.compra_cabecalho_id
WHERE c.compra_cabecalho_id IS NOT NULL
  AND h.id IS NULL;

SELECT v.id AS venda_orfa
FROM vendas v
LEFT JOIN vendas_cabecalho h ON h.id = v.venda_cabecalho_id
WHERE v.venda_cabecalho_id IS NOT NULL
  AND h.id IS NULL;

/* Orfaos de historico */
SELECT h.id AS historico_compra_orfao
FROM historico_status_compra h
LEFT JOIN compras_cabecalho c ON c.id = h.compra_cabecalho_id
WHERE c.id IS NULL;

SELECT h.id AS historico_venda_orfao
FROM historico_status_pedido h
LEFT JOIN vendas_cabecalho v ON v.id = h.venda_cabecalho_id
WHERE v.id IS NULL;

/* Duplicidade de documento */
SELECT REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), '(', ''), ')', '') AS doc_norm,
       COUNT(*) AS qtd
FROM clientes
WHERE cpf_cnpj IS NOT NULL AND TRIM(cpf_cnpj) <> ''
GROUP BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), '(', ''), ')', '')
HAVING COUNT(*) > 1;

/* Duplicidade de email */
SELECT LOWER(TRIM(email)) AS email_norm,
       COUNT(*) AS qtd
FROM users
WHERE email IS NOT NULL AND TRIM(email) <> ''
GROUP BY LOWER(TRIM(email))
HAVING COUNT(*) > 1;

/* Financeiro invalido */
SELECT id, quantidade, valor_unitario
FROM compras
WHERE IFNULL(quantidade, 0) <= 0 OR IFNULL(valor_unitario, 0) <= 0;

SELECT id, quantidade, valor_unitario
FROM vendas
WHERE IFNULL(quantidade, 0) <= 0 OR IFNULL(valor_unitario, 0) <= 0;

SELECT 'PRECHECK_ORPHANS_DUPLICATES_DONE' AS stage;
