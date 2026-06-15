-- Fix encoding dos nomes com caracteres especiais portugueses
USE sistema_mioeletrico;

-- Médicos
UPDATE utilizadores SET nome = 'Dr. António Ribeiro' WHERE email = 'antonio.ribeiro@rehablink.pt';
UPDATE utilizadores SET nome = 'Dr. João Lopes'      WHERE email = 'joao.lopes@rehablink.pt';

-- Técnicos
UPDATE utilizadores SET nome = 'Inês Almeida'    WHERE email = 'ines.almeida@rehablink.pt';
UPDATE utilizadores SET nome = 'João Rodrigues'  WHERE email = 'joao.rodrigues@rehablink.pt';

-- Administradores
UPDATE utilizadores SET nome = 'Luísa Cardoso'  WHERE email = 'luisa.cardoso@rehablink.pt';

-- Utentes
UPDATE utilizadores SET nome = 'Gonçalo Figueiredo' WHERE email = 'goncalo.figueiredo@email.pt';

-- Profissionais (especialidades e instituições)
UPDATE profissionais SET especialidade = 'Fisioterapia Neurológica'   WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'marta.fernandes@rehablink.pt');
UPDATE profissionais SET especialidade = 'Fisioterapia Ortopédica'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'ricardo.silva@rehablink.pt');
UPDATE profissionais SET especialidade = 'Fisioterapia Respiratória'  WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'ana.almeida@rehablink.pt');
UPDATE profissionais SET especialidade = 'Fisioterapia Desportiva'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'joao.lopes@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnica de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'ana.silva@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnico de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'bruno.ferreira@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnica de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'carla.santos@rehablink.pt');
UPDATE profissionais SET especialidade = 'Auxiliar de Reabilitação'   WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'daniel.costa@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnica de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'eduarda.martins@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnico de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'filipe.gomes@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnica de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'gabriela.rocha@rehablink.pt');
UPDATE profissionais SET especialidade = 'Auxiliar de Reabilitação'   WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'hugo.pereira@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnica de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'ines.almeida@rehablink.pt');
UPDATE profissionais SET especialidade = 'Técnico de Reabilitação'    WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'joao.rodrigues@rehablink.pt');

UPDATE profissionais SET instituicao = 'RehabLink — Unidade Lisboa'  WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'ana.silva@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Porto'   WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'bruno.ferreira@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Porto'   WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'carla.santos@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Lisboa'  WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'daniel.costa@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Coimbra' WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'eduarda.martins@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Lisboa'  WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'filipe.gomes@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Lisboa'  WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'gabriela.rocha@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Porto'   WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'hugo.pereira@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Madeira' WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'ines.almeida@rehablink.pt');
UPDATE profissionais SET instituicao = 'RehabLink — Unidade Lisboa'  WHERE utilizador_id = (SELECT id FROM utilizadores WHERE email = 'joao.rodrigues@rehablink.pt');
