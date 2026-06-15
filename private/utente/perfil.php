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

    // Alterar password (única operação permitida ao utente)
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

// Carregar dados RGPD
$consentimento = null;
$pedido_eliminacao = null;
try {
    $rc = $db->prepare("SELECT criado_em, detalhes FROM rgpd_consentimentos WHERE utilizador_id=? AND tipo='registo' ORDER BY criado_em ASC LIMIT 1");
    $rc->execute([$uid]); $consentimento = $rc->fetch();
    $rp = $db->prepare("SELECT estado, criado_em FROM rgpd_pedidos WHERE utilizador_id=? AND tipo='eliminacao' ORDER BY criado_em DESC LIMIT 1");
    $rp->execute([$uid]); $pedido_eliminacao = $rp->fetch();
} catch (\Throwable $e) {}

$flash_rgpd = $_SESSION['flash_rgpd'] ?? null; unset($_SESSION['flash_rgpd']);

$pagina_titulo = 'O Meu Perfil';
$pagina_ativa  = 'perfil';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">O Meu Perfil</h1>
            </div>

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

                <!-- Dados pessoais — só leitura -->
                <div class="col-lg-7">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="fa-regular fa-user me-2"></i>Dados Pessoais</h5>
                        <p class="text-muted small mb-3"><i class="fa-solid fa-circle-info me-1"></i>Para alterar os seus dados pessoais, contacte o administrador.</p>
                        <dl class="row mb-0">
                            <dt class="col-4 text-muted">Nome</dt>
                            <dd class="col-8"><?= h($dados['nome'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Email</dt>
                            <dd class="col-8"><?= h($dados['email'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Morada</dt>
                            <dd class="col-8"><?= h($dados['morada'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Código Postal</dt>
                            <dd class="col-8"><?= h($dados['codigo_postal'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Localidade</dt>
                            <dd class="col-8"><?= h($dados['localidade'] ?? '—') ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Info clínica + password -->
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
            <!-- Secção RGPD -->
            <div class="row mt-4" id="rgpd">
                <div class="col-12">
                    <div class="card p-4 border-warning">
                        <h5 class="mb-3"><i class="fa-solid fa-shield-halved me-2" style="color:#8B0000;"></i>Privacidade e Proteção de Dados (RGPD)</h5>

                        <?php if ($flash_rgpd): ?>
                            <div class="alert alert-<?= h($flash_rgpd['tipo']) ?> py-2 mb-3"><?= h($flash_rgpd['msg']) ?></div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <!-- Consentimento -->
                            <div class="col-md-4">
                                <div class="card bg-light p-3 h-100">
                                    <h6 class="fw-bold"><i class="fa-solid fa-file-signature me-1 text-success"></i>Consentimento</h6>
                                    <?php if ($consentimento): ?>
                                        <p class="small text-muted mb-0">Consentimento registado em <strong><?= h(date('d/m/Y', strtotime($consentimento['criado_em']))) ?></strong> ao abrigo do RGPD Art.&nbsp;9(2)(h).</p>
                                    <?php else: ?>
                                        <p class="small text-muted mb-0">Consentimento registado no momento da criação da conta.</p>
                                    <?php endif; ?>
                                    <a href="<?= APP_URL ?>/public/privacidade.php" target="_blank" class="btn btn-sm mt-2" style="background:#764ba2;color:#fff;">
                                        <i class="fa-solid fa-book me-1"></i>Política de Privacidade
                                    </a>
                                </div>
                            </div>

                            <!-- Exportar dados (portabilidade) -->
                            <div class="col-md-4">
                                <div class="card bg-light p-3 h-100">
                                    <h6 class="fw-bold"><i class="fa-solid fa-download me-1 text-primary"></i>Os Meus Dados (Art.&nbsp;20)</h6>
                                    <p class="small text-muted mb-2">Descarregue todos os seus dados pessoais, sessões e métricas em formato JSON.</p>
                                    <a href="<?= APP_URL ?>/api/utente/rgpd_exportar.php" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-download me-1"></i>Descarregar os meus dados
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
