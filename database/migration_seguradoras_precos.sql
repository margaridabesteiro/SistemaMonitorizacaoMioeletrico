-- =============================================================
-- Migração: Sistema de Seguradoras e Tabela de Preços
-- Executar no phpMyAdmin: base de dados sistema_mioeletrico
-- =============================================================

USE sistema_mioeletrico;

-- -------------------------------------------------------------
-- 1. Tabela de seguradoras
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seguradoras (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(100) NOT NULL,
    tipo    ENUM('SNS','Seguro','Particular') NOT NULL DEFAULT 'Seguro',
    ativa   BOOLEAN NOT NULL DEFAULT TRUE,
    notas   TEXT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO seguradoras (id, nome, tipo) VALUES
(1, 'Particular',       'Particular'),
(2, 'SNS',              'SNS'),
(3, 'Multicare',        'Seguro'),
(4, 'AdvanceCare',      'Seguro'),
(5, 'Médis',            'Seguro'),
(6, 'Allianz Saúde',    'Seguro'),
(7, 'Fidelidade Saúde', 'Seguro'),
(8, 'Lusitânia Saúde',  'Seguro');

-- -------------------------------------------------------------
-- 2. Tabela de preços (tipo_servico × seguradora)
--    seguradora_id=1 (Particular) = preço base
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tabela_precos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_servico    VARCHAR(50) NOT NULL,
    seguradora_id   INT UNSIGNED NOT NULL DEFAULT 1,
    preco_eur       DECIMAL(8,2) NOT NULL,
    UNIQUE KEY uk_tipo_seg (tipo_servico, seguradora_id),
    FOREIGN KEY (seguradora_id) REFERENCES seguradoras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Preços base (Particular)
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       1, 60.00),
('treino_mioeletrico',  1, 65.00),
('consulta_medica',     1, 80.00),
('sessao_biofeedback',  1, 55.00),
('avaliacao_funcional', 1, 70.00),
('sessao_jogo',         1, 45.00),
('teleconsulta',        1, 50.00),
('relatorio_clinico',   1, 30.00);

-- SNS (comparticipação — valor da taxa moderadora)
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       2,  8.00),
('treino_mioeletrico',  2,  8.00),
('consulta_medica',     2,  7.50),
('sessao_biofeedback',  2,  8.00),
('avaliacao_funcional', 2, 10.00),
('sessao_jogo',         2,  5.00),
('teleconsulta',        2,  5.00),
('relatorio_clinico',   2,  5.00);

-- Multicare
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       3, 50.00),
('treino_mioeletrico',  3, 55.00),
('consulta_medica',     3, 68.00),
('sessao_biofeedback',  3, 46.00),
('avaliacao_funcional', 3, 59.00),
('sessao_jogo',         3, 38.00),
('teleconsulta',        3, 42.00),
('relatorio_clinico',   3, 25.00);

-- AdvanceCare
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       4, 48.00),
('treino_mioeletrico',  4, 52.00),
('consulta_medica',     4, 64.00),
('sessao_biofeedback',  4, 44.00),
('avaliacao_funcional', 4, 56.00),
('sessao_jogo',         4, 36.00),
('teleconsulta',        4, 40.00),
('relatorio_clinico',   4, 24.00);

-- Médis
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       5, 45.00),
('treino_mioeletrico',  5, 49.00),
('consulta_medica',     5, 60.00),
('sessao_biofeedback',  5, 41.00),
('avaliacao_funcional', 5, 52.00),
('sessao_jogo',         5, 34.00),
('teleconsulta',        5, 38.00),
('relatorio_clinico',   5, 22.00);

-- Allianz Saúde
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       6, 54.00),
('treino_mioeletrico',  6, 58.00),
('consulta_medica',     6, 72.00),
('sessao_biofeedback',  6, 49.00),
('avaliacao_funcional', 6, 63.00),
('sessao_jogo',         6, 40.00),
('teleconsulta',        6, 45.00),
('relatorio_clinico',   6, 27.00);

-- Fidelidade Saúde
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       7, 51.00),
('treino_mioeletrico',  7, 55.00),
('consulta_medica',     7, 68.00),
('sessao_biofeedback',  7, 47.00),
('avaliacao_funcional', 7, 59.00),
('sessao_jogo',         7, 38.00),
('teleconsulta',        7, 42.00),
('relatorio_clinico',   7, 25.00);

-- Lusitânia Saúde
INSERT IGNORE INTO tabela_precos (tipo_servico, seguradora_id, preco_eur) VALUES
('avaliacao_emg',       8, 53.00),
('treino_mioeletrico',  8, 57.00),
('consulta_medica',     8, 70.00),
('sessao_biofeedback',  8, 48.00),
('avaliacao_funcional', 8, 61.00),
('sessao_jogo',         8, 39.00),
('teleconsulta',        8, 43.00),
('relatorio_clinico',   8, 26.00);

-- -------------------------------------------------------------
-- 3. Adicionar seguradora_id à tabela utentes
-- -------------------------------------------------------------
ALTER TABLE utentes
    ADD COLUMN seguradora_id INT UNSIGNED NULL AFTER cobertura_saude,
    ADD CONSTRAINT fk_utentes_seg FOREIGN KEY (seguradora_id) REFERENCES seguradoras(id) ON DELETE SET NULL;

-- Inicializar seguradora_id com base no cobertura_saude existente
UPDATE utentes SET seguradora_id = 2 WHERE cobertura_saude = 'SNS';
UPDATE utentes SET seguradora_id = 1 WHERE cobertura_saude = 'Particular';
-- Seguros ficam NULL até ser especificado manualmente

-- -------------------------------------------------------------
-- 4. Adicionar tipo_servico e seguradora_id à tabela faturas
-- -------------------------------------------------------------
ALTER TABLE faturas
    ADD COLUMN tipo_servico  VARCHAR(50)  NULL AFTER sessao_id,
    ADD COLUMN seguradora_id INT UNSIGNED NULL AFTER tipo_servico,
    ADD CONSTRAINT fk_faturas_seg FOREIGN KEY (seguradora_id) REFERENCES seguradoras(id) ON DELETE SET NULL;
