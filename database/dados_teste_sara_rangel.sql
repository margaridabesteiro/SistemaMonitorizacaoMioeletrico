-- =============================================================
-- DADOS DE TESTE — Utente de demonstração (utente@rehablink.pt)
-- Executar no phpMyAdmin: base de dados sistema_mioeletrico
-- Pré-requisito: equipa_landing.sql já importado
-- =============================================================

USE sistema_mioeletrico;

-- -------------------------------------------------------------
-- Garantir que as contas demo existem
-- (podem ter sido apagadas pelo reset_utilizadores.sql)
-- Password: Medico123! / Tecnico123! / Utente123!
-- Hash: password_hash('password', PASSWORD_DEFAULT) equivalente
-- -------------------------------------------------------------
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES
('Dr. João Silva',  'medico@rehablink.pt',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medico'),
('Ana Ferreira',    'tecnico@rehablink.pt', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico'),
('Carlos Mendes',   'utente@rehablink.pt',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'utente');

INSERT IGNORE INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao)
SELECT id, 'OM-12345', 'Medicina Física e Reabilitação', 'RehabLink'
FROM utilizadores WHERE email = 'medico@rehablink.pt';

INSERT IGNORE INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao)
SELECT id, 'OF-67890', 'Fisioterapia Mioeléctrica', 'RehabLink'
FROM utilizadores WHERE email = 'tecnico@rehablink.pt';

INSERT IGNORE INTO utentes (utilizador_id, data_nascimento, sexo, nif, localidade, diagnostico, fase_tratamento)
SELECT id, '1985-03-15', 'M', '123456789', 'Lisboa',
       'Lesão nervosa periférica membro superior direito. Protocolo de reabilitação mioeléctrica.', 'ativo'
FROM utilizadores WHERE email = 'utente@rehablink.pt';

UPDATE utentes u
JOIN utilizadores uu ON u.utilizador_id = uu.id
SET
    u.medico_id  = (SELECT p.id FROM profissionais p JOIN utilizadores um ON p.utilizador_id = um.id WHERE um.email = 'medico@rehablink.pt' LIMIT 1),
    u.tecnico_id = (SELECT p.id FROM profissionais p JOIN utilizadores ut ON p.utilizador_id = ut.id WHERE ut.email = 'tecnico@rehablink.pt' LIMIT 1)
WHERE uu.email = 'utente@rehablink.pt';

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
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'utente@rehablink.pt');

-- -------------------------------------------------------------
-- Sessões de reabilitação
-- -------------------------------------------------------------
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-05-05 09:00:00', 45, 'avaliacao_funcional', 'concluida',
       'Baseline de sinal EMG registado. RMS médio: 42 µV. Boa cooperação.'
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-05-08 09:00:00', 45, 'avaliacao_funcional', 'concluida',
       'Melhoria de 12% no sinal RMS. Exercícios de flexão/extensão do punho.'
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-05-12 10:30:00', 60, 'treino', 'concluida',
       'Primeiro treino com prótese de teste. Controlo inicial prometedor.'
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-05-16 10:30:00', 60, 'treino', 'concluida',
       'Precisão de controlo da prótese: 68%. Evolução positiva.'
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-05-22 09:00:00', 45, 'avaliacao_funcional', 'concluida',
       'RMS: 58 µV. Aumento de 38% face à baseline. Excelente progresso.'
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-06-03 09:00:00', 45, 'avaliacao_funcional', 'agendada', NULL
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, categoria, estado, notas)
SELECT ut.id, p.id, '2026-06-10 10:30:00', 60, 'treino', 'agendada', NULL
FROM utentes ut
JOIN utilizadores u  ON u.id  = ut.utilizador_id
JOIN profissionais p ON p.utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tecnico@rehablink.pt')
WHERE u.email = 'utente@rehablink.pt';

-- -------------------------------------------------------------
-- Faturas
-- -------------------------------------------------------------
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT 'FAT-2026-0031', ut.id, 45.00, 1, '2026-05-05', '2026-05-19', 'Sessão avaliação funcional — 05/05/2026'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'utente@rehablink.pt';

INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT 'FAT-2026-0038', ut.id, 45.00, 1, '2026-05-08', '2026-05-22', 'Sessão avaliação funcional — 08/05/2026'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'utente@rehablink.pt';

INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT 'FAT-2026-0045', ut.id, 65.00, 1, '2026-05-12', '2026-05-26', 'Sessão treino mioelétrico — 12/05/2026'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'utente@rehablink.pt';

INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT 'FAT-2026-0052', ut.id, 65.00, 1, '2026-05-16', '2026-05-30', 'Sessão treino mioelétrico — 16/05/2026'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'utente@rehablink.pt';

INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT 'FAT-2026-0061', ut.id, 45.00, 0, '2026-05-22', '2026-06-05', 'Sessão avaliação funcional — 22/05/2026'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'utente@rehablink.pt';

INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT 'FAT-2026-0075', ut.id, 65.00, 0, '2026-06-03', '2026-06-17', 'Sessão treino mioelétrico — 03/06/2026'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'utente@rehablink.pt';

-- -------------------------------------------------------------
-- Mensagens (do médico e técnico de demonstração)
-- -------------------------------------------------------------
INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em)
SELECT
    (SELECT id FROM utilizadores WHERE email = 'medico@rehablink.pt'),
    (SELECT id FROM utilizadores WHERE email = 'utente@rehablink.pt'),
    'Resultados da avaliação inicial',
    'Os resultados da sua avaliação EMG inicial são muito encorajadores. O sinal mioeléctrico dos músculos residuais está acima da média para o seu diagnóstico. Continue com o protocolo de exercícios em casa. Qualquer dúvida, contacte-nos.',
    1, '2026-05-07 11:30:00';

INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em)
SELECT
    (SELECT id FROM utilizadores WHERE email = 'medico@rehablink.pt'),
    (SELECT id FROM utilizadores WHERE email = 'utente@rehablink.pt'),
    'Próxima consulta agendada',
    'Confirmo a sua consulta de revisão para 05 de junho de 2026. Traga os registos dos exercícios realizados em casa.',
    1, '2026-05-20 14:00:00';

INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em)
SELECT
    (SELECT id FROM utilizadores WHERE email = 'medico@rehablink.pt'),
    (SELECT id FROM utilizadores WHERE email = 'utente@rehablink.pt'),
    'Relatório de progresso — maio 2026',
    'Após análise das suas sessões realizadas em maio, verificamos uma evolução excelente: o sinal RMS aumentou 38% face à baseline e a precisão de controlo da prótese subiu para 68%. Está no caminho certo!',
    0, '2026-05-26 09:15:00';
