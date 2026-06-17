<?php
date_default_timezone_set('Europe/Lisbon');

define('APP_NAME', 'RehabLink');
define('APP_URL',  'http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico');
define('APP_ROOT', __DIR__ . '/..');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['utilizador_id'])) {
        header('Location: ' . APP_URL . '/private/login/login.php');
        exit;
    }
}

function requirePerfil(string ...$perfis): void {
    requireLogin();
    if (!empty($_SESSION['deve_alterar_password'])) {
        redirect(APP_URL . '/private/login/alterar_password_obrigatoria.php');
    }
    if (!in_array($_SESSION['perfil'] ?? '', $perfis, true)) {
        http_response_code(403);
        $titulo = '403 — Acesso Negado';
        echo "<!DOCTYPE html><html lang='pt'><head><meta charset='UTF-8'><title>{$titulo}</title>
              <link rel='stylesheet' href='" . APP_URL . "/public/assets/bootstrap/bootstrap.min.css'>
              </head><body class='d-flex align-items-center justify-content-center vh-100 bg-light'>
              <div class='text-center'><h1 class='display-1 text-danger'>403</h1>
              <p class='lead'>Não tem permissão para aceder a esta página.</p>
              <a href='" . APP_URL . "' class='btn btn-dark'>Voltar ao Início</a></div></body></html>";
        exit;
    }
}

// proteção XSS
function h(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// registo de auditoria RGPD Art. 30.º — falha silenciosa
function registarAuditoria(
    string $acao,
    string $entidade    = '',
    ?int   $entidade_id = null,
    string $detalhe     = ''
): void {
    try {
        $db = getDB();
        $db->prepare('INSERT INTO auditoria (utilizador_id, nome, perfil, acao, entidade, entidade_id, detalhe, ip) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([
               $_SESSION['utilizador_id'] ?? null,
               $_SESSION['nome']          ?? null,
               $_SESSION['perfil']        ?? null,
               strtoupper($acao),
               $entidade    ?: null,
               $entidade_id ?: null,
               $detalhe     ?: null,
               $_SERVER['REMOTE_ADDR'] ?? null,
           ]);
    } catch (\Throwable $e) {}
}

function notificar(int $utilizador_id, string $tipo, string $titulo, string $corpo = '', string $url = ''): void {
    try {
        getDB()->prepare('INSERT INTO notificacoes (utilizador_id, tipo, titulo, corpo, url) VALUES (?,?,?,?,?)')
               ->execute([$utilizador_id, $tipo, $titulo, $corpo ?: null, $url ?: null]);
    } catch (\Throwable $e) {}
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }
}

// >+5% melhoria, <-5% regressão, resto estável
function calcularTendencia(PDO $db, int $utente_id, ?int $jogo_id, ?float $percentagem_atual): ?string {
    if ($jogo_id === null || $percentagem_atual === null) return null;
    $s = $db->prepare("
        SELECT m.percentagem_final FROM metricas_sessao m
        JOIN sessoes s ON s.id = m.sessao_id
        WHERE s.utente_id = ? AND s.jogo_id = ? AND s.estado = 'concluida'
        ORDER BY s.data_hora DESC LIMIT 1
    ");
    $s->execute([$utente_id, $jogo_id]);
    $anterior = $s->fetchColumn();
    if ($anterior === false || $anterior === null) return 'estavel';
    $diff = $percentagem_atual - (float)$anterior;
    if ($diff > 5)  return 'melhoria';
    if ($diff < -5) return 'regressao';
    return 'estavel';
}

function dataPt(): string {
    static $dias = [
        'Sunday'    => 'domingo',
        'Monday'    => 'segunda-feira',
        'Tuesday'   => 'terça-feira',
        'Wednesday' => 'quarta-feira',
        'Thursday'  => 'quinta-feira',
        'Friday'    => 'sexta-feira',
        'Saturday'  => 'sábado',
    ];
    static $meses = [
        'January'   => 'janeiro',
        'February'  => 'fevereiro',
        'March'     => 'março',
        'April'     => 'abril',
        'May'       => 'maio',
        'June'      => 'junho',
        'July'      => 'julho',
        'August'    => 'agosto',
        'September' => 'setembro',
        'October'   => 'outubro',
        'November'  => 'novembro',
        'December'  => 'dezembro',
    ];
    return $dias[date('l')] . ', ' . date('d') . ' de ' . $meses[date('F')] . ' de ' . date('Y');
}
