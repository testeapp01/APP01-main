-- Migration: ensure compatibility columns for legacy environments
USE hortifrut;

ALTER TABLE clientes ADD COLUMN cpf_cnpj VARCHAR(50) NULL AFTER nome;
ALTER TABLE clientes ADD COLUMN email VARCHAR(150) NULL AFTER telefone;
ALTER TABLE clientes ADD COLUMN uf VARCHAR(2) NULL AFTER email;
ALTER TABLE clientes ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER uf;
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
ALTER TABLE fornecedores ADD COLUMN email VARCHAR(150) NULL AFTER cnpj;
ALTER TABLE fornecedores ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER telefone;
ALTER TABLE fornecedores ADD COLUMN uf VARCHAR(2) NULL AFTER status;

ALTER TABLE motoristas ADD COLUMN placa VARCHAR(50) NULL AFTER nome;
ALTER TABLE motoristas ADD COLUMN veiculo VARCHAR(150) NULL AFTER placa;
ALTER TABLE motoristas ADD COLUMN uf VARCHAR(2) NULL AFTER veiculo;
ALTER TABLE motoristas ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER uf;

ALTER TABLE compras ADD COLUMN data_envio_prevista DATE NULL AFTER data_compra;
ALTER TABLE compras ADD COLUMN data_entrega_prevista DATE NULL AFTER data_envio_prevista;

ALTER TABLE vendas ADD COLUMN data_envio_prevista DATE NULL AFTER data_venda;
ALTER TABLE vendas ADD COLUMN data_entrega_prevista DATE NULL AFTER data_envio_prevista;
