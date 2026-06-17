<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Apagar Fatura'; $pagina_ativa = 'faturacao';
$db = getDB();
$num = trim($_GET['num'] ?? '');
if (!$num) redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
$stmt = $db->prepare("SELECT f.*, u.nome AS utente FROM faturas f JOIN utentes ut ON ut.id=f.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id WHERE f.numero=?");
$stmt->execute([$num]); $f = $stmt->fetch();
if (!$f) redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $db->prepare('DELETE FROM faturas WHERE numero=?')->execute([$f['numero']]);
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Fatura eliminada.']; redirect(APP_URL . '/private/admin/faturacao/controlo_faturacao.php');
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="card p-4" style="max-width:560px;margin:0 auto;text-align:center;">
                <div style="width:80px;height:80px;border-radius:50%;background:#fee;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.5rem;color:#8B0000;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h2 style="color:#8B0000;">Confirmar Eliminação</h2>
                <p class="mb-4">Esta ação não pode ser desfeita.</p>
                <div class="card p-3 mb-4 text-start">
                    <p class="mb-1"><strong>Nº Fatura:</strong> <?= h($f['numero']) ?></p>
                    <p class="mb-1"><strong>Utente:</strong> <?= h($f['utente']) ?></p>
                    <p class="mb-1"><strong>Valor:</strong> <?= number_format((float)$f['valor_eur'],2,',','.') ?>€</p>
                    <p class="mb-0"><strong>Estado:</strong> <?= $f['paga']?'Paga':'Pendente' ?></p>
                </div>
                <form method="POST" class="d-flex gap-3 justify-content-center">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <a href="controlo_faturacao.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Cancelar</a>
                    <button type="submit" name="confirmar" class="btn btn-danger"><i class="fa-regular fa-trash-can me-1"></i>Eliminar</button>
                </form>
            </div>
        </main>
