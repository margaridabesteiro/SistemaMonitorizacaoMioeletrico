<?php
/**
 * API pública — recebe o formulário de contacto da página principal
 * Método: POST (JSON ou form-data)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit;
}

$nome     = trim($_POST['nome']     ?? '');
$email    = trim($_POST['email']    ?? '');
$telefone = trim($_POST['telefone'] ?? '') ?: null;
$assunto  = trim($_POST['assunto']  ?? '') ?: null;
$mensagem = trim($_POST['mensagem'] ?? '');

// Validação básica
if ($nome === '' || $email === '' || $mensagem === '') {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'erro' => 'Nome, email e mensagem são obrigatórios.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'erro' => 'Email inválido.']);
    exit;
}

$db = getDB();
$db->prepare('INSERT INTO contactos (nome, email, telefone, assunto, mensagem) VALUES (?,?,?,?,?)')
   ->execute([$nome, $email, $telefone, $assunto, $mensagem]);

echo json_encode(['sucesso' => true, 'mensagem' => 'Mensagem recebida com sucesso.']);
