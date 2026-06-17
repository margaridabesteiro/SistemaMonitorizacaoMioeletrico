<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

// Toggle ativa/inativa — antes de qualquer output HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id']) && $pid) {
    $toggle_id = (int)$_POST['toggle_id'];
    $db->prepare('UPDATE programas_tratamento SET ativa = NOT ativa WHERE id=? AND medico_id=?')->execute([$toggle_id, $pid]);
    $stmt_nova = $db->prepare('SELECT ativa FROM programas_tratamento WHERE id=?'); $stmt_nova->execute([$toggle_id]);
    $nova = (int)$stmt_nova->fetchColumn();
    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Programa ' . ($nova ? 'ativado' : 'inativado') . ' com sucesso.'];
    redirect(APP_URL . '/private/medico/prescricoes/lista_prescricoes.php');
}

$filtro_estado = $_GET['estado'] ?? '';
$pagina_atual  = max(1,(int)($_GET['pagina'] ?? 1)); $por_pagina = 15; $offset = ($pagina_atual-1)*$por_pagina;
$where  = $pid ? 'WHERE p.medico_id=?' : 'WHERE 1=0'; $params = $pid ? [$pid] : [];
if ($filtro_estado === 'ativa')   { $where .= ' AND p.ativa=1'; }
elseif ($filtro_estado === 'inativa') { $where .= ' AND p.ativa=0'; }
$cnt = $db->prepare("SELECT COUNT(*) FROM programas_tratamento p $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
$stmt2 = $db->prepare("SELECT p.*, u.nome AS paciente FROM programas_tratamento p JOIN utentes ut ON ut.id=p.utente_id JOIN utilizadores u ON u.id=ut.utilizador_id $where ORDER BY p.data_prescricao DESC LIMIT $por_pagina OFFSET $offset");
$stmt2->execute($params); $programas = $stmt2->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$pagina_titulo = 'Programas de Tratamento'; $pagina_ativa = 'prescricoes';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Programas de Tratamento</h1>
                <a href="nova_prescricao.php" class="btn btn-sm" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-plus me-1"></i>Novo Programa</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3"><select name="estado" class="form-select form-select-sm"><option value="">Todos</option><option value="ativa" <?= $filtro_estado==='ativa'?'selected':'' ?>>Ativos</option><option value="inativa" <?= $filtro_estado==='inativa'?'selected':'' ?>>Inativos</option></select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
            </form>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Paciente</th><th>Membro</th><th>Sessões</th><th>Data Início</th><th>Validade</th><th>Estado</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($programas)): ?><tr><td colspan="7" class="text-center text-muted py-4">Sem programas.</td></tr>
                    <?php else: foreach($programas as $p): ?>
                        <?php
                        $membro_labels = ['mao_esquerda'=>'Mão Esq.','mao_direita'=>'Mão Dir.','ambas'=>'Ambas','perna_esquerda'=>'Perna Esq.','perna_direita'=>'Perna Dir.','outro'=>'Outro'];
                        ?>
                        <tr>
                            <td><?= h($p['paciente']) ?></td>
                            <td><?= h($membro_labels[$p['membro_afetado'] ?? ''] ?? '—') ?></td>
                            <td><?= $p['num_sessoes_prescritas'] ? h($p['num_sessoes_prescritas']) : '—' ?></td>
                            <td><?= h($p['data_prescricao']) ?></td>
                            <td><?= h($p['data_validade'] ?? '—') ?></td>
                            <td><?= $p['ativa'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                            <td class="d-flex gap-1 align-items-center">
                                <a href="detalhes_prescricao.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary" title="Ver"><i class="fa-regular fa-eye"></i></a>
                                <a href="editar_prescricao.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-secondary" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <form method="POST" class="d-inline m-0">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-xs <?= $p['ativa'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $p['ativa'] ? 'Inativar' : 'Ativar' ?>">
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
