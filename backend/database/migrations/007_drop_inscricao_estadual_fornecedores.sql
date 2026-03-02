-- Migration: remove inscricao_estadual from fornecedores
USE hortifrut;

ALTER TABLE fornecedores DROP COLUMN inscricao_estadual;
