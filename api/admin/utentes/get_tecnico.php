<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['tecnico_id' => null]); exit; }
$db  = getDB();
$row = $db->prepare('SELECT tecnico_id FROM utentes WHERE id=?');
$row->execute([$id]);
$data = $row->fetch();
echo json_encode(['tecnico_id' => $data ? $data['tecnico_id'] : null]);
