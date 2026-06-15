<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();
header('Content-Type: application/json');
try {
    getDB()->prepare('UPDATE notificacoes SET lida=1 WHERE utilizador_id=?')
           ->execute([$_SESSION['utilizador_id']]);
    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false]);
}
