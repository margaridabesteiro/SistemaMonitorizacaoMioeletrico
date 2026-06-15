-- Migration: Dados pessoais nos utilizadores, profissionais e utentes
-- Data: 2026-06-15

-- Adicionar colunas de dados pessoais aos profissionais (médico/técnico)
ALTER TABLE profissionais
    ADD COLUMN IF NOT EXISTS data_nascimento DATE NULL AFTER contacto,
    ADD COLUMN IF NOT EXISTS sexo ENUM('M','F','O') NULL AFTER data_nascimento,
    ADD COLUMN IF NOT EXISTS telemovel VARCHAR(20) NULL AFTER sexo;

-- Adicionar colunas de dados pessoais aos utilizadores (para admin)
ALTER TABLE utilizadores
    ADD COLUMN IF NOT EXISTS data_nascimento DATE NULL AFTER email,
    ADD COLUMN IF NOT EXISTS sexo ENUM('M','F','O') NULL AFTER data_nascimento,
    ADD COLUMN IF NOT EXISTS telemovel VARCHAR(20) NULL AFTER sexo;

-- Adicionar telemóvel aos utentes (os outros campos já existem)
ALTER TABLE utentes
    ADD COLUMN IF NOT EXISTS telemovel VARCHAR(20) NULL AFTER nif;
