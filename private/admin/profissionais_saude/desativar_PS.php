<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Desativar Profissional'; $pagina_ativa = 'profissionais';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(APP_URL . '/private/admin/profissionais_saude/gestao_PS.php');
$db = getDB();
$stmt = $db->prepare('SELECT u.nome, u.email, u.perfil, u.criado_em FROM utilizadores u WHERE u.id = ?');
$stmt->execute([$id]); $user = $stmt->fetch();
if (!$user) redirect(APP_URL . '/private/admin/profissionais_saude/gestao_PS.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    if ($id === (int)$_SESSION['utilizador_id']) { $_SESSION['flash'] = ['tipo'=>'warning','mensagem'=>'Não pode desativar a sua própria conta.']; redirect(APP_URL . '/private/admin/profissionais_saude/gestao_PS.php'); }
    $db->prepare('UPDATE utilizadores SET ativo = 0 WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Profissional desativado.']; redirect(APP_URL . '/private/admin/profissionais_saude/gestao_PS.php');
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="gestao_PS.php">Profissionais</a></li><li class="breadcrumb-item active">Desativar</li></ol></nav>
            <div class="card p-4" style="max-width:560px;margin:0 auto;text-align:center;">
                <div style="width:80px;height:80px;border-radius:50%;background:#fee;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.5rem;color:#8B0000;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h2 class="mb-3" style="color:#8B0000;">Confirmar Desativação</h2>
                <p>Tem a certeza que pretende desativar esta conta? O profissional perderá acesso ao sistema.</p>
                <div class="card p-3 mb-4 text-start">
                    <p class="mb-1"><strong>Nome:</strong> <?= h($user['nome']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= h($user['email']) ?></p>
                    <p class="mb-0"><strong>Perfil:</strong> <?= h($user['perfil']) ?></p>
                </div>
                <form method="POST" class="d-flex gap-3 justify-content-center">
                    <a href="gestao_PS.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Cancelar</a>
                    <button type="submit" name="confirmar" class="btn btn-danger"><i class="fa-solid fa-ban me-1"></i>Desativar</button>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
