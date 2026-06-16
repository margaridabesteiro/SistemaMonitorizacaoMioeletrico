<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();
header('Content-Type: application/json');
try {
    $s = getDB()->prepare('SELECT COUNT(*) FROM notificacoes WHERE utilizador_id=? AND lida=0');
    $s->execute([$_SESSION['utilizador_id']]);
    echo json_encode(['count' => (int)$s->fetchColumn()]);
} catch (\Throwable $e) {
    echo json_encode(['count' => 0]);
}
