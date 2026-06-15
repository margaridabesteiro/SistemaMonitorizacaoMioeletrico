<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Utilizador'; $pagina_ativa = 'utilizadores';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
$db = getDB(); $erros = [];
$stmt = $db->prepare('SELECT u.id, u.nome, u.email, u.perfil, u.ativo, ut.nif, ut.cobertura_saude, ut.seguradora_id FROM utilizadores u LEFT JOIN utentes ut ON ut.utilizador_id = u.id WHERE u.id=?');
$stmt->execute([$id]); $dados = $stmt->fetch();
$seguradoras = $db->query('SELECT id, nome, tipo FROM seguradoras WHERE ativa=1 ORDER BY tipo, nome')->fetchAll();

// Dados profissionais (médico/técnico)
$prof = ['numero_ordem'=>'','especialidade'=>'','instituicao'=>'','contacto'=>''];
if ($dados && in_array($dados['perfil'], ['medico','tecnico'], true)) {
    $sp = $db->prepare('SELECT numero_ordem, especialidade, instituicao, contacto FROM profissionais WHERE utilizador_id=?');
    $sp->execute([$id]); $prof_db = $sp->fetch();
    if ($prof_db) $prof = $prof_db;
}

// Dados RGPD (apenas para utentes)
$rgpd_consentimentos = [];
if ($dados && $dados['perfil'] === 'utente') {
    try {
        $rc = $db->prepare("SELECT tipo, criado_em, detalhes FROM rgpd_consentimentos WHERE utilizador_id=? ORDER BY criado_em DESC LIMIT 10");
        $rc->execute([$id]); $rgpd_consentimentos = $rc->fetchAll();
    } catch (\Throwable $e) {}
}
if (!$dados) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']   = trim($_POST['nome']   ?? '');
    $dados['email']  = trim($_POST['email']  ?? '');
    $dados['perfil'] = $_POST['perfil']      ?? '';
    $dados['ativo']  = isset($_POST['ativo'])? 1 : 0;

    if ($dados['nome'] === '') $erros[] = 'Nome obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (!in_array($dados['perfil'], ['admin','medico','tecnico','utente'], true)) $erros[] = 'Perfil inválido.';

    // Password reset
    $nova_pass = ''; $repor = isset($_POST['repor_password']);
    if ($repor && $dados['perfil'] !== 'utente') {
        $nova_pass = $_POST['password'] ?? '';
        if (strlen($nova_pass) < 8) $erros[] = 'Password inválida (mínimo 8 caracteres).';
        // Admin: validação de confirmação manual
        if ($dados['perfil'] === 'admin') {
            $conf_pass = $_POST['password_conf'] ?? '';
            if ($nova_pass !== $conf_pass) $erros[] = 'Passwords não coincidem.';
        }
    }

    if (empty($erros)) {
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email=? AND id!=?');
        $existe->execute([$dados['email'], $id]);
        if ($existe->fetch()) {
            $erros[] = 'Email já em uso.';
        } else {
            if ($nova_pass !== '') {
                $hash = password_hash($nova_pass, PASSWORD_BCRYPT, ['cost'=>12]);
                $deve_alterar = in_array($dados['perfil'], ['medico','tecnico','utente']) ? 1 : 0;
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=?,password_hash=?,deve_alterar_password=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$hash,$deve_alterar,$id]);
            } else {
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$id]);
            }
            // Dados específicos de utente
            if ($dados['perfil'] === 'utente') {
                $nif         = trim($_POST['nif']          ?? '') ?: null;
                $seg_id      = (int)($_POST['seguradora_id'] ?? 0) ?: null;
                // Inferir cobertura_saude da seguradora
                $cobertura = 'Particular';
                if ($seg_id) {
                    $st = $db->prepare('SELECT tipo FROM seguradoras WHERE id=?'); $st->execute([$seg_id]);
                    $tipo_seg = $st->fetchColumn();
                    if ($tipo_seg === 'SNS') $cobertura = 'SNS';
                    elseif ($tipo_seg === 'Seguro') $cobertura = 'Seguro';
                }
                $db->prepare('UPDATE utentes SET nif=?, cobertura_saude=?, seguradora_id=? WHERE utilizador_id=?')
                   ->execute([$nif, $cobertura, $seg_id, $id]);
            }
            // Dados profissionais (médico/técnico)
            if (in_array($dados['perfil'], ['medico','tecnico'], true)) {
                $prof['numero_ordem']  = trim($_POST['numero_ordem']  ?? '') ?: null;
                $prof['especialidade'] = trim($_POST['especialidade'] ?? '') ?: null;
                $prof['instituicao']   = trim($_POST['instituicao']   ?? '') ?: null;
                $prof['contacto']      = trim($_POST['contacto']      ?? '') ?: null;
                // Upsert: atualiza se existe, insere se não
                $existe_prof = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?');
                $existe_prof->execute([$id]);
                if ($existe_prof->fetch()) {
                    $db->prepare('UPDATE profissionais SET numero_ordem=?, especialidade=?, instituicao=?, contacto=? WHERE utilizador_id=?')
                       ->execute([$prof['numero_ordem'], $prof['especialidade'], $prof['instituicao'], $prof['contacto'], $id]);
                } else {
                    $db->prepare('INSERT INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao, contacto) VALUES (?,?,?,?,?)')
                       ->execute([$id, $prof['numero_ordem'], $prof['especialidade'], $prof['instituicao'], $prof['contacto']]);
                }
            }
            $msg = 'Utilizador atualizado.';
            if ($nova_pass !== '' && in_array($dados['perfil'], ['medico','tecnico'], true)) {
                $msg = "Password reposta. Nova password temporária: <strong class='font-monospace'>{$nova_pass}</strong> — comunique ao utilizador. Será obrigado a alterá-la no próximo acesso.";
                registarAuditoria('ATUALIZAR', 'Utilizador', $id, 'Password reposta para: ' . $dados['nome'] . ' (' . $dados['perfil'] . ')');
            } else {
                registarAuditoria('ATUALIZAR', 'Utilizador', $id, 'Dados atualizados: ' . $dados['nome'] . ' (' . $dados['perfil'] . ')');
            }
            $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>$msg];
            redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
        }
    }
}
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="lista_utilizadores.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
                <h1 class="mb-0">Editar Utilizador</h1>
            </div>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e):?><li><?=h($e)?></li><?php endforeach;?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" class="form-control" value="<?=h($dados['nome'])?>" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Email *</label><input type="email" name="email" class="form-control" value="<?=h($dados['email'])?>" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Perfil *</label>
                        <select name="perfil" class="form-select" required><?php foreach(['admin','medico','tecnico','utente'] as $p):?><option value="<?=$p?>" <?=$dados['perfil']===$p?'selected':''?>><?=ucfirst($p)?></option><?php endforeach;?></select>
                    </div>

                    <?php if ($dados['perfil'] === 'medico'): ?>
                    <div class="card p-3 mb-3 border-primary">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card me-2" style="color:#8B0000;"></i>Dados Profissionais</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nº de Cédula Profissional</label>
                                <input type="text" name="numero_ordem" class="form-control"
                                       placeholder="Ex: 12345"
                                       value="<?= h($prof['numero_ordem'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Especialidade</label>
                                <input type="text" name="especialidade" class="form-control"
                                       placeholder="Ex: Medicina Física e Reabilitação"
                                       value="<?= h($prof['especialidade'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Contacto</label>
                            <input type="text" name="contacto" class="form-control"
                                   placeholder="Ex: 912 000 001"
                                   value="<?= h($prof['contacto'] ?? '') ?>">
                        </div>
                    <!-- Password para médico -->
                    <div class="row" id="password">
                        <div class="col-12 mb-2"><hr class="my-1"><p class="small text-muted mb-1"><i class="fa-solid fa-key me-1"></i>Alterar Password (deixe vazio para manter a atual)</p></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nova Password</label><input type="password" name="password" class="form-control" placeholder="Vazio = manter atual"></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirmar Password</label><input type="password" name="password_conf" class="form-control"></div>
                    </div>
                    </div>
                    <?php elseif ($dados['perfil'] === 'tecnico'): ?>
                    <div class="card p-3 mb-3 border-primary">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card me-2" style="color:#8B0000;"></i>Dados Profissionais</h6>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Contacto</label>
                            <input type="text" name="contacto" class="form-control"
                                   placeholder="Ex: 912 000 001"
                                   value="<?= h($prof['contacto'] ?? '') ?>">
                        </div>
                    <!-- Password para técnico -->
                    <div class="row" id="password">
                        <div class="col-12 mb-2"><hr class="my-1"><p class="small text-muted mb-1"><i class="fa-solid fa-key me-1"></i>Alterar Password (deixe vazio para manter a atual)</p></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nova Password</label><input type="password" name="password" class="form-control" placeholder="Vazio = manter atual"></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirmar Password</label><input type="password" name="password_conf" class="form-control"></div>
                    </div>
                    </div>
                    <?php elseif ($dados['perfil'] === 'utente'): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIF</label>
                        <input type="text" name="nif" class="form-control" value="<?=h($dados['nif']??'')?>" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seguradora</label>
                        <select name="seguradora_id" class="form-select">
                            <option value="">— Sem seguradora definida —</option>
                            <?php foreach ($seguradoras as $seg): ?>
                                <option value="<?= $seg['id'] ?>" <?= ($dados['seguradora_id'] ?? '') == $seg['id'] ? 'selected' : '' ?>>
                                    <?= h($seg['nome']) ?> <span class="text-muted">(<?= $seg['tipo'] ?>)</span>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Cobertura de saúde é inferida automaticamente da seguradora. Usado para preços automáticos nas faturas.</div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="fa-solid fa-lock me-1"></i> A password do utente só pode ser redefinida pelo próprio utente (RGPD — dados clínicos pertencem ao titular).
                    </div>
                    <?php else: ?>
                    <!-- Admin: password fields -->
                    <div class="row" id="password">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Nova Password</label><input type="password" name="password" class="form-control" placeholder="Vazio = manter atual"></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirmar Password</label><input type="password" name="password_conf" class="form-control"></div>
                    </div>
                    <?php endif; ?>

                    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?=$dados['ativo']?'checked':''?>><label class="form-check-label" for="ativo">Conta ativa</label></div>
                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                </form>
            </div>
            <?php if ($dados['perfil'] === 'utente'): ?>
            <div class="card p-4 mt-4 border-warning" style="max-width:900px;">
                <h5 class="mb-3"><i class="fa-solid fa-shield-halved me-2" style="color:#8B0000;"></i>Ferramentas RGPD — <?= h($dados['nome']) ?></h5>
                <div class="mb-3">
                    <h6 class="fw-bold small"><i class="fa-solid fa-download me-1 text-primary"></i>Exportar Dados (Art.&nbsp;20)</h6>
                    <p class="small text-muted mb-2">Exportar todos os dados do utente em JSON estruturado.</p>
                    <a href="<?= APP_URL ?>/api/admin/rgpd/exportar_dados.php?id=<?= $id ?>" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-download me-1"></i>Exportar JSON
                    </a>
                </div>
                <!-- Histórico de consentimentos -->
                <?php if (!empty($rgpd_consentimentos)): ?>
                <h6 class="fw-bold small mb-2"><i class="fa-solid fa-list-check me-1"></i>Histórico de Consentimentos</h6>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Tipo</th><th>Data</th><th>Detalhes</th></tr></thead>
                    <tbody>
                    <?php foreach ($rgpd_consentimentos as $rc): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= h($rc['tipo']) ?></span></td>
                            <td class="small"><?= h(date('d/m/Y H:i', strtotime($rc['criado_em']))) ?></td>
                            <td class="small text-muted"><?= h(substr($rc['detalhes'] ?? '—', 0, 80)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
