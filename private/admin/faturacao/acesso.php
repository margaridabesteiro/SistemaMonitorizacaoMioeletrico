<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Registo de Acessos (Faturação)'; $pagina_ativa = 'faturacao';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
$logs = $db->query("SELECT l.acao, l.ip, l.criado_em, u.nome FROM logs_acesso l LEFT JOIN utilizadores u ON u.id=l.utilizador_id ORDER BY l.criado_em DESC LIMIT 50")->fetchAll();
?>
        <main class="content">
            <h1 class="mb-4">Registo de Acessos</h1>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Utilizador</th><th>Ação</th><th>IP</th><th>Data/Hora</th></tr></thead>
                    <tbody>
                    <?php foreach($logs as $l): ?>
                        <tr><td><?= h($l['nome'] ?? 'Anónimo') ?></td><td><?= h($l['acao']) ?></td><td><?= h($l['ip']) ?></td><td><?= h($l['criado_em']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
