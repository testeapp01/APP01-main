-- Migration: enforce unique client document (CPF/CNPJ)
USE hortifrut;

-- Normalize legacy formatted values before unique constraint
UPDATE clientes
SET cpf_cnpj = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', ''), '(', ''), ')', '')
WHERE cpf_cnpj IS NOT NULL AND cpf_cnpj <> '';

ALTER TABLE clientes
  ADD UNIQUE KEY ux_clientes_cpf_cnpj (cpf_cnpj);
