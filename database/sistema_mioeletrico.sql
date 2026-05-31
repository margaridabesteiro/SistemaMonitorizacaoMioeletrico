-- =============================================================
-- BASE DE DADOS: sistema_mioeletrico
-- Sistema de Monitorização Mioeléctrica — RehabLink
-- Importar no phpMyAdmin ou via linha de comandos:
--   mysql -u root sistema_mioeletrico < sistema_mioeletrico.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS sistema_mioeletrico
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_mioeletrico;

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
    FOREIGN KEY (utente_id)      REFERENCES utentes(id)       ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id)     REFERENCES profissionais(id)  ON DELETE RESTRICT,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id)  ON DELETE SET NULL,
    INDEX idx_utente_data  (utente_id,  data_hora),
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
    FOREIGN KEY (utente_id) REFERENCES utentes(id)       ON DELETE CASCADE,
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
    FOREIGN KEY (utente_id) REFERENCES utentes(id)       ON DELETE CASCADE,
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
    FOREIGN KEY (utente_id) REFERENCES utentes(id)   ON DELETE RESTRICT,
    FOREIGN KEY (sessao_id) REFERENCES sessoes(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- LOGS DE ACESSO (segurança / auditoria)
-- ============================================================
CREATE TABLE IF NOT EXISTS logs_acesso (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id   INT UNSIGNED        NULL,            -- NULL = tentativa sem login
    acao            VARCHAR(100)        NOT NULL,        -- ex: 'login', 'logout'
    ip              VARCHAR(45)         NOT NULL,        -- suporta IPv6
    user_agent      VARCHAR(255)        NULL,
    detalhes        TEXT                NULL,
    criado_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL,
    INDEX idx_utilizador (utilizador_id),
    INDEX idx_criado     (criado_em)
) ENGINE=InnoDB;

-- ============================================================
-- CONTEÚDO DO BACKOFFICE (textos da landing page editáveis)
-- ============================================================
CREATE TABLE IF NOT EXISTS backoffice_conteudo (
    chave       VARCHAR(80)  NOT NULL PRIMARY KEY,   -- ex: 'quem_somos_titulo'
    valor       TEXT         NOT NULL,
    atualizado  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Administrador padrão
-- Password: Admin123!
-- Hash gerado com: password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 12])
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES (
    'Administrador',
    'admin@rehablink.pt',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- Médico de demonstração
-- Password: Medico123!
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES (
    'Dr. João Silva',
    'medico@rehablink.pt',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'medico'
);

-- Técnico de demonstração
-- Password: Tecnico123!
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES (
    'Ana Ferreira',
    'tecnico@rehablink.pt',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'tecnico'
);

-- Utente de demonstração
-- Password: Utente123!
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES (
    'Carlos Mendes',
    'utente@rehablink.pt',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'utente'
);

-- Registar profissionais (médico e técnico)
INSERT IGNORE INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao, contacto)
SELECT id, 'OM-12345', 'Medicina Física e Reabilitação', 'RehabLink', '912000001'
FROM utilizadores WHERE email = 'medico@rehablink.pt';

INSERT IGNORE INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao, contacto)
SELECT id, 'OF-67890', 'Fisioterapia Mioeléctrica', 'RehabLink', '912000002'
FROM utilizadores WHERE email = 'tecnico@rehablink.pt';

-- Registar utente
INSERT IGNORE INTO utentes (utilizador_id, data_nascimento, sexo, nif, localidade, diagnostico)
SELECT id, '1985-03-15', 'M', '123456789', 'Lisboa', 'Amputação transtibial — reabilitação protetética'
FROM utilizadores WHERE email = 'utente@rehablink.pt';

-- Associar médico e técnico ao utente
UPDATE utentes u
JOIN utilizadores uu ON u.utilizador_id = uu.id
SET
    u.medico_id  = (SELECT p.id FROM profissionais p JOIN utilizadores um ON p.utilizador_id = um.id WHERE um.email = 'medico@rehablink.pt' LIMIT 1),
    u.tecnico_id = (SELECT p.id FROM profissionais p JOIN utilizadores ut ON p.utilizador_id = ut.id WHERE ut.email = 'tecnico@rehablink.pt' LIMIT 1)
WHERE uu.email = 'utente@rehablink.pt';

-- Dispositivo EMG de demonstração
INSERT IGNORE INTO dispositivos (codigo, tipo, firmware_versao, ativo) VALUES
('EMG-0001', 'Sensor Mioeléctrico de Superfície 8 Canais', 'v2.1.4', TRUE),
('EMG-0002', 'Sensor de Força de Pinça', 'v1.8.0', TRUE);

-- Conteúdo backoffice padrão
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('quem_somos_titulo', 'Tecnologia ao serviço da reabilitação'),
('quem_somos_texto', 'A RehabLink é uma plataforma inovadora de monitorização mioeléctrica que une dispositivos EMG avançados a software intuitivo para profissionais de saúde e utentes.'),
('missao_titulo', 'A Nossa Missão'),
('missao_texto', 'Capacitar profissionais de saúde com dados em tempo real para personalizar e otimizar a reabilitação de cada paciente.');
