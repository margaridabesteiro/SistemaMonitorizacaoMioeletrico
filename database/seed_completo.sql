-- =============================================================
-- SEED COMPLETO — RehabLink Sistema de Monitorização Mioeléctrica
-- Versão 2.0 | Junho 2026
-- =============================================================
-- Importar: phpMyAdmin → sistema_mioeletrico → Importar → seed_completo.sql

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;
-- =============================================================
-- Credenciais:
--   Administradores → sofia.mendes@rehablink.pt    / RehabLink2025!
--                     ricardo.sousa@rehablink.pt   / RehabLink2025!
--   Médicos         → ana.silva@rehablink.pt       / Medico2025!
--                     pedro.costa@rehablink.pt     / Medico2025!
--                     margarida.lopes@rehablink.pt / Medico2025!
--                     joao.ferreira.med@rehablink.pt / Medico2025!
--                     catarina.neves@rehablink.pt  / Medico2025!
--                     rui.baptista@rehablink.pt    / Medico2025!
--   Técnicos        → miguel.santos@rehablink.pt   / Tecnico2025!
--                     ines.rodrigues@rehablink.pt  / Tecnico2025!
--                     carlos.pinto@rehablink.pt    / Tecnico2025!
--                     beatriz.cunha@rehablink.pt   / Tecnico2025!
--                     diogo.almeida@rehablink.pt   / Tecnico2025!
--                     helena.vieira@rehablink.pt   / Tecnico2025!
--   Utentes         → {nome}.{apelido}@rehablink.pt / Utente2025!
-- =============================================================

USE sistema_mioeletrico;

-- Alargar ENUM dispositivos.estado para incluir estados usados na aplicação
ALTER TABLE dispositivos
    MODIFY COLUMN estado
    ENUM('disponivel','emprestado','manutencao','avariado','abatido','perdido','danificado')
    NOT NULL DEFAULT 'disponivel';

-- Garantir tabela fatura_linhas (criada em runtime por fatura.php)
CREATE TABLE IF NOT EXISTS fatura_linhas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fatura_id    INT UNSIGNED     NOT NULL,
    tipo_servico VARCHAR(50)      NULL,
    descricao    VARCHAR(200)     NOT NULL,
    quantidade   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    preco_unit   DECIMAL(8,2)     NOT NULL,
    total_linha  DECIMAL(8,2)     NOT NULL,
    FOREIGN KEY (fatura_id) REFERENCES faturas(id) ON DELETE CASCADE,
    INDEX idx_fatura (fatura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- LIMPAR DADOS EXISTENTES
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE auditoria;
TRUNCATE TABLE notificacoes;
TRUNCATE TABLE mensagens;
TRUNCATE TABLE fatura_linhas;
TRUNCATE TABLE faturas;
TRUNCATE TABLE metricas_sessao;
TRUNCATE TABLE sessoes;
TRUNCATE TABLE consultas;
TRUNCATE TABLE pedidos_exame;
TRUNCATE TABLE prescricoes_medicacao;
TRUNCATE TABLE programas_tratamento;
TRUNCATE TABLE emprestimos_dispositivos;
TRUNCATE TABLE dispositivos;
TRUNCATE TABLE preferencias_utilizador;
TRUNCATE TABLE logs_acesso;
TRUNCATE TABLE password_resets;
TRUNCATE TABLE utentes;
TRUNCATE TABLE profissionais;
TRUNCATE TABLE utilizadores;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DADOS BASE — seguradoras e jogos (INSERT IGNORE — idempotente)
-- ============================================================
INSERT IGNORE INTO seguradoras (id, nome, tipo) VALUES
    (1, 'Particular',       'Particular'),
    (2, 'SNS',              'SNS'),
    (3, 'Multicare',        'Seguro'),
    (4, 'AdvanceCare',      'Seguro'),
    (5, 'Médis',            'Seguro'),
    (6, 'Allianz Saúde',    'Seguro'),
    (7, 'Fidelidade Saúde', 'Seguro'),
    (8, 'Lusitânia Saúde',  'Seguro');

INSERT IGNORE INTO jogos (id, nome, nivel, descricao, forca_ref_n) VALUES
    (1, 'catch_game',     'minimo', 'Apanhar objetos em queda — controlo on/off simples',       5.0),
    (2, 'claw_game',      'medio',  'Garra arcade com dois thresholds de força',                8.0),
    (3, 'flappy_trainer', 'maximo', 'Controlo proporcional de altitude por força mioelétrica', 12.0);

-- ============================================================
-- UTILIZADORES (2 admins + 6 médicos + 6 técnicos + 12 utentes)
-- ============================================================
INSERT INTO utilizadores (id, nome, email, password_hash, perfil, ativo, criado_em) VALUES
-- Administradores  (RehabLink2025!)
(1,  'Sofia Mendes',         'sofia.mendes@rehablink.pt',         '$2y$10$6E1R/idn73iNQGZJS6THueofV/mnCG.OKzT1r45F05CHuCmfMWFL.', 'admin',   1, '2025-01-10 08:00:00'),
(2,  'Ricardo Sousa',        'ricardo.sousa@rehablink.pt',        '$2y$10$6E1R/idn73iNQGZJS6THueofV/mnCG.OKzT1r45F05CHuCmfMWFL.', 'admin',   1, '2025-01-10 08:05:00'),
-- Médicos  (Medico2025!)
(3,  'Dra. Ana Silva',       'ana.silva@rehablink.pt',            '$2y$10$PLjdU3R5uwmWtJAIw8mjguD.X3R1b8bmK59i.gXEnF48hpIwt.ArG', 'medico',  1, '2025-01-12 09:00:00'),
(4,  'Dr. Pedro Costa',      'pedro.costa@rehablink.pt',          '$2y$10$PLjdU3R5uwmWtJAIw8mjguD.X3R1b8bmK59i.gXEnF48hpIwt.ArG', 'medico',  1, '2025-01-12 09:10:00'),
(5,  'Dra. Margarida Lopes', 'margarida.lopes@rehablink.pt',      '$2y$10$PLjdU3R5uwmWtJAIw8mjguD.X3R1b8bmK59i.gXEnF48hpIwt.ArG', 'medico',  1, '2025-01-13 09:00:00'),
(6,  'Dr. João Ferreira',    'joao.ferreira.med@rehablink.pt',    '$2y$10$PLjdU3R5uwmWtJAIw8mjguD.X3R1b8bmK59i.gXEnF48hpIwt.ArG', 'medico',  1, '2025-01-13 09:15:00'),
(7,  'Dra. Catarina Neves',  'catarina.neves@rehablink.pt',       '$2y$10$PLjdU3R5uwmWtJAIw8mjguD.X3R1b8bmK59i.gXEnF48hpIwt.ArG', 'medico',  1, '2025-01-14 09:00:00'),
(8,  'Dr. Rui Baptista',     'rui.baptista@rehablink.pt',         '$2y$10$PLjdU3R5uwmWtJAIw8mjguD.X3R1b8bmK59i.gXEnF48hpIwt.ArG', 'medico',  1, '2025-01-14 09:20:00'),
-- Técnicos  (Tecnico2025!)
(9,  'Miguel Santos',        'miguel.santos@rehablink.pt',        '$2y$10$5vM99IeapxHYRC6.A86d4eTCMQa8QP17z64YgjEAreLAM/FjBHpj6', 'tecnico', 1, '2025-01-15 09:00:00'),
(10, 'Inês Rodrigues',       'ines.rodrigues@rehablink.pt',       '$2y$10$5vM99IeapxHYRC6.A86d4eTCMQa8QP17z64YgjEAreLAM/FjBHpj6', 'tecnico', 1, '2025-01-15 09:10:00'),
(11, 'Carlos Pinto',         'carlos.pinto@rehablink.pt',         '$2y$10$5vM99IeapxHYRC6.A86d4eTCMQa8QP17z64YgjEAreLAM/FjBHpj6', 'tecnico', 1, '2025-01-15 09:20:00'),
(12, 'Beatriz Cunha',        'beatriz.cunha@rehablink.pt',        '$2y$10$5vM99IeapxHYRC6.A86d4eTCMQa8QP17z64YgjEAreLAM/FjBHpj6', 'tecnico', 1, '2025-01-16 09:00:00'),
(13, 'Diogo Almeida',        'diogo.almeida@rehablink.pt',        '$2y$10$5vM99IeapxHYRC6.A86d4eTCMQa8QP17z64YgjEAreLAM/FjBHpj6', 'tecnico', 1, '2025-01-16 09:10:00'),
(14, 'Helena Vieira',        'helena.vieira@rehablink.pt',        '$2y$10$5vM99IeapxHYRC6.A86d4eTCMQa8QP17z64YgjEAreLAM/FjBHpj6', 'tecnico', 1, '2025-01-16 09:20:00'),
-- Utentes  (Utente2025!)
(15, 'João Santos',          'joao.santos@rehablink.pt',          '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-01 10:00:00'),
(16, 'Maria Oliveira',       'maria.oliveira@rehablink.pt',       '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-03 10:00:00'),
(17, 'Pedro Ferreira',       'pedro.ferreira@rehablink.pt',       '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-05 10:00:00'),
(18, 'Ana Sousa',            'ana.sousa@rehablink.pt',            '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-07 10:00:00'),
(19, 'Carlos Lima',          'carlos.lima@rehablink.pt',          '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-10 10:00:00'),
(20, 'Beatriz Costa',        'beatriz.costa@rehablink.pt',        '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-12 10:00:00'),
(21, 'Rui Matos',            'rui.matos@rehablink.pt',            '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-14 10:00:00'),
(22, 'Sofia Pires',          'sofia.pires@rehablink.pt',          '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-17 10:00:00'),
(23, 'António Silva',        'antonio.silva@rehablink.pt',        '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-19 10:00:00'),
(24, 'Inês Fernandes',       'ines.fernandes@rehablink.pt',       '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-21 10:00:00'),
(25, 'Lucas Rodrigues',      'lucas.rodrigues@rehablink.pt',      '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-24 10:00:00'),
(26, 'Diana Alves',          'diana.alves@rehablink.pt',          '$2y$10$Gzeug44L1ptGZmwYbp9FDuA2p0HpD0TNn/2vk1vu4G4IWJD9d6EBq', 'utente',  1, '2025-02-26 10:00:00');

-- ============================================================
-- PROFISSIONAIS (1-6 = médicos, 7-12 = técnicos)
-- ============================================================
INSERT INTO profissionais (id, utilizador_id, numero_ordem, especialidade, instituicao, contacto) VALUES
(1,  3,  'OM-11001', 'Medicina Física e Reabilitação', 'RehabLink', '912 001 001'),
(2,  4,  'OM-11002', 'Neurologia e Reabilitação',      'RehabLink', '912 001 002'),
(3,  5,  'OM-11003', 'Medicina Física e Reabilitação', 'RehabLink', '912 001 003'),
(4,  6,  'OM-11004', 'Ortopedia e Reabilitação',       'RehabLink', '912 001 004'),
(5,  7,  'OM-11005', 'Medicina Física e Reabilitação', 'RehabLink', '912 001 005'),
(6,  8,  'OM-11006', 'Neurologia e Reabilitação',      'RehabLink', '912 001 006'),
(7,  9,  'OF-22001', 'Fisioterapia Mioeléctrica',      'RehabLink', '913 001 001'),
(8,  10, 'OF-22002', 'Terapia Ocupacional',            'RehabLink', '913 001 002'),
(9,  11, 'OF-22003', 'Fisioterapia Mioeléctrica',      'RehabLink', '913 001 003'),
(10, 12, 'OF-22004', 'Terapia Ocupacional',            'RehabLink', '913 001 004'),
(11, 13, 'OF-22005', 'Fisioterapia Mioeléctrica',      'RehabLink', '913 001 005'),
(12, 14, 'OF-22006', 'Terapia Ocupacional',            'RehabLink', '913 001 006');

-- ============================================================
-- UTENTES (12 pacientes)
-- medico_id e tecnico_id referenciam profissionais.id
-- Distribuição:
--   u1,u2  → médico 1 (Ana Silva)    | u1→técnico 7 (Miguel), u2→técnico 8 (Inês)
--   u3,u4  → médico 2 (Pedro Costa)  | u3→técnico 7 (Miguel), u4→técnico 9 (Carlos)
--   u5,u6  → médico 3 (Margarida)    | u5→técnico 8 (Inês),   u6→técnico 10 (Beatriz)
--   u7,u8  → médico 4 (João Ferr.)   | u7→técnico 9 (Carlos), u8→técnico 11 (Diogo)
--   u9,u10 → médico 5 (Catarina)     | u9→técnico 10 (Beatriz),u10→técnico 12 (Helena)
--   u11,u12→ médico 6 (Rui Bapt.)    | u11→técnico 11 (Diogo), u12→técnico 12 (Helena)
-- ============================================================
INSERT INTO utentes (id, utilizador_id, data_nascimento, sexo, nif,
    morada, codigo_postal, localidade,
    medico_id, tecnico_id,
    diagnostico, cobertura_saude, seguradora_id,
    fase_tratamento, categoria_clinica, membro_afetado,
    data_inicio_tratamento, data_alta) VALUES
(1,  15, '1978-04-12', 'M', '123456789',
    'Rua das Flores 15, 2.º Esq',    '4200-001', 'Porto',
    1, 7,
    'AVC isquémico com hemiplegia direita — limitação funcional mão direita.',
    'Seguro', 3, 'ativo', 'avc', 'mao_direita', '2025-02-01', NULL),

(2,  16, '1965-09-23', 'F', '234567890',
    'Av. da República 88',            '4100-088', 'Porto',
    1, 8,
    'Amputação transradial esquerda — adaptação a prótese mioelétrica de gancho.',
    'SNS', 2, 'ativo', 'amputacao_ms', 'mao_esquerda', '2025-02-03', NULL),

(3,  17, '1990-07-05', 'M', '345678901',
    'Rua do Infante 34',              '4050-300', 'Porto',
    2, 7,
    'Lesão medular incompleta C5-C6 — défice motor bilateral membro superior.',
    'Seguro', 4, 'ativo', 'lesao_medular', 'ambas', '2025-02-05', NULL),

(4,  18, '1982-11-30', 'F', '456789012',
    'Praça do Marquês 7',             '4250-000', 'Porto',
    2, 9,
    'Paralisia cerebral espástica — dificuldade no controlo voluntário da mão direita.',
    'Particular', 1, 'manutencao', 'paralisia_cerebral', 'mao_direita', '2025-02-07', NULL),

(5,  19, '1955-03-17', 'M', '567890123',
    'Rua de Santa Catarina 200',      '4000-455', 'Porto',
    3, 8,
    'AVC hemorrágico — recuperação funcional membro superior esquerdo.',
    'Seguro', 5, 'ativo', 'avc', 'mao_esquerda', '2025-02-10', NULL),

(6,  20, '1988-06-08', 'F', '678901234',
    'Travessa do Carmo 12',           '4050-163', 'Porto',
    3, 10,
    'Lesão nervo mediano pós-traumática — fraqueza muscular e perda de sensibilidade mão direita.',
    'SNS', 2, 'ativo', 'lesao_nervosa_periferica', 'mao_direita', '2025-02-12', NULL),

(7,  21, '1972-02-28', 'M', '789012345',
    'Rua Formosa 56',                 '4000-248', 'Porto',
    4, 9,
    'Amputação transhumerária direita — reabilitação pós-protésica com controlo triestado.',
    'Seguro', 3, 'ativo', 'amputacao_ms', 'mao_direita', '2025-02-14', NULL),

(8,  22, '1995-12-01', 'F', '890123456',
    'Alameda das Antas 3, 5.º',       '4350-001', 'Porto',
    4, 11,
    'AVC pós-traumático bilateral — programa de estimulação mioelétrica intensiva.',
    'Seguro', 6, 'manutencao', 'avc', 'ambas', '2025-02-17', NULL),

(9,  23, '1960-08-14', 'M', '901234567',
    'Rua de Cedofeita 333',           '4050-180', 'Porto',
    5, 10,
    'Pós-operatório ortopédico de joelho direito — recuperação de força muscular quadricipital.',
    'Particular', 1, 'avaliacao', 'outro', 'perna_direita', '2025-02-19', NULL),

(10, 24, '1985-05-22', 'F', '012345678',
    'Av. de França 78, 3.º',          '4050-277', 'Porto',
    5, 12,
    'Paralisia cerebral — programa personalizado de controlo fino bilateral.',
    'Seguro', 7, 'ativo', 'paralisia_cerebral', 'ambas', '2025-02-21', NULL),

(11, 25, '1970-10-09', 'M', '112345678',
    'Rua Álvares Cabral 19',          '4050-041', 'Porto',
    6, 11,
    'AVC lacunar — défice motor moderado mão direita com espasticidade grau 1.',
    'Seguro', 4, 'ativo', 'avc', 'mao_direita', '2025-02-24', NULL),

(12, 26, '1993-01-15', 'F', '212345678',
    'Rua Miguel Bombarda 88',         '4050-379', 'Porto',
    6, 12,
    'Lesão medular D8 — programa de reabilitação funcional membro superior concluído com sucesso.',
    'SNS', 2, 'alta', 'lesao_medular', 'mao_esquerda', '2025-02-26', '2026-05-15');

-- ============================================================
-- DISPOSITIVOS (5 — um de cada estado representativo)
-- ============================================================
INSERT INTO dispositivos (id, codigo, tipo, firmware_versao, ativo, estado, token_api, ultimo_sync) VALUES
(1, 'EMG-0001', 'Sensor FSR406 — Membro Superior', 'v2.3.1', 1, 'disponivel',
    'tok_0001_a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6', '2026-06-16 14:30:00'),
(2, 'EMG-0002', 'Sensor FSR406 — Membro Superior', 'v2.3.1', 1, 'emprestado',
    'tok_0002_b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7', '2026-06-10 09:15:00'),
(3, 'EMG-0003', 'Sensor FSR406 — Membro Inferior', 'v2.1.0', 0, 'avariado',
    NULL, '2026-03-01 11:00:00'),
(4, 'EMG-0004', 'Sensor FSR406 — Membro Superior', 'v1.9.2', 0, 'perdido',
    NULL, '2026-01-14 16:45:00'),
(5, 'EMG-0005', 'Sensor FSR406 — Membro Superior', 'v2.2.0', 0, 'danificado',
    NULL, '2026-04-19 10:00:00');

-- ============================================================
-- EMPRÉSTIMOS (dispositivo 2 emprestado ao utente 1 / técnico 7)
-- ============================================================
INSERT INTO emprestimos_dispositivos
    (id, dispositivo_id, utente_id, tecnico_id, data_entrega,
     data_prevista_devolucao, estado_entrega, notas) VALUES
(1, 2, 1, 7, '2026-05-20 10:00:00', '2026-07-20', 'bom',
    'Dispositivo entregue em boas condições para treino domiciliar. Utente instruído sobre calibração e sincronização via app.');

-- ============================================================
-- PREFERÊNCIAS DE UTILIZADOR (todos os 26)
-- ============================================================
INSERT INTO preferencias_utilizador (utilizador_id, notif_email, notif_inicio_sessao, idioma) VALUES
(1,1,1,'pt'),(2,1,1,'pt'),
(3,1,1,'pt'),(4,1,1,'pt'),(5,1,0,'pt'),(6,1,1,'pt'),(7,1,1,'pt'),(8,1,0,'pt'),
(9,1,1,'pt'),(10,1,1,'pt'),(11,0,1,'pt'),(12,1,1,'pt'),(13,1,0,'pt'),(14,1,1,'pt'),
(15,1,1,'pt'),(16,1,1,'pt'),(17,0,1,'pt'),(18,1,1,'pt'),(19,1,1,'pt'),(20,1,0,'pt'),
(21,1,1,'pt'),(22,0,1,'pt'),(23,1,1,'pt'),(24,1,1,'pt'),(25,1,1,'pt'),(26,1,0,'pt');

-- ============================================================
-- PROGRAMAS DE TRATAMENTO (1 por utente)
-- ============================================================
INSERT INTO programas_tratamento
    (id, utente_id, medico_id, data_prescricao, data_validade,
     num_sessoes_prescritas, objetivos_clinicos, membro_afetado, observacoes, ativa) VALUES
(1,  1,  1, '2025-02-05', '2026-02-05', 24,
    'Recuperar controlo voluntário da extensão e flexão dos dedos da mão direita. Meta: ≥60% no catch_game ao fim de 12 sessões.',
    'mao_direita', 'Iniciar com calibragem semanal. Progredir para sessões de jogo 2x/semana.', 1),
(2,  2,  1, '2025-02-07', '2025-12-07', 20,
    'Adaptar sinal mioelétrico ao socket da prótese. Treinar controlo de abertura e fecho para AVDs.',
    'mao_esquerda', 'Coordenar com protésico. Registar threshold de ativação em cada sessão.', 1),
(3,  3,  2, '2025-02-10', '2026-02-10', 30,
    'Reabilitar controlo bilateral membro superior. Prioridade: mão dominante direita. claw_game como indicador de progresso.',
    'ambas', 'Sessões de calibração quinzenais. Regime intensivo 3x/semana nas primeiras 8 semanas.', 1),
(4,  4,  2, '2025-02-12', '2025-11-12', 18,
    'Melhorar controlo voluntário seletivo para reduzir espasticidade funcional. Objetivo: independência nas AVDs básicas.',
    'mao_direita', 'Coordenar com neurologista. Reavaliar objetivos ao fim de 6 semanas.', 1),
(5,  5,  3, '2025-02-15', '2026-02-15', 24,
    'Recuperar força e controlo motor membro superior esquerdo pós-AVC. Meta: retorno às atividades profissionais.',
    'mao_esquerda', 'Utente motivado. Integrar exercícios domiciliares complementares.', 1),
(6,  6,  3, '2025-02-17', '2026-02-17', 20,
    'Regeneração funcional nervo mediano. Treinar oponência do polegar e preensão fina.',
    'mao_direita', 'Acompanhamento médico mensal. Monitorizar sinais de dor neuropática durante treino.', 1),
(7,  7,  4, '2025-02-19', '2026-11-19', 36,
    'Dominar controlo mioelétrico triestado para prótese transumeral. Meta: independência funcional completa.',
    'mao_direita', 'Protocolo avançado. Sessões de 60 min. Flappy_trainer como teste de precisão.', 1),
(8,  8,  4, '2025-02-21', '2026-08-21', 24,
    'Programa intensivo de estimulação bilateral. Reduzir assimetria entre lados. Objetivo: condução autónoma adaptada.',
    'ambas', 'Fase de manutenção. Monitorizar fadiga muscular e escalar dificuldade progressivamente.', 1),
(9,  9,  5, '2025-02-24', '2026-02-24', 16,
    'Recuperação pós-operatória. Fortalecer musculatura quadricipital. Retorno progressivo à marcha funcional.',
    'perna_direita', 'Combinar com fisioterapia convencional. Revisão ortopédica trimestral.', 1),
(10, 10, 5, '2025-02-26', '2026-11-26', 28,
    'Programa de longa duração para desenvolvimento de controlo voluntário. Integração escolar como objetivo.',
    'ambas', 'Envolver família no processo. Sessões gamificadas para manutenção de motivação.', 1),
(11, 11, 6, '2025-03-01', '2026-03-01', 22,
    'Reduzir espasticidade e recuperar preensão funcional mão direita. Retorno ao trabalho como meta.',
    'mao_direita', 'Neuropsi normal. Capacidade de aprendizagem preservada. Prognóstico favorável.', 1),
(12, 12, 6, '2025-03-03', '2026-03-03', 18,
    'Manutenção de ganhos funcionais. Preparação para alta definitiva e programa domiciliar autónomo.',
    'mao_esquerda', 'Objetivos atingidos. Alta formal em Maio 2026. Manter avaliações pós-alta.', 0);

-- ============================================================
-- CONSULTAS (3 por utente = 36 total)
-- medico_id referencia profissionais.id
-- ============================================================
INSERT INTO consultas
    (id, utente_id, medico_id, data_hora, tipo, motivo, notas, evolucao, modalidade, estado) VALUES
-- Utente 1 (João Santos) — médico 1 (Ana Silva)
(1,  1, 1, '2025-02-05 09:00:00', 'inicial', 'Avaliação inicial pós-AVC. Definição de objetivos de reabilitação.',
    'Utente colaborante. Défice moderado extensão dedos mão direita. Inicia programa mioelétrico.', 'em_avaliacao', 'presencial', 'realizada'),
(2,  1, 1, '2025-05-12 09:00:00', 'rotina',  'Revisão de 3 meses. Avaliação de progresso e ajuste de programa.',
    'Progressos evidentes. Percentagem no catch_game subiu de 35% para 48%. Introduzir treino bilateral.', 'melhorou', 'presencial', 'realizada'),
(3,  1, 1, '2026-07-10 09:30:00', 'rotina',  'Consulta de seguimento semestral.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 2 (Maria Oliveira) — médico 1 (Ana Silva)
(4,  2, 1, '2025-02-07 14:00:00', 'inicial', 'Avaliação para prescrição de prótese mioelétrica transradial.',
    'EMG muscular residual adequado. Boa candidata a prótese. Referenciada ao protésico.', 'em_avaliacao', 'presencial', 'realizada'),
(5,  2, 1, '2025-06-20 14:00:00', 'rotina',  'Revisão de adaptação à prótese. Avaliação funcional.',
    'Adaptação satisfatória. Controlo de abertura/fecho bem estabelecido. Iniciar AVDs complexas.', 'melhorou', 'presencial', 'realizada'),
(6,  2, 1, '2026-08-05 14:00:00', 'alta',    'Consulta de alta programada.', NULL, NULL, 'video', 'agendada'),
-- Utente 3 (Pedro Ferreira) — médico 2 (Pedro Costa)
(7,  3, 2, '2025-02-10 09:00:00', 'inicial', 'Avaliação inicial lesão medular C5-C6. Potencial reabilitativo.',
    'Lesão incompleta com preservação parcial motora. Bom candidato a treino mioelétrico intensivo.', 'em_avaliacao', 'presencial', 'realizada'),
(8,  3, 2, '2025-07-22 10:00:00', 'rotina',  'Consulta de revisão semestral. Avaliação de força residual.',
    'Melhoria progressiva bilateral. Alcança ≥55% no claw_game. Programa a manter.', 'melhorou', 'presencial', 'realizada'),
(9,  3, 2, '2026-06-30 09:00:00', 'rotina',  'Consulta de seguimento trimestral.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 4 (Ana Sousa) — médico 2 (Pedro Costa)
(10, 4, 2, '2025-02-12 11:00:00', 'inicial', 'Avaliação paralisia cerebral espástica. Integração mioelétrica.',
    'Espasticidade grau 2. Controlo voluntário inconsistente mas presente. Iniciar com threshold baixo.', 'em_avaliacao', 'presencial', 'realizada'),
(11, 4, 2, '2025-08-18 11:00:00', 'rotina',  'Revisão de 6 meses. Ajuste de objetivos.',
    'Progresso lento mas consistente. Fase de manutenção. Continuar protocolo adaptado.', 'estabilizou', 'presencial', 'realizada'),
(12, 4, 2, '2026-07-20 11:00:00', 'rotina',  'Consulta de avaliação anual.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 5 (Carlos Lima) — médico 3 (Margarida Lopes)
(13, 5, 3, '2025-02-15 15:00:00', 'inicial', 'Avaliação pós-AVC hemorrágico. Programa de recuperação intensiva.',
    'Afasia leve mas cooperante. Défice motor esquerdo moderado. Potencial de recuperação preservado.', 'em_avaliacao', 'presencial', 'realizada'),
(14, 5, 3, '2025-09-10 15:00:00', 'rotina',  'Revisão após 6 meses de tratamento.',
    'Recuperação acima do esperado. Percentagem catch_game atingiu 52% (meta era 40%). Progredir.', 'melhorou', 'presencial', 'realizada'),
(15, 5, 3, '2026-07-15 15:00:00', 'rotina',  'Consulta de seguimento com avaliação funcional.', NULL, NULL, 'video', 'agendada'),
-- Utente 6 (Beatriz Costa) — médico 3 (Margarida Lopes)
(16, 6, 3, '2025-02-17 14:00:00', 'inicial', 'Lesão nervo mediano. Avaliação eletroneuromiográfica.',
    'EMG confirma lesão axonal parcial. Prognóstico 12-18 meses. Iniciar biofeedback mioelétrico.', 'em_avaliacao', 'presencial', 'realizada'),
(17, 6, 3, '2025-11-03 14:00:00', 'rotina',  'Revisão de 9 meses. Sinais de reinervação.',
    'Melhoria moderada. Dor neuropática controlada. Força de oponência do polegar a 68%.', 'melhorou', 'presencial', 'realizada'),
(18, 6, 3, '2026-08-17 14:00:00', 'rotina',  'Avaliação 18 meses pós-lesão.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 7 (Rui Matos) — médico 4 (João Ferreira)
(19, 7, 4, '2025-02-19 09:00:00', 'inicial', 'Avaliação pré-protésica. Amputação transhumerária direita.',
    'Bom remanescente muscular. Sinal EMG de qualidade. Candidato a protocolo avançado triestado.', 'em_avaliacao', 'presencial', 'realizada'),
(20, 7, 4, '2025-10-07 09:00:00', 'rotina',  'Revisão de 8 meses. Avaliação controlo triestado.',
    'Domínio do controlo triestado atingido. Treina atividades bimanuais complexas. Progresso excecional.', 'melhorou', 'presencial', 'realizada'),
(21, 7, 4, '2026-07-08 09:30:00', 'rotina',  'Consulta de revisão anual.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 8 (Sofia Pires) — médico 4 (João Ferreira)
(22, 8, 4, '2025-02-21 16:00:00', 'inicial', 'Programa bilateral pós-AVC. Avaliação neurológica de base.',
    'Défice assimétrico — lado direito mais afetado. Iniciar programa dual com intensidade progressiva.', 'em_avaliacao', 'presencial', 'realizada'),
(23, 8, 4, '2025-12-15 16:00:00', 'rotina',  'Avaliação de fase de manutenção.',
    'Assimetria reduzida significativamente. Progresso bilateral estável. Manter protocolo.', 'estabilizou', 'presencial', 'realizada'),
(24, 8, 4, '2026-09-21 16:00:00', 'rotina',  'Consulta anual de avaliação funcional.', NULL, NULL, 'video', 'agendada'),
-- Utente 9 (António Silva) — médico 5 (Catarina Neves)
(25, 9, 5, '2025-02-24 10:00:00', 'inicial', 'Avaliação pós-operatória joelho direito. Protocolo de fortalecimento.',
    'Boa preservação muscular quadricipital. Protocolo de fortalecimento progressivo prescrito.', 'em_avaliacao', 'presencial', 'realizada'),
(26, 9, 5, '2026-01-20 10:00:00', 'rotina',  'Revisão de 10 meses pós-cirurgia.',
    'Recuperação funcional excelente. Marcha normalizada. Retomou atividade profissional.', 'melhorou', 'presencial', 'realizada'),
(27, 9, 5, '2026-07-24 10:00:00', 'alta',    'Consulta de alta definitiva.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 10 (Inês Fernandes) — médico 5 (Catarina Neves)
(28,10, 5, '2025-02-26 09:00:00', 'inicial', 'Paralisia cerebral — candidatura a programa mioelétrico gamificado.',
    'Controlo voluntário presente bilateral. Cooperação excelente. Iniciar programa gamificado.', 'em_avaliacao', 'presencial', 'realizada'),
(29,10, 5, '2025-11-18 09:00:00', 'rotina',  'Revisão de 9 meses. Avaliação de desenvolvimento.',
    'Progresso consistente. Integração escolar melhorada. Manter programa de longa duração.', 'melhorou', 'presencial', 'realizada'),
(30,10, 5, '2026-08-26 09:30:00', 'rotina',  'Consulta de avaliação anual.', NULL, NULL, 'presencial', 'agendada'),
-- Utente 11 (Lucas Rodrigues) — médico 6 (Rui Baptista)
(31,11, 6, '2025-03-01 11:00:00', 'inicial', 'AVC lacunar — avaliação de espasticidade e potencial reabilitativo.',
    'Espasticidade grau 1. Controlo voluntário preservado. Bom candidato a biofeedback mioelétrico.', 'em_avaliacao', 'presencial', 'realizada'),
(32,11, 6, '2025-12-08 11:00:00', 'rotina',  'Revisão de 9 meses de reabilitação.',
    'Espasticidade reduzida. Percentagem catch_game de 58% para 72% — melhoria significativa.', 'melhorou', 'presencial', 'realizada'),
(33,11, 6, '2026-09-01 11:00:00', 'rotina',  'Consulta de seguimento.', NULL, NULL, 'video', 'agendada'),
-- Utente 12 (Diana Alves) — médico 6 (Rui Baptista)
(34,12, 6, '2025-03-03 14:00:00', 'inicial', 'Lesão medular D8 — avaliação de funcionalidade membro superior.',
    'Função membro superior conservada. Iniciar programa intensivo com vista a alta clínica.', 'em_avaliacao', 'presencial', 'realizada'),
(35,12, 6, '2026-02-10 14:00:00', 'rotina',  'Avaliação de 11 meses. Preparação para alta.',
    'Objetivos do programa atingidos. Independência funcional alcançada. Agendar alta para Maio 2026.', 'melhorou', 'presencial', 'realizada'),
(36,12, 6, '2026-05-12 14:00:00', 'alta',    'Consulta de alta clínica definitiva.',
    'Alta formalizada. Programa domiciliar autónomo entregue. Reavaliação em 6 meses se necessário.', 'melhorou', 'presencial', 'realizada');

-- ============================================================
-- SESSÕES DE TREINO (4 por utente = 48 total)
-- Notas de sessão: s1 calibração → concluida
--                 s2,s3 jogo → concluida  (têm metricas)
--                 s4 jogo → agendada (futuro)
-- Dispositivos: disp=2 (utente 1, emprestado)
--               disp=4 (usado antes de perdido Jan 2026)
--               disp=5 (usado antes de avaria Abr 2026)
--               disp=1 (disponível na clínica)
-- ============================================================
INSERT INTO sessoes
    (id, utente_id, tecnico_id, dispositivo_id, jogo_id,
     data_hora, duracao_min, categoria, objetivo_sessao, modalidade, estado) VALUES
-- === Utente 1 (João Santos) | técnico 7 (Miguel) | jogo catch_game(1) | disp=2 ===
(1,  1, 7, 2, NULL, '2025-02-10 09:00:00', 45, 'calibracao',
    'Calibração inicial do dispositivo domiciliar. Determinar threshold de ativação.', 'presencial', 'concluida'),
(2,  1, 7, 2, 1,   '2025-04-14 09:30:00', 60, 'jogo',
    'Sessão catch_game nível mínimo. Objetivo: ≥35% percentagem final.', 'presencial', 'concluida'),
(3,  1, 7, 2, 1,   '2025-10-20 09:30:00', 60, 'jogo',
    'Catch_game — consolidação de ganhos. Objetivo: ≥45%.', 'presencial', 'concluida'),
(4,  1, 7, 2, 1,   '2026-07-07 09:00:00', NULL, 'jogo',
    'Sessão de avaliação semestral com jogo.', 'presencial', 'agendada'),
-- === Utente 2 (Maria Oliveira) | técnico 8 (Inês) | jogo claw_game(2) | disp=1 ===
(5,  2, 8, 1, NULL, '2025-02-10 14:00:00', 40, 'calibracao',
    'Calibração de sinal mioelétrico residual transradial. Definir zonas de ativação.', 'presencial', 'concluida'),
(6,  2, 8, 1, 2,   '2025-05-06 14:00:00', 55, 'jogo',
    'Claw_game — treino de dois limiares de força para controlo de prótese.', 'presencial', 'concluida'),
(7,  2, 8, 1, 2,   '2025-11-18 14:30:00', 55, 'jogo',
    'Claw_game — avaliação de progresso. Objetivo: ≥50%.', 'presencial', 'concluida'),
(8,  2, 8, 1, 2,   '2026-07-14 14:00:00', NULL, 'jogo',
    'Sessão de avaliação pré-alta.', 'presencial', 'agendada'),
-- === Utente 3 (Pedro Ferreira) | técnico 7 (Miguel) | jogo claw_game(2) | disp=4/1 ===
(9,  3, 7, 4, NULL, '2025-02-12 10:00:00', 45, 'calibracao',
    'Calibração bilateral C5-C6. Definir zonas ativação para ambas as mãos.', 'presencial', 'concluida'),
(10, 3, 7, 4, 2,   '2025-06-09 10:00:00', 60, 'jogo',
    'Claw_game bilateral — avaliação de controlo bilateral C5-C6.', 'presencial', 'concluida'),
(11, 3, 7, 1, 2,   '2026-03-02 10:30:00', 60, 'jogo',
    'Claw_game — avaliação semestral. Verificar progressos pós intensificação.', 'presencial', 'concluida'),
(12, 3, 7, 1, 2,   '2026-07-20 10:00:00', NULL, 'jogo',
    'Sessão de avaliação funcional trimestral.', 'presencial', 'agendada'),
-- === Utente 4 (Ana Sousa) | técnico 9 (Carlos) | jogo catch_game(1) | disp=4/1 ===
(13, 4, 9, 4, NULL, '2025-02-14 11:00:00', 40, 'calibracao',
    'Calibração com limiar baixo. Adaptar a padrão espástico de PC.', 'presencial', 'concluida'),
(14, 4, 9, 4, 1,   '2025-07-07 11:00:00', 50, 'jogo',
    'Catch_game — controlo voluntário seletivo. Limiar 15% da força máxima.', 'presencial', 'concluida'),
(15, 4, 9, 1, 1,   '2026-02-09 11:00:00', 50, 'jogo',
    'Catch_game — avaliação de manutenção. Verificar regressão de espasticidade.', 'presencial', 'concluida'),
(16, 4, 9, 1, 1,   '2026-07-27 11:00:00', NULL, 'jogo',
    'Sessão de avaliação semestral.', 'presencial', 'agendada'),
-- === Utente 5 (Carlos Lima) | técnico 8 (Inês) | jogo catch_game(1) | disp=4/1 ===
(17, 5, 8, 4, NULL, '2025-02-17 15:00:00', 45, 'calibracao',
    'Calibração inicial pós-AVC. Identificar músculos ativos para controlo on/off.', 'presencial', 'concluida'),
(18, 5, 8, 4, 1,   '2025-07-14 15:00:00', 55, 'jogo',
    'Catch_game — recuperação AVC esquerdo. Objetivo inicial: ≥35%.', 'presencial', 'concluida'),
(19, 5, 8, 1, 1,   '2026-03-09 15:30:00', 55, 'jogo',
    'Catch_game — avaliação semestral pós intensificação de treino.', 'presencial', 'concluida'),
(20, 5, 8, 1, 1,   '2026-07-21 15:00:00', NULL, 'jogo',
    'Sessão de avaliação funcional.', 'presencial', 'agendada'),
-- === Utente 6 (Beatriz Costa) | técnico 10 (Beatriz C.) | jogo flappy_trainer(3) | disp=4/1 ===
(21, 6,10, 4, NULL, '2025-02-19 14:00:00', 40, 'calibracao',
    'Calibração nervo mediano. Definir curva de força para controlo proporcional.', 'presencial', 'concluida'),
(22, 6,10, 4, 3,   '2025-08-11 14:00:00', 60, 'jogo',
    'Flappy_trainer — treino controlo proporcional. Objetivo: manter altitude ±15% por 30s.', 'presencial', 'concluida'),
(23, 6,10, 1, 3,   '2026-04-13 14:30:00', 60, 'jogo',
    'Flappy_trainer — avaliação semestral. Verificar progressos de reinervação.', 'presencial', 'concluida'),
(24, 6,10, 1, 3,   '2026-08-24 14:00:00', NULL, 'jogo',
    'Sessão de avaliação pré-alta.', 'presencial', 'agendada'),
-- === Utente 7 (Rui Matos) | técnico 9 (Carlos) | jogo flappy_trainer(3) | disp=5/1 ===
(25, 7, 9, 5, NULL, '2025-02-21 09:00:00', 50, 'calibracao',
    'Calibração transumeral. Definir zonas bicípite/tricípite para controlo triestado.', 'presencial', 'concluida'),
(26, 7, 9, 5, 3,   '2025-08-18 09:00:00', 65, 'jogo',
    'Flappy_trainer — controlo proporcional avançado. Simular controlo de cotovelo protético.', 'presencial', 'concluida'),
(27, 7, 9, 1, 3,   '2026-04-20 09:30:00', 65, 'jogo',
    'Flappy_trainer — avaliação nível máximo. Objetivo: ≥80%.', 'presencial', 'concluida'),
(28, 7, 9, 1, 3,   '2026-08-10 09:00:00', NULL, 'jogo',
    'Sessão de avaliação anual.', 'presencial', 'agendada'),
-- === Utente 8 (Sofia Pires) | técnico 11 (Diogo) | jogo claw_game(2) | disp=5/1 ===
(29, 8,11, 5, NULL, '2025-02-24 16:00:00', 45, 'calibracao',
    'Calibração bilateral AVC. Protocolos separados direito e esquerdo.', 'presencial', 'concluida'),
(30, 8,11, 5, 2,   '2025-09-15 16:00:00', 55, 'jogo',
    'Claw_game bilateral — treino de assimetria. Foco no lado direito mais afetado.', 'presencial', 'concluida'),
(31, 8,11, 1, 2,   '2026-03-30 16:30:00', 55, 'jogo',
    'Claw_game — avaliação de fase de manutenção bilateral.', 'presencial', 'concluida'),
(32, 8,11, 1, 2,   '2026-09-14 16:00:00', NULL, 'jogo',
    'Sessão de avaliação anual.', 'presencial', 'agendada'),
-- === Utente 9 (António Silva) | técnico 10 (Beatriz C.) | jogo catch_game(1) | disp=5/1 ===
(33, 9,10, 5, NULL, '2025-02-26 10:00:00', 40, 'calibracao',
    'Calibração quadricipital — threshold para protocolo pós-op joelho.', 'presencial', 'concluida'),
(34, 9,10, 5, 1,   '2025-06-23 10:00:00', 50, 'jogo',
    'Catch_game adaptado — controlo força quadricipital. Protocolo pós-operatório.', 'presencial', 'concluida'),
(35, 9,10, 1, 1,   '2025-12-08 10:30:00', 50, 'jogo',
    'Catch_game — avaliação final pré-alta. Objetivo: ≥70%.', 'presencial', 'concluida'),
(36, 9,10, 1, 1,   '2026-07-24 10:00:00', NULL, 'jogo',
    'Sessão de confirmação de alta funcional.', 'presencial', 'agendada'),
-- === Utente 10 (Inês Fernandes) | técnico 12 (Helena) | jogo flappy_trainer(3) | disp=1 ===
(37,10,12, 1, NULL, '2025-02-28 09:00:00', 35, 'calibracao',
    'Calibração PC bilateral adaptada. Interface simplificada com reforço visual.', 'presencial', 'concluida'),
(38,10,12, 1, 3,   '2025-08-25 09:00:00', 45, 'jogo',
    'Flappy_trainer modo adaptado para PC. Sessão gamificada com feedback sonoro e visual.', 'presencial', 'concluida'),
(39,10,12, 1, 3,   '2026-03-16 09:30:00', 45, 'jogo',
    'Flappy_trainer — avaliação semestral. Controlo proporcional bilateral.', 'presencial', 'concluida'),
(40,10,12, 1, 3,   '2026-08-31 09:00:00', NULL, 'jogo',
    'Sessão de avaliação anual — integração escolar.', 'presencial', 'agendada'),
-- === Utente 11 (Lucas Rodrigues) | técnico 11 (Diogo) | jogo catch_game(1) | disp=1 ===
(41,11,11, 1, NULL, '2025-03-03 11:00:00', 45, 'calibracao',
    'Calibração AVC lacunar. Threshold adaptado a espasticidade variável.', 'presencial', 'concluida'),
(42,11,11, 1, 1,   '2025-07-28 11:00:00', 55, 'jogo',
    'Catch_game — biofeedback mioelétrico. Treino de inibição da espasticidade.', 'presencial', 'concluida'),
(43,11,11, 1, 1,   '2026-03-23 11:30:00', 55, 'jogo',
    'Catch_game — avaliação semestral. Objetivo: ≥70%.', 'presencial', 'concluida'),
(44,11,11, 1, 1,   '2026-09-07 11:00:00', NULL, 'jogo',
    'Sessão de avaliação anual.', 'presencial', 'agendada'),
-- === Utente 12 (Diana Alves) | técnico 12 (Helena) | jogo claw_game(2) | disp=1 ===
(45,12,12, 1, NULL, '2025-03-05 14:00:00', 40, 'calibracao',
    'Calibração lesão medular D8. Caracterizar função residual membro superior.', 'presencial', 'concluida'),
(46,12,12, 1, 2,   '2025-08-04 14:00:00', 50, 'jogo',
    'Claw_game — treino de preensão funcional. Protocolo intensivo pré-alta.', 'presencial', 'concluida'),
(47,12,12, 1, 2,   '2026-02-02 14:30:00', 50, 'jogo',
    'Claw_game — avaliação de competências funcionais. Verificar objetivos de alta.', 'presencial', 'concluida'),
(48,12,12, 1, 2,   '2026-05-04 14:00:00', 45, 'jogo',
    'Sessão final de avaliação antes de alta definitiva.', 'presencial', 'concluida');

-- ============================================================
-- MÉTRICAS DE SESSÃO (sessões de jogo concluídas)
-- sessao_id | perc | score | passou | tentativas | tendencia
-- tendencia NULL = primeira sessão deste jogo para o utente
-- ============================================================
INSERT INTO metricas_sessao
    (sessao_id, percentagem_final, score_jogo, passou_nivel, n_tentativas, tendencia) VALUES
-- Utente 1 — catch_game
(2,  35.5,  71, 0, 3, NULL),
(3,  48.0,  96, 0, 4, 'melhoria'),
-- Utente 2 — claw_game
(6,  42.0,  84, 0, 3, NULL),
(7,  55.5, 111, 1, 3, 'melhoria'),
-- Utente 3 — claw_game
(10, 48.0,  96, 0, 4, NULL),
(11, 58.0, 116, 1, 4, 'melhoria'),
-- Utente 4 — catch_game
(14, 62.0, 124, 1, 4, NULL),
(15, 62.5, 125, 1, 3, 'estavel'),
-- Utente 5 — catch_game
(18, 38.0,  76, 0, 3, NULL),
(19, 52.0, 104, 1, 4, 'melhoria'),
-- Utente 6 — flappy_trainer
(22, 71.0, 142, 1, 5, NULL),
(23, 68.5, 137, 1, 4, 'regressao'),
-- Utente 7 — flappy_trainer
(26, 44.0,  88, 0, 5, NULL),
(27, 81.0, 162, 1, 5, 'melhoria'),
-- Utente 8 — claw_game
(30, 78.0, 156, 1, 4, NULL),
(31, 82.5, 165, 1, 4, 'melhoria'),
-- Utente 9 — catch_game
(34, 29.0,  58, 0, 3, NULL),
(35, 71.5, 143, 1, 3, 'melhoria'),
-- Utente 10 — flappy_trainer
(38, 65.0, 130, 1, 4, NULL),
(39, 65.5, 131, 1, 4, 'estavel'),
-- Utente 11 — catch_game
(42, 58.0, 116, 1, 4, NULL),
(43, 72.0, 144, 1, 4, 'melhoria'),
-- Utente 12 — claw_game
(46, 85.0, 170, 1, 5, NULL),
(47, 89.0, 178, 1, 5, 'melhoria'),
(48, 92.0, 184, 1, 5, 'melhoria');

-- ============================================================
-- FATURAS (3 por utente = 36 total)
-- Estados: paga, pendente (venc. futuro), vencida (venc. passado), inativa (ativo=0)
-- ============================================================
INSERT INTO faturas
    (id, numero, utente_id, tipo_servico, seguradora_id, valor_eur,
     paga, metodo_pagamento, data_pagamento,
     data_emissao, data_vencimento, ativo, notas) VALUES
-- Utente 1 (Multicare seg=3)
(1,  'FT2025/001', 1, 'avaliacao_emg',      3,  50.00, 1, 'multibanco',    '2025-02-15', '2025-02-10', '2025-03-10', 1, NULL),
(2,  'FT2025/002', 1, 'sessao_jogo',         3,  38.00, 0, NULL,            NULL,         '2026-04-20', '2026-05-20', 1, 'Aguarda pagamento'),
(3,  'FT2025/003', 1, 'consulta_medica',     3,  68.00, 0, NULL,            NULL,         '2025-12-01', '2026-01-01', 1, 'Fatura vencida — contactar utente'),
-- Utente 2 (SNS seg=2)
(4,  'FT2025/004', 2, 'avaliacao_emg',       2,   8.00, 1, 'multibanco',    '2025-02-20', '2025-02-15', '2025-03-15', 1, NULL),
(5,  'FT2025/005', 2, 'treino_mioeletrico',  2,   8.00, 1, 'multibanco',    '2025-06-25', '2025-06-20', '2025-07-20', 1, NULL),
(6,  'FT2025/006', 2, 'sessao_jogo',         2,   5.00, 0, NULL,            NULL,         '2026-01-10', '2026-02-10', 0, 'Inativa — utente isentada por assistência social'),
-- Utente 3 (AdvanceCare seg=4)
(7,  'FT2025/007', 3, 'avaliacao_emg',       4,  48.00, 1, 'seguro',        '2025-02-20', '2025-02-15', '2025-03-15', 1, NULL),
(8,  'FT2025/008', 3, 'treino_mioeletrico',  4,  52.00, 0, NULL,            NULL,         '2026-04-15', '2026-05-15', 1, 'Pendente — AdvanceCare aguarda relatório'),
(9,  'FT2025/009', 3, 'consulta_medica',     4,  64.00, 0, NULL,            NULL,         '2025-10-01', '2025-11-01', 1, 'Fatura vencida — seguradora a processar'),
-- Utente 4 (Particular seg=1)
(10, 'FT2025/010', 4, 'avaliacao_emg',       1,  60.00, 1, 'transferência', '2025-02-25', '2025-02-20', '2025-03-20', 1, NULL),
(11, 'FT2025/011', 4, 'consulta_medica',     1,  80.00, 1, 'cartão',        '2025-09-05', '2025-08-25', '2025-09-25', 1, NULL),
(12, 'FT2025/012', 4, 'sessao_biofeedback',  1,  55.00, 0, NULL,            NULL,         '2026-01-05', '2026-01-20', 0, 'Inativa — cancelada a pedido do utente'),
-- Utente 5 (Médis seg=5)
(13, 'FT2025/013', 5, 'avaliacao_emg',       5,  45.00, 1, 'seguro',        '2025-03-01', '2025-02-24', '2025-03-24', 1, NULL),
(14, 'FT2025/014', 5, 'sessao_jogo',         5,  34.00, 0, NULL,            NULL,         '2026-05-10', '2026-06-10', 1, 'Pendente — aguarda processamento Médis'),
(15, 'FT2025/015', 5, 'treino_mioeletrico',  5,  49.00, 0, NULL,            NULL,         '2025-11-15', '2025-12-15', 1, 'Fatura vencida — a regularizar'),
-- Utente 6 (SNS seg=2)
(16, 'FT2025/016', 6, 'avaliacao_emg',       2,   8.00, 1, 'multibanco',    '2025-03-01', '2025-02-24', '2025-03-24', 1, NULL),
(17, 'FT2025/017', 6, 'treino_mioeletrico',  2,   8.00, 1, 'multibanco',    '2025-11-15', '2025-11-10', '2025-12-10', 1, NULL),
(18, 'FT2025/018', 6, 'relatorio_clinico',   2,   5.00, 0, NULL,            NULL,         '2026-02-28', '2026-02-28', 0, 'Inativa — utente transferida para SNS outra área'),
-- Utente 7 (Multicare seg=3)
(19, 'FT2025/019', 7, 'avaliacao_emg',       3,  50.00, 1, 'seguro',        '2025-03-05', '2025-03-01', '2025-04-01', 1, NULL),
(20, 'FT2025/020', 7, 'sessao_jogo',         3,  38.00, 0, NULL,            NULL,         '2026-05-20', '2026-06-20', 1, 'Pendente — Multicare a validar'),
(21, 'FT2025/021', 7, 'avaliacao_funcional', 3,  59.00, 0, NULL,            NULL,         '2025-12-10', '2026-01-10', 1, 'Fatura vencida — seguro a processar'),
-- Utente 8 (Allianz seg=6)
(22, 'FT2025/022', 8, 'avaliacao_emg',       6,  54.00, 1, 'seguro',        '2025-03-08', '2025-03-03', '2025-04-03', 1, NULL),
(23, 'FT2025/023', 8, 'treino_mioeletrico',  6,  58.00, 1, 'seguro',        '2026-01-05', '2025-12-20', '2026-01-20', 1, NULL),
(24, 'FT2025/024', 8, 'sessao_biofeedback',  6,  49.00, 0, NULL,            NULL,         '2026-04-01', '2026-04-01', 0, 'Inativa — suspensa por litígio com seguradora'),
-- Utente 9 (Particular seg=1)
(25, 'FT2026/001', 9, 'avaliacao_emg',       1,  60.00, 1, 'numerário',     '2025-03-05', '2025-03-01', '2025-04-01', 1, NULL),
(26, 'FT2026/002', 9, 'sessao_jogo',         1,  45.00, 0, NULL,            NULL,         '2026-05-15', '2026-07-15', 1, 'Pendente — emitida para consulta de alta'),
(27, 'FT2026/003', 9, 'relatorio_clinico',   1,  30.00, 1, 'transferência', '2026-02-15', '2026-01-25', '2026-02-25', 1, NULL),
-- Utente 10 (Fidelidade seg=7)
(28, 'FT2026/004',10, 'avaliacao_emg',       7,  51.00, 1, 'seguro',        '2025-03-10', '2025-03-05', '2025-04-05', 1, NULL),
(29, 'FT2026/005',10, 'sessao_jogo',         7,  38.00, 0, NULL,            NULL,         '2026-05-25', '2026-07-25', 1, 'Pendente — aprovação Fidelidade em curso'),
(30, 'FT2026/006',10, 'consulta_medica',     7,  68.00, 0, NULL,            NULL,         '2025-12-20', '2026-01-20', 1, 'Fatura vencida — seguradora solicitou documentação adicional'),
-- Utente 11 (AdvanceCare seg=4)
(31, 'FT2026/007',11, 'avaliacao_emg',       4,  48.00, 1, 'seguro',        '2025-03-12', '2025-03-08', '2025-04-08', 1, NULL),
(32, 'FT2026/008',11, 'treino_mioeletrico',  4,  52.00, 1, 'seguro',        '2026-01-15', '2025-12-30', '2026-01-30', 1, NULL),
(33, 'FT2026/009',11, 'consulta_medica',     4,  64.00, 1, 'seguro',        '2026-01-20', '2026-01-08', '2026-02-08', 1, NULL),
-- Utente 12 (SNS seg=2)
(34, 'FT2026/010',12, 'avaliacao_emg',       2,   8.00, 1, 'multibanco',    '2025-03-12', '2025-03-08', '2025-04-08', 1, NULL),
(35, 'FT2026/011',12, 'sessao_jogo',         2,   5.00, 0, NULL,            NULL,         '2026-05-10', '2026-07-10', 1, 'Pendente — emitida pós-alta para sessão final'),
(36, 'FT2026/012',12, 'relatorio_clinico',   2,   5.00, 0, NULL,            NULL,         '2026-01-15', '2026-01-15', 0, 'Inativa — relatório integrado em processo de alta gratuito');

-- ============================================================
-- LINHAS DE FATURA (detalhe de algumas faturas principais)
-- ============================================================
INSERT INTO fatura_linhas (fatura_id, tipo_servico, descricao, quantidade, preco_unit, total_linha) VALUES
-- FT2025/001 (utente 1, Multicare, avaliacao_emg)
(1,  'avaliacao_emg',      'Avaliação Eletromiográfica — sessão inicial + relatório', 1, 50.00, 50.00),
-- FT2025/004 (utente 2, SNS, avaliacao_emg)
(4,  'avaliacao_emg',      'Avaliação EMG — taxa moderadora SNS',                    1,  8.00,  8.00),
-- FT2025/007 (utente 3, AdvanceCare, avaliacao_emg)
(7,  'avaliacao_emg',      'Avaliação Eletromiográfica Bilateral C5-C6',             1, 48.00, 48.00),
-- FT2025/010 (utente 4, Particular, avaliacao_emg)
(10, 'avaliacao_emg',      'Avaliação EMG Particular — paralisia cerebral',          1, 60.00, 60.00),
-- FT2025/011 (utente 4, Particular, consulta_medica)
(11, 'consulta_medica',    'Consulta Médica — revisão semestral',                    1, 80.00, 80.00),
-- FT2025/013 (utente 5, Médis, avaliacao_emg)
(13, 'avaliacao_emg',      'Avaliação EMG pós-AVC — comparticipação Médis',         1, 45.00, 45.00),
-- FT2025/016 (utente 6, SNS, avaliacao_emg)
(16, 'avaliacao_emg',      'Avaliação EMG — taxa moderadora SNS',                   1,  8.00,  8.00),
-- FT2025/017 (utente 6, SNS, treino_mioeletrico)
(17, 'treino_mioeletrico', 'Treino Mioelétrico — sessão supervisionada',             1,  8.00,  8.00),
-- FT2025/019 (utente 7, Multicare, avaliacao_emg)
(19, 'avaliacao_emg',      'Avaliação EMG Transumeral — protocolo triestado',        1, 50.00, 50.00),
-- FT2025/022 (utente 8, Allianz, avaliacao_emg)
(22, 'avaliacao_emg',      'Avaliação EMG Bilateral — programa intensivo AVC',       1, 54.00, 54.00),
-- FT2025/023 (utente 8, Allianz — linha múltipla)
(23, 'treino_mioeletrico', 'Treino Mioelétrico Bilateral — 4 sessões',               4, 14.50, 58.00),
-- FT2026/001 (utente 9, Particular, avaliacao_emg)
(25, 'avaliacao_emg',      'Avaliação EMG pós-op joelho — quadricípite',             1, 60.00, 60.00),
-- FT2026/004 (utente 10, Fidelidade, avaliacao_emg — linha múltipla)
(28, 'avaliacao_emg',      'Avaliação EMG Bilateral — paralisia cerebral',           1, 51.00, 51.00),
-- FT2026/007 (utente 11, AdvanceCare, avaliacao_emg)
(31, 'avaliacao_emg',      'Avaliação EMG AVC Lacunar — espasticidade',              1, 48.00, 48.00),
-- FT2026/008 (utente 11, AdvanceCare, treino — múltiplas sessões)
(32, 'treino_mioeletrico', 'Treino Mioelétrico — sessões Julho a Dezembro 2025',     4, 13.00, 52.00),
-- FT2026/009 (utente 11, AdvanceCare, consulta_medica)
(33, 'consulta_medica',    'Consulta Médica — revisão 9 meses de reabilitação',      1, 64.00, 64.00),
-- FT2026/010 (utente 12, SNS, avaliacao_emg)
(34, 'avaliacao_emg',      'Avaliação EMG — taxa moderadora SNS',                    1,  8.00,  8.00);

-- ============================================================
-- MENSAGENS INTERNAS
-- ============================================================
INSERT INTO mensagens (id, remetente_id, destinatario_id, assunto, corpo, tipo, lida, enviada_em) VALUES
(1,  1,  3,  'Novo utente atribuído — João Santos',
    'Boa tarde, Dra. Ana Silva. O utente João Santos (AVC isquémico, mão direita) foi-lhe atribuído. Por favor, reveja o processo clínico e agende a primeira consulta. O técnico Miguel Santos irá fazer a calibração inicial.',
    'geral', 1, '2025-02-01 10:30:00'),
(2,  3,  9,  'Calibração João Santos — Notas',
    'Miguel, após a primeira consulta confirmei que o threshold inicial do João deverá ser baixo (≈20% da força máxima). Por favor, ajuste na calibração. Obrigada.',
    'sumario_clinico', 1, '2025-02-05 11:00:00'),
(3,  1,  4,  'Novo utente — Pedro Ferreira (C5-C6)',
    'Ricardo, o Dr. Pedro Costa tem novo utente Pedro Ferreira com lesão medular C5-C6. Prevê-se programa intensivo bilateral. O técnico Miguel Santos está também neste caso.',
    'geral', 1, '2025-02-05 12:00:00'),
(4,  3,  1,  'Relatório — João Santos 3 meses',
    'Sofia, junto envio nota de progresso do João Santos após 3 meses. Está acima das expetativas — passou de 35% para 48% no catch_game. Programa a continuar sem alterações.',
    'sumario_clinico', 1, '2025-05-12 11:30:00'),
(5,  4,  6,  'Pedido de consulta urgente — Pedro Ferreira',
    'Dr. Ferreira, peço consulta de avaliação adicional do Pedro Ferreira. Está a apresentar espasmos noturnos que podem comprometer o programa mioelétrico. Pode colaborar na avaliação neurológica?',
    'alerta_sistema', 0, '2025-09-15 09:00:00'),
(6,  2,  1,  'Fatura vencida — João Santos FT2025/003',
    'Sofia, a fatura FT2025/003 do João Santos (68€, Multicare) está vencida desde Janeiro. Por favor, contactar o utente e iniciar procedimento de cobrança.',
    'alerta_sistema', 0, '2026-02-01 09:00:00'),
(7,  6,  2,  'Alta clínica — Diana Alves',
    'Ricardo, a Diana Alves atingiu todos os objetivos clínicos. Consulta de alta marcada para 12 de Maio. Por favor, prepare o processo administrativo de encerramento e o relatório final para o SNS.',
    'geral', 0, '2026-04-20 14:00:00'),
(8,  5,  3,  'Transferência de utente — António Silva',
    'Dra. Ana, o António Silva deverá ser transferido para fisioterapia convencional após a nossa alta. Pode emitir carta de referenciação? Obrigada pela colaboração.',
    'sumario_clinico', 0, '2026-06-01 10:00:00');

-- ============================================================
-- NOTIFICAÇÕES
-- ============================================================
INSERT INTO notificacoes (utilizador_id, tipo, titulo, corpo, url, lida, criado_em) VALUES
-- Admins
(1, 'info',    'Sistema inicializado',
    'Base de dados de produção RehabLink v2.0 carregada com sucesso. 26 utilizadores, 12 utentes, 5 dispositivos.',
    '/private/admin/dashboard.php', 1, '2025-01-10 08:00:00'),
(2, 'info',    'Sistema pronto',
    'Todos os perfis configurados. Pode iniciar operações.', '/private/admin/dashboard.php', 1, '2025-01-10 08:05:00'),
(2, 'info',    'Fatura vencida — FT2025/003',
    'A fatura FT2025/003 de João Santos (68€) está vencida.', '/private/admin/faturacao/fatura.php?num=FT2025/003', 0, '2026-01-02 09:00:00'),
(2, 'info',    'Alta clínica — Diana Alves',
    'A utente Diana Alves recebeu alta clínica em 15 de Maio de 2026.', '/private/admin/utilizadores/lista_utilizadores.php', 0, '2026-05-15 15:00:00'),
-- Médicos
(3, 'sessao',  'Nova consulta agendada — João Santos',
    'Consulta inicial agendada para 05/02/2025 às 09:00.', '/private/medico/consultas/agenda.php', 1, '2025-02-01 10:05:00'),
(4, 'sessao',  'Novo utente atribuído — Pedro Ferreira',
    'O utente Pedro Ferreira (C5-C6) foi-lhe atribuído para acompanhamento médico.', '/private/medico/pacientes/lista_pacientes.php', 1, '2025-02-05 10:30:00'),
(6, 'info',    'Alta clínica — Diana Alves',
    'Pode agora processar a alta definitiva da Diana Alves e emitir o relatório final.', '/private/medico/pacientes/perfil_paciente.php?id=12', 0, '2026-05-12 14:30:00'),
-- Técnicos
(9, 'info',    'Novo utente atribuído — João Santos',
    'O utente João Santos foi-lhe atribuído para acompanhamento técnico.', '/private/tecnico/index_F.php', 1, '2025-02-01 10:05:00'),
(9, 'info',    'Novo utente atribuído — Pedro Ferreira',
    'O utente Pedro Ferreira (C5-C6) foi-lhe atribuído para acompanhamento técnico.', '/private/tecnico/index_F.php', 1, '2025-02-05 10:05:00'),
(12, 'info',   'Alta clínica — Diana Alves',
    'A sua utente Diana Alves recebeu alta. Arquivar os registos de sessão.', '/private/tecnico/index_F.php', 0, '2026-05-15 15:05:00'),
-- Utentes
(15, 'sessao', 'Nova consulta agendada',
    'Foi agendada uma consulta para 05/02/2025 às 09:00.', '/private/utente/sessoes_consultas.php', 1, '2025-02-01 10:05:00'),
(15, 'sessao', 'Sessão de calibração agendada',
    'Sessão de calibração marcada para 10/02/2025 às 09:00.', '/private/utente/sessoes_consultas.php', 1, '2025-02-05 11:00:00'),
(26, 'info',   'Alta clínica confirmada',
    'A sua alta clínica foi confirmada em 15 de Maio de 2026. Parabéns pela evolução!', '/private/utente/sessoes_consultas.php', 1, '2026-05-15 15:00:00');

-- ============================================================
-- AUDITORIA (registo de ações de sistema)
-- ============================================================
INSERT INTO auditoria (utilizador_id, nome, perfil, acao, entidade, entidade_id, detalhe, ip, criado_em) VALUES
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Utilizador',  1,  'Admin Sofia Mendes criado',                                        '192.168.1.1', '2025-01-10 08:00:00'),
(2,  'Ricardo Sousa',        'admin',   'CRIAR',      'Utilizador',  2,  'Admin Ricardo Sousa criado',                                       '192.168.1.1', '2025-01-10 08:05:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Utilizador',  3,  'Médico Dra. Ana Silva registada',                                  '192.168.1.1', '2025-01-12 09:00:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Utilizador',  9,  'Técnico Miguel Santos registado',                                  '192.168.1.1', '2025-01-15 09:00:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Utilizador',  15, 'Utente João Santos admitido. Médico: Dra. Ana Silva. Técnico: Miguel Santos.', '192.168.1.10', '2025-02-01 10:00:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Utilizador',  16, 'Utente Maria Oliveira admitida.',                                  '192.168.1.10', '2025-02-03 10:00:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Utilizador',  17, 'Utente Pedro Ferreira admitido.',                                  '192.168.1.10', '2025-02-05 10:00:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Dispositivo', 1,  'Dispositivo EMG-0001 registado. Estado: disponivel.',              '192.168.1.10', '2025-01-20 09:00:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Dispositivo', 2,  'Dispositivo EMG-0002 registado. Estado: disponivel.',              '192.168.1.10', '2025-01-20 09:05:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Dispositivo', 3,  'Dispositivo EMG-0003 registado. Estado: disponivel.',              '192.168.1.10', '2025-01-20 09:10:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Dispositivo', 4,  'Dispositivo EMG-0004 registado. Estado: disponivel.',              '192.168.1.10', '2025-01-20 09:15:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Dispositivo', 5,  'Dispositivo EMG-0005 registado. Estado: disponivel.',              '192.168.1.10', '2025-01-20 09:20:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Emprestimo',  1,  'EMG-0002 emprestado ao utente João Santos. Técnico: Miguel Santos.','192.168.1.10','2026-05-20 10:05:00'),
(9,  'Miguel Santos',        'tecnico', 'ATUALIZAR',  'Dispositivo', 4,  'Estado alterado para perdido. Dispositivo não localizado após sessão.', '192.168.1.20', '2026-01-14 17:00:00'),
(9,  'Miguel Santos',        'tecnico', 'ATUALIZAR',  'Dispositivo', 3,  'Estado alterado para avariado. Falha de firmware após atualização.', '192.168.1.20', '2026-03-01 11:00:00'),
(11, 'Carlos Pinto',         'tecnico', 'ATUALIZAR',  'Dispositivo', 5,  'Estado alterado para danificado. Queda acidental durante sessão.', '192.168.1.20', '2026-04-19 10:05:00'),
(3,  'Dra. Ana Silva',       'medico',  'CRIAR',      'Consulta',    1,  'Consulta inicial agendada — João Santos.',                          '192.168.1.30', '2025-02-01 10:10:00'),
(3,  'Dra. Ana Silva',       'medico',  'CRIAR',      'Programa',    1,  'Programa de tratamento criado para João Santos.',                  '192.168.1.30', '2025-02-05 09:30:00'),
(4,  'Dr. Pedro Costa',      'medico',  'CRIAR',      'Programa',    3,  'Programa de tratamento bilateral C5-C6 criado — Pedro Ferreira.',  '192.168.1.31', '2025-02-10 09:30:00'),
(6,  'Dr. Rui Baptista',     'medico',  'CRIAR',      'Consulta',    36, 'Alta clínica da Diana Alves formalizada.',                         '192.168.1.33', '2026-05-12 14:30:00'),
(1,  'Sofia Mendes',         'admin',   'CRIAR',      'Fatura',      1,  'Fatura FT2025/001 emitida — João Santos — Multicare — 50€.',       '192.168.1.10', '2025-02-10 10:00:00'),
(1,  'Sofia Mendes',         'admin',   'ATUALIZAR',  'Fatura',      1,  'Fatura FT2025/001 marcada como paga — Multibanco.',                '192.168.1.10', '2025-02-15 09:00:00'),
(2,  'Ricardo Sousa',        'admin',   'ATUALIZAR',  'Fatura',      6,  'Fatura FT2025/006 inativada — assistência social SNS.',            '192.168.1.11', '2026-01-10 10:00:00'),
(2,  'Ricardo Sousa',        'admin',   'ATUALIZAR',  'Utilizador',  26, 'Utente Diana Alves — registo de alta clínica em 15/05/2026.',      '192.168.1.11', '2026-05-15 15:00:00'),
(15, 'João Santos',          'utente',  'LOGIN',      NULL,          NULL,'Login bem-sucedido.',                                              '85.244.10.5',  '2026-06-15 08:30:00'),
(16, 'Maria Oliveira',       'utente',  'LOGIN',      NULL,          NULL,'Login bem-sucedido.',                                              '91.152.33.8',  '2026-06-14 14:00:00'),
(3,  'Dra. Ana Silva',       'medico',  'LOGIN',      NULL,          NULL,'Login bem-sucedido.',                                              '192.168.1.30', '2026-06-17 08:00:00'),
(9,  'Miguel Santos',        'tecnico', 'LOGIN',      NULL,          NULL,'Login bem-sucedido.',                                              '192.168.1.20', '2026-06-17 08:15:00'),
(1,  'Sofia Mendes',         'admin',   'LOGIN',      NULL,          NULL,'Login bem-sucedido.',                                              '192.168.1.10', '2026-06-17 07:55:00');

-- ============================================================
-- BACKOFFICE — conteúdo da landing page
-- ============================================================
INSERT INTO backoffice_conteudo (chave, valor) VALUES
('quem_somos_titulo',     'Tecnologia ao serviço da reabilitação')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('quem_somos_texto',      'A RehabLink é uma plataforma inovadora de telereabilitação que une dispositivos de força FSR406 a software intuitivo para profissionais de saúde e utentes de prótese mioelétrica. Combinamos biofeedback, gamificação e monitorização remota para tornar a reabilitação mais eficaz e acessível.')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('missao_titulo',         'A Nossa Missão')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('missao_texto',          'Capacitar profissionais de saúde com dados em tempo real para personalizar e otimizar a reabilitação de cada utente de prótese mioelétrica de membro superior, reduzindo o tempo de adaptação e melhorando os resultados funcionais.')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_nome',         'RehabLink — Centro de Reabilitação Mioelétrica')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_morada',       'Rua Dr. António Bernardino de Almeida, 431 | 4200-072 Porto')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_email',        'clinica@rehablink.pt')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_telefone',     '222 001 234')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_horario_semana', 'Segunda a Sexta: 08h00 – 20h00')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_horario_sabado', 'Sábado: 09h00 – 13h00')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO backoffice_conteudo (chave, valor) VALUES
('contacto_horario_domingo', 'Domingo: Encerrado')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- ============================================================
-- FIM DO SEED
-- ============================================================
-- Resumo do que foi inserido:
--   utilizadores ......... 26 (2 admin, 6 médicos, 6 técnicos, 12 utentes)
--   profissionais ........ 12
--   utentes .............. 12 (fases: 8 ativo, 2 manutencao, 1 avaliacao, 1 alta)
--   dispositivos .......... 5 (disponivel, emprestado, avariado, perdido, danificado)
--   emprestimos ........... 1 (EMG-0002 → João Santos)
--   programas_tratamento .. 12
--   consultas ............ 36 (3/utente; mix: 12 iniciais, 12 rotina realiz., 10 agendadas, 2 alta)
--   sessoes .............. 48 (4/utente; 3 concluídas + 1 agendada)
--   metricas_sessao ...... 25 (sessões de jogo concluídas)
--   faturas .............. 36 (3/utente; 18 pagas, 8 pendentes, 6 vencidas, 4 inativas)
--   fatura_linhas ........ 17
--   mensagens ............. 8
--   notificacoes ......... 17
--   auditoria ............ 29
-- =============================================================
