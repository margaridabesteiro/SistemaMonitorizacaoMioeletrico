<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Backoffice - Serviços'; $pagina_ativa = 'backoffice';
require_once __DIR__ . '/../../../includes/header_admin.php';
require_once __DIR__ . '/../../../includes/sidebar_admin.php';
?>
        <main class="content">
            <div class="dashboard-tabs mb-4">
                <a href="backoffice_quem_somos.php" class="dashboard-tab"><i class="fa-solid fa-building"></i> Quem Somos</a>
                <a href="backoffice_equipa.php" class="dashboard-tab"><i class="fa-solid fa-users"></i> Nossa Equipa</a>
                <a href="backoffice_servicos.php" class="dashboard-tab active"><i class="fa-solid fa-stethoscope"></i> Serviços</a>
                <a href="backoffice_seguros.php" class="dashboard-tab"><i class="fa-solid fa-handshake"></i> Acordos</a>
            </div>
            <h1 class="mb-4" style="color:#8B0000;">Backoffice — Nossos Serviços</h1>
            <div class="card p-4">
                <h5>Adicionar Serviço</h5>
                <form class="row g-3 mt-1">
                    <div class="col-md-4"><label class="form-label">Título</label><input type="text" class="form-control" placeholder="Ex: Análises Clínicas"></div>
                    <div class="col-md-4"><label class="form-label">Ícone FontAwesome</label><input type="text" class="form-control" placeholder="fa-solid fa-flask"></div>
                    <div class="col-md-4"><label class="form-label">Descrição</label><input type="text" class="form-control"></div>
                    <div class="col-12"><button type="button" class="btn" style="background:#8B0000;color:#fff;" onclick="alert('Em desenvolvimento')"><i class="fa-solid fa-plus me-1"></i>Adicionar</button></div>
                </form>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
