-- database/schema.sql
-- Schema completo do sistema RehabLink
-- Executar no phpMyAdmin ou via linha de comandos: mysql -u root rehablink < schema.sql

CREATE DATABASE IF NOT EXISTS rehablink
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rehablink;

-- ============================================================
-- UTILIZADORES (tabela base para todos os perfis)
-- ============================================================
CREATE TABLE IF NOT EXISTS utilizadores (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(150)        NOT NULL,
    email         VARCHAR(255)        NOT NULL UNIQUE,
    password_hash VARCHAR(255)        NOT NULL,          -- bcrypt via password_hash()
    perfil        ENUM('admin','medico','tecnico','utente') NOT NULL,
    ativo         BOOLEAN             NOT NULL DEFAULT TRUE,
    criado_em     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login  DATETIME            NULL,
    INDEX idx_email (email),
    INDEX idx_perfil (perfil)
) ENGINE=InnoDB;

-- ============================================================
-- PROFISSIONAIS DE SAÚDE (médicos e técnicos)
-- ============================================================
CREATE TABLE IF NOT EXISTS profissionais (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id   INT UNSIGNED        NOT NULL UNIQUE,
    numero_ordem    VARCHAR(30)         NULL,           -- cédula profissional
    especialidade   VARCHAR(100)        NULL,
    instituicao     VARCHAR(150)        NULL,
    contacto        VARCHAR(20)         NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- UTENTES (pacientes)
-- ============================================================
CREATE TABLE IF NOT EXISTS utentes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id   INT UNSIGNED        NOT NULL UNIQUE,
    data_nascimento DATE                NULL,
    sexo            ENUM('M','F','O')   NULL,
    nif             VARCHAR(9)          NULL,
    morada          VARCHAR(255)        NULL,
    codigo_postal   VARCHAR(8)          NULL,
    localidade      VARCHAR(100)        NULL,
    medico_id       INT UNSIGNED        NULL,           -- médico responsável
    tecnico_id      INT UNSIGNED        NULL,           -- técnico/fisioterapeuta responsável
    diagnostico     TEXT                NULL,
    observacoes     TEXT                NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id)     REFERENCES profissionais(id) ON DELETE SET NULL,
    FOREIGN KEY (tecnico_id)    REFERENCES profissionais(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- DISPOSITIVOS EMG
-- ============================================================
CREATE TABLE IF NOT EXISTS dispositivos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(20)         NOT NULL UNIQUE, -- ex: PS-1024
    tipo            VARCHAR(80)         NOT NULL,        -- ex: 'Força de pinça'
    firmware_versao VARCHAR(20)         NULL,
    utente_id       INT UNSIGNED        NULL,
    associado_em    DATETIME            NULL,
    ultimo_sync     DATETIME            NULL,
    ativo           BOOLEAN             NOT NULL DEFAULT TRUE,
    FOREIGN KEY (utente_id) REFERENCES utentes(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB;

-- ============================================================
-- SESSÕES DE TREINO
-- ============================================================
CREATE TABLE IF NOT EXISTS sessoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utente_id       INT UNSIGNED        NOT NULL,
    tecnico_id      INT UNSIGNED        NOT NULL,
    dispositivo_id  INT UNSIGNED        NULL,
    data_hora       DATETIME            NOT NULL,
    duracao_min     SMALLINT UNSIGNED   NULL,
    tipo            VARCHAR(80)         NULL,            -- ex: 'Calibração', 'Jogo'
    estado          ENUM('agendada','em_curso','concluida','cancelada') NOT NULL DEFAULT 'agendada',
    notas           TEXT                NULL,
    criada_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id)    REFERENCES utentes(id)    ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id)   REFERENCES profissionais(id) ON DELETE RESTRICT,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id) ON DELETE SET NULL,
    INDEX idx_utente_data (utente_id, data_hora),
    INDEX idx_tecnico_data (tecnico_id, data_hora)
) ENGINE=InnoDB;

-- ============================================================
-- DADOS EMG (leituras por sessão)
-- ============================================================
CREATE TABLE IF NOT EXISTS leituras_emg (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_id       INT UNSIGNED        NOT NULL,
    canal           TINYINT UNSIGNED    NOT NULL DEFAULT 1,
    timestamp_ms    INT UNSIGNED        NOT NULL,        -- ms desde início da sessão
    amplitude_uv    FLOAT               NOT NULL,        -- µV
    FOREIGN KEY (sessao_id) REFERENCES sessoes(id) ON DELETE CASCADE,
    INDEX idx_sessao_ts (sessao_id, timestamp_ms)
) ENGINE=InnoDB;

-- ============================================================
-- MÉTRICAS CALCULADAS (por sessão — evita recálculo)
-- ============================================================
CREATE TABLE IF NOT EXISTS metricas_sessao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_id       INT UNSIGNED        NOT NULL UNIQUE,
    rms_uv          FLOAT               NULL,
    mav_uv          FLOAT               NULL,
    frequencia_hz   FLOAT               NULL,
    score_jogo      INT                 NULL,
    precisao_pct    FLOAT               NULL,
    calculado_em    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sessao_id) REFERENCES sessoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PRESCRIÇÕES MÉDICAS
-- ============================================================
CREATE TABLE IF NOT EXISTS prescricoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utente_id       INT UNSIGNED        NOT NULL,
    medico_id       INT UNSIGNED        NOT NULL,
    data_prescricao DATE                NOT NULL,
    data_validade   DATE                NULL,
    tipo            ENUM('SNS','Particular','Seguro') NOT NULL DEFAULT 'SNS',
    prioridade      ENUM('Baixa','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
    observacoes     TEXT                NULL,
    ativa           BOOLEAN             NOT NULL DEFAULT TRUE,
    criada_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utentes(id)    ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES profissionais(id) ON DELETE RESTRICT,
    INDEX idx_utente (utente_id),
    INDEX idx_medico (medico_id)
) ENGINE=InnoDB;

-- ============================================================
-- CONSULTAS
-- ============================================================
CREATE TABLE IF NOT EXISTS consultas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utente_id       INT UNSIGNED        NOT NULL,
    medico_id       INT UNSIGNED        NOT NULL,
    data_hora       DATETIME            NOT NULL,
    motivo          VARCHAR(255)        NULL,
    notas           TEXT                NULL,
    estado          ENUM('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
    FOREIGN KEY (utente_id) REFERENCES utentes(id)    ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES profissionais(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- MENSAGENS INTERNAS
-- ============================================================
CREATE TABLE IF NOT EXISTS mensagens (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    remetente_id    INT UNSIGNED        NOT NULL,
    destinatario_id INT UNSIGNED        NOT NULL,
    assunto         VARCHAR(255)        NULL,
    corpo           TEXT                NOT NULL,
    lida            BOOLEAN             NOT NULL DEFAULT FALSE,
    enviada_em      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id)    REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    INDEX idx_destinatario (destinatario_id, lida)
) ENGINE=InnoDB;

-- ============================================================
-- FATURAS
-- ============================================================
CREATE TABLE IF NOT EXISTS faturas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero          VARCHAR(20)         NOT NULL UNIQUE, -- ex: FT2026/001
    utente_id       INT UNSIGNED        NOT NULL,
    sessao_id       INT UNSIGNED        NULL,
    valor_eur       DECIMAL(8,2)        NOT NULL,
    paga            BOOLEAN             NOT NULL DEFAULT FALSE,
    data_emissao    DATE                NOT NULL,
    data_vencimento DATE                NULL,
    notas           TEXT                NULL,
    FOREIGN KEY (utente_id) REFERENCES utentes(id) ON DELETE RESTRICT,
    FOREIGN KEY (sessao_id) REFERENCES sessoes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- LOGS DE ACESSO (segurança / auditoria)
-- ============================================================
CREATE TABLE IF NOT EXISTS logs_acesso (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id   INT UNSIGNED        NULL,            -- NULL = tentativa sem login
    acao            VARCHAR(100)        NOT NULL,        -- ex: 'login', 'logout', 'acesso_negado'
    ip              VARCHAR(45)         NOT NULL,        -- suporta IPv6
    user_agent      VARCHAR(255)        NULL,
    detalhes        TEXT                NULL,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL,
    INDEX idx_utilizador (utilizador_id),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB;

-- ============================================================
-- UTILIZADOR ADMIN PADRÃO (password: admin123 — ALTERAR EM PRODUÇÃO)
-- ============================================================
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil)
VALUES (
    'Administrador',
    'admin@rehablink.pt',
    '$2y$12$YourHashHere',   -- substituir por password_hash('admin123', PASSWORD_BCRYPT)
    'admin'
);
