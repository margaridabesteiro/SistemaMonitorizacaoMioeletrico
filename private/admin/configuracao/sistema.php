<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Configuração do Sistema'; $pagina_ativa = 'configuracao';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <h1 class="mb-4">Configuração do Sistema</h1>
            <div class="card p-4 mb-4" style="max-width:700px;">
                <h5><i class="fa-solid fa-sliders me-2" style="color:#8B0000;"></i>Parâmetros Globais de Treino</h5><hr>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Força mínima padrão (N)</label><input type="number" class="form-control" value="5" min="0" max="20"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Força máxima padrão (N)</label><input type="number" class="form-control" value="15" min="0" max="30"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Duração padrão sessão (min)</label><input type="number" class="form-control" value="15" min="5" max="60"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Tempo máximo sessão (min)</label><input type="number" class="form-control" value="45" min="15" max="90"></div>
                </div>
                <button class="btn" style="background:#8B0000;color:#fff;"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar Configurações</button>
            </div>
            <div class="card p-3 mb-4" style="max-width:700px;">
                <h5><i class="fa-solid fa-database me-2" style="color:#8B0000;"></i>Informação da Base de Dados</h5><hr>
                <?php
                $db = getDB();
                $tbls = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo '<p><strong>BD:</strong> ' . DB_NAME . ' · <strong>Tabelas:</strong> ' . count($tbls) . '</p>';
                echo '<p><small class="text-muted">' . implode(', ', $tbls) . '</small></p>';
                ?>
            </div>
        </main>
