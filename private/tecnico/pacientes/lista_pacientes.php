<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Pacientes'; $pagina_ativa = 'pacientes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
$db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);
$pesquisa = trim($_GET['q'] ?? '');
$pagina_atual = max(1,(int)($_GET['pagina'] ?? 1)); $por_pagina = 15; $offset = ($pagina_atual-1)*$por_pagina;
$where = $pid ? 'WHERE ut.tecnico_id=?' : 'WHERE 1=0'; $params = $pid ? [$pid] : [];
if ($pesquisa !== '') { $where .= ' AND u.nome LIKE ?'; $params[] = "%$pesquisa%"; }
$cnt = $db->prepare("SELECT COUNT(*) FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
$stmt2 = $db->prepare("SELECT ut.id, u.nome, u.email, ut.diagnostico, (SELECT COUNT(*) FROM sessoes s WHERE s.utente_id=ut.id AND s.estado='concluida') AS n_sessoes FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id $where ORDER BY u.nome LIMIT $por_pagina OFFSET $offset");
$stmt2->execute($params); $pacientes = $stmt2->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestão de Pacientes</h1>
                <a href="novo_paciente.php" class="btn btn-sm" style="background:#1a5f8a;color:#fff;"><i class="fa-solid fa-user-plus me-1"></i>Novo Paciente</a>
            </div>
            <?php if ($flash): ?><div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div><?php endif; ?>
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5"><input type="text" name="q" class="form-control form-control-sm" placeholder="Nome..." value="<?= h($pesquisa) ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Pesquisar</button></div>
            </form>
            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Nome</th><th>Email</th><th>Diagnóstico</th><th>Sessões</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if(empty($pacientes)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sem pacientes.</td></tr>
                    <?php else: foreach($pacientes as $p): ?>
                        <tr>
                            <td><?= h($p['nome']) ?></td>
                            <td><?= h($p['email']) ?></td>
                            <td><small><?= h(substr($p['diagnostico']??'—',0,50)) ?></small></td>
                            <td><span class="badge bg-success"><?= $p['n_sessoes'] ?></span></td>
                            <td>
                                <a href="perfil_paciente.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary me-1"><i class="fa-regular fa-eye"></i></a>
                                <a href="historico_paciente.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-secondary"><i class="fa-solid fa-clock-rotate-left"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
