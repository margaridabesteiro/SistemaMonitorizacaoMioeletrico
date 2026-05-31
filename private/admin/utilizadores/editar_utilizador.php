<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Utilizador'; $pagina_ativa = 'utilizadores';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
$db = getDB(); $erros = [];
$stmt = $db->prepare('SELECT u.id, u.nome, u.email, u.perfil, u.ativo, ut.nif, ut.cobertura_saude FROM utilizadores u LEFT JOIN utentes ut ON ut.utilizador_id = u.id WHERE u.id=?');
$stmt->execute([$id]); $dados = $stmt->fetch();
if (!$dados) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']   = trim($_POST['nome']   ?? '');
    $dados['email']  = trim($_POST['email']  ?? '');
    $dados['perfil'] = $_POST['perfil']      ?? '';
    $dados['ativo']  = isset($_POST['ativo'])? 1 : 0;

    if ($dados['nome'] === '') $erros[] = 'Nome obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (!in_array($dados['perfil'], ['admin','medico','tecnico','utente'], true)) $erros[] = 'Perfil inválido.';

    // Password: apenas para não-utentes
    $nova_pass = ''; $conf_pass = '';
    if ($dados['perfil'] !== 'utente') {
        $nova_pass = $_POST['password']      ?? '';
        $conf_pass = $_POST['password_conf'] ?? '';
        if ($nova_pass !== '' && strlen($nova_pass) < 8)  $erros[] = 'Password mínimo 8 caracteres.';
        if ($nova_pass !== '' && $nova_pass !== $conf_pass) $erros[] = 'Passwords não coincidem.';
    }

    if (empty($erros)) {
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email=? AND id!=?');
        $existe->execute([$dados['email'], $id]);
        if ($existe->fetch()) {
            $erros[] = 'Email já em uso.';
        } else {
            if ($nova_pass !== '') {
                $hash = password_hash($nova_pass, PASSWORD_BCRYPT, ['cost'=>12]);
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=?,password_hash=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$hash,$id]);
            } else {
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$id]);
            }
            // Dados específicos de utente
            if ($dados['perfil'] === 'utente') {
                $nif       = trim($_POST['nif']             ?? '') ?: null;
                $cobertura = $_POST['cobertura_saude']      ?? 'SNS';
                $db->prepare('UPDATE utentes SET nif=?, cobertura_saude=? WHERE utilizador_id=?')
                   ->execute([$nif, $cobertura, $id]);
            }
            $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Utilizador atualizado.'];
            redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
        }
    }
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIF</label>
                        <input type="text" name="nif" class="form-control" value="<?=h($dados['nif']??'')?>" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cobertura de Saúde</label>
                        <select name="cobertura_saude" class="form-select">
                            <?php foreach(['SNS','Particular','Seguro'] as $c): ?>
                                <option value="<?=$c?>" <?=($dados['cobertura_saude']??'SNS')===$c?'selected':''?>><?=$c?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Faturas só são geradas para Particular e Seguro.</div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="fa-solid fa-lock me-1"></i> A password do utente só pode ser redefinida pelo próprio utente (RGPD — dados clínicos pertencem ao titular).
                    </div>
                    <?php else: ?>
                    <div class="row" id="password">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nova Password</label><input type="password" name="password" class="form-control" placeholder="Vazio = manter atual"></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirmar Password</label><input type="password" name="password_conf" class="form-control"></div>
                    </div>
                    <?php endif; ?>

                    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?=$dados['ativo']?'checked':''?>><label class="form-check-label" for="ativo">Conta ativa</label></div>
                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
