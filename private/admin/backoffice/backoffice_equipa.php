<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Backoffice - Equipa'; $pagina_ativa = 'backoffice';
$db = getDB();

$upload_dir = APP_ROOT . '/public/assets/img/medicos/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']          ?? '';
    $prof_id = (int)($_POST['profissional_id'] ?? 0);
    $util_id = (int)($_POST['utilizador_id']   ?? 0);

    if ($action === 'salvar' && $prof_id) {
        $legenda   = trim($_POST['legenda'] ?? '');
        $foto_path = null;

        if (!empty($_FILES['foto']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                $_SESSION['flash'] = ['tipo'=>'danger','mensagem'=>'Formato inválido. Use JPG, PNG ou WebP.'];
                redirect(APP_URL . '/private/admin/backoffice/backoffice_equipa.php');
            }
            if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $_SESSION['flash'] = ['tipo'=>'danger','mensagem'=>'Imagem demasiado grande (máx. 2 MB).'];
                redirect(APP_URL . '/private/admin/backoffice/backoffice_equipa.php');
            }
            $filename  = 'medico_' . $prof_id . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $filename);
            $foto_path = 'public/assets/img/medicos/' . $filename;
        }

        if ($foto_path !== null) {
            $db->prepare("UPDATE profissionais SET legenda=?, foto_path=? WHERE id=?")
               ->execute([$legenda, $foto_path, $prof_id]);
        } else {
            $db->prepare("UPDATE profissionais SET legenda=? WHERE id=?")
               ->execute([$legenda, $prof_id]);
        }
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Dados guardados com sucesso.'];
        redirect(APP_URL . '/private/admin/backoffice/backoffice_equipa.php');
    }

    if ($action === 'toggle' && $util_id) {
        $db->prepare("UPDATE utilizadores SET ativo = NOT ativo WHERE id=? AND perfil='medico'")
           ->execute([$util_id]);
        $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Estado do médico atualizado.'];
        redirect(APP_URL . '/private/admin/backoffice/backoffice_equipa.php');
    }
}

$medicos = $db->query("
    SELECT u.id AS util_id, u.nome, u.email, u.ativo,
           p.id AS prof_id, p.especialidade, p.foto_path, p.legenda
    FROM utilizadores u
    JOIN profissionais p ON p.utilizador_id = u.id
    WHERE u.perfil = 'medico'
    ORDER BY u.ativo DESC, u.nome
")->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="dashboard-tabs mb-4">
                <a href="backoffice_quem_somos.php" class="dashboard-tab"><i class="fa-solid fa-building"></i> Quem Somos</a>
                <a href="backoffice_equipa.php"      class="dashboard-tab active"><i class="fa-solid fa-users"></i> Nossa Equipa</a>
                <a href="backoffice_servicos.php"    class="dashboard-tab"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php"     class="dashboard-tab"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <h1 class="mb-4" style="color:#8B0000;">Backoffice — Nossa Equipa</h1>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div>
            <?php endif; ?>

            <div class="card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color:#8B0000;"><i class="fa-solid fa-user-doctor me-2"></i>Médicos Ativos no Sistema</h5>
                    <a href="<?= APP_URL ?>/private/admin/utilizadores/novo_utilizador.php" class="btn btn-sm" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-plus me-1"></i>Adicionar médico
                    </a>
                </div>

                <?php if (empty($medicos)): ?>
                    <p class="text-muted">Sem médicos registados.</p>
                <?php else: ?>
                    <div class="row g-4">
                    <?php foreach ($medicos as $m): ?>
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm <?= $m['ativo'] ? '' : 'border-secondary' ?>" style="<?= $m['ativo'] ? '' : 'opacity:.75;' ?>">
                                <div class="text-center pt-4 pb-2">
                                    <?php if ($m['foto_path'] && file_exists(APP_ROOT . '/' . $m['foto_path'])): ?>
                                        <img src="<?= APP_URL . '/' . h($m['foto_path']) ?>?v=<?= filemtime(APP_ROOT . '/' . $m['foto_path']) ?>"
                                             alt="<?= h($m['nome']) ?>"
                                             style="width:110px;height:110px;object-fit:cover;border-radius:50%;border:3px solid <?= $m['ativo'] ? '#8B0000' : '#aaa' ?>;">
                                    <?php else: ?>
                                        <div style="width:110px;height:110px;border-radius:50%;background:#f5f5f5;display:inline-flex;align-items:center;justify-content:center;border:3px solid <?= $m['ativo'] ? '#8B0000' : '#aaa' ?>;">
                                            <i class="fa-solid fa-user-doctor fa-2x" style="color:#8B0000;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body pt-1">
                                    <h6 class="text-center fw-bold mb-0"><?= h($m['nome']) ?></h6>
                                    <p class="text-center text-muted small mb-2"><?= h($m['especialidade'] ?? '—') ?></p>
                                    <?php if (!$m['ativo']): ?>
                                        <div class="text-center mb-2"><span class="badge bg-secondary">Inativo</span></div>
                                    <?php endif; ?>

                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="salvar">
                                        <input type="hidden" name="profissional_id" value="<?= $m['prof_id'] ?>">
                                        <input type="hidden" name="utilizador_id"   value="<?= $m['util_id'] ?>">
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small mb-1">Legenda</label>
                                            <textarea name="legenda" class="form-control form-control-sm" rows="2" placeholder="Descrição breve do médico..."><?= h($m['legenda'] ?? '') ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small mb-1">Foto <span class="text-muted fw-normal">(JPG/PNG/WebP, máx. 2MB)</span></label>
                                            <input type="file" name="foto" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
                                        </div>
                                        <button type="submit" class="btn btn-sm w-100 mb-2" style="background:#8B0000;color:#fff;">
                                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                                        </button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="utilizador_id"   value="<?= $m['util_id'] ?>">
                                        <input type="hidden" name="profissional_id" value="<?= $m['prof_id'] ?>">
                                        <button type="submit" class="btn btn-sm w-100 <?= $m['ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <i class="fa-solid fa-<?= $m['ativo'] ? 'ban' : 'circle-check' ?> me-1"></i>
                                            <?= $m['ativo'] ? 'Inativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <a href="<?= APP_URL ?>/index.php?preview=1#nossa-equipa" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-eye me-1"></i>Ver página
            </a>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
