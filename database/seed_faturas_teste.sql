-- Faturas de teste para preencher o gráfico "Por Tipo de Serviço"
-- Executar no phpMyAdmin: selecionar sistema_mioeletrico → separador SQL
-- Usa INSERT IGNORE para não falhar se já existirem

USE sistema_mioeletrico;

-- Pegar os primeiros 3 utentes existentes
SET @u1 = (SELECT id FROM utentes ORDER BY id LIMIT 1 OFFSET 0);
SET @u2 = (SELECT id FROM utentes ORDER BY id LIMIT 1 OFFSET 1);
SET @u3 = (SELECT id FROM utentes ORDER BY id LIMIT 1 OFFSET 2);

-- Se só existir 1 utente, usar o mesmo para todos
SET @u2 = COALESCE(@u2, @u1);
SET @u3 = COALESCE(@u3, @u1);

INSERT IGNORE INTO faturas (numero, utente_id, tipo_servico, valor_eur, paga, data_emissao, metodo_pagamento) VALUES
('FT2026/T01', @u1, 'videoconsulta',       35.00, 1, '2026-01-15', 'multibanco'),
('FT2026/T02', @u2, 'consulta_medica',     60.00, 1, '2026-02-10', 'cartão'),
('FT2026/T03', @u3, 'avaliacao_funcional', 70.00, 1, '2026-02-20', 'multibanco'),
('FT2026/T04', @u1, 'sessao_jogo',         40.00, 1, '2026-03-05', 'numerário'),
('FT2026/T05', @u2, 'relatorio_clinico',   50.00, 1, '2026-03-18', 'transferência'),
('FT2026/T06', @u3, 'videoconsulta',       35.00, 1, '2026-04-02', 'cartão'),
('FT2026/T07', @u1, 'consulta_medica',     60.00, 1, '2026-04-14', 'multibanco'),
('FT2026/T08', @u2, 'avaliacao_funcional', 70.00, 1, '2026-05-03', 'seguro'),
('FT2026/T09', @u3, 'sessao_jogo',         40.00, 1, '2026-05-20', 'multibanco'),
('FT2026/T10', @u1, 'relatorio_clinico',   50.00, 1, '2026-06-01', 'cartão'),
('FT2026/T11', @u2, 'videoconsulta',       35.00, 1, '2026-06-08', 'multibanco'),
('FT2026/T12', @u3, 'consulta_medica',     60.00, 1, '2026-06-10', 'numerário');
