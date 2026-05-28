-- =============================================================
-- DADOS DE TESTE — Dr. José Rangel (utilizador_id=9, profissional_id=5)
-- Executar no phpMyAdmin: base de dados sistema_mioeletrico
-- =============================================================

USE sistema_mioeletrico;

-- -------------------------------------------------------------
-- 1. Completar registo do profissional
-- -------------------------------------------------------------
UPDATE profissionais
SET especialidade = 'Fisiatria',
    instituicao   = 'RehabLink — Unidade Porto'
WHERE utilizador_id = 9;

-- -------------------------------------------------------------
-- 2. Criar pacientes de teste
-- -------------------------------------------------------------
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES
('Maria João Costa',  'mariajcosta@email.pt',      '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Pedro Alves Sousa', 'pedroasousa@email.pt',       '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente'),
('Fernanda Nunes',    'fernanda.nunes@email.pt',    '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'utente');

-- Registos na tabela utentes (medico_id=5 = profissional do Dr. José Rangel)
INSERT IGNORE INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, diagnostico)
SELECT id, '1985-03-15', 'F', '123456789', 'Rua das Flores 12, 2ºDt', '4100-123', 'Porto', 5,
       'Reabilitação pós-AVC. Défice motor membro superior esquerdo. Monitorização mioeléctrica em curso.'
FROM utilizadores WHERE email = 'mariajcosta@email.pt';

INSERT IGNORE INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, diagnostico)
SELECT id, '1972-11-08', 'M', '987654321', 'Av. da Boavista 500, 3ºEsq', '4100-456', 'Porto', 5,
       'Amputação transradial direita. Candidato a prótese mioeléctrica. EMG dos músculos residuais em avaliação.'
FROM utilizadores WHERE email = 'pedroasousa@email.pt';

INSERT IGNORE INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, codigo_postal, localidade, medico_id, diagnostico)
SELECT id, '1990-07-22', 'F', '456789123', 'Rua do Heroísmo 88', '4300-789', 'Porto', 5,
       'Lesão medular incompleta C5-C6. Reabilitação neuromotora. Protocolo de EMG de superfície ativo.'
FROM utilizadores WHERE email = 'fernanda.nunes@email.pt';

-- Associar Sara Rangel (utente existente) ao Dr. José Rangel
UPDATE utentes SET medico_id = 5 WHERE id = 2;

-- -------------------------------------------------------------
-- 3. Consultas (passadas + futuras + hoje)
-- -------------------------------------------------------------
-- Maria João Costa
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-05-10 09:30:00',
       'Avaliação inicial mioeléctrica',
       'Primeira consulta. EMG baseline estabelecido. Tónus muscular adequado. Iniciado protocolo de reabilitação.',
       'realizada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-05-20 11:00:00',
       'Revisão do protocolo de reabilitação',
       'Melhoria de 15% no sinal RMS em relação à baseline. Ajuste do programa de exercícios. Boa adesão.',
       'realizada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-06-05 10:00:00',
       'Consulta de seguimento mensal',
       NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

-- Pedro Alves Sousa
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-05-15 14:30:00',
       'Avaliação para adaptação de prótese mioeléctrica',
       'EMG dos músculos residuais apresenta sinal adequado para controlo de prótese. Encaminhado para protésico.',
       'realizada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'pedroasousa@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-06-10 09:00:00',
       'Revisão pós-adaptação de prótese',
       NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'pedroasousa@email.pt';

-- Fernanda Nunes (consulta hoje para aparecer no dashboard)
INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-05-28 15:00:00',
       'Avaliação neuromotora',
       NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'fernanda.nunes@email.pt';

INSERT INTO consultas (utente_id, medico_id, data_hora, motivo, notas, estado)
SELECT ut.id, 5, '2026-05-28 16:30:00',
       'Controlo espasticidade C5-C6',
       NULL, 'agendada'
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

-- -------------------------------------------------------------
-- 4. Prescrições
-- -------------------------------------------------------------
INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa)
SELECT ut.id, 5, '2026-05-10', '2026-08-10', 'SNS', 'Alta',
       'EMG de superfície — 12 sessões. Avaliação muscular bíceps e tríceps braquial esquerdo.', 1
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa)
SELECT ut.id, 5, '2026-05-10', '2026-11-10', 'SNS', 'Media',
       'Fisioterapia neurológica — 2x/semana. Foco na recuperação funcional do membro superior esquerdo.', 1
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa)
SELECT ut.id, 5, '2026-04-01', '2026-05-01', 'SNS', 'Baixa',
       'Análises sanguíneas de rotina. Hemograma completo e bioquímica.', 0
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'mariajcosta@email.pt';

INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa)
SELECT ut.id, 5, '2026-05-15', '2026-09-15', 'Particular', 'Urgente',
       'Avaliação muscular para candidatura a prótese mioeléctrica. EMG de superfície e intra-muscular.', 1
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'pedroasousa@email.pt';

INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa)
SELECT ut.id, 5, '2026-05-20', '2026-07-20', 'SNS', 'Media',
       'Ressonância magnética cervical C5-C6. Confirmar extensão da lesão medular.', 1
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'fernanda.nunes@email.pt';

INSERT INTO prescricoes (utente_id, medico_id, data_prescricao, data_validade, tipo, prioridade, observacoes, ativa)
SELECT ut.id, 5, '2026-05-20', '2026-08-20', 'SNS', 'Alta',
       'EMG de superfície protocolo neuromotor — 8 sessões. Músculos alvo: deltóide, bíceps, extensor do punho.', 1
FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id WHERE u.email = 'fernanda.nunes@email.pt';

-- -------------------------------------------------------------
-- 5. Logs de acesso (auditoria)
-- -------------------------------------------------------------
INSERT INTO logs_acesso (utilizador_id, acao, ip, user_agent, detalhes, criado_em) VALUES
(9, 'login',  '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Login bem-sucedido', '2026-05-10 08:55:00'),
(9, 'logout', '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Logout',             '2026-05-10 13:20:00'),
(9, 'login',  '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Login bem-sucedido', '2026-05-15 14:10:00'),
(9, 'logout', '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Logout',             '2026-05-15 18:00:00'),
(9, 'login',  '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Login bem-sucedido', '2026-05-20 10:45:00'),
(9, 'login',  '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Login bem-sucedido', '2026-05-28 08:30:00');
