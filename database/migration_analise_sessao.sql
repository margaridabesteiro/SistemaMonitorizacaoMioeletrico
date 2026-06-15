-- Campos para análise de desempenho preenchida pelo técnico ao concluir sessão
USE sistema_mioeletrico;

ALTER TABLE sessoes
    ADD COLUMN progressao    ENUM('melhoria','estavel','regressao') NULL AFTER notas,
    ADD COLUMN esforco_score TINYINT UNSIGNED NULL COMMENT '1-5' AFTER progressao,
    ADD COLUMN analise_tecnica TEXT NULL AFTER esforco_score;
