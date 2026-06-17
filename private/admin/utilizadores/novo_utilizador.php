<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$pagina_titulo = 'Novo Utilizador';
$pagina_ativa  = 'utilizadores';
requirePerfil('admin');

$erros = [];
$dados = ['nome'=>'','email'=>'','perfil'=>'','ativo'=>1];

$chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
$len   = strlen($chars);
$senha_gerada = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    for ($i = 0; $i < 10; $i++) $senha_gerada .= $chars[random_int(0, $len - 1)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']    = trim($_POST['nome']    ?? '');
    $dados['email']   = trim($_POST['email']   ?? '');
    $dados['perfil']  = $_POST['perfil']       ?? '';
    $dados['ativo']   = isset($_POST['ativo']) ? 1 : 0;

    if ($dados['perfil'] === 'utente') {
        $password     = $_POST['password'] ?? '';
        $senha_gerada = $password;
    } else {
        $password = 'Rehablink2026!';
    }

    $data_nascimento  = trim($_POST['data_nascimento'] ?? '') ?: null;
    $sexo             = trim($_POST['sexo']            ?? '') ?: null;
    $telemovel        = trim($_POST['telemovel']       ?? '') ?: null;
    $especialidade    = trim($_POST['especialidade']   ?? '') ?: null;
    $cedula           = trim($_POST['cedula']          ?? '') ?: null;
    $nif              = trim($_POST['nif']             ?? '') ?: null;
    $morada           = trim($_POST['morada']          ?? '') ?: null;
    $codigo_postal    = trim($_POST['codigo_postal']   ?? '') ?: null;
    $localidade       = trim($_POST['localidade']      ?? '') ?: null;

    // Normalizar prefixo Dr. para médicos (evita "Dr. Dr.")
    if ($dados['perfil'] === 'medico') {
        $nome_limpo = preg_replace('/^(Dr\.\s+)+/u', '', $dados['nome']);
        $dados['nome'] = 'Dr. ' . $nome_limpo;
    }
    if ($dados['nome'] === '')  $erros[] = 'O nome é obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (!in_array($dados['perfil'], ['admin','medico','tecnico','utente'], true)) $erros[] = 'Perfil inválido.';
    if ($dados['perfil'] === 'utente' && strlen($password) < 8) $erros[] = 'Password inválida (mínimo 8 caracteres).';
    if ($dados['perfil'] === 'utente' && empty($_POST['rgpd_consentimento'])) $erros[] = 'É obrigatório confirmar o consentimento RGPD do utente.';
    if ($data_nascimento && $data_nascimento > date('Y-m-d')) $erros[] = 'Data de nascimento não pode ser futura.';
    if ($telemovel !== null && !preg_match('/^\d{9}$/', $telemovel)) $erros[] = 'O contacto deve ter exatamente 9 dígitos.';
    if ($dados['perfil'] === 'medico' && $cedula !== null && !preg_match('/^\d{5}$/', $cedula)) $erros[] = 'O número de cédula profissional deve ter exatamente 5 dígitos.';

    if (empty($erros)) {
        $db = getDB();
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email = ?');
        $existe->execute([$dados['email']]);
        if ($existe->fetch()) {
            $erros[] = 'Já existe um utilizador com esse email.';
        } else {
            // Unicidade: telemóvel (cross-perfil)
            if ($telemovel !== null) {
                $tel_dup = false;
                try { $s = $db->prepare("SELECT 1 FROM profissionais WHERE contacto = ? LIMIT 1"); $s->execute([$telemovel]); if ($s->fetch()) $tel_dup = true; } catch (\Throwable $e) {}
                if (!$tel_dup) { try { $s = $db->prepare("SELECT 1 FROM utentes WHERE telemovel = ? LIMIT 1"); $s->execute([$telemovel]); if ($s->fetch()) $tel_dup = true; } catch (\Throwable $e) {} }
                if ($tel_dup) $erros[] = 'Este número de telemóvel já está registado noutro utilizador.';
            }
            // Unicidade: NIF
            if ($dados['perfil'] === 'utente' && $nif !== null) {
                $s = $db->prepare("SELECT 1 FROM utentes WHERE nif = ? LIMIT 1");
                $s->execute([$nif]);
                if ($s->fetch()) $erros[] = 'Este NIF já está registado noutro utente.';
            }

            if (empty($erros)) {
            $hash         = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $deve_alterar = $dados['perfil'] === 'utente' ? 1 : 0;
            $stmt = $db->prepare('INSERT INTO utilizadores (nome, email, password_hash, deve_alterar_password, perfil, ativo) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$dados['nome'], $dados['email'], $hash, $deve_alterar, $dados['perfil'], $dados['ativo']]);
            $novo_id = (int)$db->lastInsertId();

            if ($dados['perfil'] === 'medico') {
                $db->prepare('INSERT INTO profissionais (utilizador_id, numero_ordem, especialidade, contacto) VALUES (?,?,?,?)')
                   ->execute([$novo_id, $cedula, $especialidade, $telemovel]);
                try {
                    $db->prepare('UPDATE profissionais SET data_nascimento=?, sexo=? WHERE utilizador_id=?')
                       ->execute([$data_nascimento, $sexo, $novo_id]);
                } catch (\Throwable $e) {}

            } elseif ($dados['perfil'] === 'tecnico') {
                $db->prepare('INSERT INTO profissionais (utilizador_id, contacto) VALUES (?,?)')
                   ->execute([$novo_id, $telemovel]);
                try {
                    $db->prepare('UPDATE profissionais SET data_nascimento=?, sexo=? WHERE utilizador_id=?')
                       ->execute([$data_nascimento, $sexo, $novo_id]);
                } catch (\Throwable $e) {}

            } elseif ($dados['perfil'] === 'utente') {
                $seg_id    = (int)($_POST['seguradora_id'] ?? 0) ?: null;
                $cobertura = 'Particular';
                if ($seg_id) {
                    $st = $db->prepare('SELECT tipo FROM seguradoras WHERE id=?'); $st->execute([$seg_id]);
                    $tipo_seg = $st->fetchColumn();
                    if ($tipo_seg === 'SNS')    $cobertura = 'SNS';
                    elseif ($tipo_seg === 'Seguro') $cobertura = 'Seguro';
                }
                try {
                    $db->prepare('INSERT INTO utentes (utilizador_id, cobertura_saude, seguradora_id, nif, morada, codigo_postal, localidade, data_nascimento, sexo) VALUES (?,?,?,?,?,?,?,?,?)')
                       ->execute([$novo_id, $cobertura, $seg_id, $nif, $morada, $codigo_postal, $localidade, $data_nascimento, $sexo]);
                } catch (\Throwable $e) {
                    $db->prepare('INSERT INTO utentes (utilizador_id, cobertura_saude, seguradora_id) VALUES (?,?,?)')
                       ->execute([$novo_id, $cobertura, $seg_id]);
                }
                $utente_row_id = (int)$db->lastInsertId();
                if ($utente_row_id) {
                    try {
                        $db->prepare('UPDATE utentes SET telemovel=? WHERE id=?')->execute([$telemovel, $utente_row_id]);
                    } catch (\Throwable $e) {}
                }
                $medico = $db->query("SELECT p.id, p.utilizador_id FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE u.perfil='medico' AND u.ativo=1 ORDER BY (SELECT COUNT(*) FROM utentes WHERE medico_id=p.id) ASC, RAND() LIMIT 1")->fetch();
                if ($medico) {
                    $db->prepare('UPDATE utentes SET medico_id=? WHERE utilizador_id=?')->execute([$medico['id'], $novo_id]);
                    notificar(
                        (int)$medico['utilizador_id'],
                        'info',
                        'Novo utente atribuído',
                        'O utente ' . $dados['nome'] . ' foi-lhe atribuído para acompanhamento.',
                        APP_URL . '/private/medico/consultas/consulta.php'
                    );
                }
                $tecnico = $db->query("SELECT p.id, p.utilizador_id FROM profissionais p JOIN utilizadores u ON u.id=p.utilizador_id WHERE u.perfil='tecnico' AND u.ativo=1 ORDER BY (SELECT COUNT(*) FROM utentes WHERE tecnico_id=p.id) ASC, RAND() LIMIT 1")->fetch();
                if ($tecnico) {
                    $db->prepare('UPDATE utentes SET tecnico_id=? WHERE utilizador_id=?')->execute([$tecnico['id'], $novo_id]);
                    notificar(
                        (int)$tecnico['utilizador_id'],
                        'info',
                        'Novo utente atribuído',
                        'O utente ' . $dados['nome'] . ' foi-lhe atribuído para acompanhamento.',
                        APP_URL . '/private/tecnico/pacientes/lista_pacientes.php'
                    );
                }
                try {
                    $db->prepare('INSERT INTO rgpd_consentimentos (utilizador_id, tipo, registado_por, ip, detalhes) VALUES (?,?,?,?,?)')
                       ->execute([$novo_id,'registo',$_SESSION['utilizador_id'],$_SERVER['REMOTE_ADDR']??null,'Consentimento RGPD Art.9(2)(h) registado pelo administrador na criação de conta']);
                } catch (\Throwable $e) {}

            } elseif ($dados['perfil'] === 'admin') {
                try {
                    $db->prepare('UPDATE utilizadores SET data_nascimento=?, sexo=?, telemovel=? WHERE id=?')
                       ->execute([$data_nascimento, $sexo, $telemovel, $novo_id]);
                } catch (\Throwable $e) {}
            }

            $db->prepare('INSERT IGNORE INTO preferencias_utilizador (utilizador_id) VALUES (?)')->execute([$novo_id]);
            registarAuditoria('CRIAR', 'Utilizador', $novo_id, 'Utilizador criado: ' . $dados['nome'] . ' (' . $dados['perfil'] . ')');

            $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Utilizador criado.'];
            redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
            } // end unicidade
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

            <div class="card p-4" style="max-width:640px;">
                <form method="POST" id="form-novo-user">

                    <!-- Nome -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome completo *</label>
                        <input type="text" name="nome" class="form-control" value="<?= h($dados['nome']) ?>" required>
                    </div>

                    <!-- Perfil -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Perfil *</label>
                        <select name="perfil" class="form-select" required id="select-perfil">
                            <option value="">-- Selecionar --</option>
                            <?php foreach (['admin'=>'Administrador','medico'=>'Médico','tecnico'=>'Técnico','utente'=>'Utente'] as $p=>$l): ?>
                                <option value="<?= $p ?>" <?= $dados['perfil'] === $p ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email *</label>
                        <div class="input-group">
                            <input type="text" name="email" id="email-input" class="form-control"
                                   value="<?= h($dados['email']) ?>" required>
                            <span class="input-group-text text-muted" id="email-suffix" style="display:none;">@rehablink.pt</span>
                        </div>
                        <div class="form-text" id="email-nota" style="display:none;">
                            <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                            O domínio @rehablink.pt é preenchido automaticamente para este perfil.
                        </div>
                    </div>

                    <!-- Data de Nascimento + Sexo -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control"
                                   max="<?= date('Y-m-d') ?>"
                                   value="<?= h($_POST['data_nascimento'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Sexo</label>
                            <select name="sexo" class="form-select">
                                <option value="">-- Não especificado --</option>
                                <?php foreach (['M'=>'Masculino','F'=>'Feminino','O'=>'Outro'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($_POST['sexo']??'')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Telemóvel -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Telemóvel</label>
                        <input type="tel" name="telemovel" class="form-control" placeholder="Ex: 912000001"
                               pattern="[0-9]{9}" maxlength="9" minlength="9"
                               title="Introduza exatamente 9 dígitos, sem espaços"
                               oninput="this.value=this.value.replace(/\D/g,'')"
                               value="<?= h($_POST['telemovel'] ?? '') ?>">
                        <div class="form-text">Exatamente 9 dígitos, sem espaços.</div>
                    </div>

                    <!-- Password (utente: temporária gerada; outros: Rehablink2026! fixa) -->
                    <div id="bloco-password" class="mb-3">
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
                        <div class="form-text">
                            <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                            Copie esta password antes de guardar.
                            <span id="nota-alterar" style="display:none;">O utente será obrigado a alterá-la no primeiro acesso.</span>
                        </div>
                        <div id="copiado" class="text-success small mt-1" style="display:none;"><i class="fa-solid fa-check me-1"></i>Copiado!</div>
                    </div>
                    <div id="bloco-password-padrao" class="mb-3 alert alert-secondary py-2 small" style="display:none;">
                        <i class="fa-solid fa-key me-1"></i>
                        Palavra-passe padrão: <strong class="font-monospace">Rehablink2026!</strong>
                        Só o administrador pode repô-la.
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= ($dados['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Conta ativa</label>
                    </div>

                    <!-- Bloco Médico -->
                    <div id="bloco-medico" class="card p-3 mb-3 border-primary" style="display:none;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-user-doctor me-2" style="color:#8B0000;"></i>Dados Clínicos</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Especialidade</label>
                                <input type="text" name="especialidade" class="form-control" placeholder="Ex: Medicina Física e Reabilitação" value="<?= h($_POST['especialidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nº de Cédula Profissional</label>
                                <input type="text" name="cedula" class="form-control" placeholder="Ex: 12345"
                                       pattern="[0-9]{5}" maxlength="5" minlength="5"
                                       title="Introduza exatamente 5 dígitos"
                                       oninput="this.value=this.value.replace(/\D/g,'')"
                                       value="<?= h($_POST['cedula'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Bloco Utente -->
                    <div id="bloco-utente" style="display:none;">
                        <div class="card p-3 mb-3 border-success">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-address-card me-2" style="color:#198754;"></i>Dados do Utente</h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">NIF</label>
                                <input type="text" name="nif" class="form-control" placeholder="Ex: 123456789" maxlength="9"
                                       value="<?= h($_POST['nif'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Morada</label>
                                <input type="text" name="morada" class="form-control" placeholder="Ex: Rua das Flores, 25, 2º Esq"
                                       value="<?= h($_POST['morada'] ?? '') ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-semibold">Código Postal</label>
                                    <input type="text" name="codigo_postal" class="form-control" placeholder="Ex: 4000-001"
                                           value="<?= h($_POST['codigo_postal'] ?? '') ?>">
                                </div>
                                <div class="col-md-7 mb-3">
                                    <label class="form-label fw-semibold">Localidade</label>
                                    <input type="text" name="localidade" class="form-control" placeholder="Ex: Porto"
                                           value="<?= h($_POST['localidade'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold">Seguradora</label>
                                <select name="seguradora_id" class="form-select">
                                    <option value="">— Sem seguradora (Particular) —</option>
                                    <?php foreach ($seguradoras_list as $seg): ?>
                                        <option value="<?= $seg['id'] ?>" <?= ($_POST['seguradora_id']??'')==$seg['id']?'selected':'' ?>><?= h($seg['nome']) ?> (<?= $seg['tipo'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Determina os preços automáticos nas faturas e a cobertura de saúde.</div>
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rgpd_consentimento" id="rgpd_consentimento">
                                <label class="form-check-label small" for="rgpd_consentimento">
                                    <i class="fa-solid fa-shield-halved me-1"></i>
                                    <strong>Consentimento RGPD</strong> — Confirmo que o utente prestou consentimento informado
                                    para o tratamento dos seus dados de saúde ao abrigo do
                                    <strong>RGPD Art.&nbsp;9.º, n.º&nbsp;2, al.&nbsp;h)</strong>.
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn" style="background:#8B0000;color:#fff;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Utilizador
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

const REHABLINK_SUFFIX = '@rehablink.pt';
const PERFIS_REHABLINK = ['admin','medico','tecnico'];

function atualizarBlocos() {
    const perfil   = document.getElementById('select-perfil').value;
    const emailInp = document.getElementById('email-input');
    const suffix   = document.getElementById('email-suffix');
    const emailNota = document.getElementById('email-nota');
    const isRL     = PERFIS_REHABLINK.includes(perfil);
    const nomeInp  = document.querySelector('input[name="nome"]');

    // Prefixo Dr. para médicos
    if (perfil === 'medico') {
        if (!nomeInp.value.startsWith('Dr. ')) nomeInp.value = 'Dr. ' + nomeInp.value;
    } else {
        if (nomeInp.value.startsWith('Dr. ')) nomeInp.value = nomeInp.value.slice(4);
    }

    // Email suffix logic
    if (isRL) {
        suffix.style.display  = '';
        emailNota.style.display = '';
        // Remove @rehablink.pt if it's already there (show just prefix)
        if (emailInp.value.endsWith(REHABLINK_SUFFIX)) {
            emailInp.value = emailInp.value.slice(0, -REHABLINK_SUFFIX.length);
        }
        // Remove type=email to allow just prefix
        emailInp.type = 'text';
    } else {
        suffix.style.display  = 'none';
        emailNota.style.display = 'none';
        emailInp.type = 'email';
    }

    // Show/hide professional blocks
    document.getElementById('bloco-medico').style.display  = perfil === 'medico'  ? 'block' : 'none';
    document.getElementById('bloco-utente').style.display  = perfil === 'utente'  ? 'block' : 'none';
    document.getElementById('nota-alterar').style.display  = perfil === 'utente'  ? 'inline' : 'none';

    // Password: utente tem temporária gerada; outros têm Rehablink2026! fixa
    const blocoPass       = document.getElementById('bloco-password');
    const blocoPassPadrao = document.getElementById('bloco-password-padrao');
    if (perfil === 'utente' || perfil === '') {
        blocoPass.style.display       = 'block';
        blocoPassPadrao.style.display = 'none';
    } else {
        blocoPass.style.display       = 'none';
        blocoPassPadrao.style.display = 'block';
    }
}

// On submit: assemble full email for rehablink profiles
document.getElementById('form-novo-user').addEventListener('submit', function() {
    const perfil   = document.getElementById('select-perfil').value;
    const emailInp = document.getElementById('email-input');
    if (PERFIS_REHABLINK.includes(perfil) && emailInp.value && !emailInp.value.includes('@')) {
        emailInp.value = emailInp.value + REHABLINK_SUFFIX;
    }
});

document.getElementById('select-perfil').addEventListener('change', atualizarBlocos);
atualizarBlocos();
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
