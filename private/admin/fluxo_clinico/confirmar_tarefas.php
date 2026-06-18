<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Fluxo Clínico'; $pagina_ativa = 'fluxo_clinico';
requirePerfil('admin');
$db = getDB();

// Ações do administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $acao   = $_POST['acao']   ?? '';
    $tipo   = $_POST['tipo']   ?? '';
    $item_id = (int)($_POST['id'] ?? 0);

    if ($item_id && $acao === 'aprovar') {
        try {
            match ($tipo) {
                'prescricao' => $db->prepare("UPDATE prescricoes SET estado='ativo', aprovado_admin=1 WHERE id=?")->execute([$item_id]),
                'sessao'     => $db->prepare("UPDATE sessoes SET estado='agendada' WHERE id=? AND estado='pendente'")->execute([$item_id]),
                default      => null,
            };
            $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Item aprovado.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['tipo'=>'warning','mensagem'=>'Ação executada (pode precisar de migração DB).'];
        }
    } elseif ($item_id && $acao === 'rejeitar') {
        try {
            match ($tipo) {
                'prescricao' => $db->prepare("UPDATE prescricoes SET estado='cancelado' WHERE id=?")->execute([$item_id]),
                default      => null,
            };
            $_SESSION['flash'] = ['tipo'=>'warning','mensagem'=>'Item rejeitado.'];
        } catch (\Throwable $e) {}
    }
    redirect(APP_URL . '/private/admin/fluxo_clinico/confirmar_tarefas.php');
}

$flash = getFlash();

// --- Sessões pendentes de confirmação ---
$sessoes_pendentes = [];
try {
    $sessoes_pendentes = $db->query("
        SELECT s.id, s.data_hora, s.categoria, s.modalidade,
               u_ut.nome AS utente, u_tec.nome AS tecnico
        FROM sessoes s
        JOIN utentes ut ON ut.id=s.utente_id
        JOIN utilizadores u_ut ON u_ut.id=ut.utilizador_id
        LEFT JOIN profissionais p ON p.id=s.tecnico_id
        LEFT JOIN utilizadores u_tec ON u_tec.id=p.utilizador_id
        WHERE s.estado='pendente'
        ORDER BY s.data_hora
    ")->fetchAll();
} catch (\Throwable $e) {}

// --- Prescrições aguardando aprovação ---
$prescricoes_pendentes = [];
try {
    $prescricoes_pendentes = $db->query("
        SELECT p.id, p.data_prescricao, p.descricao, p.estado,
               u_ut.nome AS utente, u_med.nome AS medico
        FROM prescricoes p
        JOIN utentes ut ON ut.id=p.utente_id
        JOIN utilizadores u_ut ON u_ut.id=ut.utilizador_id
        JOIN profissionais pr ON pr.id=p.medico_id
        JOIN utilizadores u_med ON u_med.id=pr.utilizador_id
        WHERE p.estado='pendente' OR (p.aprovado_admin IS NOT NULL AND p.aprovado_admin=0)
        ORDER BY p.data_prescricao DESC
    ")->fetchAll();
} catch (\Throwable $e) {}


// --- Sessões recentemente concluídas (visão geral) ---
$sessoes_recentes = [];
try {
    $sessoes_recentes = $db->query("
        SELECT s.id, s.data_hora, s.progressao, s.analise_tecnica,
               u_ut.nome AS utente, u_tec.nome AS tecnico, j.nome AS jogo
        FROM sessoes s
        JOIN utentes ut ON ut.id=s.utente_id
        JOIN utilizadores u_ut ON u_ut.id=ut.utilizador_id
        LEFT JOIN profissionais p ON p.id=s.tecnico_id
        LEFT JOIN utilizadores u_tec ON u_tec.id=p.utilizador_id
        LEFT JOIN jogos j ON j.id=s.jogo_id
        WHERE s.estado='concluida' AND s.data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY s.data_hora DESC LIMIT 20
    ")->fetchAll();
} catch (\Throwable $e) {}

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <h1 class="mb-4">Fluxo Clínico</h1>

            <?php if ($flash): ?>
            <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2">
                <?= h($flash['mensagem']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- KPIs rápidos -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-<?= count($sessoes_pendentes)?'warning':'light' ?>">
                        <div class="fs-3 fw-bold text-warning"><?= count($sessoes_pendentes) ?></div>
                        <div class="small text-muted">Sessões pendentes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-<?= count($prescricoes_pendentes)?'danger':'light' ?>">
                        <div class="fs-3 fw-bold text-danger"><?= count($prescricoes_pendentes) ?></div>
                        <div class="small text-muted">Tratamentos por aprovar</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center">
                        <div class="fs-3 fw-bold text-success"><?= count($sessoes_recentes) ?></div>
                        <div class="small text-muted">Sessões (últimos 7 dias)</div>
                    </div>
                </div>
            </div>

            <!-- Sessões pendentes de confirmação -->
            <?php if (!empty($sessoes_pendentes)): ?>
            <div class="card p-4 mb-4 border-warning">
                <h5 class="mb-3"><i class="fa-solid fa-calendar-check me-2 text-warning"></i>Sessões Aguardando Confirmação</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Utente</th><th>Técnico</th><th>Modalidade</th><th>Ação</th></tr></thead>
                        <tbody>
                        <?php foreach ($sessoes_pendentes as $s): ?>
                        <tr>
                            <td><?= h(date('d/m/Y H:i', strtotime($s['data_hora']))) ?></td>
                            <td><?= h($s['utente']) ?></td>
                            <td><?= h($s['tecnico'] ?? '—') ?></td>
                            <td><?= $s['modalidade']==='remota'?'<span class="badge bg-primary">Remota</span>':'<span class="badge bg-secondary">Presencial</span>' ?></td>
                            <td class="d-flex gap-1">
                                <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <input type="hidden" name="tipo" value="sessao">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-check me-1"></i>Confirmar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tratamentos por aprovar -->
            <?php if (!empty($prescricoes_pendentes)): ?>
            <div class="card p-4 mb-4 border-danger">
                <h5 class="mb-3"><i class="fa-solid fa-file-medical me-2 text-danger"></i>Tratamentos Aguardando Aprovação</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Utente</th><th>Médico</th><th>Descrição</th><th>Ação</th></tr></thead>
                        <tbody>
                        <?php foreach ($prescricoes_pendentes as $p): ?>
                        <tr>
                            <td><?= h(date('d/m/Y', strtotime($p['data_prescricao']))) ?></td>
                            <td><?= h($p['utente']) ?></td>
                            <td><?= h($p['medico']) ?></td>
                            <td class="small"><?= h(mb_substr($p['descricao']??'—',0,60)) ?></td>
                            <td class="d-flex gap-1">
                                <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <input type="hidden" name="tipo" value="prescricao">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                </form>
                                <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="acao" value="rejeitar">
                                    <input type="hidden" name="tipo" value="prescricao">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-xmark"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>


            <?php if (empty($sessoes_pendentes) && empty($prescricoes_pendentes)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check me-2"></i>Sem itens pendentes de aprovação.
            </div>
            <?php endif; ?>

            <!-- Atividade clínica recente -->
            <div class="card p-4">
                <h5 class="mb-3"><i class="fa-solid fa-clock-rotate-left me-2" style="color:#8B0000;"></i>Sessões Concluídas — Últimos 7 Dias</h5>
                <?php if (empty($sessoes_recentes)): ?>
                    <p class="text-muted">Sem sessões concluídas nos últimos 7 dias.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Data</th><th>Utente</th><th>Técnico</th><th>Sessão</th><th>Progressão</th></tr></thead>
                        <tbody>
                        <?php foreach ($sessoes_recentes as $s):
                            $pCor = match($s['progressao']??'') { 'melhoria'=>'success', 'regressao'=>'danger', default=>'secondary' };
                        ?>
                        <tr>
                            <td class="small"><?= h(date('d/m/Y', strtotime($s['data_hora']))) ?></td>
                            <td><?= h($s['utente']) ?></td>
                            <td><?= h($s['tecnico'] ?? '—') ?></td>
                            <td class="small"><?= h($s['jogo'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $pCor ?>"><?= h(ucfirst($s['progressao'] ?? '—')) ?></span></td>
                        </tr>
                        <?php if (!empty($s['analise_tecnica'])): ?>
                        <tr class="table-light"><td colspan="5" class="small text-muted ps-3"><i class="fa-solid fa-note-sticky me-1"></i><?= h(mb_substr($s['analise_tecnica'],0,120)) ?></td></tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
