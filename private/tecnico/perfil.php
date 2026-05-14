<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
$pagina_titulo = 'Meu Perfil'; $pagina_ativa = 'preferencias';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT u.*, p.especialidade, p.instituicao, p.contacto, p.numero_ordem FROM utilizadores u LEFT JOIN profissionais p ON p.utilizador_id=u.id WHERE u.id=?");
$stmt->execute([$uid]); $user = $stmt->fetch();
$erros = []; $sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? ''); $contacto = trim($_POST['contacto'] ?? ''); $inst = trim($_POST['instituicao'] ?? '');
    $nova_pw = $_POST['password'] ?? ''; $pw_conf = $_POST['password_conf'] ?? '';
    if ($nome === '') $erros[] = 'Nome obrigatório.';
    if ($nova_pw !== '' && strlen($nova_pw) < 8) $erros[] = 'Password mín. 8 chars.';
    if ($nova_pw !== $pw_conf) $erros[] = 'Passwords não coincidem.';
    if (empty($erros)) {
        $db->prepare('UPDATE utilizadores SET nome=? WHERE id=?')->execute([$nome,$uid]);
        $db->prepare('UPDATE profissionais SET contacto=?,instituicao=? WHERE utilizador_id=?')->execute([$contacto,$inst,$uid]);
        if ($nova_pw !== '') { $hash = password_hash($nova_pw,PASSWORD_BCRYPT,['cost'=>12]); $db->prepare('UPDATE utilizadores SET password_hash=? WHERE id=?')->execute([$hash,$uid]); }
        $sucesso = true; $_SESSION['nome'] = $nome;
        $stmt = $db->prepare("SELECT u.*, p.especialidade, p.instituicao, p.contacto FROM utilizadores u LEFT JOIN profissionais p ON p.utilizador_id=u.id WHERE u.id=?"); $stmt->execute([$uid]); $user = $stmt->fetch();
    }
}
require_once __DIR__ . '/../../includes/header_tecnico.php';
require_once __DIR__ . '/../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <h1 class="mb-4">Meu Perfil</h1>
            <?php if ($sucesso): ?><div class="alert alert-success py-2">Perfil atualizado com sucesso.</div><?php endif; ?>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-3 text-center mb-3">
                        <i class="fa-solid fa-user-nurse fa-3x mb-2" style="color:#1a5f8a;"></i>
                        <h4><?= h($user['nome']) ?></h4>
                        <p class="text-muted small"><?= h($user['perfil']) ?><?= $user['numero_ordem'] ? ' · Cédula: ' . h($user['numero_ordem']) : '' ?></p>
                        <p class="small"><i class="fa-regular fa-envelope me-1"></i><?= h($user['email']) ?></p>
                        <?php if ($user['contacto']): ?><p class="small"><i class="fa-solid fa-phone me-1"></i><?= h($user['contacto']) ?></p><?php endif; ?>
                        <?php if ($user['instituicao']): ?><p class="small"><i class="fa-regular fa-building me-1"></i><?= h($user['instituicao']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-4">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nome</label><input type="text" name="nome" class="form-control" value="<?= h($user['nome']) ?>" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Contacto</label><input type="text" name="contacto" class="form-control" value="<?= h($user['contacto'] ?? '') ?>"></div>
                                <div class="col-md-12 mb-3"><label class="form-label fw-semibold">Instituição</label><input type="text" name="instituicao" class="form-control" value="<?= h($user['instituicao'] ?? '') ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nova Password</label><input type="password" name="password" class="form-control" placeholder="Vazio = manter"></div>
                                <div class="col-md-6 mb-4"><label class="form-label fw-semibold">Confirmar</label><input type="password" name="password_conf" class="form-control"></div>
                            </div>
                            <button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
