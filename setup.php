<?php
/**
 * setup.php — Script de configuração inicial
 * 
 * ATENÇÃO: APAGAR ESTE FICHEIRO APÓS A INSTALAÇÃO!
 * 
 * Aceder em: http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico/setup.php
 * 
 * Este script:
 *   1. Testa a ligação à base de dados
 *   2. Cria a BD e as tabelas automaticamente (alternativa ao phpMyAdmin)
 *   3. Cria/atualiza o utilizador admin com a password definida abaixo
 */

// ============================================================
// CONFIGURAÇÃO — alterar antes de executar
// ============================================================
$NOVA_PASSWORD_ADMIN = 'Admin123!';   // ← alterar para a password desejada
$ADMIN_EMAIL         = 'admin@rehablink.pt';
$ADMIN_NOME          = 'Administrador';
// ============================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'sistema_mioeletrico');
define('DB_CHARSET', 'utf8mb4');

$resultado = [];
$erro_fatal = '';

// --- 1. Testar ligação ---
try {
    $pdo_sem_bd = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $resultado[] = ['ok', 'Ligação ao MySQL estabelecida com sucesso.'];
} catch (PDOException $e) {
    $erro_fatal = 'Não foi possível ligar ao MySQL: ' . $e->getMessage();
}

if (!$erro_fatal) {
    // --- 2. Criar base de dados ---
    try {
        $pdo_sem_bd->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $resultado[] = ['ok', 'Base de dados "' . DB_NAME . '" criada/verificada.'];
    } catch (PDOException $e) {
        $erro_fatal = 'Erro ao criar BD: ' . $e->getMessage();
    }
}

if (!$erro_fatal) {
    // --- 3. Ligar com a BD e criar tabelas ---
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $schema_file = __DIR__ . '/database/sistema_mioeletrico.sql';
        if (file_exists($schema_file)) {
            $sql = file_get_contents($schema_file);
            // Remover os CREATE DATABASE e USE para não conflituar
            $sql = preg_replace('/CREATE DATABASE.*?;\s*/si', '', $sql);
            $sql = preg_replace('/USE\s+\w+\s*;\s*/si', '', $sql);
            // Dividir por ; e executar
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            $tabelas = 0;
            foreach ($queries as $q) {
                if (!empty($q)) {
                    $pdo->exec($q);
                    if (stripos($q, 'CREATE TABLE') !== false) $tabelas++;
                }
            }
            $resultado[] = ['ok', "Schema importado: {$tabelas} tabela(s) criada(s)/verificada(s)."];
        } else {
            $resultado[] = ['warn', 'Ficheiro database/sistema_mioeletrico.sql não encontrado. Crie as tabelas manualmente no phpMyAdmin.'];
        }

        // --- 4. Criar/atualizar admin ---
        $hash = password_hash($NOVA_PASSWORD_ADMIN, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $existe = $pdo->prepare('SELECT id FROM utilizadores WHERE email = ?');
        $existe->execute([$ADMIN_EMAIL]);
        $admin = $existe->fetch();

        if ($admin) {
            $pdo->prepare('UPDATE utilizadores SET password_hash = ?, nome = ?, ativo = 1 WHERE email = ?')
                ->execute([$hash, $ADMIN_NOME, $ADMIN_EMAIL]);
            $resultado[] = ['ok', "Utilizador admin atualizado. Email: {$ADMIN_EMAIL}"];
        } else {
            $pdo->prepare('INSERT INTO utilizadores (nome, email, password_hash, perfil, ativo) VALUES (?,?,?,?,1)')
                ->execute([$ADMIN_NOME, $ADMIN_EMAIL, $hash, 'admin']);
            $resultado[] = ['ok', "Utilizador admin criado. Email: {$ADMIN_EMAIL}"];
        }

        $resultado[] = ['info', "Hash gerado para '{$NOVA_PASSWORD_ADMIN}': <code>" . htmlspecialchars($hash) . "</code>"];

    } catch (PDOException $e) {
        $erro_fatal = 'Erro: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink — Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f4f6f9; display: flex; justify-content: center; padding: 2rem; }
        .card { background: #fff; border-radius: 12px; padding: 2rem; max-width: 700px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { font-size: 1.5rem; margin-bottom: .25rem; color: #8B0000; }
        .sub { color: #666; font-size: .9rem; margin-bottom: 1.5rem; }
        .item { display: flex; gap: .75rem; align-items: flex-start; padding: .6rem 0; border-bottom: 1px solid #f0f0f0; font-size: .92rem; }
        .badge { padding: .2rem .6rem; border-radius: 20px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .ok   .badge { background: #d4edda; color: #155724; }
        .warn .badge { background: #fff3cd; color: #856404; }
        .info .badge { background: #d1ecf1; color: #0c5460; }
        .erro { background: #f8d7da; border-radius: 8px; padding: 1rem; color: #721c24; margin: 1rem 0; }
        .success-box { background: #d4edda; border-radius: 8px; padding: 1rem; color: #155724; margin: 1.5rem 0; }
        .btn { display: inline-block; margin-top: 1.5rem; padding: .65rem 1.4rem; background: #8B0000; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .btn-warn { background: #dc3545; margin-left: .5rem; }
        code { background: #f4f4f4; padding: .15rem .4rem; border-radius: 4px; font-size: .85rem; word-break: break-all; }
        .warning-box { background: #fff3cd; border-radius: 8px; padding: 1rem; color: #856404; margin-top: 1.5rem; font-size: .9rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔧 RehabLink — Instalação</h1>
    <p class="sub">Sistema de Monitorização Mioeléctrica</p>

    <?php if ($erro_fatal): ?>
        <div class="erro">
            <strong>❌ Erro Fatal:</strong> <?= htmlspecialchars($erro_fatal) ?><br><br>
            Verifique se o MySQL está iniciado no XAMPP Control Panel.
        </div>
    <?php else: ?>
        <?php foreach ($resultado as [$tipo, $msg]): ?>
            <div class="item <?= $tipo ?>">
                <span class="badge"><?= strtoupper($tipo) ?></span>
                <span><?= $msg ?></span>
            </div>
        <?php endforeach; ?>

        <div class="success-box" style="margin-top:1rem">
            <strong>✅ Instalação concluída!</strong><br>
            Pode aceder ao sistema em:
            <a href="http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico/private/login/login.php">
                http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico/private/login/login.php
            </a><br><br>
            <strong>Credenciais admin:</strong><br>
            Email: <code><?= htmlspecialchars($ADMIN_EMAIL) ?></code><br>
            Password: <code><?= htmlspecialchars($NOVA_PASSWORD_ADMIN) ?></code>
        </div>
    <?php endif; ?>

    <div class="warning-box">
        ⚠️ <strong>IMPORTANTE:</strong> Após confirmar que o sistema funciona,
        <strong>apague o ficheiro <code>setup.php</code></strong> por razões de segurança.
    </div>

    <?php if (!$erro_fatal): ?>
        <a class="btn" href="private/login/login.php">Ir para o Login</a>
    <?php endif; ?>
</div>
</body>
</html>
