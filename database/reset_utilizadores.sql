-- =============================================================
-- RESET DE UTILIZADORES
-- Mantém: médicos e técnicos da landing page (IDs 10-24)
-- Apaga:  todos os outros (admins, médicos/técnicos antigos, utentes)
-- Cria:   5 administradores + 10 utentes com dados completos
-- Password universal: Rehablink2026!
-- Hash:   $2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq
-- =============================================================

USE sistema_mioeletrico;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Limpar dados dependentes dos utilizadores a apagar
--    (IDs a apagar: 1,3,4,5,7,8,9,25,26,27)
-- -------------------------------------------------------------
DELETE FROM leituras_emg   WHERE sessao_id IN (SELECT id FROM sessoes WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27)));
DELETE FROM metricas_sessao WHERE sessao_id IN (SELECT id FROM sessoes WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27)));
DELETE FROM sessoes        WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27));
DELETE FROM faturas        WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27));
DELETE FROM consultas      WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27));
DELETE FROM prescricoes    WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27));
DELETE FROM dispositivos   WHERE utente_id IN (SELECT id FROM utentes WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27));
DELETE FROM mensagens      WHERE remetente_id IN (1,3,4,5,7,8,9,25,26,27) OR destinatario_id IN (1,3,4,5,7,8,9,25,26,27);
DELETE FROM logs_acesso    WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27);
DELETE FROM utentes        WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27);
DELETE FROM profissionais  WHERE utilizador_id IN (1,3,4,5,7,8,9,25,26,27);
DELETE FROM utilizadores   WHERE id            IN (1,3,4,5,7,8,9,25,26,27);

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 2. Administradores
-- -------------------------------------------------------------
INSERT INTO utilizadores (nome, email, password_hash, perfil) VALUES
('Luísa Cardoso',    'luisa.cardoso@rehablink.pt',    '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'admin'),
('Miguel Santos',    'miguel.santos@rehablink.pt',    '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'admin'),
('Carolina Ferreira','carolina.ferreira@rehablink.pt','$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'admin'),
('Nuno Costa',       'nuno.costa@rehablink.pt',       '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'admin'),
('Patrícia Sousa',   'patricia.sousa@rehablink.pt',   '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'admin');

-- -------------------------------------------------------------
-- 3. Utentes (utilizadores)
-- -------------------------------------------------------------
INSERT INTO utilizadores (nome, email, password_hash, perfil) VALUES
('Rui Andrade',         'rui.andrade@email.pt',         '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Catarina Lemos',      'catarina.lemos@email.pt',      '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Gonçalo Figueiredo',  'goncalo.figueiredo@email.pt',  '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Beatriz Pinheiro',    'beatriz.pinheiro@email.pt',    '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Afonso Carvalho',     'afonso.carvalho@email.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Leonor Bastos',       'leonor.bastos@email.pt',       '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Tiago Monteiro',      'tiago.monteiro@email.pt',      '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Mariana Freitas',     'mariana.freitas@email.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Rodrigo Azevedo',     'rodrigo.azevedo@email.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Sofia Ribeiro',       'sofia.ribeiro@email.pt',       '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente');

-- -------------------------------------------------------------
-- 4. Registos de utente (perfil clínico completo)
--    medico_id e tecnico_id referenciam profissionais.id:
--      Médicos da landing: António Ribeiro=6, Marta Fernandes=7,
--                          Ricardo Silva=8, Ana Almeida=9, João Lopes=10
--      Técnicos da landing: Ana Silva=11, Bruno Ferreira=12,
--                           Carla Santos=13, Daniel Costa=14,
--                           Eduarda Martins=15, Filipe Gomes=16,
--                           Gabriela Rocha=17, Hugo Pereira=18,
--                           Inês Almeida=19, João Rodrigues=20
-- -------------------------------------------------------------
INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1978-04-12', 'M', '123789456', 'Rua de Santo Ildefonso 45, 3ºDt', '4000-541', 'Porto', 6, 11,
  'Reabilitação pós-AVC isquémico. Hemiplegia esquerda com défice motor nos membros superiores e inferiores. Monitorização EMG de superfície ativa.',
  'Sessões preferencialmente de manhã. Boa adesão ao protocolo. Familiar presente nas consultas.'
FROM utilizadores WHERE email = 'rui.andrade@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1990-08-23', 'F', '234891567', 'Av. dos Aliados 200, 1ºEsq', '4000-067', 'Porto', 7, 12,
  'Amputação transradial direita (acidente de trabalho, 2024). Candidata a prótese mioeléctrica. Sinal EMG dos músculos residuais avaliado como adequado.',
  'Muito motivada. Empregada numa empresa de design — recuperar função da mão é prioritário. Seguro de saúde ativo (Multicare).'
FROM utilizadores WHERE email = 'catarina.lemos@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1985-01-30', 'M', '345902678', 'Rua Miguel Bombarda 12', '4050-379', 'Porto', 8, 13,
  'Lesão medular incompleta C6-C7 (ASIA B). Reabilitação neuromotora dos membros superiores. Protocolo EMG de superfície — deltóide e bíceps.',
  'Cadeirante. Necessita de acompanhante nas sessões. Evolução lenta mas consistente.'
FROM utilizadores WHERE email = 'goncalo.figueiredo@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1995-11-15', 'F', '456013789', 'Rua da Constituição 88, 2ºDt', '4200-193', 'Porto', 9, 14,
  'Síndrome do túnel cárpico bilateral grau moderado-severo. Neuropatia periférica por compressão. Pós-cirurgia de descompressão do nervo mediano (março 2026).',
  'Fisioterapeuta de profissão. Necessita de retorno rápido ao trabalho. Sessões ao fim do dia (18h-19h).'
FROM utilizadores WHERE email = 'beatriz.pinheiro@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '2001-06-07', 'M', '567124890', 'Rua da Boavista 310, 4ºEsq', '4100-130', 'Porto', 10, 15,
  'Distrofia muscular de Duchenne — fase de manutenção. Protocolo de estimulação mioeléctrica para retardar atrofia. Monitorização trimestral de força.',
  'Acompanhado pelos pais em todas as sessões. Usa cadeira de rodas motorizada. Muito cooperativo e bem-humorado.'
FROM utilizadores WHERE email = 'afonso.carvalho@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1982-03-19', 'F', '678235901', 'Rua de Cedofeita 55, 1ºDt', '4050-180', 'Porto', 6, 16,
  'Paralisia cerebral espástica unilateral (membro superior direito). Reabilitação funcional com recurso a biofeedback EMG. Objetivo: melhorar preensão.',
  'Professora primária. Muito empenhada. Realiza exercícios diários em casa com dispositivo portátil.'
FROM utilizadores WHERE email = 'leonor.bastos@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1973-09-04', 'M', '789346012', 'Rua de Antero de Quental 7', '4050-022', 'Porto', 7, 17,
  'Lesão nervosa periférica pós-traumática no nervo radial esquerdo (queda de mota, 2025). Recuperação de força e coordenação. EMG intra-muscular realizado.',
  'Mecânico. Ansioso por retomar trabalho. Boa força residual no bíceps. Protocolo intensivo autorizado pelo médico.'
FROM utilizadores WHERE email = 'tiago.monteiro@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1967-12-28', 'F', '890457123', 'Rua do Almada 150, 5ºDt', '4050-037', 'Porto', 8, 18,
  'Pós-AVC isquémico (janeiro 2026). Hemiplegia direita moderada. Afasia motora em remissão. Reabilitação intensiva membro superior direito.',
  'Reformada. Vive com o marido que a acompanha nas sessões. Muito determinada. Défice de atenção — sessões curtas e frequentes mais eficazes.'
FROM utilizadores WHERE email = 'mariana.freitas@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1988-07-11', 'M', '901568234', 'Rua do Campo Alegre 200, 3ºEsq', '4150-180', 'Porto', 9, 19,
  'Amputação transumeral esquerda (tumor ósseo, 2023). Em avaliação para prótese mioeléctrica de ombro. EMG dos músculos peitorais e dorsais em curso.',
  'Engenheiro informático, trabalha remotamente. Dextro. Muito tecnológico — interage bem com os sistemas de biofeedback.'
FROM utilizadores WHERE email = 'rodrigo.azevedo@email.pt';

INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, tecnico_id, diagnostico, observacoes)
SELECT id, '1994-02-25', 'F', '012679345', 'Av. da República 78, 2ºDt', '4050-493', 'Porto', 10, 20,
  'Esclerose múltipla recidivante-remitente. Surto de reabilitação ativo — fadiga muscular e espasticidade moderada nos membros superiores. Protocolo EMG de monitorização.',
  'Advogada. Muito organizada. Leva diário de sintomas para cada consulta. Medicada com interferon beta.'
FROM utilizadores WHERE email = 'sofia.ribeiro@email.pt';

-- -------------------------------------------------------------
-- 5. Sessões (3 por utente: 2 concluídas + 1 agendada)
-- -------------------------------------------------------------
-- Rui Andrade
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 11, '2026-05-06 09:00:00', 45, 'EMG Superfície', 'concluida', 'Baseline EMG registado. RMS bíceps: 38 µV. Boa cooperação do paciente.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 11, '2026-05-14 09:00:00', 45, 'EMG Superfície', 'concluida', 'RMS: 44 µV (+16%). Evolução positiva. Exercícios de extensão do cotovelo.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 11, '2026-06-04 09:00:00', 45, 'EMG Superfície', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';

-- Catarina Lemos
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 12, '2026-05-07 14:00:00', 60, 'Treino Mioeléctrico', 'concluida', 'Primeiro treino com prótese de teste. Precisão: 61%. Progresso inicial prometedor.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 12, '2026-05-19 14:00:00', 60, 'Treino Mioeléctrico', 'concluida', 'Precisão: 74% (+13%). Controlo de abertura/fecho da mão muito melhorado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 12, '2026-06-09 14:00:00', 60, 'Treino Mioeléctrico', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';

-- Gonçalo Figueiredo
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 13, '2026-05-08 10:30:00', 50, 'Reabilitação Neuromotora', 'concluida', 'Avaliação EMG deltóide e bíceps. Sinal fraco mas detetável. Protocolo iniciado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 13, '2026-05-22 10:30:00', 50, 'Reabilitação Neuromotora', 'concluida', 'Melhoria de 8% no sinal RMS. Progressão lenta mas consistente com lesão C6-C7.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 13, '2026-06-05 10:30:00', 50, 'Reabilitação Neuromotora', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';

-- Beatriz Pinheiro
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 14, '2026-05-09 18:00:00', 40, 'EMG Superfície', 'concluida', 'Pós-cirurgia mês 2. Sinal nervoso em recuperação. RMS flexores do punho: 29 µV.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 14, '2026-05-23 18:00:00', 40, 'EMG Superfície', 'concluida', 'RMS: 41 µV (+41%). Excelente recuperação. Dor residual reduzida.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 14, '2026-06-06 18:00:00', 40, 'EMG Superfície', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';

-- Afonso Carvalho
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 15, '2026-05-05 11:00:00', 45, 'Estimulação Mioeléctrica', 'concluida', 'Sessão de manutenção muscular. Estabilidade dos valores EMG face ao mês anterior.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 15, '2026-05-19 11:00:00', 45, 'Estimulação Mioeléctrica', 'concluida', 'Valores mantidos. Sem sinais de progressão da atrofia. Protocolo cumprido.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 15, '2026-06-02 11:00:00', 45, 'Estimulação Mioeléctrica', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';

-- Leonor Bastos
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 16, '2026-05-06 15:30:00', 45, 'Biofeedback EMG', 'concluida', 'Exercícios de preensão com biofeedback. Ativação do extensor digital melhorada.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 16, '2026-05-20 15:30:00', 45, 'Biofeedback EMG', 'concluida', 'Precisão de preensão: 58%. Redução da espasticidade notada pela própria paciente.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 16, '2026-06-03 15:30:00', 45, 'Biofeedback EMG', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';

-- Tiago Monteiro
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 17, '2026-05-07 09:30:00', 50, 'Reabilitação Nervosa', 'concluida', 'Avaliação pós-lesão radial. Início de protocolo de estimulação para reinervação.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 17, '2026-05-21 09:30:00', 50, 'Reabilitação Nervosa', 'concluida', 'Sinal EMG extensor radial longo detetado. Sinal de reinervação incipiente confirmado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 17, '2026-06-04 09:30:00', 50, 'Reabilitação Nervosa', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';

-- Mariana Freitas
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 18, '2026-05-08 10:00:00', 40, 'EMG Superfície', 'concluida', 'Mês 4 pós-AVC. RMS deltóide direito: 22 µV. Força funcional ainda reduzida.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 18, '2026-05-22 10:00:00', 40, 'EMG Superfície', 'concluida', 'RMS: 31 µV (+41%). Melhoria notável. Paciente consegue elevar o braço a 45 graus.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 18, '2026-06-05 10:00:00', 40, 'EMG Superfície', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';

-- Rodrigo Azevedo
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 19, '2026-05-09 14:30:00', 60, 'Avaliação Prótese Ombro', 'concluida', 'EMG peitoral maior e grande dorsal. Sinais de controlo independente confirmados.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 19, '2026-05-23 14:30:00', 60, 'Avaliação Prótese Ombro', 'concluida', 'Teste de controlo de prótese experimental. 3 graus de liberdade controlados com sucesso.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 19, '2026-06-06 14:30:00', 60, 'Avaliação Prótese Ombro', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';

-- Sofia Ribeiro
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 20, '2026-05-07 16:00:00', 45, 'Monitorização EMG', 'concluida', 'Avaliação durante surto. Fadiga muscular documentada. Espasticidade grau 2 (Ashworth).'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 20, '2026-05-21 16:00:00', 45, 'Monitorização EMG', 'concluida', 'Melhoria na espasticidade (grau 1). Surto em remissão. RMS estabilizado.'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
INSERT INTO sessoes (utente_id, tecnico_id, data_hora, duracao_min, tipo, estado, notas)
SELECT ut.id, 20, '2026-06-04 16:00:00', 45, 'Monitorização EMG', 'agendada', NULL
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';

-- -------------------------------------------------------------
-- 6. Faturas (3 por utente: 2 pagas + 1 pendente)
-- -------------------------------------------------------------
INSERT INTO faturas (numero, utente_id, valor_eur, paga, data_emissao, data_vencimento, notas)
SELECT CONCAT('FAT-2026-', LPAD(ROW_NUMBER() OVER (ORDER BY u.email), 4, '0')), ut.id, val, paga, emissao, vencimento, nota
FROM (
  SELECT 'rui.andrade@email.pt'        e, 45.00 val, 1 paga, '2026-05-06' emissao, '2026-05-20' vencimento, 'Sessão EMG Superfície — 06/05/2026'        nota UNION ALL
  SELECT 'rui.andrade@email.pt',          45.00,     1,      '2026-05-14',          '2026-05-28',             'Sessão EMG Superfície — 14/05/2026'            UNION ALL
  SELECT 'rui.andrade@email.pt',          45.00,     0,      '2026-06-04',          '2026-06-18',             'Sessão EMG Superfície — 04/06/2026'            UNION ALL
  SELECT 'catarina.lemos@email.pt',       65.00,     1,      '2026-05-07',          '2026-05-21',             'Sessão Treino Mioeléctrico — 07/05/2026'       UNION ALL
  SELECT 'catarina.lemos@email.pt',       65.00,     1,      '2026-05-19',          '2026-06-02',             'Sessão Treino Mioeléctrico — 19/05/2026'       UNION ALL
  SELECT 'catarina.lemos@email.pt',       65.00,     0,      '2026-06-09',          '2026-06-23',             'Sessão Treino Mioeléctrico — 09/06/2026'       UNION ALL
  SELECT 'goncalo.figueiredo@email.pt',   55.00,     1,      '2026-05-08',          '2026-05-22',             'Sessão Reabilitação Neuromotora — 08/05/2026'  UNION ALL
  SELECT 'goncalo.figueiredo@email.pt',   55.00,     1,      '2026-05-22',          '2026-06-05',             'Sessão Reabilitação Neuromotora — 22/05/2026'  UNION ALL
  SELECT 'goncalo.figueiredo@email.pt',   55.00,     0,      '2026-06-05',          '2026-06-19',             'Sessão Reabilitação Neuromotora — 05/06/2026'  UNION ALL
  SELECT 'beatriz.pinheiro@email.pt',     45.00,     1,      '2026-05-09',          '2026-05-23',             'Sessão EMG Superfície — 09/05/2026'            UNION ALL
  SELECT 'beatriz.pinheiro@email.pt',     45.00,     1,      '2026-05-23',          '2026-06-06',             'Sessão EMG Superfície — 23/05/2026'            UNION ALL
  SELECT 'beatriz.pinheiro@email.pt',     45.00,     0,      '2026-06-06',          '2026-06-20',             'Sessão EMG Superfície — 06/06/2026'            UNION ALL
  SELECT 'afonso.carvalho@email.pt',      50.00,     1,      '2026-05-05',          '2026-05-19',             'Sessão Estimulação Mioeléctrica — 05/05/2026'  UNION ALL
  SELECT 'afonso.carvalho@email.pt',      50.00,     1,      '2026-05-19',          '2026-06-02',             'Sessão Estimulação Mioeléctrica — 19/05/2026'  UNION ALL
  SELECT 'afonso.carvalho@email.pt',      50.00,     0,      '2026-06-02',          '2026-06-16',             'Sessão Estimulação Mioeléctrica — 02/06/2026'  UNION ALL
  SELECT 'leonor.bastos@email.pt',        45.00,     1,      '2026-05-06',          '2026-05-20',             'Sessão Biofeedback EMG — 06/05/2026'           UNION ALL
  SELECT 'leonor.bastos@email.pt',        45.00,     1,      '2026-05-20',          '2026-06-03',             'Sessão Biofeedback EMG — 20/05/2026'           UNION ALL
  SELECT 'leonor.bastos@email.pt',        45.00,     0,      '2026-06-03',          '2026-06-17',             'Sessão Biofeedback EMG — 03/06/2026'           UNION ALL
  SELECT 'tiago.monteiro@email.pt',       55.00,     1,      '2026-05-07',          '2026-05-21',             'Sessão Reabilitação Nervosa — 07/05/2026'      UNION ALL
  SELECT 'tiago.monteiro@email.pt',       55.00,     1,      '2026-05-21',          '2026-06-04',             'Sessão Reabilitação Nervosa — 21/05/2026'      UNION ALL
  SELECT 'tiago.monteiro@email.pt',       55.00,     0,      '2026-06-04',          '2026-06-18',             'Sessão Reabilitação Nervosa — 04/06/2026'      UNION ALL
  SELECT 'mariana.freitas@email.pt',      45.00,     1,      '2026-05-08',          '2026-05-22',             'Sessão EMG Superfície — 08/05/2026'            UNION ALL
  SELECT 'mariana.freitas@email.pt',      45.00,     1,      '2026-05-22',          '2026-06-05',             'Sessão EMG Superfície — 22/05/2026'            UNION ALL
  SELECT 'mariana.freitas@email.pt',      45.00,     0,      '2026-06-05',          '2026-06-19',             'Sessão EMG Superfície — 05/06/2026'            UNION ALL
  SELECT 'rodrigo.azevedo@email.pt',      75.00,     1,      '2026-05-09',          '2026-05-23',             'Sessão Avaliação Prótese Ombro — 09/05/2026'   UNION ALL
  SELECT 'rodrigo.azevedo@email.pt',      75.00,     1,      '2026-05-23',          '2026-06-06',             'Sessão Avaliação Prótese Ombro — 23/05/2026'   UNION ALL
  SELECT 'rodrigo.azevedo@email.pt',      75.00,     0,      '2026-06-06',          '2026-06-20',             'Sessão Avaliação Prótese Ombro — 06/06/2026'   UNION ALL
  SELECT 'sofia.ribeiro@email.pt',        45.00,     1,      '2026-05-07',          '2026-05-21',             'Sessão Monitorização EMG — 07/05/2026'         UNION ALL
  SELECT 'sofia.ribeiro@email.pt',        45.00,     1,      '2026-05-21',          '2026-06-04',             'Sessão Monitorização EMG — 21/05/2026'         UNION ALL
  SELECT 'sofia.ribeiro@email.pt',        45.00,     0,      '2026-06-04',          '2026-06-18',             'Sessão Monitorização EMG — 04/06/2026'
) AS dados
JOIN utilizadores u ON u.email = dados.e
JOIN utentes ut ON ut.utilizador_id = u.id;

-- -------------------------------------------------------------
-- 7. Consultas (2 por utente: 1 realizada + 1 agendada futura)
-- -------------------------------------------------------------
-- medico_id dos médicos da landing: António=6, Marta=7, Ricardo=8, Ana=9, João=10
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 6, '2026-05-13 10:00:00', 'Avaliação inicial e plano de reabilitação', 'EMG baseline avaliado. Protocolo de 12 sessões EMG iniciado. Bom prognóstico.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 6, '2026-06-17 10:00:00', 'Revisão do protocolo de reabilitação', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rui.andrade@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 7, '2026-05-14 11:00:00', 'Avaliação para prótese mioeléctrica', 'Candidata confirmada. Encaminhada para protésico. Protocolo de treino iniciado.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 7, '2026-06-18 11:00:00', 'Revisão pós-adaptação prótese', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='catarina.lemos@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 8, '2026-05-15 09:30:00', 'Consulta de seguimento lesão medular', 'Progresso lento mas positivo. Manter protocolo atual por mais 8 semanas.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 8, '2026-07-10 09:30:00', 'Reavaliação ASIA e protocolo', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='goncalo.figueiredo@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 9, '2026-05-16 17:00:00', 'Controlo pós-cirurgia túnel cárpico', 'Recuperação acima do esperado. RMS em crescimento. Alta prevista para agosto 2026.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 9, '2026-07-16 17:00:00', 'Alta clínica (prevista)', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='beatriz.pinheiro@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 10, '2026-05-12 11:30:00', 'Consulta trimestral de manutenção', 'Valores EMG estáveis. Sem progressão da atrofia. Protocolo mantido.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 10, '2026-08-12 11:30:00', 'Consulta trimestral de manutenção', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='afonso.carvalho@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 6, '2026-05-13 15:00:00', 'Consulta de biofeedback EMG — avaliação inicial', 'Espasticidade grau 2. Protocolo biofeedback iniciado. Boa tolerância ao treino.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 6, '2026-07-01 15:00:00', 'Revisão biofeedback', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='leonor.bastos@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 7, '2026-05-14 10:00:00', 'Avaliação lesão nervo radial', 'Sinal de reinervação incipiente detetado no EMG. Protocolo intensivo autorizado.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 7, '2026-07-09 10:00:00', 'Avaliação da reinervação', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='tiago.monteiro@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 8, '2026-05-15 10:30:00', 'Consulta mensal pós-AVC', 'Evolução excelente para mês 4. Elevar braço a 45 graus confirmado. Aumentar intensidade.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 8, '2026-06-15 10:30:00', 'Consulta mensal pós-AVC', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='mariana.freitas@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 9, '2026-05-16 14:00:00', 'Avaliação para prótese transumeral', 'Sinais de controlo independentes confirmados. Encaminhado para prótese de ombro.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 9, '2026-07-16 14:00:00', 'Revisão pós-adaptação prótese ombro', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='rodrigo.azevedo@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 10, '2026-05-12 16:30:00', 'Monitorização surto EM', 'Surto em remissão. Espasticidade grau 1. Continuar protocolo de monitorização.', 'realizada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 10, '2026-08-12 16:30:00', 'Monitorização trimestral EM', NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE u.email='sofia.ribeiro@email.pt';
