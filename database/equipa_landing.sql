-- =============================================================
-- PATCH: Equipa da landing page
-- Executar no phpMyAdmin: base de dados sistema_mioeletrico
-- Password de todos os membros: Rehablink2026!
-- =============================================================

USE sistema_mioeletrico;

-- ============================================================
-- EQUIPA MÉDICA (perfil: medico)
-- ============================================================
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES
('Dr. António Ribeiro',  'antonio.ribeiro@rehablink.pt',  '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'medico'),
('Dra. Marta Fernandes', 'marta.fernandes@rehablink.pt',  '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'medico'),
('Dr. Ricardo Silva',    'ricardo.silva@rehablink.pt',    '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'medico'),
('Dra. Ana Almeida',     'ana.almeida@rehablink.pt',      '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'medico'),
('Dr. João Lopes',       'joao.lopes@rehablink.pt',       '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'medico');

INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Fisiatria', 'RehabLink' FROM utilizadores WHERE email = 'antonio.ribeiro@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Fisioterapia Neurológica', 'RehabLink' FROM utilizadores WHERE email = 'marta.fernandes@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Fisioterapia Ortopédica', 'RehabLink' FROM utilizadores WHERE email = 'ricardo.silva@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Fisioterapia Respiratória', 'RehabLink' FROM utilizadores WHERE email = 'ana.almeida@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Fisioterapia Desportiva', 'RehabLink' FROM utilizadores WHERE email = 'joao.lopes@rehablink.pt';

-- ============================================================
-- EQUIPA TÉCNICA (perfil: tecnico)
-- ============================================================
INSERT IGNORE INTO utilizadores (nome, email, password_hash, perfil) VALUES
('Ana Silva',       'ana.silva@rehablink.pt',       '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Bruno Ferreira',  'bruno.ferreira@rehablink.pt',  '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Carla Santos',    'carla.santos@rehablink.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Daniel Costa',    'daniel.costa@rehablink.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Eduarda Martins', 'eduarda.martins@rehablink.pt',  '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Filipe Gomes',    'filipe.gomes@rehablink.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Gabriela Rocha',  'gabriela.rocha@rehablink.pt',   '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Hugo Pereira',    'hugo.pereira@rehablink.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('Inês Almeida',    'ines.almeida@rehablink.pt',     '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico'),
('João Rodrigues',  'joao.rodrigues@rehablink.pt',   '$2y$12$9Z5xNWG79zBLWlFDsl3R0uy0n/KJNMwOucqrs9SsPThal35SoTJxq', 'tecnico');

INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnica de Reabilitação',  'RehabLink — Unidade Lisboa'  FROM utilizadores WHERE email = 'ana.silva@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnico de Reabilitação',  'RehabLink — Unidade Porto'   FROM utilizadores WHERE email = 'bruno.ferreira@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnica de Reabilitação',  'RehabLink — Unidade Porto'   FROM utilizadores WHERE email = 'carla.santos@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Auxiliar de Reabilitação', 'RehabLink — Unidade Lisboa'  FROM utilizadores WHERE email = 'daniel.costa@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnica de Reabilitação',  'RehabLink — Unidade Coimbra' FROM utilizadores WHERE email = 'eduarda.martins@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnico de Reabilitação',  'RehabLink — Unidade Lisboa'  FROM utilizadores WHERE email = 'filipe.gomes@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnica de Reabilitação',  'RehabLink — Unidade Lisboa'  FROM utilizadores WHERE email = 'gabriela.rocha@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Auxiliar de Reabilitação', 'RehabLink — Unidade Porto'   FROM utilizadores WHERE email = 'hugo.pereira@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnica de Reabilitação',  'RehabLink — Unidade Madeira' FROM utilizadores WHERE email = 'ines.almeida@rehablink.pt';
INSERT IGNORE INTO profissionais (utilizador_id, especialidade, instituicao)
SELECT id, 'Técnico de Reabilitação',  'RehabLink — Unidade Lisboa'  FROM utilizadores WHERE email = 'joao.rodrigues@rehablink.pt';
