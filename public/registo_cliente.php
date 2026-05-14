<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Registo público de novos utentes
$erros = [];
$dados = ['nome'=>'','email'=>'','morada'=>'','cod_postal'=>'','localidade'=>'','nif'=>'','data_nascimento'=>'','sexo'=>''];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = array_map('trim', [
        'nome'           => $_POST['nome']           ?? '',
        'email'          => $_POST['email']          ?? '',
        'morada'         => $_POST['morada']         ?? '',
        'cod_postal'     => $_POST['cod_postal']     ?? '',
        'localidade'     => $_POST['localidade']     ?? '',
        'nif'            => $_POST['nif']            ?? '',
        'data_nascimento'=> $_POST['data_nascimento']?? '',
        'sexo'           => $_POST['sexo']           ?? '',
    ]);
    $password     = $_POST['password']     ?? '';
    $password_conf = $_POST['password_conf'] ?? '';

    if ($dados['nome'] === '') $erros[] = 'Nome obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (strlen($password) < 8) $erros[] = 'Password mínimo 8 caracteres.';
    if ($password !== $password_conf) $erros[] = 'Passwords não coincidem.';

    if (empty($erros)) {
        $db = getDB();
        $dup = $db->prepare('SELECT id FROM utilizadores WHERE email = ?');
        $dup->execute([$dados['email']]);
        if ($dup->fetch()) { $erros[] = 'Este email já está registado.'; }
    }

    if (empty($erros)) {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('INSERT INTO utilizadores (nome, email, password_hash, perfil, ativo) VALUES (?,?,?,?,1)')
           ->execute([$dados['nome'], $dados['email'], $hash, 'utente']);
        $uid = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO utentes (utilizador_id, data_nascimento, sexo, nif, morada, cod_postal, localidade) VALUES (?,?,?,?,?,?,?)')
           ->execute([$uid, $dados['data_nascimento']?: null, $dados['sexo']?:null, $dados['nif']?:null, $dados['morada']?:null, $dados['cod_postal']?:null, $dados['localidade']?:null]);
        $sucesso = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RehabLink — Criar Conta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .register-wrap { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 2rem 1rem; }
        .register-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); max-width: 700px; width: 100%; overflow: hidden; }
        .card-header-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 1.5rem; font-size: 1.1rem; font-weight: 600; }
        .card-body-custom { padding: 2rem; }
        .btn-registar { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; width: 100%; padding: .75rem; border-radius: 8px; font-size: 1rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="topbar">
        <div><i class="fa-solid fa-hand-holding-heart me-2" style="color:#667eea;"></i><strong>RehabLink</strong></div>
        <a href="<?= APP_URL ?>/private/login/login.php" class="text-decoration-none text-muted"><i class="fa-solid fa-arrow-left me-1"></i>Já tenho conta</a>
    </div>
    <div class="register-wrap">
        <div class="register-card">
            <div class="card-header-custom"><i class="fa-solid fa-user-plus me-2"></i>Criar nova conta de utente</div>
            <div class="card-body-custom">
                <?php if ($sucesso): ?>
                    <div class="alert alert-success text-center py-4">
                        <i class="fa-solid fa-circle-check fa-2x mb-2"></i>
                        <h5>Conta criada com sucesso!</h5>
                        <p class="mb-3">Aguarde a ativação pela equipa clínica para aceder ao sistema.</p>
                        <a href="<?= APP_URL ?>/private/login/login.php" class="btn btn-success">Ir para o Login</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($erros)): ?><div class="alert alert-danger mb-4"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nome Completo *</label>
                                <input type="text" name="nome" class="form-control" value="<?= h($dados['nome']) ?>" required minlength="3">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= h($dados['email']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">NIF</label>
                                <input type="text" name="nif" class="form-control" value="<?= h($dados['nif']) ?>" maxlength="9">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Data de Nascimento</label>
                                <input type="date" name="data_nascimento" class="form-control" value="<?= h($dados['data_nascimento']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Sexo</label>
                                <select name="sexo" class="form-select">
                                    <option value="">—</option>
                                    <option value="F" <?= $dados['sexo']==='F'?'selected':'' ?>>Feminino</option>
                                    <option value="M" <?= $dados['sexo']==='M'?'selected':'' ?>>Masculino</option>
                                    <option value="O" <?= $dados['sexo']==='O'?'selected':'' ?>>Outro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Código Postal</label>
                                <input type="text" name="cod_postal" class="form-control" value="<?= h($dados['cod_postal']) ?>" placeholder="1000-001">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Morada</label>
                                <input type="text" name="morada" class="form-control" value="<?= h($dados['morada']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Localidade</label>
                                <input type="text" name="localidade" class="form-control" value="<?= h($dados['localidade']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password *</label>
                                <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirmar Password *</label>
                                <input type="password" name="password_conf" class="form-control" required>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn-registar"><i class="fa-solid fa-user-plus me-2"></i>Criar Conta</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
