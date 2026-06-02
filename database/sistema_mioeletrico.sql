-- =============================================================
-- BASE DE DADOS: sistema_mioeletrico
-- Sistema de Monitorização Mioeléctrica — RehabLink
-- Schema completo (versão final com todas as migrações aplicadas)
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
    password_hash VARCHAR(255)        NOT NULL,
    perfil        ENUM('admin','medico','tecnico','utente') NOT NULL,
    ativo         BOOLEAN             NOT NULL DEFAULT TRUE,
    criado_em     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login  DATETIME            NULL,
    INDEX idx_email  (email),
    INDEX idx_perfil (perfil)
) ENGINE=InnoDB;

-- ============================================================
-- PROFISSIONAIS DE SAÚDE (médicos e técnicos)
-- ============================================================
CREATE TABLE IF NOT EXISTS profissionais (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id   INT UNSIGNED        NOT NULL UNIQUE,
    numero_ordem    VARCHAR(30)         NULL,
    especialidade   VARCHAR(100)        NULL,
    instituicao     VARCHAR(150)        NULL,
    contacto        VARCHAR(20)         NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- UTENTES (pacientes)
-- ============================================================
CREATE TABLE IF NOT EXISTS utentes (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id           INT UNSIGNED        NOT NULL UNIQUE,
    data_nascimento         DATE                NULL,
    sexo                    ENUM('M','F','O')   NULL,
    nif                     CHAR(9)             NULL UNIQUE,
    morada                  VARCHAR(255)        NULL,
    codigo_postal           VARCHAR(8)          NULL,
    localidade              VARCHAR(100)        NULL,
    medico_id               INT UNSIGNED        NULL,
    tecnico_id              INT UNSIGNED        NULL,
    diagnostico             TEXT                NULL,
    observacoes             TEXT                NULL,
    cobertura_saude         ENUM('SNS','Particular','Seguro')
                            NOT NULL DEFAULT 'SNS'
                            COMMENT 'Gerida pelo admin na admissão — nunca pelo médico',
    fase_tratamento         ENUM('avaliacao','ativo','manutencao','alta')
                            NOT NULL DEFAULT 'avaliacao',
    categoria_clinica       ENUM('avc','amputacao_ms','amputacao_mi',
                                 'lesao_medular','lesao_nervosa_periferica',
                                 'paralisia_cerebral','outro') NULL,
    membro_afetado          ENUM('mao_esquerda','mao_direita','ambas',
                                 'perna_esquerda','perna_direita','outro') NULL,
    data_inicio_tratamento  DATE NULL,
    data_alta               DATE NULL COMMENT 'Preenchida quando médico regista alta',
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id)     REFERENCES profissionais(id) ON DELETE SET NULL,
    FOREIGN KEY (tecnico_id)    REFERENCES profissionais(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- JOGOS DE REABILITAÇÃO
-- ============================================================
CREATE TABLE IF NOT EXISTS jogos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL UNIQUE,
    nivel       ENUM('minimo','medio','maximo') NOT NULL,
    descricao   TEXT NULL,
    forca_ref_n FLOAT NULL COMMENT 'Força de referência em Newtons para este nível',
    ativo       BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- ============================================================
-- DISPOSITIVOS EMG / FSR
-- ============================================================
CREATE TABLE IF NOT EXISTS dispositivos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(20)         NOT NULL UNIQUE,
    tipo            VARCHAR(80)         NOT NULL,
    firmware_versao VARCHAR(20)         NULL,
    ultimo_sync     DATETIME            NULL,
    ativo           BOOLEAN             NOT NULL DEFAULT TRUE,
    estado          ENUM('disponivel','emprestado','manutencao','avariado','abatido')
                    NOT NULL DEFAULT 'disponivel',
    token_api       VARCHAR(64)         UNIQUE NULL
                    COMMENT 'Token de autenticação do ESP32 — gerado no registo',
    INDEX idx_codigo    (codigo),
    INDEX idx_token_api (token_api)
) ENGINE=InnoDB;

-- ============================================================
-- EMPRÉSTIMOS DE DISPOSITIVOS
-- ============================================================
CREATE TABLE IF NOT EXISTS emprestimos_dispositivos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id          INT UNSIGNED NOT NULL,
    utente_id               INT UNSIGNED NOT NULL,
    tecnico_id              INT UNSIGNED NULL,
    data_entrega            DATETIME NOT NULL,
    data_prevista_devolucao DATE NULL,
    data_devolucao          DATETIME NULL COMMENT 'NULL enquanto o dispositivo está com o utente',
    estado_entrega          ENUM('bom','danificado') NOT NULL DEFAULT 'bom',
    estado_devolucao        ENUM('bom','danificado','perdido') NULL,
    notas                   TEXT NULL,
    criado_em               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id) ON DELETE RESTRICT,
    FOREIGN KEY (utente_id)      REFERENCES utentes(id)      ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id)     REFERENCES profissionais(id) ON DELETE SET NULL,
    INDEX idx_dispositivo (dispositivo_id),
    INDEX idx_utente      (utente_id)
) ENGINE=InnoDB;

-- ============================================================
-- SESSÕES DE TREINO
-- ============================================================
CREATE TABLE IF NOT EXISTS sessoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utente_id       INT UNSIGNED        NOT NULL,
    tecnico_id      INT UNSIGNED        NOT NULL,
    dispositivo_id  INT UNSIGNED        NULL,
    jogo_id         INT UNSIGNED        NULL
                    COMMENT 'FK para jogos. NULL se categoria != jogo',
    data_hora       DATETIME            NOT NULL,
    duracao_min     SMALLINT UNSIGNED   NULL,
    categoria       ENUM('calibracao','treino','jogo','avaliacao_funcional')
                    NOT NULL DEFAULT 'jogo',
    objetivo_sessao TEXT                NULL,
    modalidade      ENUM('presencial','remota') NOT NULL DEFAULT 'presencial',
    link_videochamada VARCHAR(500)      NULL,
    estado          ENUM('agendada','em_curso','concluida','cancelada') NOT NULL DEFAULT 'agendada',
    estado_sync     ENUM('local','sincronizado') NOT NULL DEFAULT 'local'
                    COMMENT 'local = guardado no ESP32; sincronizado = no servidor',
    data_sync       DATETIME            NULL
                    COMMENT 'Timestamp de quando o ESP32 sincronizou com o servidor',
    notas           TEXT                NULL,
    criada_em       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id)       REFERENCES utentes(id)      ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id)      REFERENCES profissionais(id) ON DELETE RESTRICT,
    FOREIGN KEY (dispositivo_id)  REFERENCES dispositivos(id)  ON DELETE SET NULL,
    FOREIGN KEY (jogo_id)         REFERENCES jogos(id)          ON DELETE SET NULL,
    INDEX idx_utente_data  (utente_id,  data_hora),
    INDEX idx_tecnico_data (tecnico_id, data_hora)
) ENGINE=InnoDB;

-- ============================================================
-- MÉTRICAS CALCULADAS (por sessão — métricas de jogo, não EMG)
-- ============================================================
CREATE TABLE IF NOT EXISTS metricas_sessao (
    sessao_id         INT UNSIGNED        NOT NULL,
    percentagem_final FLOAT               NULL
                      COMMENT 'Percentagem final atingida no jogo (0-100)',
    score_jogo        INT                 NULL,
    passou_nivel      BOOLEAN             NOT NULL DEFAULT FALSE,
    n_tentativas      SMALLINT UNSIGNED   NOT NULL DEFAULT 1
                      COMMENT 'Número de rondas jogadas na sessão',
    tendencia         ENUM('melhoria','estavel','regressao') NULL
                      COMMENT 'Calculado automaticamente vs última sessão com o mesmo jogo_id',
    calculado_em      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (sessao_id),
    FOREIGN KEY (sessao_id) REFERENCES sessoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PROGRAMAS DE TRATAMENTO (antes: prescricoes)
-- ============================================================
CREATE TABLE IF NOT EXISTS programas_tratamento (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utente_id              INT UNSIGNED        NOT NULL,
    medico_id              INT UNSIGNED        NOT NULL,
    data_prescricao        DATE                NOT NULL,
    data_validade          DATE                NULL,
    num_sessoes_prescritas INT UNSIGNED        NULL
                           COMMENT 'Número de sessões que o médico prescreveu',
    objetivos_clinicos     TEXT                NULL
                           COMMENT 'O que se pretende atingir com o tratamento',
    membro_afetado         ENUM('mao_esquerda','mao_direita','ambas',
                                'perna_esquerda','perna_direita','outro') NULL,
    observacoes            TEXT                NULL,
    ativa                  BOOLEAN             NOT NULL DEFAULT TRUE,
    criada_em              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utentes(id)       ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES profissionais(id) ON DELETE RESTRICT,
    INDEX idx_utente (utente_id),
    INDEX idx_medico (medico_id)
) ENGINE=InnoDB;

-- ============================================================
-- CONSULTAS
-- ============================================================
CREATE TABLE IF NOT EXISTS consultas (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utente_id         INT UNSIGNED        NOT NULL,
    medico_id         INT UNSIGNED        NOT NULL,
    data_hora         DATETIME            NOT NULL,
    tipo              ENUM('inicial','rotina','alta','urgente') NOT NULL DEFAULT 'rotina',
    motivo            VARCHAR(255)        NULL,
    notas             TEXT                NULL,
    evolucao          ENUM('melhorou','estabilizou','piorou','em_avaliacao') NULL,
    modalidade        ENUM('presencial','video') NOT NULL DEFAULT 'presencial',
    link_videochamada VARCHAR(500)        NULL
                      COMMENT 'URL Google Meet / Jitsi / Teams — preenchido pelo médico',
    estado            ENUM('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
    FOREIGN KEY (utente_id) REFERENCES utentes(id)       ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES profissionais(id) ON DELETE RESTRICT,
    INDEX idx_utente (utente_id),
    INDEX idx_medico (medico_id)
) ENGINE=InnoDB;

-- ============================================================
-- PRESCRIÇÕES DE MEDICAÇÃO
-- ============================================================
CREATE TABLE IF NOT EXISTS prescricoes_medicacao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consulta_id     INT UNSIGNED NOT NULL COMMENT 'Consulta onde foi emitida a prescrição',
    medicamento     VARCHAR(150) NOT NULL,
    dosagem         VARCHAR(80)  NOT NULL  COMMENT 'ex: 500mg',
    posologia       TEXT         NOT NULL  COMMENT 'ex: 1 comprimido de 8 em 8 horas às refeições',
    data_inicio     DATE         NOT NULL,
    data_fim        DATE         NULL      COMMENT 'NULL = tratamento contínuo',
    num_renovacoes  INT UNSIGNED NOT NULL DEFAULT 0,
    ativa           BOOLEAN      NOT NULL DEFAULT TRUE,
    observacoes     TEXT NULL,
    criada_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE RESTRICT,
    INDEX idx_consulta (consulta_id)
) ENGINE=InnoDB;

-- ============================================================
-- PEDIDOS DE EXAME
-- ============================================================
CREATE TABLE IF NOT EXISTS pedidos_exame (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consulta_id      INT UNSIGNED NOT NULL,
    tipo_exame       VARCHAR(100) NOT NULL   COMMENT 'ex: RMN Crânio-Encefálica, EMG do coto',
    categoria        ENUM('imagiologia','laboratorial','funcional','neurologico','outro')
                     NOT NULL DEFAULT 'outro',
    urgencia         ENUM('rotina','urgente') NOT NULL DEFAULT 'rotina',
    estado           ENUM('pendente','realizado','cancelado') NOT NULL DEFAULT 'pendente',
    data_pedido      DATE NOT NULL,
    data_realizacao  DATE NULL,
    resultado        TEXT NULL COMMENT 'Preenchido após realização',
    observacoes      TEXT NULL,
    criada_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE RESTRICT,
    INDEX idx_consulta (consulta_id),
    INDEX idx_estado   (estado)
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
    tipo            ENUM('geral','sumario_clinico','alerta_sistema') NOT NULL DEFAULT 'geral',
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
    numero          VARCHAR(20)         NOT NULL UNIQUE,
    utente_id       INT UNSIGNED        NOT NULL,
    sessao_id       INT UNSIGNED        NULL,
    valor_eur       DECIMAL(8,2)        NOT NULL,
    paga            BOOLEAN             NOT NULL DEFAULT FALSE,
    data_emissao    DATE                NOT NULL,
    data_vencimento DATE                NULL,
    notas           TEXT                NULL,
    CONSTRAINT chk_valor_positivo CHECK (valor_eur > 0),
    CONSTRAINT chk_vencimento    CHECK (data_vencimento IS NULL OR data_vencimento >= data_emissao),
    FOREIGN KEY (utente_id) REFERENCES utentes(id)  ON DELETE RESTRICT,
    FOREIGN KEY (sessao_id) REFERENCES sessoes(id)  ON DELETE SET NULL,
    INDEX idx_utente      (utente_id),
    INDEX idx_data_emissao (data_emissao)
) ENGINE=InnoDB;

-- ============================================================
-- PREFERÊNCIAS DE UTILIZADOR
-- ============================================================
CREATE TABLE IF NOT EXISTS preferencias_utilizador (
    utilizador_id       INT UNSIGNED PRIMARY KEY,
    notif_email         BOOLEAN NOT NULL DEFAULT TRUE,
    notif_inicio_sessao BOOLEAN NOT NULL DEFAULT TRUE,
    idioma              VARCHAR(5) NOT NULL DEFAULT 'pt',
    atualizado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- RESET DE PASSWORD (self-service — apenas utentes)
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT UNSIGNED NOT NULL COMMENT 'Sempre perfil utente',
    token         VARCHAR(64)  NOT NULL UNIQUE COMMENT 'Gerado com random_bytes(32) em hex',
    expira_em     DATETIME     NOT NULL COMMENT 'NOW() + INTERVAL 1 HOUR',
    usado         BOOLEAN      NOT NULL DEFAULT FALSE,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    INDEX idx_token      (token),
    INDEX idx_utilizador (utilizador_id)
) ENGINE=InnoDB;

-- ============================================================
-- LOGS DE ACESSO (segurança / auditoria)
-- ============================================================
CREATE TABLE IF NOT EXISTS logs_acesso (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id   INT UNSIGNED        NULL,
    acao            VARCHAR(100)        NOT NULL,
    ip              VARCHAR(45)         NOT NULL,
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
    chave       VARCHAR(80)  NOT NULL PRIMARY KEY,
    valor       TEXT         NOT NULL,
    atualizado  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Administrador
-- Password: Admin123!
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

-- Profissionais
INSERT IGNORE INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao, contacto)
SELECT id, 'OM-12345', 'Medicina Física e Reabilitação', 'RehabLink', '912000001'
FROM utilizadores WHERE email = 'medico@rehablink.pt';

INSERT IGNORE INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao, contacto)
SELECT id, 'OF-67890', 'Fisioterapia Mioeléctrica', 'RehabLink', '912000002'
FROM utilizadores WHERE email = 'tecnico@rehablink.pt';

-- Utente
INSERT IGNORE INTO utentes (utilizador_id, data_nascimento, sexo, nif, localidade,
                             diagnostico, cobertura_saude, fase_tratamento, categoria_clinica)
SELECT id, '1985-03-15', 'M', '123456789', 'Lisboa',
       'Amputação transtibial — reabilitação protetética', 'SNS', 'ativo', 'amputacao_ms'
FROM utilizadores WHERE email = 'utente@rehablink.pt';

-- Associar médico e técnico ao utente de demonstração
UPDATE utentes u
JOIN utilizadores uu ON u.utilizador_id = uu.id
SET
    u.medico_id  = (SELECT p.id FROM profissionais p JOIN utilizadores um ON p.utilizador_id = um.id WHERE um.email = 'medico@rehablink.pt' LIMIT 1),
    u.tecnico_id = (SELECT p.id FROM profissionais p JOIN utilizadores ut ON p.utilizador_id = ut.id WHERE ut.email = 'tecnico@rehablink.pt' LIMIT 1)
WHERE uu.email = 'utente@rehablink.pt';

-- Dispositivos de demonstração
INSERT IGNORE INTO dispositivos (codigo, tipo, firmware_versao, estado, ativo) VALUES
    ('FSR-0001', 'Sensor de Força FSR406 — Membro Superior', 'v2.1.4', 'disponivel', TRUE),
    ('FSR-0002', 'Sensor de Força FSR406 — Membro Inferior', 'v1.8.0', 'disponivel', TRUE);

-- 4 Jogos de reabilitação
INSERT IGNORE INTO jogos (nome, nivel, descricao) VALUES
    ('catch_game',         'minimo', 'Apanhar objetos em queda — controlo on/off'),
    ('claw_game',          'minimo', 'Garra arcade com dois thresholds de força'),
    ('flappy_trainer',     'medio',  'Controlo proporcional de altitude por força'),
    ('prosthesis_trainer', 'maximo', 'Simulação de tarefas reais de prótese mioelétrica');

-- Preferências padrão para todos os utilizadores
INSERT IGNORE INTO preferencias_utilizador (utilizador_id)
SELECT id FROM utilizadores;

-- Conteúdo backoffice padrão
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
    ('quem_somos_titulo', 'Tecnologia ao serviço da reabilitação'),
    ('quem_somos_texto',  'A RehabLink é uma plataforma inovadora de telereabilitação que une dispositivos de força (FSR406) a software intuitivo para profissionais de saúde e utentes de prótese mioelétrica.'),
    ('missao_titulo',     'A Nossa Missão'),
    ('missao_texto',      'Capacitar profissionais de saúde com dados em tempo real para personalizar e otimizar a reabilitação de cada paciente de prótese mioelétrica de membro superior.');
