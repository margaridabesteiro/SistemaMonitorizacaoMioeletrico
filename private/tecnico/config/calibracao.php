<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Calibração'; $pagina_ativa = 'calibracao';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>

        <main class="content">
            <?php
            $db = getDB(); $uid = (int)$_SESSION['utilizador_id'];
            $stmt = $db->prepare('SELECT id FROM profissionais WHERE utilizador_id=?'); $stmt->execute([$uid]); $pid = (int)$stmt->fetchColumn();
            $pacientes = $pid ? $db->prepare("SELECT ut.id, u.nome FROM utentes ut JOIN utilizadores u ON u.id=ut.utilizador_id WHERE ut.tecnico_id=? ORDER BY u.nome") : null;
            if ($pacientes) { $pacientes->execute([$pid]); $pacientes = $pacientes->fetchAll(); } else { $pacientes = []; }
            ?>
            <h1 class="mb-4">Calibração do Sensor</h1>
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-3 mb-3">
                        <h5>Selecionar Paciente</h5>
                        <select class="form-select mb-3" id="selectPac">
                            <?php foreach($pacientes as $p): ?><option value="<?= $p['id'] ?>"><?= h($p['nome']) ?></option><?php endforeach; ?>
                        </select>
                        <h5>Limites de Força</h5>
                        <label class="form-label">Força Mínima (N): <span id="minVal">8</span>N</label>
                        <input type="range" class="form-range" min="0" max="20" value="8" step="0.5" oninput="document.getElementById('minVal').textContent=this.value">
                        <label class="form-label mt-2">Força Máxima (N): <span id="maxVal">12</span>N</label>
                        <input type="range" class="form-range" min="0" max="30" value="12" step="0.5" oninput="document.getElementById('maxVal').textContent=this.value">
                        <button class="btn mt-3" style="background:#1a5f8a;color:#fff;" onclick="alert('Calibração enviada para o dispositivo.')"><i class="fa-solid fa-sliders me-1"></i>Aplicar Calibração</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5>Dispositivo Associado</h5>
                        <?php
                        $sel = $pacientes[0]['id'] ?? 0;
                        if ($sel) { $d = $db->query("SELECT d.codigo, d.tipo, d.ultimo_sync, d.ativo FROM dispositivos d JOIN utentes ut ON ut.id=d.utente_id WHERE ut.id=$sel LIMIT 1")->fetch(); }
                        if (!empty($d)): ?>
                        <p><strong>Código:</strong> <?= h($d['codigo']) ?></p>
                        <p><strong>Tipo:</strong> <?= h($d['tipo']) ?></p>
                        <p><strong>Último sync:</strong> <?= $d['ultimo_sync'] ? h(substr($d['ultimo_sync'],0,16)) : 'Nunca' ?></p>
                        <p><strong>Estado:</strong> <?= $d['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></p>
                        <?php else: ?><p class="text-muted">Sem dispositivo associado.</p><?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
