<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

// Toggle ativa/inativa — tem de correr ANTES de qualquer output HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id']) && $pid) {
    $toggle_id = (int)$_POST['toggle_id'];
    $db->prepare('UPDATE prescricoes SET ativa = NOT ativa WHERE id=? AND medico_id=?')->execute([$toggle_id, $pid]);
    $stmt_nova = $db->prepare('SELECT ativa FROM prescricoes WHERE id=?'); $stmt_nova->execute([$toggle_id]);
    $nova = (int)$stmt_nova->fetchColumn();
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Prescrição ' . ($nova ? 'ativada' : 'inativada') . ' com sucesso.'];
        redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
}

$filtro_estado = $_GET['estado'] ?? '';
$pagina_atual = max(1,(int)($_GET['pagina'] ?? 1)); $por_pagina = 15; $offset = ($pagina_atual-1)*$por_pagina;
$where = $pid ? 'WHERE p.medico_id=?' : 'WHERE 1=0'; $params = $pid ? [$pid] : [];
if ($filtro_estado === 'ativa') { $where .= ' AND p.ativa=1'; }
elseif ($filtro_estado === 'inativa') { $where .= ' AND p.ativa=0'; }
$cnt = $db->prepare("SELECT COUNT(*) FROM prescricoes p $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
$stmt2 = $db->prepare("SELECT p.*, u.nome AS paciente FROM prescricoes p JOIN utentes ut ON ut.id=p.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id $where ORDER BY p.data_prescricao DESC LIMIT $por_pagina OFFSET $offset");
$stmt2->execute($params); $prescricoes = $stmt2->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$pagina_titulo = 'Prescrições'; $pagina_ativa = 'prescricoes';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Prescrições</h1>
                <a href="nova_prescricao.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-plus me-1"></i>Nova Prescrição</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><select name="estado" class="form-select form-select-sm"><option value="">Todas</option><option value="ativa" <?= $filtro_estado==='ativa'?'selected':'' ?>>Ativas</option><option value="inativa" <?= $filtro_estado==='inativa'?'selected':'' ?>>Inativas</option></select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
            </form>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Paciente</th><th>Tipo</th><th>Data</th><th>Validade</th><th>Prioridade</th><th>Estado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($prescricoes)): ?><tr><td colspan="7" class="text-center text-muted py-4">Sem prescrições.</td></tr>
                    <?php else: foreach($prescricoes as $p): ?>
                        <tr>
                            <td><?= h($p['paciente']) ?></td>
                            <td><?= h($p['tipo']) ?></td>
                            <td><?= h($p['data_prescricao']) ?></td>
                            <td><?= h($p['data_validade'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= ['Baixa'=>'success','Media'=>'info','Alta'=>'warning','Urgente'=>'danger'][$p['prioridade']] ?? 'secondary' ?>"><?= h($p['prioridade']) ?></span></td>
                            <td><?= $p['ativa'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>' ?></td>
                            <td class="d-flex gap-1 align-items-center">
                                <a href="detalhes_prescricao.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary" title="Ver"><i class="fa-regular fa-eye"></i></a>
                                <a href="editar_prescricao.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-secondary" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <form method="POST" class="d-inline m-0">
                                    <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-xs <?= $p['ativa'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                            title="<?= $p['ativa'] ? 'Inativar' : 'Ativar' ?>">
                                        <i class="fa-solid <?= $p['ativa'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
