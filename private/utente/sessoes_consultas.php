<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
requirePerfil('utente');
$pagina_titulo = 'Sessões / Consultas'; $pagina_ativa = 'sessoes';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT id FROM utentes WHERE utilizador_id=?");
$stmt->execute([$uid]); $utid = (int)$stmt->fetchColumn();

$sessoes = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT s.id, s.data_hora, s.estado, s.modalidade, s.link_videochamada,
                   COALESCE(j.nome, s.categoria, 'Sessão de Treino') AS titulo,
                   u.nome AS profissional, 'sessao' AS tipo_item
            FROM sessoes s
            LEFT JOIN jogos j ON j.id = s.jogo_id
            LEFT JOIN profissionais p ON p.id = s.tecnico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE s.utente_id = ? AND s.estado != 'cancelada'
            ORDER BY s.data_hora DESC
        ");
        $s->execute([$utid]);
        $sessoes = $s->fetchAll();
    } catch (\Throwable $e) {}
}

$consultas = [];
if ($utid) {
    try {
        $s = $db->prepare("
            SELECT c.id, c.data_hora, c.estado, c.modalidade, c.link_videochamada,
                   COALESCE(c.tipo, 'Consulta Médica') AS titulo,
                   u.nome AS profissional, 'consulta' AS tipo_item
            FROM consultas c
            LEFT JOIN profissionais p ON p.id = c.medico_id
            LEFT JOIN utilizadores u ON u.id = p.utilizador_id
            WHERE c.utente_id = ? AND c.estado != 'cancelada'
            ORDER BY c.data_hora DESC
        ");
        $s->execute([$utid]);
        $consultas = $s->fetchAll();
    } catch (\Throwable $e) {}
}

$todos = array_merge($sessoes, $consultas);
usort($todos, fn($a, $b) => strtotime($b['data_hora']) <=> strtotime($a['data_hora']));

require_once __DIR__ . '/../../includes/header_utente.php';
require_once __DIR__ . '/../../includes/sidebar_utente.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Sessões / Consultas</h1>
                <a href="agenda.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-regular fa-calendar me-1"></i>Ver Agenda
                </a>
            </div>

            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Profissional</th>
                            <th>Data / Hora</th>
                            <th>Modalidade</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($todos)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            <i class="fa-regular fa-calendar-xmark fa-2x d-block mb-2 opacity-50"></i>
                            Sem sessões ou consultas.
                        </td></tr>
                    <?php else: foreach ($todos as $item):
                        $e_sessao = $item['tipo_item'] === 'sessao';
                        $ts       = strtotime($item['data_hora']);
                        $e_video  = in_array($item['modalidade'], ['video', 'remota'], true);
                        $cor_tipo = $e_sessao ? '#667eea' : '#8B0000';
                        $estado_cores = [
                            'agendada'  => 'primary',
                            'concluida' => 'success',
                            'cancelada' => 'danger',
                            'em_curso'  => 'info text-dark',
                        ];
                    ?>
                        <tr>
                            <td>
                                <span class="badge" style="background:<?= $cor_tipo ?>;">
                                    <i class="fa-solid <?= $e_sessao ? 'fa-dumbbell' : 'fa-stethoscope' ?> me-1"></i>
                                    <?= $e_sessao ? 'Sessão' : 'Consulta' ?>
                                </span>
                            </td>
                            <td class="fw-semibold"><?= h($item['profissional'] ?? '—') ?></td>
                            <td><?= h(date('d/m/Y H:i', $ts)) ?></td>
                            <td>
                                <?php if ($e_video): ?>
                                    <span class="badge bg-primary"><i class="fa-solid fa-video me-1"></i>Remota</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-hospital me-1"></i>Presencial</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $estado_cores[$item['estado']] ?? 'secondary' ?>">
                                    <?= h(ucfirst($item['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($e_video && !empty($item['link_videochamada'])): ?>
                                <a href="<?= h($item['link_videochamada']) ?>" target="_blank" rel="noopener"
                                   class="btn btn-xs btn-primary">
                                    <i class="fa-solid fa-video me-1"></i>Entrar
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
