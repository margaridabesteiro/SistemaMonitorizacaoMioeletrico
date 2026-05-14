<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Associar Dispositivo'; $pagina_ativa = 'dispositivos';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
$db = getDB();
?>

        <main class="content">
            <?php
            $erros = [];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $codigo = trim($_POST['codigo'] ?? '');
                $tipo = trim($_POST['tipo'] ?? '');
                $utente_id = (int)($_POST['utente_id'] ?? 0);
                $serie = trim($_POST['serie'] ?? '');
                if ($codigo === '') $erros[] = 'Código obrigatório.';
                if ($tipo === '') $erros[] = 'Tipo obrigatório.';
                if (empty($erros)) {
                    $dup = $db->prepare('SELECT id FROM dispositivos WHERE codigo = ?');
                    $dup->execute([$codigo]);
                    if ($dup->fetch()) { $erros[] = 'Código já existe.'; }
                }
                if (empty($erros)) {
                    $db->prepare('INSERT INTO dispositivos (codigo, tipo, utente_id, associado_em, ativo) VALUES (?,?,?,NOW(),1)')
                       ->execute([$codigo, $tipo, $utente_id ?: null]);
                    $_SESSION['flash'] = ['tipo'=>'success','mensagem'=>'Dispositivo associado.']; redirect(APP_URL . '/private/admin/dispositivos/lista_dispositivos.php');
                }
            }
            $utentes = $db->query("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id = ut.utilizador_id ORDER BY u.nome")->fetchAll();
            ?>
            <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="lista_dispositivos.php">Dispositivos</a></li><li class="breadcrumb-item active">Associar</li></ol></nav>
            <h1 class="mb-4">Associar Dispositivo</h1>
            <?php if (!empty($erros)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($erros as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="card p-4" style="max-width:600px;">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">ID do Dispositivo *</label><input type="text" name="codigo" class="form-control" placeholder="Ex: PS-1032" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="">Selecionar...</option>
                                <option>Força de pinça</option><option>Precisão</option><option>Resistência</option><option>Multi-canal</option>
                            </select></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Paciente</label>
                        <select name="utente_id" class="form-select">
                            <option value="">Nenhum (não associado)</option>
                            <?php foreach($utentes as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['nome']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="mb-4"><label class="form-label fw-semibold">Versão Firmware</label><input type="text" name="firmware" class="form-control" placeholder="Ex: 2.1.0"></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Associar</button>
                        <a href="lista_dispositivos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
