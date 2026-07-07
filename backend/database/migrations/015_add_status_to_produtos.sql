ALTER TABLE produtos ADD COLUMN IF NOT EXISTS status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo';
