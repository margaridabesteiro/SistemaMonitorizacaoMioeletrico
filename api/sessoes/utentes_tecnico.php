<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['utilizador_id']) || $_SESSION['perfil'] !== 'tecnico') {
    echo json_encode([]); exit;
}

$db = getDB();
$s  = $db->prepare("
    SELECT ut.id, u.nome
    FROM utentes ut
    JOIN utilizadores u ON u.id = ut.utilizador_id
    JOIN profissionais p ON p.id = ut.tecnico_id
    WHERE p.utilizador_id = ?
    ORDER BY u.nome
");
$s->execute([$_SESSION['utilizador_id']]);
echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
