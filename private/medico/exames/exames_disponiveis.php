<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Exames Disponíveis'; $pagina_ativa = 'exames';
require_once __DIR__ . '/../../../includes/header_medico.php';
require_once __DIR__ . '/../../../includes/sidebar_medico.php';
$exames = [['Análises Clínicas','fa-dna','Hemograma, bioquímica, etc.'],['Radiologia','fa-xray','Raio-X, TAC, RMN'],['Cardiologia','fa-heart-pulse','ECG, Holter, teste de esforço'],['Neurologia','fa-brain','EEG, EMG clínico'],['Ortopedia','fa-bone','Avaliação funcional, escalas'],];
?>
        <main class="content">
            <h1 class="mb-4">Exames Disponíveis</h1>
            <div class="row">
                <?php foreach($exames as [$titulo,$icone,$desc]): ?>
                <div class="col-md-4 mb-3">
                    <div class="card p-3">
                        <h5><i class="fa-solid <?= $icone ?> me-2" style="color:#1e7b4b;"></i><?= $titulo ?></h5>
                        <p class="text-muted small"><?= $desc ?></p>
                        <button class="btn btn-sm" style="background:#1e7b4b;color:#fff;">Selecionar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
