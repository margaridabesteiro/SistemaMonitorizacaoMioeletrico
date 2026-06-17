<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Backoffice - Serviços'; $pagina_ativa = 'backoffice';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $s = $db->prepare("INSERT INTO backoffice_conteudo (chave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?");
    for ($i = 1; $i <= 6; $i++) {
        foreach (['titulo','icone','desc'] as $campo) {
            $ch  = "servico_{$i}_{$campo}";
            $val = trim($_POST[$ch] ?? '');
            $s->execute([$ch, $val, $val]);
        }
    }
    for ($i = 1; $i <= 4; $i++) {
        foreach (['nome','morada','tel'] as $campo) {
            $ch  = "unidade_{$i}_{$campo}";
            $val = trim($_POST[$ch] ?? '');
            $s->execute([$ch, $val, $val]);
        }
    }
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Conteúdo guardado com sucesso.'];
    redirect(APP_URL . '/private/admin/backoffice/backoffice_servicos.php');
}

$c = $db->query('SELECT chave, valor FROM backoffice_conteudo')->fetchAll(PDO::FETCH_KEY_PAIR);
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="dashboard-tabs mb-4">
                <a href="backoffice_quem_somos.php" class="dashboard-tab"><i class="fa-solid fa-building"></i> Quem Somos</a>
                <a href="backoffice_equipa.php"      class="dashboard-tab"><i class="fa-solid fa-users"></i> Nossa Equipa</a>
                <a href="backoffice_servicos.php"    class="dashboard-tab active"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php"     class="dashboard-tab"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>

            <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <!-- Serviços -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3" style="color:#8B0000;"><i class="fa-solid fa-stethoscope me-2"></i>Os Nossos Serviços (6 cartões)</h5>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="border rounded p-3 mb-3">
                        <p class="fw-semibold mb-2">Serviço <?= $i ?></p>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small">Título</label>
                                <input type="text" name="servico_<?= $i ?>_titulo" class="form-control form-control-sm"
                                       value="<?= h($c["servico_{$i}_titulo"] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Ícone FontAwesome</label>
                                <input type="text" name="servico_<?= $i ?>_icone" class="form-control form-control-sm"
                                       placeholder="fa-solid fa-stethoscope"
                                       value="<?= h($c["servico_{$i}_icone"] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Descrição</label>
                                <input type="text" name="servico_<?= $i ?>_desc" class="form-control form-control-sm"
                                       value="<?= h($c["servico_{$i}_desc"] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Unidades -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3" style="color:#8B0000;"><i class="fa-solid fa-building me-2"></i>As Nossas Unidades (4)</h5>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="border rounded p-3 mb-3">
                        <p class="fw-semibold mb-2">Unidade <?= $i ?></p>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Nome</label>
                                <input type="text" name="unidade_<?= $i ?>_nome" class="form-control form-control-sm"
                                       value="<?= h($c["unidade_{$i}_nome"] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Morada</label>
                                <input type="text" name="unidade_<?= $i ?>_morada" class="form-control form-control-sm"
                                       value="<?= h($c["unidade_{$i}_morada"] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Telefone</label>
                                <input type="text" name="unidade_<?= $i ?>_tel" class="form-control form-control-sm"
                                       value="<?= h($c["unidade_{$i}_tel"] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn px-4" style="background:#8B0000;color:#fff;">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Alterações
                </button>
                <a href="<?= APP_URL ?>/index.php?preview=1" target="_blank" class="btn btn-outline-secondary ms-2">
                    <i class="fa-solid fa-eye me-1"></i>Ver página
                </a>
            </form>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
