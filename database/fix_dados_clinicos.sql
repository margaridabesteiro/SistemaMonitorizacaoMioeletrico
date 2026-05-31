-- =============================================================
-- FIX: Corrige encoding de todos os dados clínicos
-- Executar com: mysql.exe --default-character-set=utf8mb4
-- =============================================================

USE sistema_mioeletrico;
SET FOREIGN_KEY_CHECKS = 0;

-- Limpar dados clínicos corrompidos
DELETE FROM mensagens;
DELETE FROM prescricoes;
DELETE FROM consultas;
DELETE FROM faturas;
DELETE FROM sessoes;

-- =============================================================
-- 1. Diagnósticos e observações dos utentes
-- =============================================================
UPDATE utentes SET
  diagnostico  = 'Reabilitação pós-AVC isquémico. Hemiplegia esquerda com défice motor nos membros superiores e inferiores. Monitorização EMG de superfície ativa.',
  observacoes  = 'Sessões preferencialmente de manhã. Boa adesão ao protocolo. Familiar presente nas consultas.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'rui.andrade@email.pt');

UPDATE utentes SET
  diagnostico  = 'Amputação transradial direita (acidente de trabalho, 2024). Candidata a prótese mioeléctrica. Sinal EMG dos músculos residuais avaliado como adequado.',
  observacoes  = 'Muito motivada. Empregada numa empresa de design — recuperar função da mão é prioritário. Seguro de saúde ativo (Multicare).'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'catarina.lemos@email.pt');

UPDATE utentes SET
  diagnostico  = 'Lesão medular incompleta C6-C7 (ASIA B). Reabilitação neuromotora dos membros superiores. Protocolo EMG de superfície — deltóide e bíceps.',
  observacoes  = 'Cadeirante. Necessita de acompanhante nas sessões. Evolução lenta mas consistente.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'goncalo.figueiredo@email.pt');

UPDATE utentes SET
  diagnostico  = 'Síndrome do túnel cárpico bilateral grau moderado-severo. Neuropatia periférica por compressão. Pós-cirurgia de descompressão do nervo mediano (março 2026).',
  observacoes  = 'Fisioterapeuta de profissão. Necessita de retorno rápido ao trabalho. Sessões ao fim do dia (18h-19h).'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'beatriz.pinheiro@email.pt');

UPDATE utentes SET
  diagnostico  = 'Distrofia muscular de Duchenne — fase de manutenção. Protocolo de estimulação mioeléctrica para retardar atrofia. Monitorização trimestral de força.',
  observacoes  = 'Acompanhado pelos pais em todas as sessões. Usa cadeira de rodas motorizada. Muito cooperativo e bem-humorado.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'afonso.carvalho@email.pt');

UPDATE utentes SET
  diagnostico  = 'Paralisia cerebral espástica unilateral (membro superior direito). Reabilitação funcional com recurso a biofeedback EMG. Objetivo: melhorar preensão.',
  observacoes  = 'Professora primária. Muito empenhada. Realiza exercícios diários em casa com dispositivo portátil.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'leonor.bastos@email.pt');

UPDATE utentes SET
  diagnostico  = 'Lesão nervosa periférica pós-traumática no nervo radial esquerdo (queda de mota, 2025). Recuperação de força e coordenação. EMG intra-muscular realizado.',
  observacoes  = 'Mecânico. Ansioso por retomar trabalho. Boa força residual no bíceps. Protocolo intensivo autorizado pelo médico.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'tiago.monteiro@email.pt');

UPDATE utentes SET
  diagnostico  = 'Pós-AVC isquémico (janeiro 2026). Hemiplegia direita moderada. Afasia motora em remissão. Reabilitação intensiva membro superior direito.',
  observacoes  = 'Reformada. Vive com o marido que a acompanha nas sessões. Muito determinada. Défice de atenção — sessões curtas e frequentes mais eficazes.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'mariana.freitas@email.pt');

UPDATE utentes SET
  diagnostico  = 'Amputação transumeral esquerda (tumor ósseo, 2023). Em avaliação para prótese mioeléctrica de ombro. EMG dos músculos peitorais e dorsais em curso.',
  observacoes  = 'Engenheiro informático, trabalha remotamente. Dextro. Muito tecnológico — interage bem com os sistemas de biofeedback.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'rodrigo.azevedo@email.pt');

UPDATE utentes SET
  diagnostico  = 'Esclerose múltipla recidivante-remitente. Surto de reabilitação ativo — fadiga muscular e espasticidade moderada nos membros superiores. Protocolo EMG de monitorização.',
  observacoes  = 'Advogada. Muito organizada. Leva diário de sintomas para cada consulta. Medicada com interferão beta.'
WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'sofia.ribeiro@email.pt');

-- =============================================================
-- 2. Sessões
-- =============================================================
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 11, '2026-05-06 09:00:00', 45, 'EMG Superfície', 'concluida',
  'Baseline EMG registado. RMS bíceps: 38 µV. Boa cooperação do paciente.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 11, '2026-05-14 09:00:00', 45, 'EMG Superfície', 'concluida',
  'RMS: 44 µV (+16%). Evolução positiva. Exercícios de extensão do cotovelo.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 11, '2026-06-04 09:00:00', 45, 'EMG Superfície', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 12, '2026-05-07 14:00:00', 60, 'Treino Mioeléctrico', 'concluida',
  'Primeiro treino com prótese de teste. Precisão: 61%. Progresso inicial prometedor.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 12, '2026-05-19 14:00:00', 60, 'Treino Mioeléctrico', 'concluida',
  'Precisão: 74% (+13%). Controlo de abertura/fecho da mão muito melhorado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 12, '2026-06-09 14:00:00', 60, 'Treino Mioeléctrico', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 13, '2026-05-08 10:30:00', 50, 'Reabilitação Neuromotora', 'concluida',
  'Avaliação EMG deltóide e bíceps. Sinal fraco mas detetável. Protocolo iniciado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 13, '2026-05-22 10:30:00', 50, 'Reabilitação Neuromotora', 'concluida',
  'Melhoria de 8% no sinal RMS. Progressão lenta mas consistente com lesão C6-C7.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 13, '2026-06-05 10:30:00', 50, 'Reabilitação Neuromotora', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 14, '2026-05-09 18:00:00', 40, 'EMG Superfície', 'concluida',
  'Pós-cirurgia mês 2. Sinal nervoso em recuperação. RMS flexores do punho: 29 µV.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 14, '2026-05-23 18:00:00', 40, 'EMG Superfície', 'concluida',
  'RMS: 41 µV (+41%). Excelente recuperação. Dor residual reduzida.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 14, '2026-06-06 18:00:00', 40, 'EMG Superfície', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 15, '2026-05-05 11:00:00', 45, 'Estimulação Mioeléctrica', 'concluida',
  'Sessão de manutenção muscular. Estabilidade dos valores EMG face ao mês anterior.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 15, '2026-05-19 11:00:00', 45, 'Estimulação Mioeléctrica', 'concluida',
  'Valores mantidos. Sem sinais de progressão da atrofia. Protocolo cumprido.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 15, '2026-06-02 11:00:00', 45, 'Estimulação Mioeléctrica', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 16, '2026-05-06 15:30:00', 45, 'Biofeedback EMG', 'concluida',
  'Exercícios de preensão com biofeedback. Ativação do extensor digital melhorada.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 16, '2026-05-20 15:30:00', 45, 'Biofeedback EMG', 'concluida',
  'Precisão de preensão: 58%. Redução da espasticidade notada pela própria paciente.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 16, '2026-06-03 15:30:00', 45, 'Biofeedback EMG', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 17, '2026-05-07 09:30:00', 50, 'Reabilitação Nervosa', 'concluida',
  'Avaliação pós-lesão radial. Início de protocolo de estimulação para reinervação.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 17, '2026-05-21 09:30:00', 50, 'Reabilitação Nervosa', 'concluida',
  'Sinal EMG extensor radial longo detetado. Sinal de reinervação incipiente confirmado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 17, '2026-06-04 09:30:00', 50, 'Reabilitação Nervosa', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 18, '2026-05-08 10:00:00', 40, 'EMG Superfície', 'concluida',
  'Mês 4 pós-AVC. RMS deltóide direito: 22 µV. Força funcional ainda reduzida.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 18, '2026-05-22 10:00:00', 40, 'EMG Superfície', 'concluida',
  'RMS: 31 µV (+41%). Melhoria notável. Paciente consegue elevar o braço a 45 graus.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 18, '2026-06-05 10:00:00', 40, 'EMG Superfície', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 19, '2026-05-09 14:30:00', 60, 'Avaliação Prótese Ombro', 'concluida',
  'EMG peitoral maior e grande dorsal. Sinais de controlo independente confirmados.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 19, '2026-05-23 14:30:00', 60, 'Avaliação Prótese Ombro', 'concluida',
  'Teste de controlo de prótese experimental. 3 graus de liberdade controlados com sucesso.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 19, '2026-06-06 14:30:00', 60, 'Avaliação Prótese Ombro', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 20, '2026-05-07 16:00:00', 45, 'Monitorização EMG', 'concluida',
  'Avaliação durante surto. Fadiga muscular documentada. Espasticidade grau 2 (Ashworth).'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 20, '2026-05-21 16:00:00', 45, 'Monitorização EMG', 'concluida',
  'Melhoria na espasticidade (grau 1). Surto em remissão. RMS estabilizado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 20, '2026-06-04 16:00:00', 45, 'Monitorização EMG', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

-- =============================================================
-- 3. Faturas
-- =============================================================
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0101', ut.id, 45.00, 1, '2026-05-06', '2026-05-20', 'Sessão EMG Superfície — 06/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0102', ut.id, 45.00, 1, '2026-05-14', '2026-05-28', 'Sessão EMG Superfície — 14/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0103', ut.id, 45.00, 0, '2026-06-04', '2026-06-18', 'Sessão EMG Superfície — 04/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0104', ut.id, 65.00, 1, '2026-05-07', '2026-05-21', 'Sessão Treino Mioeléctrico — 07/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0105', ut.id, 65.00, 1, '2026-05-19', '2026-06-02', 'Sessão Treino Mioeléctrico — 19/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0106', ut.id, 65.00, 0, '2026-06-09', '2026-06-23', 'Sessão Treino Mioeléctrico — 09/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0107', ut.id, 55.00, 1, '2026-05-08', '2026-05-22', 'Sessão Reabilitação Neuromotora — 08/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0108', ut.id, 55.00, 1, '2026-05-22', '2026-06-05', 'Sessão Reabilitação Neuromotora — 22/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0109', ut.id, 55.00, 0, '2026-06-05', '2026-06-19', 'Sessão Reabilitação Neuromotora — 05/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0110', ut.id, 45.00, 1, '2026-05-09', '2026-05-23', 'Sessão EMG Superfície — 09/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0111', ut.id, 45.00, 1, '2026-05-23', '2026-06-06', 'Sessão EMG Superfície — 23/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0112', ut.id, 45.00, 0, '2026-06-06', '2026-06-20', 'Sessão EMG Superfície — 06/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0113', ut.id, 50.00, 1, '2026-05-05', '2026-05-19', 'Sessão Estimulação Mioeléctrica — 05/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0114', ut.id, 50.00, 1, '2026-05-19', '2026-06-02', 'Sessão Estimulação Mioeléctrica — 19/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0115', ut.id, 50.00, 0, '2026-06-02', '2026-06-16', 'Sessão Estimulação Mioeléctrica — 02/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0116', ut.id, 45.00, 1, '2026-05-06', '2026-05-20', 'Sessão Biofeedback EMG — 06/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0117', ut.id, 45.00, 1, '2026-05-20', '2026-06-03', 'Sessão Biofeedback EMG — 20/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0118', ut.id, 45.00, 0, '2026-06-03', '2026-06-17', 'Sessão Biofeedback EMG — 03/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0119', ut.id, 55.00, 1, '2026-05-07', '2026-05-21', 'Sessão Reabilitação Nervosa — 07/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0120', ut.id, 55.00, 1, '2026-05-21', '2026-06-04', 'Sessão Reabilitação Nervosa — 21/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0121', ut.id, 55.00, 0, '2026-06-04', '2026-06-18', 'Sessão Reabilitação Nervosa — 04/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0122', ut.id, 45.00, 1, '2026-05-08', '2026-05-22', 'Sessão EMG Superfície — 08/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0123', ut.id, 45.00, 1, '2026-05-22', '2026-06-05', 'Sessão EMG Superfície — 22/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0124', ut.id, 45.00, 0, '2026-06-05', '2026-06-19', 'Sessão EMG Superfície — 05/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0125', ut.id, 75.00, 1, '2026-05-09', '2026-05-23', 'Sessão Avaliação Prótese Ombro — 09/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0126', ut.id, 75.00, 1, '2026-05-23', '2026-06-06', 'Sessão Avaliação Prótese Ombro — 23/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0127', ut.id, 75.00, 0, '2026-06-06', '2026-06-20', 'Sessão Avaliação Prótese Ombro — 06/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0128', ut.id, 45.00, 1, '2026-05-07', '2026-05-21', 'Sessão Monitorização EMG — 07/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0129', ut.id, 45.00, 1, '2026-05-21', '2026-06-04', 'Sessão Monitorização EMG — 21/05/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas) SELECT 'FAT-2026-0130', ut.id, 45.00, 0, '2026-06-04', '2026-06-18', 'Sessão Monitorização EMG — 04/06/2026' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

-- =============================================================
-- 4. Consultas
-- =============================================================
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 6, '2026-05-13 10:00:00', 'Avaliação inicial e plano de reabilitação', 'EMG baseline avaliado. Protocolo de 12 sessões EMG iniciado. Bom prognóstico.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 6, '2026-06-17 10:00:00', 'Revisão do protocolo de reabilitação', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 7, '2026-05-14 11:00:00', 'Avaliação para prótese mioeléctrica', 'Candidata confirmada. Encaminhada para protésico. Protocolo de treino iniciado.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 7, '2026-06-18 11:00:00', 'Revisão pós-adaptação prótese', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 8, '2026-05-15 09:30:00', 'Consulta de seguimento lesão medular', 'Progresso lento mas positivo. Manter protocolo atual por mais 8 semanas.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 8, '2026-07-10 09:30:00', 'Reavaliação ASIA e protocolo', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 9, '2026-05-16 17:00:00', 'Controlo pós-cirurgia túnel cárpico', 'Recuperação acima do esperado. RMS em crescimento. Alta prevista para agosto 2026.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 9, '2026-07-16 17:00:00', 'Alta clínica (prevista)', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 10, '2026-05-12 11:30:00', 'Consulta trimestral de manutenção', 'Valores EMG estáveis. Sem progressão da atrofia. Protocolo mantido.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 10, '2026-08-12 11:30:00', 'Consulta trimestral de manutenção', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 6, '2026-05-13 15:00:00', 'Consulta de biofeedback EMG — avaliação inicial', 'Espasticidade grau 2. Protocolo biofeedback iniciado. Boa tolerância ao treino.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 6, '2026-07-01 15:00:00', 'Revisão biofeedback', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 7, '2026-05-14 10:00:00', 'Avaliação lesão nervo radial', 'Sinal de reinervação incipiente detetado no EMG. Protocolo intensivo autorizado.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 7, '2026-07-09 10:00:00', 'Avaliação da reinervação', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 8, '2026-05-15 10:30:00', 'Consulta mensal pós-AVC', 'Evolução excelente para mês 4. Elevar braço a 45 graus confirmado. Aumentar intensidade.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 8, '2026-06-15 10:30:00', 'Consulta mensal pós-AVC', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 9, '2026-05-16 14:00:00', 'Avaliação para prótese transumeral', 'Sinais de controlo independentes confirmados. Encaminhado para prótese de ombro.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 9, '2026-07-16 14:00:00', 'Revisão pós-adaptação prótese ombro', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 10, '2026-05-12 16:30:00', 'Monitorização surto EM', 'Surto em remissão. Espasticidade grau 1. Continuar protocolo de monitorização.', 'realizada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado) SELECT ut.id, 10, '2026-08-12 16:30:00', 'Monitorização trimestral EM', NULL, 'agendada' FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

-- =============================================================
-- 5. Prescrições
-- =============================================================
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 6, '2026-05-13', '2026-08-13', 'SNS', 'Alta', 'EMG de superfície — 12 sessões. Avaliação muscular bíceps e tríceps braquial.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 6, '2026-05-13', '2026-11-13', 'SNS', 'Media', 'Fisioterapia neurológica — 2x/semana. Foco na recuperação funcional do membro superior esquerdo.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 7, '2026-05-14', '2026-09-14', 'Particular', 'Urgente', 'Avaliação muscular para candidatura a prótese mioeléctrica. EMG de superfície e intra-muscular.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 8, '2026-05-15', '2026-08-15', 'SNS', 'Media', 'Reabilitação neuromotora — 2x/semana. Protocolo EMG deltóide e bíceps.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 9, '2026-05-16', '2026-07-16', 'SNS', 'Alta', 'EMG de superfície pós-cirúrgico — 8 sessões. Flexores e extensores do punho.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 10, '2026-05-12', '2026-11-12', 'SNS', 'Baixa', 'Estimulação mioeléctrica de manutenção — 1x/quinzena. Monitorização trimestral.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 6, '2026-05-13', '2026-09-13', 'SNS', 'Media', 'Biofeedback EMG — 10 sessões. Redução da espasticidade e melhoria da preensão.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 7, '2026-05-14', '2026-10-14', 'SNS', 'Alta', 'Reabilitação nervosa periférica — protocolo intensivo. 3x/semana durante 3 meses.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 8, '2026-05-15', '2026-09-15', 'SNS', 'Alta', 'EMG de superfície — reabilitação pós-AVC. 3x/semana. Deltóide e bíceps direito.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 9, '2026-05-16', '2026-09-16', 'Particular', 'Urgente', 'Avaliação EMG para prótese transumeral. Peitoral maior, grande dorsal e deltóide.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa) SELECT ut.id, 10, '2026-05-12', '2026-11-12', 'SNS', 'Media', 'Monitorização EMG — esclerose múltipla. Avaliação mensal da fadiga muscular e espasticidade.', 1 FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

-- =============================================================
-- 6. Mensagens
-- =============================================================
INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em)
SELECT med.id, ut_u.id,
  'Resultados da sua avaliação inicial',
  'Prezado(a), os resultados da sua avaliação EMG inicial são muito encorajadores. O sinal mioeléctrico está acima da média para o seu diagnóstico. Continue com o protocolo de exercícios em casa. Qualquer dúvida, contacte-nos. Cumprimentos, Dr. António Ribeiro',
  0, '2026-05-14 10:00:00'
FROM utilizadores med, utilizadores ut_u
WHERE med.email = 'antonio.ribeiro@rehablink.pt' AND ut_u.email = 'rui.andrade@email.pt';

INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em)
SELECT med.id, ut_u.id,
  'Confirmação de candidatura a prótese mioeléctrica',
  'Prezada Catarina, confirmo que foi aprovada como candidata à prótese mioeléctrica. O protésico irá contactá-la esta semana para agendar a avaliação técnica. Continue com as sessões de treino. Cumprimentos, Dra. Marta Fernandes',
  0, '2026-05-15 14:30:00'
FROM utilizadores med, utilizadores ut_u
WHERE med.email = 'marta.fernandes@rehablink.pt' AND ut_u.email = 'catarina.lemos@email.pt';

INSERT INTO mensagens (remetente_id, destinatario_id, assunto, corpo, lida, enviada_em)
SELECT med.id, ut_u.id,
  'Relatório de progresso — maio 2026',
  'Prezada Sofia, após análise das suas sessões de maio, verificamos uma melhoria na espasticidade (de grau 2 para grau 1) e estabilização do sinal RMS. O surto encontra-se em remissão. Continue com a medicação prescrita e os exercícios diários. Com os melhores cumprimentos, Dr. João Lopes',
  0, '2026-05-26 09:00:00'
FROM utilizadores med, utilizadores ut_u
WHERE med.email = 'joao.lopes@rehablink.pt' AND ut_u.email = 'sofia.ribeiro@email.pt';

SET FOREIGN_KEY_CHECKS = 1;
