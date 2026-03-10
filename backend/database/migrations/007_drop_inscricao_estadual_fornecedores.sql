-- Migration: remove inscricao_estadual from fornecedores
ALTER TABLE fornecedores DROP COLUMN inscricao_estadual;
