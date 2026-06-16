<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Atribuições de Utentes';
$pagina_ativa  = 'atribuicoes';
requirePerfil('admin');

$db = getDB();

// --- POST: guardar alterações ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicos_post  = $_POST['medico_id']  ?? [];
    $tecnicos_post = $_POST['tecnico_id'] ?? [];
    $ids = array_unique(array_merge(array_keys($medicos_post), array_keys($tecnicos_post)));
    $ids = array_filter(array_map('intval', $ids));

    if ($ids) {
        $in = implode(',', $ids);

        // Carregar estado atual antes de atualizar
        $atuais = [];
        foreach ($db->query("SELECT ut.id, ut.medico_id, ut.tecnico_id, u.nome
                             FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id
                             WHERE ut.id IN ($in)")->fetchAll() as $r) {
            $atuais[(int)$r['id']] = $r;
        }

        // Mapa profissional.id -> utilizador_id para notificações
        $prof_uid = [];
        foreach ($db->query("SELECT id, utilizador_id FROM profissionais")->fetchAll() as $p) {
            $prof_uid[(int)$p['id']] = (int)$p['utilizador_id'];
        }

        $stmt = $db->prepare("UPDATE utentes SET medico_id=?, tecnico_id=? WHERE id=?");
        $alteracoes = 0;

        foreach ($ids as $uid) {
            if ($uid <= 0) continue;
            $mid = !empty($medicos_post[$uid])  ? (int)$medicos_post[$uid]  : null;
            $tid = !empty($tecnicos_post[$uid]) ? (int)$tecnicos_post[$uid] : null;
            $stmt->execute([$mid, $tid, $uid]);
            $alteracoes++;

            $nome_utente   = $atuais[$uid]['nome']      ?? 'Utente';
            $anterior_mid  = $atuais[$uid]['medico_id']  ? (int)$atuais[$uid]['medico_id']  : null;
            $anterior_tid  = $atuais[$uid]['tecnico_id'] ? (int)$atuais[$uid]['tecnico_id'] : null;

            // Notificar novo médico se mudou
            if ($mid && $mid !== $anterior_mid && isset($prof_uid[$mid])) {
                notificar(
                    $prof_uid[$mid],
                    'info',
                    'Novo utente atribuído',
                    'O utente ' . $nome_utente . ' foi-lhe atribuído para acompanhamento.',
                    APP_URL . '/private/medico/consultas/consulta.php'
                );
            }

            // Notificar novo técnico se mudou
            if ($tid && $tid !== $anterior_tid && isset($prof_uid[$tid])) {
                notificar(
                    $prof_uid[$tid],
                    'info',
                    'Novo utente atribuído',
                    'O utente ' . $nome_utente . ' foi-lhe atribuído para acompanhamento.',
                    APP_URL . '/private/tecnico/index_F.php'
                );
            }
        }
    } else {
        $alteracoes = 0;
    }

    registarAuditoria('UPDATE', 'utentes', null, "Admin atualizou atribuições de $alteracoes utente(s)");
    $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => "Atribuições guardadas ($alteracoes utente(s) atualizado(s))."];
    redirect(APP_URL . '/private/admin/relatorios/atribuicoes.php');
}

// --- Carregar dados ---
$utentes = $db->query("
    SELECT ut.id, u.nome AS nome, u.ativo, ut.medico_id, ut.tecnico_id
    FROM utentes ut
    JOIN utilizadores u ON u.id = ut.utilizador_id
    WHERE u.email NOT LIKE 'anonimizado_%'
    ORDER BY u.nome
")->fetchAll();

$medicos = $db->query("
    SELECT p.id, u.nome
    FROM profissionais p
    JOIN utilizadores u ON u.id = p.utilizador_id
    WHERE u.perfil = 'medico' AND u.ativo = 1
    ORDER BY u.nome
")->fetchAll();

$tecnicos = $db->query("
    SELECT p.id, u.nome
    FROM profissionais p
    JOIN utilizadores u ON u.id = p.utilizador_id
    WHERE u.perfil = 'tecnico' AND u.ativo = 1
    ORDER BY u.nome
")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Atribuições de Utentes</h1>
                <a href="relatorios_sistema.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Relatórios
                </a>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> alert-dismissible py-2">
                    <?= h($flash['mensagem']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-3 border-0" style="background:#fff8e1;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info" style="color:#f59e0b;"></i>
                    <span class="small text-muted">
                        Altere o médico e/ou técnico responsável por cada utente. Escolha "Nenhum" para remover a atribuição.
                        Clique em <strong>Guardar Alterações</strong> para confirmar.
                    </span>
                </div>
            </div>

            <?php if (empty($utentes)): ?>
                <div class="card p-4 text-center text-muted">Sem utentes registados.</div>
            <?php else: ?>
            <form method="POST">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Utente</th>
                                    <th style="min-width:200px;">
                                        <i class="fa-solid fa-user-doctor me-1" style="color:#8B0000;"></i>Médico
                                    </th>
                                    <th style="min-width:200px;">
                                        <i class="fa-solid fa-user-nurse me-1" style="color:#8B0000;"></i>Técnico
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($utentes as $ut): ?>
                                <tr>
                                    <td>
                                        <?= h($ut['nome']) ?>
                                        <?php if (!$ut['ativo']): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size:.65rem;">inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="medico_id[<?= $ut['id'] ?>]" class="form-select form-select-sm">
                                            <option value="">— Nenhum —</option>
                                            <?php foreach ($medicos as $m): ?>
                                                <option value="<?= $m['id'] ?>"
                                                    <?= (int)$ut['medico_id'] === (int)$m['id'] ? 'selected' : '' ?>>
                                                    <?= h($m['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="tecnico_id[<?= $ut['id'] ?>]" class="form-select form-select-sm">
                                            <option value="">— Nenhum —</option>
                                            <?php foreach ($tecnicos as $t): ?>
                                                <option value="<?= $t['id'] ?>"
                                                    <?= (int)$ut['tecnico_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                                                    <?= h($t['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Alterações
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
