-- migration_notificacoes.sql
-- Tabela de notificações internas para todos os perfis
-- Correr em: phpMyAdmin → sistema_mioeletrico → SQL

CREATE TABLE IF NOT EXISTS notificacoes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    tipo          VARCHAR(50) NOT NULL DEFAULT 'info',
    titulo        VARCHAR(200) NOT NULL,
    corpo         TEXT NULL,
    url           VARCHAR(500) NULL,
    lida          TINYINT(1) NOT NULL DEFAULT 0,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_not_user_lida (utilizador_id, lida),
    INDEX idx_not_data (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
