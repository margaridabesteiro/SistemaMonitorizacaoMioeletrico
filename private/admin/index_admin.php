<?php
// private/admin/index_admin.php
// Dashboard administrativo — exemplo de página PHP completa com includes

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pagina_titulo = 'Dashboard Admin';
$pagina_ativa  = 'dashboard';

require_once __DIR__ . '/../../includes/header_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';

// --- Dados reais da BD ---
$db = getDB();

// Contagem de utilizadores ativos por perfil
$stats = $db->query('
    SELECT perfil, COUNT(*) AS total
    FROM utilizadores
    WHERE ativo = 1
    GROUP BY perfil
')->fetchAll();
$contagens = array_column($stats, 'total', 'perfil');

// Sessões do dia
$sessoes_hoje = $db->query('
    SELECT COUNT(*) FROM sessoes
    WHERE DATE(data_hora) = CURDATE()
')->fetchColumn();

// Faturação do mês atual
$faturacao_mes = $db->query('
    SELECT COALESCE(SUM(valor_eur),0) FROM faturas
    WHERE MONTH(data_emissao) = MONTH(NOW()) AND YEAR(data_emissao) = YEAR(NOW())
')->fetchColumn();

// Dispositivos sem sync há +3 dias
$disp_sem_sync = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE ativo=1 AND (ultimo_sync IS NULL OR ultimo_sync < DATE_SUB(NOW(), INTERVAL 3 DAY))")->fetchColumn();

// Últimos 5 logs de acesso
$logs = $db->query('
    SELECT l.acao, l.ip, l.criado_em, u.nome
    FROM logs_acesso l
    LEFT JOIN utilizadores u ON u.id = l.utilizador_id
    ORDER BY l.criado_em DESC
    LIMIT 5
')->fetchAll();
?>
        <!-- Conteúdo principal -->
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Dashboard de Controlo</h1>
                <span class="badge" style="background:#8B0000;color:white;padding:8px 15px;">
                    <i class="fa-regular fa-clock me-2"></i><?= date('d M Y H:i') ?>
                </span>
            </div>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-danger"><?= $contagens['medico'] ?? 0 ?></div>
                        <div class="text-muted small">Médicos Ativos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= $contagens['tecnico'] ?? 0 ?></div>
                        <div class="text-muted small">Técnicos Ativos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-success"><?= $contagens['utente'] ?? 0 ?></div>
                        <div class="text-muted small">Utentes Ativos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center p-3">
                        <div class="fs-2 fw-bold text-warning"><?= $sessoes_hoje ?></div>
                        <div class="text-muted small">Sessões Hoje</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="<?= APP_URL ?>/private/admin/dispositivos/lista_dispositivos.php" class="text-decoration-none">
                        <div class="card text-center p-3 <?= $disp_sem_sync > 0 ? 'border-danger' : '' ?>">
                            <div class="fs-2 fw-bold <?= $disp_sem_sync > 0 ? 'text-danger' : 'text-muted' ?>"><?= $disp_sem_sync ?></div>
                            <div class="text-muted small">Disp. sem sync &gt;3 dias</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Faturação do mês -->
            <div class="card p-3 mb-4">
                <h5>Faturação do Mês</h5>
                <div class="fs-3 fw-bold text-danger"><?= number_format((float)$faturacao_mes, 2, ',', '.') ?> €</div>
            </div>

            <!-- Últimos logs -->
            <div class="card p-3">
                <h5 class="mb-3">Últimos Acessos</h5>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr><th>Utilizador</th><th>Ação</th><th>Data/Hora</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= h($log['nome'] ?? 'Anónimo') ?></td>
                            <td><?= h($log['acao']) ?></td>
                            <td><?= h($log['criado_em']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?= APP_URL ?>/private/admin/seguranca/logs_acesso.php" class="btn btn-sm btn-outline-secondary mt-2">
                    Ver todos os logs
                </a>
            </div>
        </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
