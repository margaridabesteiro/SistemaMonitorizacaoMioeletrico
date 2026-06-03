<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Editar Utilizador'; $pagina_ativa = 'utilizadores';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
$db = getDB(); $erros = [];
$stmt = $db->prepare('SELECT u.id, u.nome, u.email, u.perfil, u.ativo, ut.nif, ut.cobertura_saude FROM utilizadores u LEFT JOIN utentes ut ON ut.utilizador_id = u.id WHERE u.id=?');
$stmt->execute([$id]); $dados = $stmt->fetch();

// Dados profissionais (médico/técnico)
$prof = ['numero_ordem'=>'','especialidade'=>'','instituicao'=>'','contacto'=>''];
if ($dados && in_array($dados['perfil'], ['medico','tecnico'], true)) {
    $sp = $db->prepare('SELECT numero_ordem, especialidade, instituicao, contacto FROM profissionais WHERE utilizador_id=?');
    $sp->execute([$id]); $prof_db = $sp->fetch();
    if ($prof_db) $prof = $prof_db;
}

// Dados RGPD (apenas para utentes)
$rgpd_consentimentos = []; $rgpd_pedidos = [];
if ($dados && $dados['perfil'] === 'utente') {
    try {
        $rc = $db->prepare("SELECT tipo, criado_em, detalhes FROM rgpd_consentimentos WHERE utilizador_id=? ORDER BY criado_em DESC LIMIT 10");
        $rc->execute([$id]); $rgpd_consentimentos = $rc->fetchAll();
        $rp = $db->prepare("SELECT tipo, estado, mensagem, criado_em FROM rgpd_pedidos WHERE utilizador_id=? ORDER BY criado_em DESC LIMIT 5");
        $rp->execute([$id]); $rgpd_pedidos = $rp->fetchAll();
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

    // Password: apenas para não-utentes
    $nova_pass = ''; $conf_pass = '';
    if ($dados['perfil'] !== 'utente') {
        $nova_pass = $_POST['password']      ?? '';
        $conf_pass = $_POST['password_conf'] ?? '';
        if ($nova_pass !== '' && strlen($nova_pass) < 8)  $erros[] = 'Password mínimo 8 caracteres.';
        if ($nova_pass !== '' && $nova_pass !== $conf_pass) $erros[] = 'Passwords não coincidem.';
    }

    if (empty($erros)) {
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email=? AND id!=?');
        $existe->execute([$dados['email'], $id]);
        if ($existe->fetch()) {
            $erros[] = 'Email já em uso.';
        } else {
            if ($nova_pass !== '') {
                $hash = password_hash($nova_pass, PASSWORD_BCRYPT, ['cost'=>12]);
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=?,password_hash=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$hash,$id]);
            } else {
                $db->prepare('UPDATE utilizadores SET nome=?,email=?,perfil=?,ativo=? WHERE id=?')
                   ->execute([$dados['nome'],$dados['email'],$dados['perfil'],$dados['ativo'],$id]);
            }
            // Dados específicos de utente
            if ($dados['perfil'] === 'utente') {
                $nif       = trim($_POST['nif']             ?? '') ?: null;
                $cobertura = $_POST['cobertura_saude']      ?? 'SNS';
                $db->prepare('UPDATE utentes SET nif=?, cobertura_saude=? WHERE utilizador_id=?')
                   ->execute([$nif, $cobertura, $id]);
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
            $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Utilizador atualizado.'];
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

                    <?php if (in_array($dados['perfil'], ['medico','tecnico'], true)): ?>
                    <div class="card p-3 mb-3 border-primary">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card me-2" style="color:#8B0000;"></i>Dados Profissionais</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nº Cédula / Ordem</label>
                                <input type="text" name="numero_ordem" class="form-control"
                                       placeholder="Ex: OM-12345"
                                       value="<?= h($prof['numero_ordem'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Especialidade</label>
                                <input type="text" name="especialidade" class="form-control"
                                       placeholder="Ex: Fisioterapia Mioeléctrica"
                                       value="<?= h($prof['especialidade'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-semibold">Instituição</label>
                                <input type="text" name="instituicao" class="form-control"
                                       placeholder="Ex: RehabLink"
                                       value="<?= h($prof['instituicao'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-semibold">Contacto</label>
                                <input type="text" name="contacto" class="form-control"
                                       placeholder="Ex: 912 000 001"
                                       value="<?= h($prof['contacto'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <?php elseif ($dados['perfil'] === 'utente'): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIF</label>
                        <input type="text" name="nif" class="form-control" value="<?=h($dados['nif']??'')?>" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cobertura de Saúde</label>
                        <select name="cobertura_saude" class="form-select">
                            <?php foreach(['SNS','Particular','Seguro'] as $c): ?>
                                <option value="<?=$c?>" <?=($dados['cobertura_saude']??'SNS')===$c?'selected':''?>><?=$c?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Faturas só são geradas para Particular e Seguro.</div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="fa-solid fa-lock me-1"></i> A password do utente só pode ser redefinida pelo próprio utente (RGPD — dados clínicos pertencem ao titular).
                    </div>
                    <?php else: ?>
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
                <div class="row g-3 mb-3">
                    <!-- Exportar dados -->
                    <div class="col-md-4">
                        <div class="card bg-light p-3 h-100">
                            <h6 class="fw-bold small"><i class="fa-solid fa-download me-1 text-primary"></i>Exportar Dados (Art.&nbsp;20)</h6>
                            <p class="small text-muted mb-2">Exportar todos os dados do utente em JSON estruturado.</p>
                            <a href="<?= APP_URL ?>/api/admin/rgpd/exportar_dados.php?id=<?= $id ?>" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-download me-1"></i>Exportar JSON
                            </a>
                        </div>
                    </div>
                    <!-- Anonimizar -->
                    <div class="col-md-4">
                        <div class="card bg-light p-3 h-100">
                            <h6 class="fw-bold small"><i class="fa-solid fa-user-slash me-1 text-danger"></i>Anonimizar (Art.&nbsp;17)</h6>
                            <p class="small text-muted mb-2">Remove dados pessoais. Dados clínicos são mantidos por obrigação legal.</p>
                            <form method="POST" action="<?= APP_URL ?>/api/admin/rgpd/anonimizar_utilizador.php"
                                  onsubmit="return confirm('Confirma a anonimização permanente dos dados pessoais de <?= h(addslashes($dados['nome'])) ?>? Esta ação é irreversível.')">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa-solid fa-user-slash me-1"></i>Anonimizar
                                </button>
                            </form>
                        </div>
                    </div>
                    <!-- Pedidos pendentes -->
                    <div class="col-md-4">
                        <div class="card bg-light p-3 h-100">
                            <h6 class="fw-bold small"><i class="fa-solid fa-inbox me-1 text-warning"></i>Pedidos RGPD</h6>
                            <?php if (empty($rgpd_pedidos)): ?>
                                <p class="small text-muted mb-0">Sem pedidos registados.</p>
                            <?php else: foreach ($rgpd_pedidos as $rp): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small"><?= h(ucfirst($rp['tipo'])) ?></span>
                                    <span class="badge bg-<?= $rp['estado']==='pendente'?'warning text-dark':($rp['estado']==='processado'?'success':'secondary') ?>"><?= h($rp['estado']) ?></span>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
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
