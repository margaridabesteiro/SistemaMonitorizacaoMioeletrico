<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Próximas Sessões'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT ut.id FROM utentes ut WHERE ut.utilizador_id=?"); $stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();
$filtro_tipo = $_GET['tipo'] ?? '';
$where = 'WHERE s.utente_id=? AND s.data_hora >= NOW() AND s.estado IN (\'agendada\',\'em_curso\')'; $params = [$utid];
if ($filtro_tipo !== '') { $where .= ' AND s.tipo LIKE ?'; $params[] = "%$filtro_tipo%"; }
$sessoes = [];
if ($utid) {
    $stmt2 = $db->prepare("SELECT s.*, u.nome AS tecnico, d.codigo AS dispositivo FROM sessoes s LEFT JOIN profissionais p ON p.id=s.tecnico_id LEFT JOIN utilizadores u ON u.id=p.utilizador_id LEFT JOIN dispositivos d ON d.id=s.dispositivo_id $where ORDER BY s.data_hora LIMIT 50");
    $stmt2->execute($params); $sessoes = $stmt2->fetchAll();
}
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Próximas Sessões</h1>
                <div class="d-flex align-items-center gap-3">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="fw-semibold text-muted">Filtrar:</label>
                        <select name="tipo" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="">Todos os tipos</option>
                            <option value="forca" <?= $filtro_tipo==='forca'?'selected':'' ?>>Treino de força</option>
                            <option value="precisao" <?= $filtro_tipo==='precisao'?'selected':'' ?>>Treino de precisão</option>
                            <option value="avaliacao" <?= $filtro_tipo==='avaliacao'?'selected':'' ?>>Avaliação funcional</option>
                            <option value="gamificado" <?= $filtro_tipo==='gamificado'?'selected':'' ?>>Sessão gamificada</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>Técnico</th><th>Tipo</th><th>Data</th><th>Hora</th><th>Duração</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($sessoes)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Sem sessões agendadas.</td></tr>
                    <?php else: foreach ($sessoes as $s): ?>
                        <tr>
                            <td><?= h($s['tecnico'] ?? '—') ?></td>
                            <td><?= h($s['tipo'] ?? '—') ?></td>
                            <td><?= h(substr($s['data_hora'],0,10)) ?></td>
                            <td><?= h(substr($s['data_hora'],11,5)) ?></td>
                            <td><?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></td>
                            <td>
                                <span class="badge bg-<?= $s['estado']==='agendada'?'warning text-dark':'success' ?>">
                                    <?= h($s['estado']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
