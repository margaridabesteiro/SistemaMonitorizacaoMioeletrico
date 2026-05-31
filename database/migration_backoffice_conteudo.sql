-- Migração: popular backoffice_conteudo com todos os valores editáveis da página principal
-- Executar no phpMyAdmin: seleccionar sistema_mioeletrico → separador SQL

USE sistema_mioeletrico;

-- Hero
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('hero_titulo',        'Tecnologia e Humanização'),
('hero_subtitulo',     'para a sua recuperação'),
('hero_descricao',     'Na RehabLink, combinamos fisioterapia tradicional com jogos de reabilitação e monitorização contínua para resultados mais rápidos e eficazes.'),
('hero_stat1_num',     '+5000'),
('hero_stat1_label',   'Utentes tratados'),
('hero_stat2_num',     '98%'),
('hero_stat2_label',   'Taxa de sucesso'),
('hero_stat3_num',     '4'),
('hero_stat3_label',   'Unidades');

-- Quem Somos
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('qs_h3',    'Inovação, qualidade e compromisso com a sua saúde'),
('qs_texto', 'A RehabLink nasceu da convicção de que a reabilitação pode ser mais eficaz quando aliada à tecnologia. Desde 2020, temos ajudado milhares de utentes a recuperar a sua qualidade de vida através de métodos inovadores e personalizados.');

-- Serviços (6)
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('servico_1_titulo', 'Fisioterapia Tradicional'),
('servico_1_icone',  'fa-solid fa-dumbbell'),
('servico_1_desc',   'Sessões personalizadas com fisioterapeutas especializados para recuperação de lesões e melhoria da mobilidade.'),
('servico_2_titulo', 'Jogos de Reabilitação'),
('servico_2_icone',  'fa-solid fa-gamepad'),
('servico_2_desc',   'Terapia gamificada com jogos interativos que tornam o processo de recuperação mais envolvente e motivador.'),
('servico_3_titulo', 'Monitorização Contínua'),
('servico_3_icone',  'fa-solid fa-chart-line'),
('servico_3_desc',   'Acompanhamento remoto do seu progresso com relatórios detalhados para si e para o seu fisioterapeuta.'),
('servico_4_titulo', 'Reabilitação Cardíaca'),
('servico_4_icone',  'fa-solid fa-heart-pulse'),
('servico_4_desc',   'Programas especializados para recuperação de eventos cardíacos, com monitorização constante e exercícios adaptados.'),
('servico_5_titulo', 'Reabilitação Neurológica'),
('servico_5_icone',  'fa-solid fa-brain'),
('servico_5_desc',   'Tratamento especializado para AVC, lesões medulares e doenças neurodegenerativas com técnicas avançadas.'),
('servico_6_titulo', 'Reabilitação da Mão'),
('servico_6_icone',  'fa-solid fa-hand'),
('servico_6_desc',   'Terapia especializada para recuperação da força e destreza da mão, ideal para lesões profissionais ou pós-cirúrgicas.');

-- Unidades (4)
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('unidade_1_nome',   'Unidade Lisboa'),
('unidade_1_morada', 'Av. da República, 1000 — Lisboa'),
('unidade_1_tel',    '21 345 6789'),
('unidade_2_nome',   'Unidade Porto'),
('unidade_2_morada', 'Rua de Santa Catarina, 500 — Porto'),
('unidade_2_tel',    '22 456 7890'),
('unidade_3_nome',   'Unidade Coimbra'),
('unidade_3_morada', 'Rua da Sofia, 150 — Coimbra'),
('unidade_3_tel',    '23 678 9012'),
('unidade_4_nome',   'Unidade Madeira'),
('unidade_4_morada', 'Rua dos Ferreiros, 120 — Funchal'),
('unidade_4_tel',    '29 890 1234');

-- Acordos / Seguros
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('seguros', 'Multicare,AdvanceCare,Médis,Allianz,SNS,Fidelidade,Lusitânia,Ageas,Real Vida,Generali');

-- Contacto
INSERT IGNORE INTO backoffice_conteudo (chave, valor) VALUES
('contacto_morada',    'Av. da República, 1000 — 1050-100 Lisboa'),
('contacto_tel',       '21 345 6789'),
('contacto_telemovel', '91 234 5678'),
('contacto_horario_semana',  '2ª a 6ª: 8h - 20h'),
('contacto_horario_sabado',  'Sábado: 9h - 13h');
