<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$pagina_titulo = 'Novo Utilizador';
$pagina_ativa  = 'utilizadores';
requirePerfil('admin');

$erros  = [];
$dados  = ['nome'=>'','email'=>'','perfil'=>'','ativo'=>1];
$prof   = ['numero_ordem'=>'','especialidade'=>'','instituicao'=>'','contacto'=>''];

// Gerar password automática (só em GET; em POST usa o valor submetido)
$chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
$len   = strlen($chars);
$senha_gerada = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    for ($i = 0; $i < 10; $i++) $senha_gerada .= $chars[random_int(0, $len - 1)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']    = trim($_POST['nome']     ?? '');
    $dados['email']   = trim($_POST['email']    ?? '');
    $dados['perfil']  = $_POST['perfil']        ?? '';
    $dados['ativo']   = isset($_POST['ativo'])  ? 1 : 0;
    $password         = $_POST['password']      ?? '';

    $prof['numero_ordem']  = trim($_POST['numero_ordem']  ?? '') ?: null;
    $prof['especialidade'] = trim($_POST['especialidade'] ?? '') ?: null;
    $prof['instituicao']   = trim($_POST['instituicao']   ?? '') ?: null;
    $prof['contacto']      = trim($_POST['contacto']      ?? '') ?: null;

    if ($dados['nome'] === '')   $erros[] = 'O nome é obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (!in_array($dados['perfil'], ['admin','medico','tecnico','utente'], true)) $erros[] = 'Perfil inválido.';
    if (strlen($password) < 8)   $erros[] = 'Password inválida (mínimo 8 caracteres).';
    if ($dados['perfil'] === 'utente' && empty($_POST['rgpd_consentimento'])) $erros[] = 'É obrigatório confirmar o consentimento RGPD do utente.';

    // Guardar password submetida para repopular o form em caso de erro
    $senha_gerada = $password;

    if (empty($erros)) {
        $db = getDB();
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email = ?');
        $existe->execute([$dados['email']]);
        if ($existe->fetch()) {
            $erros[] = 'Já existe um utilizador com esse email.';
        } else {
            $hash         = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $deve_alterar = in_array($dados['perfil'], ['medico','tecnico','utente']) ? 1 : 0;
            $stmt = $db->prepare('INSERT INTO utilizadores (nome, email, password_hash, deve_alterar_password, perfil, ativo) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$dados['nome'], $dados['email'], $hash, $deve_alterar, $dados['perfil'], $dados['ativo']]);
            $novo_id = (int)$db->lastInsertId();

            if (in_array($dados['perfil'], ['medico','tecnico'], true)) {
                $db->prepare('INSERT INTO profissionais (utilizador_id, numero_ordem, especialidade, instituicao, contacto) VALUES (?,?,?,?,?)')
                   ->execute([$novo_id, $prof['numero_ordem'], $prof['especialidade'], $prof['instituicao'], $prof['contacto']]);
            } elseif ($dados['perfil'] === 'utente') {
                $seg_id    = (int)($_POST['seguradora_id'] ?? 0) ?: null;
                $cobertura = 'Particular';
                if ($seg_id) {
                    $st = $db->prepare('SELECT tipo FROM seguradoras WHERE id=?'); $st->execute([$seg_id]);
                    $tipo_seg = $st->fetchColumn();
                    if ($tipo_seg === 'SNS')    $cobertura = 'SNS';
                    elseif ($tipo_seg === 'Seguro') $cobertura = 'Seguro';
                }
                $db->prepare('INSERT INTO utentes (utilizador_id, cobertura_saude, seguradora_id) VALUES (?,?,?)')->execute([$novo_id, $cobertura, $seg_id]);
                $utente_row_id = (int)$db->lastInsertId();
                $medico = $db->query("
                    SELECT p.id FROM profissionais p
                    JOIN utilizadores u ON u.id = p.utilizador_id
                    WHERE u.perfil='medico' AND u.ativo=1
                    ORDER BY (SELECT COUNT(*) FROM utentes WHERE medico_id=p.id) ASC, RAND() LIMIT 1
                ")->fetch();
                if ($medico) {
                    $db->prepare('UPDATE utentes SET medico_id=? WHERE id=?')->execute([$medico['id'], $utente_row_id]);
                }
                try {
                    $db->prepare('INSERT INTO rgpd_consentimentos (utilizador_id, tipo, registado_por, ip, detalhes) VALUES (?,?,?,?,?)')
                       ->execute([$novo_id,'registo',$_SESSION['utilizador_id'],$_SERVER['REMOTE_ADDR']??null,
                           'Consentimento RGPD Art.9(2)(h) registado pelo administrador na criação de conta']);
                } catch (\Throwable $e) {}
            }

            $db->prepare('INSERT IGNORE INTO preferencias_utilizador (utilizador_id) VALUES (?)')->execute([$novo_id]);
            registarAuditoria('CRIAR', 'Utilizador', $novo_id, 'Utilizador criado: ' . $dados['nome'] . ' (' . $dados['perfil'] . ')');

            $msg_pass = $deve_alterar
                ? "Utilizador criado. Password temporária: <strong class='font-monospace'>{$password}</strong> — comunique ao utilizador. Será obrigado a alterá-la no primeiro acesso."
                : "Utilizador administrador criado. Password: <strong class='font-monospace'>{$password}</strong>";
            $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => $msg_pass];
            redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
        }
    }
}

$db_seg = getDB();
$seguradoras_list = $db_seg->query('SELECT id, nome, tipo FROM seguradoras WHERE ativa=1 ORDER BY tipo, nome')->fetchAll();
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="lista_utilizadores.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                </a>
                <h1 class="mb-0">Novo Utilizador</h1>
            </div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card p-4" style="max-width:600px;">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome completo *</label>
                        <input type="text" name="nome" class="form-control" value="<?= h($dados['nome']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email *</label>
                        <input type="email" name="email" class="form-control" value="<?= h($dados['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Perfil *</label>
                        <select name="perfil" class="form-select" required id="select-perfil">
                            <option value="">-- Selecionar --</option>
                            <?php foreach (['admin','medico','tecnico','utente'] as $p): ?>
                                <option value="<?= $p ?>" <?= $dados['perfil'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Password gerada automaticamente -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password temporária *</label>
                        <div class="input-group">
                            <input type="text" id="senha_visivel" class="form-control font-monospace fw-bold"
                                   value="<?= h($senha_gerada) ?>" readonly style="letter-spacing:.08em;">
                            <button type="button" class="btn btn-outline-secondary" title="Copiar" onclick="copiarSenha()">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" title="Regenerar" onclick="regenerarSenha()">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <input type="hidden" name="password" id="senha_hidden" value="<?= h($senha_gerada) ?>">
                        <div class="form-text" id="nota-pass">
                            <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                            Copie esta password antes de guardar — não poderá ser recuperada depois.
                            <span id="nota-alterar" style="display:none;">O utilizador será obrigado a alterá-la no primeiro acesso.</span>
                        </div>
                        <div id="copiado" class="text-success small mt-1" style="display:none;"><i class="fa-solid fa-check me-1"></i>Copiado!</div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= $dados['ativo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Conta ativa</label>
                    </div>

                    <!-- Dados profissionais — visíveis só para médico/técnico -->
                    <div id="bloco-profissional" class="card p-3 mb-3 border-primary" style="display:none;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card me-2" style="color:#8B0000;"></i>Dados Profissionais</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nº Cédula / Ordem</label>
                                <input type="text" name="numero_ordem" class="form-control" placeholder="Ex: OM-12345" value="<?= h($prof['numero_ordem'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Especialidade</label>
                                <input type="text" name="especialidade" class="form-control" placeholder="Ex: Medicina Física e Reabilitação" value="<?= h($prof['especialidade'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-semibold">Instituição</label>
                                <input type="text" name="instituicao" class="form-control" placeholder="Ex: RehabLink" value="<?= h($prof['instituicao'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label fw-semibold">Contacto</label>
                                <input type="text" name="contacto" class="form-control" placeholder="Ex: 912 000 001" value="<?= h($prof['contacto'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seguradora — só para utentes -->
                    <div id="bloco-seguradora" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">Seguradora</label>
                        <select name="seguradora_id" class="form-select">
                            <option value="">— Sem seguradora (Particular) —</option>
                            <?php foreach ($seguradoras_list as $seg): ?>
                                <option value="<?= $seg['id'] ?>"><?= h($seg['nome']) ?> (<?= $seg['tipo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Determina os preços automáticos nas faturas e a cobertura de saúde.</div>
                    </div>

                    <div id="bloco-rgpd" class="alert alert-warning py-2 mb-3" style="display:none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="rgpd_consentimento" id="rgpd_consentimento">
                            <label class="form-check-label small" for="rgpd_consentimento">
                                <i class="fa-solid fa-shield-halved me-1"></i>
                                <strong>Consentimento RGPD</strong> — Confirmo que o utente prestou consentimento informado
                                para o tratamento dos seus dados de saúde ao abrigo do
                                <strong>RGPD Art.&nbsp;9.º, n.º&nbsp;2, al.&nbsp;h)</strong>
                                (cuidados de saúde e telerreabilitação).
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
                    </button>
                </form>
            </div>
        </main>

<script>
const CHARS = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';

function regenerarSenha() {
    let p = '';
    for (let i = 0; i < 10; i++) p += CHARS[Math.floor(Math.random() * CHARS.length)];
    document.getElementById('senha_visivel').value = p;
    document.getElementById('senha_hidden').value  = p;
    document.getElementById('copiado').style.display = 'none';
}

function copiarSenha() {
    const v = document.getElementById('senha_visivel').value;
    navigator.clipboard.writeText(v).then(() => {
        const el = document.getElementById('copiado');
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 2500);
    });
}

function atualizarBlocos() {
    var perfil = document.getElementById('select-perfil').value;
    document.getElementById('bloco-profissional').style.display = (perfil === 'medico' || perfil === 'tecnico') ? 'block' : 'none';
    document.getElementById('bloco-seguradora').style.display   = perfil === 'utente' ? 'block' : 'none';
    document.getElementById('bloco-rgpd').style.display         = perfil === 'utente' ? 'block' : 'none';
    document.getElementById('nota-alterar').style.display       = (perfil && perfil !== 'admin') ? 'inline' : 'none';
}

document.getElementById('select-perfil').addEventListener('change', atualizarBlocos);
atualizarBlocos();
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
