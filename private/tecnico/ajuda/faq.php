<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/database.php';
$pagina_titulo = 'Ajuda / FAQ'; $pagina_ativa = 'ajuda';
require_once __DIR__ . '/../../../includes/header_tecnico.php';
require_once __DIR__ . '/../../../includes/sidebar_tecnico.php';
?>

        <main class="content">
            <h1 class="mb-4">Centro de Ajuda</h1>
            <?php $faqs = [
                ['Como iniciar uma sessão?','Vá a Sessões → Lista de Sessões, selecione a sessão agendada e clique em Iniciar.'],
                ['Como calibrar o sensor EMG?','Aceda a Calibração no menu, selecione o paciente e ajuste os limites de força com os sliders.'],
                ['Como gerar um relatório?','Aceda a Relatórios → Gerar Relatório, selecione o paciente e clique em PDF / Imprimir.'],
                ['Como registar um novo paciente?','Aceda a Pacientes → Novo Paciente e preencha os dados clínicos e de acesso.'],
                ['Como ver os dados EMG em tempo real?','Durante uma sessão iniciada, os dados são recebidos via WebSocket do dispositivo ESP32.'],
            ]; ?>
            <div class="accordion" id="faq">
                <?php foreach($faqs as $i=>[$q,$r]): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button <?= $i>0?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"><?= h($q) ?></button></h2>
                    <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>" data-bs-parent="#faq">
                        <div class="accordion-body"><?= h($r) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
