<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Detalhes Dispositivo'; $pagina_ativa = 'dispositivos';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
?>

        <main class="content">
            <?php
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) redirect(APP_URL . '/private/admin/dispositivos/lista_dispositivos.php');
            $stmt = $db->prepare("SELECT d.*, u.nome AS paciente FROM dispositivos d LEFT JOIN utentes ut ON ut.id = d.utente_id LEFT JOIN utilizadores u ON u.id = ut.utilizador_id WHERE d.id = ?");
            $stmt->execute([$id]); $dev = $stmt->fetch();
            if (!$dev) redirect(APP_URL . '/private/admin/dispositivos/lista_dispositivos.php');
            $sl = $db->prepare("SELECT COUNT(*) FROM leituras_emg l JOIN sessoes s ON s.id = l.sessao_id WHERE s.dispositivo_id = ?");
            $sl->execute([$id]); $n_leituras = (int)$sl->fetchColumn();
            ?>
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_dispositivos.php">Dispositivos</a></li><li class="breadcrumb-item active"><?= h($dev['codigo']) ?></li></ol></nav>
            <h1 class="mb-4">Dispositivo <?= h($dev['codigo']) ?></h1>
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-3 mb-3">
                        <h5><i class="fa-solid fa-info-circle me-2" style="color:#8B0000;"></i>Informação Geral</h5><hr>
                        <p><strong>Código:</strong> <?= h($dev['codigo']) ?></p>
                        <p><strong>Tipo:</strong> <?= h($dev['tipo']) ?></p>
                        <p><strong>Firmware:</strong> <?= h($dev['firmware_versao'] ?? '—') ?></p>
                        <p><strong>Paciente:</strong> <?= h($dev['paciente'] ?? 'Não associado') ?></p>
                        <p><strong>Associado em:</strong> <?= $dev['associado_em'] ? h(substr($dev['associado_em'],0,10)) : '—' ?></p>
                        <p><strong>Último sync:</strong> <?= $dev['ultimo_sync'] ? h(substr($dev['ultimo_sync'],0,16)) : 'Nunca' ?></p>
                        <p><strong>Estado:</strong> <?= $dev['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></p>
                        <p><strong>Leituras EMG:</strong> <?= number_format($n_leituras) ?></p>
                    </div>
                </div>
            </div>
            <a href="lista_dispositivos.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
