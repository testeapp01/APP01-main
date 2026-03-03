-- Migration: add address fields for clients and suppliers
ALTER TABLE clientes ADD COLUMN endereco VARCHAR(255) NULL AFTER nome;
ALTER TABLE clientes ADD COLUMN numero VARCHAR(20) NULL AFTER endereco;
ALTER TABLE clientes ADD COLUMN complemento VARCHAR(100) NULL AFTER numero;
ALTER TABLE clientes ADD COLUMN bairro VARCHAR(100) NULL AFTER complemento;
ALTER TABLE clientes ADD COLUMN cep VARCHAR(20) NULL AFTER bairro;

ALTER TABLE fornecedores ADD COLUMN endereco VARCHAR(255) NULL AFTER razao_social;
ALTER TABLE fornecedores ADD COLUMN numero VARCHAR(20) NULL AFTER endereco;
ALTER TABLE fornecedores ADD COLUMN complemento VARCHAR(100) NULL AFTER numero;
ALTER TABLE fornecedores ADD COLUMN bairro VARCHAR(100) NULL AFTER complemento;
ALTER TABLE fornecedores ADD COLUMN cep VARCHAR(20) NULL AFTER bairro;
ALTER TABLE fornecedores ADD COLUMN cidade VARCHAR(100) NULL AFTER cep;
