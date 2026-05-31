-- Migração: adicionar foto_path e legenda à tabela profissionais
-- Executar no phpMyAdmin: seleccionar sistema_mioeletrico → separador SQL

USE sistema_mioeletrico;

ALTER TABLE profissionais
    ADD COLUMN IF NOT EXISTS foto_path VARCHAR(255) NULL AFTER contacto,
    ADD COLUMN IF NOT EXISTS legenda   TEXT         NULL AFTER foto_path;
