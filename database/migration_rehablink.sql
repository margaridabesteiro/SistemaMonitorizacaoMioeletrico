-- =============================================================
-- MIGRAÇÃO RehabLink — de schema original para schema completo
-- Executar no phpMyAdmin:
--   1. Selecionar base de dados "sistema_mioeletrico"
--   2. Separador SQL → colar este ficheiro → Executar
--
-- Compatível com MariaDB 10.x (XAMPP). Idempotente: pode ser
-- executado mais do que uma vez sem erros.
-- =============================================================

USE sistema_mioeletrico;

-- ============================================================
-- PASSO 1: Remover tabela leituras_emg (dados brutos EMG)
-- A app não guarda leituras brutas — só resultados de jogo.
-- ============================================================
DROP TABLE IF EXISTS leituras_emg;

-- ============================================================
-- PASSO 2: Criar tabela jogos (necessária antes de sessoes FK)
-- ============================================================
CREATE TABLE IF NOT EXISTS jogos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL UNIQUE,
  nivel       ENUM('minimo','medio','maximo') NOT NULL,
  descricao   TEXT NULL,
  forca_ref_n FLOAT NULL COMMENT 'Força de referência em Newtons para este nível',
  ativo       BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

INSERT IGNORE INTO jogos (nome, nivel, descricao) VALUES
  ('catch_game',         'minimo', 'Apanhar objetos em queda — controlo on/off'),
  ('claw_game',          'minimo', 'Garra arcade com dois thresholds de força'),
  ('flappy_trainer',     'medio',  'Controlo proporcional de altitude por força'),
  ('prosthesis_trainer', 'maximo', 'Simulação de tarefas reais de prótese mioelétrica');

-- ============================================================
-- PASSO 3: Alterar tabela utentes — campos clínicos
-- ============================================================
ALTER TABLE utentes
  ADD COLUMN IF NOT EXISTS cobertura_saude        ENUM('SNS','Particular','Seguro')
                            NOT NULL DEFAULT 'SNS'
                            COMMENT 'Gerida pelo admin na admissão — nunca pelo médico',
  ADD COLUMN IF NOT EXISTS fase_tratamento         ENUM('avaliacao','ativo','manutencao','alta')
                            NOT NULL DEFAULT 'avaliacao',
  ADD COLUMN IF NOT EXISTS categoria_clinica       ENUM('avc','amputacao_ms','amputacao_mi',
                                                        'lesao_medular','lesao_nervosa_periferica',
                                                        'paralisia_cerebral','outro') NULL,
  ADD COLUMN IF NOT EXISTS membro_afetado          ENUM('mao_esquerda','mao_direita','ambas',
                                                        'perna_esquerda','perna_direita','outro') NULL,
  ADD COLUMN IF NOT EXISTS data_inicio_tratamento  DATE NULL,
  ADD COLUMN IF NOT EXISTS data_alta               DATE NULL
                            COMMENT 'Preenchida quando médico regista alta';

-- ============================================================
-- PASSO 4: Renomear prescricoes → programas_tratamento
-- ============================================================
SET @tbl_presc = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescricoes');
SET @sql = IF(@tbl_presc > 0,
              'RENAME TABLE prescricoes TO programas_tratamento',
              'SELECT 1 /* prescricoes ja renomeada */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Remover campo tipo (era SNS/Particular/Seguro — agora em utentes.cobertura_saude)
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='programas_tratamento' AND COLUMN_NAME='tipo');
SET @sql = IF(@col > 0,
              'ALTER TABLE programas_tratamento DROP COLUMN tipo',
              'SELECT 1');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Remover campo prioridade (Urgente/Alta/Média/Baixa — sem sentido clínico)
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='programas_tratamento' AND COLUMN_NAME='prioridade');
SET @sql = IF(@col > 0,
              'ALTER TABLE programas_tratamento DROP COLUMN prioridade',
              'SELECT 1');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Adicionar campos clínicos
ALTER TABLE programas_tratamento
  ADD COLUMN IF NOT EXISTS num_sessoes_prescritas  INT UNSIGNED NULL
              COMMENT 'Número de sessões que o médico prescreveu',
  ADD COLUMN IF NOT EXISTS objetivos_clinicos       TEXT NULL
              COMMENT 'O que se pretende atingir com o tratamento',
  ADD COLUMN IF NOT EXISTS membro_afetado           ENUM('mao_esquerda','mao_direita','ambas',
                                                         'perna_esquerda','perna_direita','outro') NULL;

-- ============================================================
-- PASSO 5: Alterar tabela consultas
-- ============================================================
ALTER TABLE consultas
  ADD COLUMN IF NOT EXISTS tipo              ENUM('inicial','rotina','alta','urgente')
                            NOT NULL DEFAULT 'rotina',
  ADD COLUMN IF NOT EXISTS evolucao          ENUM('melhorou','estabilizou','piorou','em_avaliacao') NULL,
  ADD COLUMN IF NOT EXISTS modalidade        ENUM('presencial','video')
                            NOT NULL DEFAULT 'presencial',
  ADD COLUMN IF NOT EXISTS link_videochamada VARCHAR(500) NULL
              COMMENT 'URL Google Meet / Jitsi / Teams — preenchido pelo médico';

-- ============================================================
-- PASSO 6: Alterar tabela sessoes
-- ============================================================

-- Renomear coluna tipo → categoria (se tipo ainda existir)
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sessoes' AND COLUMN_NAME='tipo');
SET @sql = IF(@col > 0,
              "ALTER TABLE sessoes CHANGE COLUMN tipo categoria ENUM('calibracao','treino','jogo','avaliacao_funcional') NOT NULL DEFAULT 'jogo'",
              'SELECT 1 /* tipo ja renomeado para categoria */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

ALTER TABLE sessoes
  ADD COLUMN IF NOT EXISTS jogo_id           INT UNSIGNED NULL
              COMMENT 'FK para jogos. NULL se categoria != jogo',
  ADD COLUMN IF NOT EXISTS objetivo_sessao   TEXT NULL,
  ADD COLUMN IF NOT EXISTS modalidade        ENUM('presencial','remota')
                            NOT NULL DEFAULT 'presencial',
  ADD COLUMN IF NOT EXISTS link_videochamada VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS estado_sync       ENUM('local','sincronizado')
                            NOT NULL DEFAULT 'local'
              COMMENT 'local = guardado no ESP32; sincronizado = no servidor',
  ADD COLUMN IF NOT EXISTS data_sync         DATETIME NULL
              COMMENT 'Timestamp de quando o ESP32 sincronizou com o servidor';

-- FK para jogos (apenas se a coluna e a tabela existirem e a FK não existir)
SET @fk_jogo = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sessoes'
                AND COLUMN_NAME='jogo_id' AND REFERENCED_TABLE_NAME='jogos');
SET @sql = IF(@fk_jogo = 0,
              'ALTER TABLE sessoes ADD CONSTRAINT fk_sessoes_jogo FOREIGN KEY (jogo_id) REFERENCES jogos(id) ON DELETE SET NULL',
              'SELECT 1 /* fk_sessoes_jogo ja existe */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- ============================================================
-- PASSO 7: Alterar tabela dispositivos
-- ============================================================
ALTER TABLE dispositivos
  ADD COLUMN IF NOT EXISTS estado    ENUM('disponivel','emprestado','manutencao','avariado','abatido')
                            NOT NULL DEFAULT 'disponivel',
  ADD COLUMN IF NOT EXISTS token_api VARCHAR(64) NULL
              COMMENT 'Token de autenticação do ESP32 no servidor — gerado no registo';

-- Adicionar índice UNIQUE em token_api se não existir
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dispositivos' AND INDEX_NAME='idx_token_api');
SET @sql = IF(@idx = 0,
              'ALTER TABLE dispositivos ADD UNIQUE INDEX idx_token_api (token_api)',
              'SELECT 1 /* idx_token_api ja existe */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Remover FK de utente_id para poder remover a coluna
SET @fk_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dispositivos'
                AND COLUMN_NAME='utente_id' AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1);
SET @sql = IF(@fk_name IS NOT NULL,
              CONCAT('ALTER TABLE dispositivos DROP FOREIGN KEY ', @fk_name),
              'SELECT 1 /* FK utente_id ja removida */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Remover coluna utente_id
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dispositivos' AND COLUMN_NAME='utente_id');
SET @sql = IF(@col > 0,
              'ALTER TABLE dispositivos DROP COLUMN utente_id',
              'SELECT 1 /* utente_id ja removida */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Remover coluna associado_em
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dispositivos' AND COLUMN_NAME='associado_em');
SET @sql = IF(@col > 0,
              'ALTER TABLE dispositivos DROP COLUMN associado_em',
              'SELECT 1 /* associado_em ja removida */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- ============================================================
-- PASSO 8: Alterar tabela metricas_sessao
-- Substituir métricas EMG por métricas de jogo
-- ============================================================

-- Remover colunas EMG obsoletas
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='metricas_sessao' AND COLUMN_NAME='rms_uv');
SET @sql = IF(@col > 0, 'ALTER TABLE metricas_sessao DROP COLUMN rms_uv', 'SELECT 1');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='metricas_sessao' AND COLUMN_NAME='mav_uv');
SET @sql = IF(@col > 0, 'ALTER TABLE metricas_sessao DROP COLUMN mav_uv', 'SELECT 1');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='metricas_sessao' AND COLUMN_NAME='frequencia_hz');
SET @sql = IF(@col > 0, 'ALTER TABLE metricas_sessao DROP COLUMN frequencia_hz', 'SELECT 1');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Renomear precisao_pct → percentagem_final
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='metricas_sessao' AND COLUMN_NAME='precisao_pct');
SET @sql = IF(@col > 0,
              'ALTER TABLE metricas_sessao CHANGE COLUMN precisao_pct percentagem_final FLOAT NULL COMMENT "Percentagem final atingida no jogo (0-100)"',
              'SELECT 1 /* precisao_pct ja renomeada */');
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Adicionar novas colunas de jogo
ALTER TABLE metricas_sessao
  ADD COLUMN IF NOT EXISTS passou_nivel  BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS n_tentativas  SMALLINT UNSIGNED NOT NULL DEFAULT 1
              COMMENT 'Número de rondas jogadas na sessão',
  ADD COLUMN IF NOT EXISTS tendencia     ENUM('melhoria','estavel','regressao') NULL
              COMMENT 'Calculado automaticamente vs última sessão com o mesmo jogo_id';

-- ============================================================
-- PASSO 9: Alterar tabela mensagens
-- ============================================================
ALTER TABLE mensagens
  ADD COLUMN IF NOT EXISTS tipo ENUM('geral','sumario_clinico','alerta_sistema')
                                NOT NULL DEFAULT 'geral'
              COMMENT 'sumario_clinico = técnico envia resumo ao médico';

-- ============================================================
-- PASSO 10: Criar tabela emprestimos_dispositivos
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
-- PASSO 11: Criar tabela prescricoes_medicacao
-- ============================================================
CREATE TABLE IF NOT EXISTS prescricoes_medicacao (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consulta_id     INT UNSIGNED NOT NULL COMMENT 'Consulta onde foi emitida a prescrição',
  utente_id       INT UNSIGNED NOT NULL,
  medico_id       INT UNSIGNED NOT NULL,
  medicamento     VARCHAR(150) NOT NULL,
  dosagem         VARCHAR(80)  NOT NULL  COMMENT 'ex: 500mg',
  posologia       TEXT         NOT NULL  COMMENT 'ex: 1 comprimido de 8 em 8 horas às refeições',
  data_inicio     DATE         NOT NULL,
  data_fim        DATE         NULL      COMMENT 'NULL = tratamento contínuo sem data definida',
  num_renovacoes  INT UNSIGNED NOT NULL DEFAULT 0,
  ativa           BOOLEAN      NOT NULL DEFAULT TRUE,
  observacoes     TEXT NULL,
  criada_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consulta_id) REFERENCES consultas(id)      ON DELETE RESTRICT,
  FOREIGN KEY (utente_id)   REFERENCES utentes(id)        ON DELETE CASCADE,
  FOREIGN KEY (medico_id)   REFERENCES profissionais(id)  ON DELETE RESTRICT,
  INDEX idx_utente  (utente_id),
  INDEX idx_consulta(consulta_id)
) ENGINE=InnoDB;

-- ============================================================
-- PASSO 12: Criar tabela pedidos_exame
-- ============================================================
CREATE TABLE IF NOT EXISTS pedidos_exame (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consulta_id      INT UNSIGNED NOT NULL,
  utente_id        INT UNSIGNED NOT NULL,
  medico_id        INT UNSIGNED NOT NULL,
  tipo_exame       VARCHAR(100) NOT NULL   COMMENT 'ex: RMN Crânio-Encefálica, EMG do coto',
  categoria        ENUM('imagiologia','laboratorial','funcional','neurologico','outro')
                   NOT NULL DEFAULT 'outro',
  urgencia         ENUM('rotina','urgente') NOT NULL DEFAULT 'rotina',
  estado           ENUM('pendente','realizado','cancelado') NOT NULL DEFAULT 'pendente',
  data_pedido      DATE NOT NULL,
  data_realizacao  DATE NULL,
  resultado        TEXT NULL COMMENT 'Preenchido pelo médico ou técnico após realização',
  observacoes      TEXT NULL,
  criada_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consulta_id) REFERENCES consultas(id)     ON DELETE RESTRICT,
  FOREIGN KEY (utente_id)   REFERENCES utentes(id)       ON DELETE CASCADE,
  FOREIGN KEY (medico_id)   REFERENCES profissionais(id) ON DELETE RESTRICT,
  INDEX idx_utente (utente_id),
  INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================================
-- PASSO 13: Criar tabela preferencias_utilizador
-- ============================================================
CREATE TABLE IF NOT EXISTS preferencias_utilizador (
  utilizador_id       INT UNSIGNED PRIMARY KEY,
  notif_email         BOOLEAN NOT NULL DEFAULT TRUE,
  notif_inicio_sessao BOOLEAN NOT NULL DEFAULT TRUE,
  idioma              ENUM('pt') NOT NULL DEFAULT 'pt',
  atualizado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Criar preferências para utilizadores que ainda não têm registo
INSERT IGNORE INTO preferencias_utilizador (utilizador_id)
SELECT id FROM utilizadores;

-- ============================================================
-- PASSO 14: Criar tabela password_resets
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

-- =============================================================
-- FIM DA MIGRAÇÃO
-- Verificar resultado: SHOW TABLES; e DESCRIBE sessoes;
-- =============================================================
