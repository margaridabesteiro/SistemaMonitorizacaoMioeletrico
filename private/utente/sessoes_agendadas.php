<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Próximas Sessões'; $pagina_ativa = 'sessoes';
require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT ut.id FROM utentes ut WHERE ut.utilizador_id=?");
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

$sessoes = [];
if ($utid) {
    $stmt2 = $db->prepare("
        SELECT s.*, u.nome AS tecnico, j.nome AS jogo_nome, j.nivel AS jogo_nivel,
               d.codigo AS dispositivo
        FROM sessoes s
        LEFT JOIN profissionais p ON p.id=s.tecnico_id
        LEFT JOIN utilizadores u ON u.id=p.utilizador_id
        LEFT JOIN jogos j ON j.id=s.jogo_id
        LEFT JOIN dispositivos d ON d.id=s.dispositivo_id
        WHERE s.utente_id=? AND s.data_hora >= NOW() AND s.estado IN ('agendada','em_curso')
        ORDER BY s.data_hora LIMIT 50");
    $stmt2->execute([$utid]); $sessoes = $stmt2->fetchAll();
}

$nivel_badges = ['minimo'=>'success','medio'=>'warning','maximo'=>'danger'];
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Próximas Sessões</h1>
            </div>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="color:#212529;background:#fff;">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Hora</th><th>Jogo / Categoria</th><th>Técnico</th><th>Modalidade</th><th>Duração</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($sessoes)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Sem sessões agendadas.</td></tr>
                    <?php else: foreach ($sessoes as $s): ?>
                        <tr>
                            <td><?= h(substr($s['data_hora'],0,10)) ?></td>
                            <td><?= h(substr($s['data_hora'],11,5)) ?></td>
                            <td>
                                <?php if ($s['jogo_nome']): ?>
                                    <?= h($s['jogo_nome']) ?>
                                    <span class="badge bg-<?= $nivel_badges[$s['jogo_nivel']] ?? 'secondary' ?> ms-1"><?= h($s['jogo_nivel']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted"><?= h(ucfirst(str_replace('_',' ',$s['categoria']??'—'))) ?></span>
                                <?php endif; ?>
                                <?php if ($s['modalidade']==='remota' && $s['link_videochamada']): ?>
                                    <a href="<?= h($s['link_videochamada']) ?>" target="_blank" class="btn btn-xs btn-primary ms-2"><i class="fa-solid fa-video me-1"></i>Entrar</a>
                                <?php endif; ?>
                            </td>
                            <td><?= h($s['tecnico'] ?? '—') ?></td>
                            <td>
                                <?= $s['modalidade']==='remota'
                                    ? '<span class="badge bg-primary"><i class="fa-solid fa-video me-1"></i>Remota</span>'
                                    : '<span class="badge bg-secondary">Presencial</span>' ?>
                            </td>
                            <td><?= $s['duracao_min'] ? h($s['duracao_min']).' min' : '—' ?></td>
                            <td><span class="badge bg-<?= $s['estado']==='agendada'?'warning text-dark':'success' ?>"><?= h($s['estado']) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
