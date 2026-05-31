<?php
// private/admin/utilizadores/lista_utilizadores.php
// Listagem de utilizadores com pesquisa, filtro por perfil e paginação

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$pagina_titulo = 'Utilizadores';
$pagina_ativa  = 'utilizadores';

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';

$db = getDB();

// --- Filtros e paginação ---
$pesquisa     = trim($_GET['q']      ?? '');
$filtro_perfil = $_GET['perfil']     ?? '';
$pagina_atual  = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina    = 15;
$offset        = ($pagina_atual - 1) * $por_pagina;

$where  = 'WHERE 1=1';
$params = [];

if ($pesquisa !== '') {
    $where   .= ' AND (nome LIKE ? OR email LIKE ?)';
    $params[] = "%$pesquisa%";
    $params[] = "%$pesquisa%";
}
if (in_array($filtro_perfil, ['admin','medico','tecnico','utente'], true)) {
    $where   .= ' AND perfil = ?';
    $params[] = $filtro_perfil;
}

$total = $db->prepare("SELECT COUNT(*) FROM utilizadores $where");
$total->execute($params);
$total_registos = (int)$total->fetchColumn();
$total_paginas  = (int)ceil($total_registos / $por_pagina);

$stmt = $db->prepare("SELECT id, nome, email, perfil, ativo, criado_em, ultimo_login
                       FROM utilizadores $where
                       ORDER BY criado_em DESC
                       LIMIT $por_pagina OFFSET $offset");
$stmt->execute($params);
$utilizadores = $stmt->fetchAll();

// Mensagem flash (após criar/editar/eliminar)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Utilizadores</h1>
                <a href="<?= APP_URL ?>/private/admin/utilizadores/novo_utilizador.php"
                   class="btn btn-sm" style="background:#8B0000;color:#fff;">
                    <i class="fa-solid fa-plus me-1"></i>Novo Utilizador
                </a>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2">
                    <?= h($flash['mensagem']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Pesquisar por nome ou email..."
                           value="<?= h($pesquisa) ?>">
                </div>
                <div class="col-md-3">
                    <select name="perfil" class="form-select form-select-sm">
                        <option value="">Todos os perfis</option>
                        <?php foreach (['admin','medico','tecnico','utente'] as $p): ?>
                            <option value="<?= $p ?>" <?= $filtro_perfil === $p ? 'selected' : '' ?>>
                                <?= ucfirst($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">Filtrar</button>
                </div>
            </form>

            <!-- Tabela -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th><th>Email</th><th>Perfil</th>
                                <th>Estado</th><th>Criado em</th><th>Último Login</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($utilizadores)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Sem resultados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($utilizadores as $u): ?>
                            <tr>
                                <td><?= h($u['nome']) ?></td>
                                <td><?= h($u['email']) ?></td>
                                <td><span class="badge bg-secondary"><?= h($u['perfil']) ?></span></td>
                                <td>
                                    <?php if ($u['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h(substr($u['criado_em'], 0, 10)) ?></td>
                                <td><?= $u['ultimo_login'] ? h(substr($u['ultimo_login'], 0, 16)) : '<span class="text-muted">Nunca</span>' ?></td>
                                <td>
                                    <a href="editar_utilizador.php?id=<?= $u['id'] ?>"
                                       class="btn btn-xs btn-outline-primary me-1" title="Editar">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <?php if ($u['perfil'] === 'utente'): ?>
                                    <a href="perfil_utente.php?id=<?= $u['id'] ?>"
                                       class="btn btn-xs btn-outline-info me-1" title="Perfil Clínico">
                                        <i class="fa-solid fa-stethoscope"></i>
                                    </a>
                                    <?php elseif (in_array($u['perfil'], ['medico','tecnico'], true)): ?>
                                    <a href="editar_utilizador.php?id=<?= $u['id'] ?>#password"
                                       class="btn btn-xs btn-outline-warning me-1" title="Alterar Password">
                                        <i class="fa-solid fa-key"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?= APP_URL ?>/api/admin/utilizadores/toggle_ativo.php?id=<?= $u['id'] ?>"
                                       class="btn btn-xs btn-outline-<?= $u['ativo'] ? 'danger' : 'success' ?>"
                                       title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                        <i class="fa-solid fa-<?= $u['ativo'] ? 'ban' : 'check' ?>"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm justify-content-end">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina_atual ? 'active' : '' ?>">
                            <a class="page-link"
                               href="?pagina=<?= $i ?>&q=<?= urlencode($pesquisa) ?>&perfil=<?= urlencode($filtro_perfil) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <p class="text-muted small mt-2"><?= $total_registos ?> utilizadores encontrados.</p>
        </main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
