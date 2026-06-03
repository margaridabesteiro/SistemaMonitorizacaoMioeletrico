-- =============================================================
-- MIGRAÇÃO: Melhorias do modelo relacional
-- Data: 2026-06-02
-- Aplicar sobre uma BD sistema_mioeletrico já existente
--
-- ATENÇÃO: antes de executar, verificar os nomes exatos das FK:
--   SHOW CREATE TABLE metricas_sessao;
--   SHOW CREATE TABLE prescricoes_medicacao;
--   SHOW CREATE TABLE pedidos_exame;
-- Os nomes ibfk_N abaixo correspondem à instalação limpa via
-- sistema_mioeletrico.sql — podem diferir em BDs mais antigas.
-- =============================================================

USE sistema_mioeletrico;

-- ------------------------------------------------------------
-- 1. metricas_sessao
--    sessao_id passa a ser a PK (remove id redundante)
--    Relação 1:1 com sessoes — não justifica PK separado
-- ------------------------------------------------------------
ALTER TABLE metricas_sessao MODIFY COLUMN id INT UNSIGNED NOT NULL;
ALTER TABLE metricas_sessao
    DROP PRIMARY KEY,
    DROP COLUMN id,
    DROP INDEX sessao_id,
    ADD PRIMARY KEY (sessao_id);

-- ------------------------------------------------------------
-- 2. prescricoes_medicacao
--    Remove utente_id e medico_id — redundantes face a consulta_id
--    (consulta_id → utente_id e consulta_id → medico_id)
-- ------------------------------------------------------------
ALTER TABLE prescricoes_medicacao
    DROP FOREIGN KEY prescricoes_medicacao_ibfk_2,
    DROP FOREIGN KEY prescricoes_medicacao_ibfk_3,
    DROP INDEX idx_utente,
    DROP COLUMN utente_id,
    DROP COLUMN medico_id;

-- ------------------------------------------------------------
-- 3. pedidos_exame
--    Mesma razão que prescricoes_medicacao
--    Adiciona idx_consulta para compensar a remoção de idx_utente
-- ------------------------------------------------------------
ALTER TABLE pedidos_exame
    DROP FOREIGN KEY pedidos_exame_ibfk_2,
    DROP FOREIGN KEY pedidos_exame_ibfk_3,
    DROP INDEX idx_utente,
    DROP COLUMN utente_id,
    DROP COLUMN medico_id,
    ADD INDEX idx_consulta (consulta_id);

-- ------------------------------------------------------------
-- 4. faturas
--    CHECK de integridade de domínio + índices para queries comuns
-- ------------------------------------------------------------
ALTER TABLE faturas
    ADD CONSTRAINT chk_valor_positivo CHECK (valor_eur > 0),
    ADD CONSTRAINT chk_vencimento     CHECK (data_vencimento IS NULL OR data_vencimento >= data_emissao),
    ADD INDEX idx_utente       (utente_id),
    ADD INDEX idx_data_emissao (data_emissao);

-- ------------------------------------------------------------
-- 5. consultas
--    Índices explícitos nas FK (InnoDB cria internamente,
--    mas é boa prática declarar para clareza e ferramentas)
-- ------------------------------------------------------------
ALTER TABLE consultas
    ADD INDEX idx_utente (utente_id),
    ADD INDEX idx_medico (medico_id);

-- ------------------------------------------------------------
-- 6. utentes.nif
--    CHAR(9) — comprimento fixo para NIF português (9 dígitos)
--    UNIQUE  — NIF é identificador único em Portugal
-- ------------------------------------------------------------
ALTER TABLE utentes
    MODIFY COLUMN nif CHAR(9) NULL,
    ADD UNIQUE INDEX idx_nif (nif);

-- ------------------------------------------------------------
-- 7. preferencias_utilizador.idioma
--    VARCHAR(5) em vez de ENUM('pt') de valor único
--    Permite adicionar 'en', 'es', etc. sem nova migração
-- ------------------------------------------------------------
ALTER TABLE preferencias_utilizador
    MODIFY COLUMN idioma VARCHAR(5) NOT NULL DEFAULT 'pt';
