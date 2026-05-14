<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Backoffice - Equipa'; $pagina_ativa = 'backoffice';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$medicos = $db->query("SELECT u.nome, p.especialidade, p.instituicao FROM utilizadores u JOIN profissionais p ON p.utilizador_id=u.id WHERE u.perfil='medico' AND u.ativo=1 ORDER BY u.nome")->fetchAll();
?>
        <main class="content">
            <div class="dashboard-tabs mb-4">
                <a href="backoffice_quem_somos.php" class="dashboard-tab"><i class="fa-solid fa-building"></i> Quem Somos</a>
                <a href="backoffice_equipa.php" class="dashboard-tab active"><i class="fa-solid fa-users"></i> Nossa Equipa</a>
                <a href="backoffice_servicos.php" class="dashboard-tab"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php" class="dashboard-tab"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <h1 class="mb-4" style="color:#8B0000;">Backoffice — Nossa Equipa</h1>
            <div class="card p-3 mb-4">
                <h5>Médicos Ativos no Sistema</h5>
                <div class="row mt-3">
                    <?php foreach($medicos as $m): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card p-3 text-center">
                            <i class="fa-solid fa-user-doctor fa-2x mb-2" style="color:#8B0000;"></i>
                            <strong><?= h($m['nome']) ?></strong>
                            <p class="text-muted small mb-0"><?= h($m['especialidade'] ?? '—') ?></p>
                            <p class="text-muted small"><?= h($m['instituicao'] ?? '—') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($medicos)): ?><div class="col-12"><p class="text-muted">Sem médicos registados.</p></div><?php endif; ?>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
