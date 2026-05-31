-- Migração: tabela de mensagens do formulário de contacto da página principal
-- Executar no phpMyAdmin: seleccionar sistema_mioeletrico e colar no separador SQL

USE sistema_mioeletrico;

CREATE TABLE IF NOT EXISTS contactos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(150)    NOT NULL,
    email       VARCHAR(255)    NOT NULL,
    telefone    VARCHAR(20)     NULL,
    assunto     VARCHAR(80)     NULL,
    mensagem    TEXT            NOT NULL,
    lida        BOOLEAN         NOT NULL DEFAULT FALSE,
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lida     (lida),
    INDEX idx_criado   (criado_em)
) ENGINE=InnoDB;
