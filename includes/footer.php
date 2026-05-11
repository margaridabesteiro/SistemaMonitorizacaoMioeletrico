<?php
// includes/footer.php
// Rodapé reutilizável — fecha .wrapper, .content e carrega scripts globais
// Variável opcional: $js_extra (array de URLs de scripts a carregar no fim)
?>
        </main><!-- fim .content -->
    </div><!-- fim .wrapper -->

    <!-- Scripts globais -->
    <script src="<?= APP_URL ?>/public/assets/jQuery/jquery-3.6.0.min.js"></script>
    <script src="<?= APP_URL ?>/public/assets/bootstrap/bootstrap.bundle.min.js"></script>

    <?php if (!empty($js_extra)): ?>
        <?php foreach ($js_extra as $js): ?>
            <script src="<?= h($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($js_inline)): ?>
        <script><?= $js_inline ?></script>
    <?php endif; ?>
</body>
</html>
