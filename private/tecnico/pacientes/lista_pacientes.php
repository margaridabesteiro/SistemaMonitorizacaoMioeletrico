<?php
// private/tecnico/pacientes/lista_pacientes.php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
requirePerfil('tecnico');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
$stmt->execute([$uid]); $pid = (int)($stmt->fetchColumn() ?: 0);

$pesquisa    = trim($_GET['q'] ?? '');
$pagina_atual = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina  = 15;
$offset      = ($pagina_atual - 1) * $por_pagina;

// Contar pacientes DESTE técnico
$cnt_associados = 0;
if ($pid) {
    $s = $db->prepare("SELECT COUNT(*) FROM utentes WHERE tecnico_id=?");
    $s->execute([$pid]); $cnt_associados = (int)$s->fetchColumn();
}

// Se tiver pacientes associados, mostrar apenas os seus; senão mostrar todos
if ($pid && $cnt_associados > 0) {
    $where  = 'WHERE ut.tecnico_id=?';
    $params = [$pid];
    $mostrar_aviso = false;
} else {
    // Sem associações — mostrar todos os utentes
    $where  = 'WHERE 1=1';
    $params = [];
    $mostrar_aviso = true;
}

if ($pesquisa !== '') { $where .= ' AND u.nome LIKE ?'; $params[] = "%$pesquisa%"; }

$cnt = $db->prepare("SELECT COUNT(*) FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id $where");
$cnt->execute($params); $total = (int)$cnt->fetchColumn();

$stmt2 = $db->prepare("
    SELECT ut.id, u.nome, u.email, ut.diagnostico, ut.tecnico_id,
           (SELECT COUNT(*) FROM sessoes s WHERE s.utente_id=ut.id AND s.estado='concluida') AS n_sessoes,
           (SELECT COUNT(*) FROM sessoes s WHERE s.utente_id=ut.id AND s.estado='agendada')  AS n_agendadas
    FROM utentes ut
    JOIN utilizadores u ON u.id=ut.utilizador_id
    $where ORDER BY u.nome LIMIT $por_pagina OFFSET $offset");
$stmt2->execute($params);
$pacientes = $stmt2->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$pagina_titulo = 'Pacientes'; $pagina_ativa = 'pacientes';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestão de Pacientes</h1>
                <a href="novo_paciente.php" class="btn btn-sm" style="background:#1a5f8a;color:#fff;">
                    <i class="fa-solid fa-user-plus me-1"></i>Novo Paciente
                </a>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> py-2"><?= h($flash['mensagem']) ?></div>
            <?php endif; ?>

            <?php if ($mostrar_aviso): ?>
                <div class="alert alert-info mb-3">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Sem pacientes atribuídos.</strong>
                    A mostrar todos os utentes do sistema. O administrador pode associar utentes ao seu perfil em
                    <a href="<?= APP_URL ?>/private/admin/utilizadores/gestao_utilizadores.php" class="alert-link">Gestão de Utilizadores</a>.
                </div>
            <?php endif; ?>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Pesquisar por nome..." value="<?= h($pesquisa) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">Pesquisar</button>
                </div>
                <?php if ($pesquisa): ?>
                <div class="col-md-2">
                    <a href="?" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
                </div>
                <?php endif; ?>
            </form>

            <div class="card"><div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Nome</th><th>Email</th><th>Diagnóstico</th><th>Sessões</th><th>Agendadas</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pacientes)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Sem pacientes.</td></tr>
                    <?php else: foreach ($pacientes as $p): ?>
                        <tr>
                            <td class="fw-semibold"><?= h($p['nome']) ?></td>
                            <td class="text-muted small"><?= h($p['email']) ?></td>
                            <td><small><?= h(substr($p['diagnostico'] ?? '—', 0, 50)) ?></small></td>
                            <td><span class="badge bg-success"><?= $p['n_sessoes'] ?></span></td>
                            <td><span class="badge bg-primary"><?= $p['n_agendadas'] ?></span></td>
                            <td>
                                <a href="perfil_paciente.php?id=<?= $p['id'] ?>"
                                   class="btn btn-xs btn-outline-primary me-1" title="Ver perfil">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="historico_paciente.php?id=<?= $p['id'] ?>"
                                   class="btn btn-xs btn-outline-secondary" title="Histórico">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div></div>

            <!-- Paginação -->
            <?php $total_paginas = (int)ceil($total / $por_pagina); if ($total_paginas > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>&q=<?= urlencode($pesquisa) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
