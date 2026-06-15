-- Criar os 3 dispositivos EMG
-- Executar no phpMyAdmin: selecionar sistema_mioeletrico → separador SQL
-- ATENÇÃO: apaga empréstimos e dispositivos existentes antes de inserir

USE sistema_mioeletrico;

DELETE FROM emprestimos_dispositivos;
DELETE FROM dispositivos;

INSERT INTO dispositivos (codigo, tipo, firmware_versao, estado, token_api, ativo) VALUES
('EMG-0001', 'ESP32-FSR406', NULL, 'disponivel', SHA2(CONCAT(RAND(), NOW(), 'EMG-0001'), 256), 1),
('EMG-0002', 'ESP32-FSR406', NULL, 'disponivel', SHA2(CONCAT(RAND(), NOW(), 'EMG-0002'), 256), 1),
('EMG-0003', 'ESP32-FSR406', NULL, 'disponivel', SHA2(CONCAT(RAND(), NOW(), 'EMG-0003'), 256), 1);
