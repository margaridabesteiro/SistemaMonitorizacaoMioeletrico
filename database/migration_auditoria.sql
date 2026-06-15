-- Tabela de auditoria — RGPD Art. 30.º (Registos das Atividades de Tratamento)
USE sistema_mioeletrico;

CREATE TABLE IF NOT EXISTS auditoria (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT            NULL,
    nome          VARCHAR(200)   NULL,
    perfil        VARCHAR(20)    NULL,
    acao          VARCHAR(30)    NOT NULL,
    entidade      VARCHAR(50)    NULL,
    entidade_id   INT            NULL,
    detalhe       TEXT           NULL,
    ip            VARCHAR(45)    NULL,
    criado_em     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_criado_em   (criado_em),
    INDEX idx_utilizador  (utilizador_id),
    INDEX idx_acao        (acao),
    INDEX idx_entidade    (entidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
