-- =============================================================
-- DADOS DE TESTE — Sara Rangel (utilizador_id=7, utente_id=2)
-- Executar no phpMyAdmin: base de dados sistema_mioeletrico
-- =============================================================

USE sistema_mioeletrico;

-- Completar registo de utente
UPDATE utentes
SET data_nascimento = '2004-03-15',
    sexo            = 'F',
    nif             = '258963147',
    morada          = 'Rua de Cedofeita 120, 1ºEsq',
    codigo_postal   = '4050-180',
    localidade      = 'Porto',
    diagnostico     = 'Lesão nervosa periférica membro superior direito. Protocolo de reabilitação mioeléctrica com prótese de treino.',
    observacoes     = 'Boa evolução. Motivada para o tratamento. Prefere sessões de manhã.'
WHERE id = 2;

-- -------------------------------------------------------------
-- Sessões de reabilitação
-- -------------------------------------------------------------
-- Técnico Ana Ferreira (prof_id=2), Pedro Silva (prof_id=4)
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas) VALUES
(2, 2, '2026-05-05 09:00:00', 45, 'EMG Superfície', 'concluida', 'Baseline de sinal EMG registado. RMS médio: 42 µV. Boa cooperação.'),
(2, 2, '2026-05-08 09:00:00', 45, 'EMG Superfície', 'concluida', 'Melhoria de 12% no sinal RMS. Exercícios de flexão/extensão do punho.'),
(2, 4, '2026-05-12 10:30:00', 60, 'Treino Mioeléctrico', 'concluida', 'Primeiro treino com prótese de teste. Controlo inicial prometedor.'),
(2, 4, '2026-05-16 10:30:00', 60, 'Treino Mioeléctrico', 'concluida', 'Precisão de controlo da prótese: 68%. Evolução positiva.'),
(2, 2, '2026-05-22 09:00:00', 45, 'EMG Superfície', 'concluida', 'RMS: 58 µV. Aumento de 38% face à baseline. Excelente progresso.'),
(2, 2, '2026-06-03 09:00:00', 45, 'EMG Superfície', 'agendada', NULL),
(2, 4, '2026-06-10 10:30:00', 60, 'Treino Mioeléctrico', 'agendada', NULL);

-- -------------------------------------------------------------
-- Faturas
-- -------------------------------------------------------------
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) VALUES
('FAT-2026-0031', 2, 45.00, 1, '2026-05-05', '2026-05-19', 'Sessão EMG Superfície — 05/05/2026'),
('FAT-2026-0038', 2, 45.00, 1, '2026-05-08', '2026-05-22', 'Sessão EMG Superfície — 08/05/2026'),
('FAT-2026-0045', 2, 65.00, 1, '2026-05-12', '2026-05-26', 'Sessão Treino Mioeléctrico — 12/05/2026'),
('FAT-2026-0052', 2, 65.00, 1, '2026-05-16', '2026-05-30', 'Sessão Treino Mioeléctrico — 16/05/2026'),
('FAT-2026-0061', 2, 45.00, 0, '2026-05-22', '2026-06-05', 'Sessão EMG Superfície — 22/05/2026'),
('FAT-2026-0075', 2, 65.00, 0, '2026-06-03', '2026-06-17', 'Sessão Treino Mioeléctrico — 03/06/2026');

-- -------------------------------------------------------------
-- Mensagens (do médico José Rangel e técnica Ana Ferreira)
-- -------------------------------------------------------------
INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em) VALUES
(9, 7, 'Resultados da avaliação inicial',
 'Cara Sara, os resultados da sua avaliação EMG inicial são muito encorajadores. O sinal mioeléctrico dos músculos residuais está acima da média para o seu diagnóstico. Continue com o protocolo de exercícios em casa. Qualquer dúvida, contacte-nos. Cumprimentos, Dr. José Rangel',
 1, '2026-05-07 11:30:00'),
(9, 7, 'Próxima consulta agendada',
 'Cara Sara, confirmo a sua consulta de revisão para 05 de junho de 2026. Traga os registos dos exercícios realizados em casa. Estará também a receber um relatório do progresso da Ana Ferreira. Cumprimentos, Dr. José Rangel',
 1, '2026-05-20 14:00:00'),
(9, 7, 'Relatório de progresso — maio 2026',
 'Cara Sara, após análise das suas 5 sessões realizadas em maio, verificamos uma evolução excelente: o seu sinal RMS aumentou 38% face à baseline e a precisão de controlo da prótese subiu para 68%. Está no caminho certo! Continuemos com o protocolo atual nas próximas semanas. Com os melhores cumprimentos, Dr. José Rangel',
 0, '2026-05-26 09:15:00');
