<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Utilizador'; $pagina_ativa = 'utilizadores';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
$db = getDB(); $erros = [];
$stmt = $db->prepare('SELECT u.id, u.nome, u.email, u.perfil, u.ativo, ut.nif FROM utilizadores u LEFT JOIN utentes ut ON ut.utilizador_id = u.id WHERE u.id=?');
$stmt->execute([$id]); $dados = $stmt->fetch();
if (!$dados) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome'] = trim($_POST['nome'] ?? ''); $dados['email'] = trim($_POST['email'] ?? '');
    $dados['perfil'] = $_POST['perfil'] ?? ''; $dados['ativo'] = isset($_POST['ativo']) ? 1 : 0;
    $nova_pass = $_POST['password'] ?? ''; $conf_pass = $_POST['password_conf'] ?? '';
    if ($dados['nome'] === '') $erros[] = 'Nome obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (!in_array($dados['perfil'], ['admin','medico','tecnico','utente'], true)) $erros[] = 'Perfil inválido.';
    if ($nova_pass !== '' && strlen($nova_pass) < 8) $erros[] = 'Password mínimo 8 caracteres.';
    if ($nova_pass !== '' && $nova_pass !== $conf_pass) $erros[] = 'Passwords não coincidem.';
    if (empty($erros)) {
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email=? AND id!=?');
        $existe->execute([$dados['email'],$id]);
        if ($existe->fetch()) { $erros[] = 'Email já em uso.'; }
        else {
            if ($nova_pass !== '') {
                $hash = password_hash($nova_pass, PASSWORD_BCRYPT, ['cost'=>12]);
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=?,password_hash=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$hash,$id]);
            } else {
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$id]);
            }
            $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Utilizador atualizado.'];
            redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
        }
    }
}
// Handle NIF update for utentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dados['perfil'] === 'utente' && isset($_POST['nif'])) {
    $nif_novo = trim($_POST['nif'] ?? '');
    $db->prepare('UPDATE utentes SET nif = ? WHERE utilizador_id = ?')->execute([$nif_novo ?: null, $id]);
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="lista_utilizadores.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                <h1 class="mb-0">Editar Utilizador</h1>
            </div>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" value="<?=h($dados['nome'])?>" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" value="<?=h($dados['email'])?>" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Perfil *</label>
                        <select name="perfil" class="form-select" required><?php foreach(['admin','medico','tecnico','utente'] as $p):?><option value="<?=$p?>" <?=$dados['perfil']===$p?'selected':''?>><?=ucfirst($p)?></option><?php endforeach;?></select>
                    </div>
                    <?php if ($dados['perfil'] === 'utente'): ?>
                    <div class="mb-3"><label class="form-label fw-semibold">NIF</label><input type="text" name="nif" class="form-control" value="<?= h($dados['nif'] ?? '') ?>" placeholder="NIF do utente" maxlength="20"></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nova Password</label><input type="password" name="password" class="form-control" placeholder="Vazio = manter atual"></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirmar Password</label><input type="password" name="password_conf" class="form-control"></div>
                    </div>
                    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?=$dados['ativo']?'checked':''?>><label class="form-check-label" for="ativo">Conta ativa</label></div>
                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
