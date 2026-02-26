-- Migration: add extra columns for clients and drivers
USE hortifrut;

ALTER TABLE clientes
  ADD COLUMN cpf_cnpj VARCHAR(50) NULL AFTER nome,
  ADD COLUMN email VARCHAR(150) NULL AFTER telefone,
  ADD COLUMN uf VARCHAR(2) NULL AFTER email,
  ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER uf;

ALTER TABLE motoristas
  ADD COLUMN placa VARCHAR(50) NULL AFTER nome,
  ADD COLUMN veiculo VARCHAR(150) NULL AFTER placa,
  ADD COLUMN uf VARCHAR(2) NULL AFTER veiculo,
  ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER uf;
