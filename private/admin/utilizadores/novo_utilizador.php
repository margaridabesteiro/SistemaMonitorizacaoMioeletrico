<?php
// private/admin/utilizadores/novo_utilizador.php
// Formulário de criação de utilizador — GET mostra form, POST processa

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';

$pagina_titulo = 'Novo Utilizador';
$pagina_ativa  = 'utilizadores';

$erros  = [];
$dados  = ['nome'=>'','email'=>'','perfil'=>'','ativo'=>1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']    = trim($_POST['nome']     ?? '');
    $dados['email']   = trim($_POST['email']    ?? '');
    $dados['perfil']  = $_POST['perfil']        ?? '';
    $dados['ativo']   = isset($_POST['ativo'])  ? 1 : 0;
    $password         = $_POST['password']      ?? '';
    $password_conf    = $_POST['password_conf'] ?? '';

    // Validação
    if ($dados['nome'] === '')   $erros[] = 'O nome é obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (!in_array($dados['perfil'], ['admin','medico','tecnico','utente'], true)) $erros[] = 'Perfil inválido.';
    if (strlen($password) < 8)   $erros[] = 'A password deve ter pelo menos 8 caracteres.';
    if ($password !== $password_conf) $erros[] = 'As passwords não coincidem.';
    if ($dados['perfil'] === 'utente' && empty($_POST['rgpd_consentimento'])) $erros[] = 'É obrigatório confirmar o consentimento RGPD do utente.';

    if (empty($erros)) {
        $db = getDB();

        // Verificar email duplicado
        $existe = $db->prepare('SELECT id FROM utilizadores WHERE email = ?');
        $existe->execute([$dados['email']]);
        if ($existe->fetch()) {
            $erros[] = 'Já existe um utilizador com esse email.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO utilizadores (nome, email, password_hash, perfil, ativo) VALUES (?,?,?,?,?)');
            $stmt->execute([$dados['nome'], $dados['email'], $hash, $dados['perfil'], $dados['ativo']]);

            // Se for médico ou técnico, criar registo em profissionais
            $novo_id = (int)$db->lastInsertId();
            if (in_array($dados['perfil'], ['medico', 'tecnico'], true)) {
                $db->prepare('INSERT INTO profissionais (utilizador_id) VALUES (?)')->execute([$novo_id]);
            } elseif ($dados['perfil'] === 'utente') {
                $db->prepare('INSERT INTO utentes (utilizador_id) VALUES (?)')->execute([$novo_id]);
                $utente_row_id = (int)$db->lastInsertId();
                // Auto-atribuir ao médico ativo com menos utentes (distribuição equilibrada; desempate aleatório)
                $medico = $db->query("
                    SELECT p.id
                    FROM profissionais p
                    JOIN utilizadores u ON u.id = p.utilizador_id
                    WHERE u.perfil = 'medico' AND u.ativo = 1
                    ORDER BY (SELECT COUNT(*) FROM utentes WHERE medico_id = p.id) ASC, RAND()
                    LIMIT 1
                ")->fetch();
                if ($medico) {
                    $db->prepare('UPDATE utentes SET medico_id=? WHERE id=?')
                       ->execute([$medico['id'], $utente_row_id]);
                }
                // Registar consentimento RGPD
                try {
                    $db->prepare('INSERT INTO rgpd_consentimentos (utilizador_id, tipo, registado_por, ip, detalhes) VALUES (?,?,?,?,?)')
                       ->execute([$novo_id, 'registo', $_SESSION['utilizador_id'], $_SERVER['REMOTE_ADDR'] ?? null,
                           'Consentimento RGPD Art.9(2)(h) registado pelo administrador na criação de conta']);
                } catch (\Throwable $e) {}
            }
            // Criar preferências por defeito para todos os perfis
            $db->prepare('INSERT IGNORE INTO preferencias_utilizador (utilizador_id) VALUES (?)')->execute([$novo_id]);

            $_SESSION['flash'] = ['tipo' => 'success', 'mensagem' => 'Utilizador criado com sucesso.'];
            redirect(APP_URL . '/private/admin/utilizadores/lista_utilizadores.php');
        }
    }
}

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
                        <input type="text" name="nome" class="form-control"
                               value="<?= h($dados['nome']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email *</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= h($dados['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Perfil *</label>
                        <select name="perfil" class="form-select" required>
                            <option value="">-- Selecionar --</option>
                            <?php foreach (['admin','medico','tecnico','utente'] as $p): ?>
                                <option value="<?= $p ?>" <?= $dados['perfil'] === $p ? 'selected' : '' ?>>
                                    <?= ucfirst($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Mín. 8 caracteres" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Confirmar Password *</label>
                            <input type="password" name="password_conf" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo"
                               <?= $dados['ativo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Conta ativa</label>
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
document.querySelector('select[name="perfil"]').addEventListener('change', function() {
    document.getElementById('bloco-rgpd').style.display = this.value === 'utente' ? 'block' : 'none';
});
</script>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
