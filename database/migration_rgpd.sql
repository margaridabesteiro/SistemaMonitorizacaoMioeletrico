-- Migração RGPD: tabelas de consentimentos e pedidos
-- Executar no phpMyAdmin: seleccionar sistema_mioeletrico → separador SQL

USE sistema_mioeletrico;

-- Registo de consentimentos e ações RGPD
CREATE TABLE IF NOT EXISTS rgpd_consentimentos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT UNSIGNED    NOT NULL,
    tipo           ENUM('registo','revogacao','exportacao','eliminacao_pedido') NOT NULL,
    registado_por  INT UNSIGNED    NULL,     -- admin que registou (NULL = próprio titular)
    ip             VARCHAR(45)     NULL,
    detalhes       TEXT            NULL,
    criado_em      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (utilizador_id),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Pedidos de exercício de direitos RGPD (portabilidade, eliminação, retificação)
CREATE TABLE IF NOT EXISTS rgpd_pedidos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id  INT UNSIGNED    NOT NULL,
    tipo           ENUM('exportacao','eliminacao','retificacao') NOT NULL,
    estado         ENUM('pendente','processado','rejeitado') NOT NULL DEFAULT 'pendente',
    mensagem       TEXT            NULL,
    resposta       TEXT            NULL,
    criado_em      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processado_em  DATETIME        NULL,
    INDEX idx_user (utilizador_id),
    INDEX idx_estado (estado),
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;
