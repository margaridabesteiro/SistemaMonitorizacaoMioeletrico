-- =============================================================
-- Migração: Método de pagamento nas faturas
-- Executar no phpMyAdmin: base de dados sistema_mioeletrico
-- =============================================================

USE sistema_mioeletrico;

ALTER TABLE faturas
    ADD COLUMN metodo_pagamento ENUM('multibanco','cartão','seguro','numerário','transferência') NULL AFTER paga,
    ADD COLUMN data_pagamento   DATE NULL AFTER metodo_pagamento;

-- Faturas já marcadas como pagas ficam com data = data_emissao (estimativa)
UPDATE faturas SET data_pagamento = data_emissao WHERE paga = 1;
