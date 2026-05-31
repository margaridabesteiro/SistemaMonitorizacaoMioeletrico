<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Backoffice - Quem Somos'; $pagina_ativa = 'backoffice';
$db = getDB();

// Gravar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chaves = ['hero_titulo','hero_subtitulo','hero_descricao',
               'hero_stat1_num','hero_stat1_label',
               'hero_stat2_num','hero_stat2_label',
               'hero_stat3_num','hero_stat3_label',
               'qs_h3','qs_texto'];
    $s = $db->prepare("INSERT INTO backoffice_conteudo (chave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?");
    foreach ($chaves as $ch) {
        $val = trim($_POST[$ch] ?? '');
        $s->execute([$ch, $val, $val]);
    }
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Conteúdo guardado com sucesso.'];
    redirect(APP_URL . '/private/admin/backoffice/backoffice_quem_somos.php');
}

// Carregar valores actuais
$c = $db->query('SELECT chave, valor FROM backoffice_conteudo')->fetchAll(PDO::FETCH_KEY_PAIR);
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="dashboard-tabs mb-4">
                <a href="backoffice_quem_somos.php" class="dashboard-tab active"><i class="fa-solid fa-building"></i> Quem Somos</a>
                <a href="backoffice_equipa.php"      class="dashboard-tab"><i class="fa-solid fa-users"></i> Nossa Equipa</a>
                <a href="backoffice_servicos.php"    class="dashboard-tab"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php"     class="dashboard-tab"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>

            <form method="POST">
                <!-- Hero -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3" style="color:#8B0000;"><i class="fa-solid fa-star me-2"></i>Secção Hero (topo da página)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Título (linha 1)</label>
                            <input type="text" name="hero_titulo" class="form-control" value="<?= h($c['hero_titulo'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Título (linha 2 — sublinhada)</label>
                            <input type="text" name="hero_subtitulo" class="form-control" value="<?= h($c['hero_subtitulo'] ?? '') ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea name="hero_descricao" class="form-control" rows="2"><?= h($c['hero_descricao'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-2"><label class="form-label fw-semibold">Estatística 1 — Número</label><input type="text" name="hero_stat1_num" class="form-control" value="<?= h($c['hero_stat1_num'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-2"><label class="form-label fw-semibold">Estatística 1 — Legenda</label><input type="text" name="hero_stat1_label" class="form-control" value="<?= h($c['hero_stat1_label'] ?? '') ?>"></div>
                        <div class="col-md-2 mb-2"><label class="form-label fw-semibold">Estatística 2 — Número</label><input type="text" name="hero_stat2_num" class="form-control" value="<?= h($c['hero_stat2_num'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-2"><label class="form-label fw-semibold">Estatística 2 — Legenda</label><input type="text" name="hero_stat2_label" class="form-control" value="<?= h($c['hero_stat2_label'] ?? '') ?>"></div>
                        <div class="col-md-2 mb-2"><label class="form-label fw-semibold">Estatística 3 — Número</label><input type="text" name="hero_stat3_num" class="form-control" value="<?= h($c['hero_stat3_num'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-2"><label class="form-label fw-semibold">Estatística 3 — Legenda</label><input type="text" name="hero_stat3_label" class="form-control" value="<?= h($c['hero_stat3_label'] ?? '') ?>"></div>
                    </div>
                </div>

                <!-- Quem Somos -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3" style="color:#8B0000;"><i class="fa-solid fa-building me-2"></i>Secção "Quem Somos"</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título em destaque (h3)</label>
                        <input type="text" name="qs_h3" class="form-control" value="<?= h($c['qs_h3'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parágrafo de texto</label>
                        <textarea name="qs_texto" class="form-control" rows="4"><?= h($c['qs_texto'] ?? '') ?></textarea>
                    </div>
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
