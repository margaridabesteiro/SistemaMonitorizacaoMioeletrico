<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
$pagina_titulo = 'Meu Perfil'; $pagina_ativa = 'preferencias';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT u.*, p.especialidade, p.instituicao, p.contacto, p.numero_ordem FROM utilizadores u LEFT JOIN profissionais p ON p.utilizador_id=u.id WHERE u.id=?");
$stmt->execute([$uid]); $user = $stmt->fetch();
require_once __DIR__ . '/../../includes/header_tecnico.php';
require_once __DIR__ . '/../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <h1 class="mb-4">Meu Perfil</h1>
            <div class="alert alert-info py-2 small mb-4">
                <i class="fa-solid fa-circle-info me-1"></i>
                Para alterar os seus dados pessoais ou password, contacte o administrador.
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-3 text-center mb-3">
                        <i class="fa-solid fa-user-nurse fa-3x mb-2" style="color:#1a5f8a;"></i>
                        <h4><?= h($user['nome']) ?></h4>
                        <p class="text-muted small"><?= h(ucfirst($user['perfil'])) ?><?= $user['numero_ordem'] ? ' · Cédula: ' . h($user['numero_ordem']) : '' ?></p>
                        <p class="small mb-1"><i class="fa-regular fa-envelope me-1"></i><?= h($user['email']) ?></p>
                        <?php if ($user['contacto']): ?><p class="small mb-1"><i class="fa-solid fa-phone me-1"></i><?= h($user['contacto']) ?></p><?php endif; ?>
                        <?php if ($user['instituicao']): ?><p class="small mb-0"><i class="fa-regular fa-building me-1"></i><?= h($user['instituicao']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="fa-solid fa-id-card me-2"></i>Informação Profissional</h5>
                        <dl class="row mb-0">
                            <dt class="col-4 text-muted">Nome</dt>
                            <dd class="col-8"><?= h($user['nome'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Email</dt>
                            <dd class="col-8"><?= h($user['email'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Especialidade</dt>
                            <dd class="col-8"><?= h($user['especialidade'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Instituição</dt>
                            <dd class="col-8"><?= h($user['instituicao'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Contacto</dt>
                            <dd class="col-8"><?= h($user['contacto'] ?? '—') ?></dd>
                            <dt class="col-4 text-muted">Número de Ordem</dt>
                            <dd class="col-8"><?= h($user['numero_ordem'] ?? '—') ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
