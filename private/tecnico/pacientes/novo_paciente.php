<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Novo Paciente'; $pagina_ativa = 'pacientes';
$erros = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
    $stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
    $nome = trim($_POST['nome'] ?? ''); $email = trim($_POST['email'] ?? '');
    $dn = $_POST['data_nascimento'] ?? null; $sexo = $_POST['sexo'] ?? null;
    $nif = trim($_POST['nif'] ?? ''); $diagnostico = trim($_POST['diagnostico'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($nome === '') $erros[] = 'Nome obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (strlen($password) < 8) $erros[] = 'Password mín. 8 chars.';
    if (empty($erros)) {
        $dup = $db->prepare('SELECT id FROM utilizadores WHERE email=?'); $dup->execute([$email]);
        if ($dup->fetch()) { $erros[] = 'Email já em uso.'; }
    }
    if (empty($erros)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
        $db->prepare('INSERT INTO utilizadores (nome,email,password_hash,perfil,ativo) VALUES (?,?,?,?,1)')->execute([$nome,$email,$hash,'utente']);
        $novo_uid = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO utentes (utilizador_id,data_nascimento,sexo,nif,diagnostico,tecnico_id) VALUES (?,?,?,?,?,?)')->execute([$novo_uid,$dn?:null,$sexo?:null,$nif?:null,$diagnostico?:null,$pid?:null]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Paciente registado.']; redirect(APP_URL . '/private/tecnico/pacientes/lista_pacientes.php');
    }
}
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <h1 class="mb-4">Novo Paciente</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:700px;">
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <h5 class="mb-3">Informação Pessoal</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" required></div>
                        <div class="col-md-3 mb-3"><label class="form-label fw-semibold">Data Nascimento</label><input type="date" name="data_nascimento" class="form-control"></div>
                        <div class="col-md-3 mb-3"><label class="form-label fw-semibold">Sexo</label><select name="sexo" class="form-select"><option value="">—</option><option value="F">Feminino</option><option value="M">Masculino</option><option value="O">Outro</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">NIF</label><input type="text" name="nif" class="form-control" maxlength="9"></div>
                        <div class="col-md-8 mb-3"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Password *</label><input type="password" name="password" class="form-control" placeholder="Mín. 8 chars" required></div>
                        <div class="col-md-12 mb-4"><label class="form-label fw-semibold">Diagnóstico</label><textarea name="diagnostico" class="form-control" rows="2"></textarea></div>
                    </div>
                    <div class="d-flex gap-2"><button type="submit" class="btn" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Registar</button><a href="lista_pacientes.php" class="btn btn-outline-secondary">Cancelar</a></div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
