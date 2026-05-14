<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Backoffice - Quem Somos'; $pagina_ativa = 'backoffice';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="dashboard-tabs mb-4">
                <a href="backoffice_quem_somos.php" class="dashboard-tab active"><i class="fa-solid fa-building"></i> Quem Somos</a>
                <a href="backoffice_equipa.php" class="dashboard-tab"><i class="fa-solid fa-users"></i> Nossa Equipa</a>
                <a href="backoffice_servicos.php" class="dashboard-tab"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php" class="dashboard-tab"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <div class="card p-4">
                <h2 class="mb-4" style="color:#8B0000;">Backoffice — Editar "Quem Somos"</h2>
                <form>
                    <div class="mb-3"><label class="form-label">Título</label><input type="text" class="form-control" value="Quem Somos"></div>
                    <div class="mb-3"><label class="form-label">Subtítulo</label><input type="text" class="form-control" value="Excelência em Diagnóstico e Inovação"></div>
                    <div class="mb-4"><label class="form-label">Descrição</label><textarea class="form-control" rows="5">A RehabLink combina fisioterapia tradicional com jogos de reabilitação e monitorização contínua.</textarea></div>
                    <button type="button" class="btn" style="background:#8B0000;color:#fff;" onclick="alert('Funcionalidade em desenvolvimento — ligação à BD de conteúdos.')"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
