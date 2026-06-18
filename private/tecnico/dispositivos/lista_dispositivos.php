<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$pagina_titulo = 'Dispositivos'; $pagina_ativa = 'dispositivos';

// Restaurar dispositivo avariado/perdido para disponível
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_acao'] ?? '') === 'disponivel') {
    $disp_id = (int)($_POST['disp_id'] ?? 0);
    if ($disp_id) {
        $db_tmp = getDB();
        $atual  = $db_tmp->prepare('SELECT estado FROM dispositivos WHERE id=?');
        $atual->execute([$disp_id]);
        $estado_atual = $atual->fetchColumn();
        if (in_array($estado_atual, ['avariado','danificado','perdido'], true)) {
            $db_tmp->prepare('UPDATE dispositivos SET estado=\'disponivel\' WHERE id=?')->execute([$disp_id]);
            registarAuditoria('ATUALIZAR', 'Dispositivo', $disp_id, 'Estado restaurado para disponível pelo técnico');
            $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Dispositivo marcado como disponível.'];
        }
    }
    redirect(APP_URL . '/private/tecnico/dispositivos/lista_dispositivos.php');
}

$db = getDB();

$total_disp  = (int)$db->query("SELECT COUNT(*) FROM dispositivos")->fetchColumn();
$emprestados = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE estado='emprestado'")->fetchColumn();
$avariados   = (int)$db->query("SELECT COUNT(*) FROM dispositivos WHERE estado IN ('avariado','danificado','perdido')")->fetchColumn();

$dispositivos = $db->query("
    SELECT d.*,
           u.nome AS paciente,
           e.data_entrega, e.id AS emp_id,
           (SELECT s.data_hora FROM sessoes s
            WHERE s.dispositivo_id = d.id
              AND s.estado IN ('agendada','em_curso')
              AND DATE(s.data_hora) = CURDATE()
            ORDER BY s.data_hora ASC LIMIT 1) AS sessao_hoje,
           (SELECT s.data_hora FROM sessoes s
            WHERE s.dispositivo_id = d.id
              AND s.estado IN ('agendada','em_curso')
              AND DATE(s.data_hora) > CURDATE()
            ORDER BY s.data_hora ASC LIMIT 1) AS proxima_sessao
    FROM dispositivos d
    LEFT JOIN emprestimos_dispositivos e ON e.dispositivo_id=d.id AND e.data_devolucao IS NULL
    LEFT JOIN utentes ut ON ut.id=e.utente_id
    LEFT JOIN utilizadores u ON u.id=ut.utilizador_id
    ORDER BY d.codigo
")->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$estado_badge = [
    'disponivel'   => 'success',
    'em_sessao'    => 'warning text-dark',
    'agendado'     => 'info text-dark',
    'emprestado'   => 'primary',
    'manutencao'   => 'warning text-dark',
    'avariado'     => 'danger',
    'danificado'   => 'danger',
    'perdido'      => 'dark',
    'abatido'      => 'secondary',
];

require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2">
                    <?= h($flash['mensagem']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Dispositivos</h1>
                <div class="d-flex gap-2">
                    <a href="emprestimos.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Histórico de Empréstimos
                    </a>
                    <a href="novo_emprestimo.php" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">
                        <i class="fa-solid fa-plus me-1"></i>Novo Empréstimo
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card text-center p-3">
                    <div class="fs-2 fw-bold"><?= $total_disp ?></div>
                    <div class="text-muted small">Total</div>
                </div></div>
                <div class="col-md-4"><div class="card text-center p-3">
                    <div class="fs-2 fw-bold text-primary"><?= $emprestados ?></div>
                    <div class="text-muted small">Emprestados</div>
                </div></div>
                <div class="col-md-4"><div class="card text-center p-3">
                    <div class="fs-2 fw-bold text-danger"><?= $avariados ?></div>
                    <div class="text-muted small">Avariados / Perdidos</div>
                </div></div>
            </div>

            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Código</th><th>Estado</th><th>Utente Atual</th><th>Último Sync</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($dispositivos)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Sem dispositivos registados.</td></tr>
                    <?php else: foreach ($dispositivos as $d):
                        // Determinar estado efetivo (sessão sobrepõe estado da BD se "disponivel")
                        $estado_ef = $d['estado'];
                        $badge_extra = '';
                        if ($d['estado'] === 'disponivel') {
                            if (!empty($d['sessao_hoje'])) {
                                $estado_ef  = 'em_sessao';
                                $badge_extra = ' · ' . date('H:i', strtotime($d['sessao_hoje']));
                            } elseif (!empty($d['proxima_sessao'])) {
                                $estado_ef  = 'agendado';
                                $badge_extra = ' · ' . date('d/m', strtotime($d['proxima_sessao']));
                            }
                        }
                    ?>
                        <tr>
                            <td><strong><?= h($d['codigo']) ?></strong></td>
                            <td>
                                <?php $label = match($estado_ef) {
                                    'em_sessao' => 'Em sessão',
                                    'agendado'  => 'Agendado',
                                    default     => ucfirst($d['estado'])
                                }; ?>
                                <span class="badge bg-<?= $estado_badge[$estado_ef] ?? 'secondary' ?>">
                                    <?= $label . h($badge_extra) ?>
                                </span>
                            </td>
                            <td><?= $d['paciente'] ? h($d['paciente']) : '<span class="text-muted">—</span>' ?></td>
                            <td class="small text-muted"><?= $d['ultimo_sync'] ? h(substr($d['ultimo_sync'],0,16)) : 'Nunca' ?></td>
                            <td>
                                <?php if ($d['estado'] === 'disponivel' && $estado_ef === 'disponivel'): ?>
                                    <a href="novo_emprestimo.php?disp=<?= $d['id'] ?>"
                                       class="btn btn-xs btn-outline-success" title="Emprestar">
                                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Emprestar
                                    </a>
                                <?php elseif ($d['estado'] === 'disponivel' && in_array($estado_ef, ['em_sessao','agendado'])): ?>
                                    <span class="text-muted small">—</span>
                                <?php elseif ($d['estado'] === 'emprestado' && $d['emp_id']): ?>
                                    <a href="devolver_dispositivo.php?emp=<?= $d['emp_id'] ?>"
                                       class="btn btn-xs btn-outline-warning" title="Registar devolução">
                                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Devolver
                                    </a>
                                <?php elseif (in_array($d['estado'], ['avariado','danificado','perdido'], true)): ?>
                                    <form method="POST" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="_acao" value="disponivel">
                                        <input type="hidden" name="disp_id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-success"
                                                onclick="return confirm('Confirma que o dispositivo está em bom estado e pode ser marcado como disponível?')">
                                            <i class="fa-solid fa-rotate-left me-1"></i>Restaurar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
