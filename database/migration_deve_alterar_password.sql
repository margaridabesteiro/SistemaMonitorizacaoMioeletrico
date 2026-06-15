-- Adicionar coluna para forçar alteração de password no primeiro acesso
USE sistema_mioeletrico;

ALTER TABLE utilizadores
    ADD COLUMN deve_alterar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
