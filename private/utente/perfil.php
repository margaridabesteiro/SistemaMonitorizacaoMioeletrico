<?php
// private/utente/perfil.php
// Perfil pessoal do utente — ver e editar dados
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];

// Carregar dados do utilizador e do utente
$stmt = $db->prepare("
    SELECT u.nome, u.email, u.criado_em,
           ut.data_nascimento, ut.sexo, ut.nif,
           ut.morada, ut.codigo_postal, ut.localidade,
           ut.diagnostico, ut.observacoes
    FROM utilizadores u
    LEFT JOIN utentes ut ON ut.utilizador_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$uid]);
$dados = $stmt->fetch();

$sucesso = '';
$erro    = '';

// --- Processar formulário de atualização ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // Atualizar dados pessoais
    if ($acao === 'dados') {
        $nome         = trim($_POST['nome']         ?? '');
        $morada       = trim($_POST['morada']       ?? '');
        $cod_postal   = trim($_POST['codigo_postal']?? '');
        $localidade   = trim($_POST['localidade']   ?? '');

        if ($nome === '') {
            $erro = 'O nome não pode estar vazio.';
        } else {
            $db->prepare("UPDATE utilizadores SET nome = ? WHERE id = ?")
               ->execute([$nome, $uid]);
            $db->prepare("
                UPDATE utentes SET morada = ?, codigo_postal = ?, localidade = ?
                WHERE utilizador_id = ?
            ")->execute([$morada ?: null, $cod_postal ?: null, $localidade ?: null, $uid]);

            $_SESSION['nome'] = $nome;
            $sucesso = 'Dados atualizados com sucesso.';

            // Recarregar dados
            $stmt->execute([$uid]);
            $dados = $stmt->fetch();
        }
    }

    // Alterar password
    if ($acao === 'password') {
        $atual   = $_POST['password_atual']    ?? '';
        $nova    = $_POST['password_nova']     ?? '';
        $confirma = $_POST['password_confirma'] ?? '';

        $hash_stmt = $db->prepare("SELECT password_hash FROM utilizadores WHERE id = ?");
        $hash_stmt->execute([$uid]);
        $hash_atual = $hash_stmt->fetchColumn();

        if (!password_verify($atual, $hash_atual)) {
            $erro = 'A password atual está incorreta.';
        } elseif (strlen($nova) < 8) {
            $erro = 'A nova password deve ter pelo menos 8 caracteres.';
        } elseif ($nova !== $confirma) {
            $erro = 'As novas passwords não coincidem.';
        } else {
            $novo_hash = password_hash($nova, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE utilizadores SET password_hash = ? WHERE id = ?")
               ->execute([$novo_hash, $uid]);
            $sucesso = 'Password alterada com sucesso.';
        }
    }
}

$pagina_titulo = 'O Meu Perfil';
$pagina_ativa  = 'perfil';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <h1 class="mb-4">O Meu Perfil</h1>

            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fa-solid fa-check-circle me-2"></i><?= h($sucesso) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= h($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- Dados pessoais -->
                <div class="col-lg-7">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="fa-regular fa-user me-2"></i>Dados Pessoais</h5>
                        <form method="POST">
                            <input type="hidden" name="acao" value="dados">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nome completo</label>
                                <input type="text" name="nome" class="form-control"
                                       value="<?= h($dados['nome'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control"
                                       value="<?= h($dados['email'] ?? '') ?>" disabled>
                                <div class="form-text">O email não pode ser alterado aqui.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Morada</label>
                                <input type="text" name="morada" class="form-control"
                                       value="<?= h($dados['morada'] ?? '') ?>">
                            </div>
                            <div class="row">
                                <div class="col-5 mb-3">
                                    <label class="form-label fw-semibold">Código Postal</label>
                                    <input type="text" name="codigo_postal" class="form-control"
                                           placeholder="0000-000"
                                           value="<?= h($dados['codigo_postal'] ?? '') ?>">
                                </div>
                                <div class="col-7 mb-3">
                                    <label class="form-label fw-semibold">Localidade</label>
                                    <input type="text" name="localidade" class="form-control"
                                           value="<?= h($dados['localidade'] ?? '') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Alterações
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info clínica (só leitura) + password -->
                <div class="col-lg-5 d-flex flex-column gap-4">

                    <!-- Info clínica -->
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="fa-solid fa-stethoscope me-2"></i>Informação Clínica</h5>
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted">Data de nascimento</dt>
                            <dd class="col-7"><?= h($dados['data_nascimento'] ?? '—') ?></dd>
                            <dt class="col-5 text-muted">Sexo</dt>
                            <dd class="col-7">
                                <?php
                                $sexo_map = ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'];
                                echo h($sexo_map[$dados['sexo'] ?? ''] ?? '—');
                                ?>
                            </dd>
                            <dt class="col-5 text-muted">NIF</dt>
                            <dd class="col-7"><?= h($dados['nif'] ?? '—') ?></dd>
                            <dt class="col-5 text-muted">Diagnóstico</dt>
                            <dd class="col-7"><?= h($dados['diagnostico'] ?? '—') ?></dd>
                            <dt class="col-5 text-muted">Membro desde</dt>
                            <dd class="col-7"><?= h(date('d/m/Y', strtotime($dados['criado_em']))) ?></dd>
                        </dl>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Para alterar dados clínicos, contacte o seu técnico responsável.
                        </p>
                    </div>

                    <!-- Alterar password -->
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="fa-solid fa-lock me-2"></i>Alterar Password</h5>
                        <form method="POST">
                            <input type="hidden" name="acao" value="password">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password atual</label>
                                <input type="password" name="password_atual" class="form-control"
                                       placeholder="••••••••" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nova password</label>
                                <input type="password" name="password_nova" class="form-control"
                                       placeholder="Mínimo 8 caracteres" required minlength="8">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Confirmar nova password</label>
                                <input type="password" name="password_confirma" class="form-control"
                                       placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fa-solid fa-key me-2"></i>Alterar Password
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
