<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('admin');
$pagina_titulo = 'Backoffice - Acordos'; $pagina_ativa = 'backoffice';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $db->prepare("INSERT INTO backoffice_conteudo (chave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?");
    $chaves = ['seguros','contacto_morada','contacto_tel','contacto_telemovel',
               'contacto_horario_semana','contacto_horario_sabado'];
    foreach ($chaves as $ch) {
        $val = trim($_POST[$ch] ?? '');
        $s->execute([$ch, $val, $val]);
    }
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Conteúdo guardado com sucesso.'];
    redirect(APP_URL . '/private/admin/backoffice/backoffice_seguros.php');
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
                <a href="backoffice_servicos.php"    class="dashboard-tab"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php"     class="dashboard-tab active"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>

            <form method="POST">
                <!-- Seguros -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3" style="color:#8B0000;"><i class="fa-solid fa-handshake me-2"></i>Acordos e Seguros</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lista de seguradoras / parceiros</label>
                        <input type="text" name="seguros" class="form-control"
                               value="<?= h($c['seguros'] ?? '') ?>"
                               placeholder="Multicare,AdvanceCare,Médis,Allianz,SNS,...">
                        <div class="form-text">Separa cada nome por vírgula. Cada um aparece como um cartão na página.</div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Pré-visualização:</small><br>
                        <?php
                        $prev = array_filter(array_map('trim', explode(',', $c['seguros'] ?? '')));
                        foreach ($prev as $seg): ?>
                            <span class="badge bg-secondary me-1 mb-1"><?= h($seg) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Contacto -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3" style="color:#8B0000;"><i class="fa-solid fa-phone me-2"></i>Informações de Contacto</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Morada</label>
                            <input type="text" name="contacto_morada" class="form-control"
                                   value="<?= h($c['contacto_morada'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="text" name="contacto_tel" class="form-control"
                                   value="<?= h($c['contacto_tel'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Telemóvel</label>
                            <input type="text" name="contacto_telemovel" class="form-control"
                                   value="<?= h($c['contacto_telemovel'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Horário — Semana</label>
                            <input type="text" name="contacto_horario_semana" class="form-control"
                                   value="<?= h($c['contacto_horario_semana'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Horário — Sábado</label>
                            <input type="text" name="contacto_horario_sabado" class="form-control"
                                   value="<?= h($c['contacto_horario_sabado'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn px-4" style="background:#8B0000;color:#fff;">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Alterações
                </button>
                <a href="<?= APP_URL ?>/index.php" target="_blank" class="btn btn-outline-secondary ms-2">
                    <i class="fa-solid fa-eye me-1"></i>Ver página
                </a>
            </form>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
