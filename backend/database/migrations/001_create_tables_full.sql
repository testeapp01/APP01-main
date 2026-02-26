-- Tipos de caminhão fixos
CREATE TABLE IF NOT EXISTS tipos_caminhao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO tipos_caminhao (nome) VALUES
  ('VUC'),
  ('3/4'),
  ('Toco'),
  ('Truck'),
  ('Baú'),
  ('Baú Refrigerado'),
  ('Sider'),
  ('Plataforma'),
  ('Carreta'),
  ('Rodotrem');

-- Migration: create base schema for hortifrut box management
CREATE DATABASE IF NOT EXISTS hortifrut CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hortifrut;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fornecedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  razao_social VARCHAR(255) NOT NULL,
  cnpj VARCHAR(30),
  telefone VARCHAR(50),
  cidade VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS motoristas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cpf VARCHAR(30),
  telefone VARCHAR(50),
  TpCaminhao INT DEFAULT NULL,
  FOREIGN KEY (TpCaminhao) REFERENCES tipos_caminhao(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  tipo VARCHAR(100),
  unidade VARCHAR(50) DEFAULT 'saco',
  estoque_atual DECIMAL(14,4) DEFAULT 0,
  custo_medio DECIMAL(14,4) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fornecedor_id INT NOT NULL,
  produto_id INT NOT NULL,
  motorista_id INT NOT NULL,
  quantidade DECIMAL(14,4) NOT NULL,
  valor_unitario DECIMAL(14,4) NOT NULL,
  tipo_comissao VARCHAR(50) DEFAULT NULL,
  valor_comissao DECIMAL(14,4) DEFAULT NULL,
  extra_por_saco DECIMAL(14,4) DEFAULT 0,
  custo_total DECIMAL(16,4) DEFAULT 0,
  comissao_total DECIMAL(16,4) DEFAULT 0,
  custo_final_real DECIMAL(16,4) DEFAULT 0,
  status ENUM('NEGOCIADA','EM_TRANSPORTE','RECEBIDA','CANCELADA') DEFAULT 'NEGOCIADA',
  data_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE RESTRICT,
  FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE RESTRICT,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  telefone VARCHAR(50),
  cidade VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade DECIMAL(14,4) NOT NULL,
  valor_unitario DECIMAL(14,4) NOT NULL,
  receita_total DECIMAL(16,4) DEFAULT 0,
  custo_proporcional DECIMAL(16,4) DEFAULT 0,
  lucro_bruto DECIMAL(16,4) DEFAULT 0,
  margem_percentual DECIMAL(8,4) DEFAULT 0,
  status ENUM('ORCAMENTO','CONFIRMADA','ENTREGUE','CANCELADA') DEFAULT 'ORCAMENTO',
  data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
);

-- Tabela de logs
CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  acao VARCHAR(255) NOT NULL,
  detalhes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de rate limit
CREATE TABLE IF NOT EXISTS rate_limit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  endpoint VARCHAR(255) NOT NULL,
  count INT DEFAULT 0,
  last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de tokens/sessões
CREATE TABLE IF NOT EXISTS user_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
