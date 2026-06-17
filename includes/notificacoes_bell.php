<?php
// includes/notificacoes_bell.php
// Sino de notificações — incluir em cada header dentro de um flex container
// Requer: $_SESSION['utilizador_id'] e APP_URL definidos

$_nb_count = 0;
$_nb_nots  = [];
try {
    $_nb_db = getDB();
    $_s1 = $_nb_db->prepare("SELECT COUNT(*) FROM notificacoes WHERE utilizador_id=? AND lida=0");
    $_s1->execute([$_SESSION['utilizador_id']]);
    $_nb_count = (int)$_s1->fetchColumn();
    $_s2 = $_nb_db->prepare("SELECT titulo, corpo, url, tipo, lida, criado_em FROM notificacoes WHERE utilizador_id=? ORDER BY criado_em DESC LIMIT 10");
    $_s2->execute([$_SESSION['utilizador_id']]);
    $_nb_nots = $_s2->fetchAll();
} catch (\Throwable $e) {}

$_nb_icons = [
    'prescricao'   => ['icon'=>'fa-file-medical',         'cor'=>'#1a5f8a'],
    'regressao'    => ['icon'=>'fa-triangle-exclamation', 'cor'=>'#dc3545'],
    'mensagem'     => ['icon'=>'fa-message',              'cor'=>'#198754'],
    'sessao'       => ['icon'=>'fa-calendar-check',       'cor'=>'#0dcaf0'],
    'videoconsulta' => ['icon'=>'fa-video',                'cor'=>'#6f42c1'],
    'info'         => ['icon'=>'fa-circle-info',          'cor'=>'#6c757d'],
];
?>
<div class="dropdown" id="dropdown-sino">
    <button class="btn btn-sm btn-outline-secondary position-relative" type="button"
            data-bs-toggle="dropdown" aria-expanded="false"
            style="padding:.35rem .6rem;border-color:rgba(255,255,255,.3);color:inherit;"
            onclick="notBellOpen()">
        <i class="fa-regular fa-bell"></i>
        <?php if ($_nb_count > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              id="not-badge" style="font-size:.6rem;padding:.2em .4em;">
            <?= min($_nb_count, 99) ?>
        </span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg" style="min-width:360px;max-width:460px;max-height:560px;overflow-y:auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light border-bottom sticky-top">
            <strong style="font-size:.85rem;">Notificações</strong>
            <button type="button" class="btn btn-link btn-sm p-0 text-muted" style="font-size:.75rem;" onclick="notMarcarTodas()">
                Marcar todas lidas
            </button>
        </div>
        <?php if (empty($_nb_nots)): ?>
        <div class="p-4 text-center text-muted" style="font-size:.85rem;">
            <i class="fa-regular fa-bell-slash fa-lg d-block mb-2"></i>Sem notificações.
        </div>
        <?php else: ?>
        <?php foreach ($_nb_nots as $_nb): $_nb_ic = $_nb_icons[$_nb['tipo']] ?? $_nb_icons['info']; ?>
        <a href="<?= $_nb['url'] ? h($_nb['url']) : '#' ?>"
           class="d-flex align-items-start gap-2 px-3 py-2 border-bottom text-decoration-none"
           style="<?= !$_nb['lida'] ? 'background:#f0f4ff;' : '' ?>">
            <i class="fa-solid <?= $_nb_ic['icon'] ?> mt-1" style="color:<?= $_nb_ic['cor'] ?>;font-size:.85rem;flex-shrink:0;"></i>
            <div class="min-w-0 flex-grow-1">
                <div class="<?= !$_nb['lida'] ? 'fw-semibold' : 'text-muted' ?>" style="font-size:.8rem;color:inherit;"><?= h($_nb['titulo']) ?></div>
                <?php if ($_nb['corpo']): ?>
                <div class="text-muted" style="font-size:.73rem;white-space:normal;word-break:break-word;"><?= h($_nb['corpo']) ?></div>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.68rem;"><?= date('d/m H:i', strtotime($_nb['criado_em'])) ?></div>
            </div>
            <?php if (!$_nb['lida']): ?>
            <span style="width:7px;height:7px;border-radius:50%;background:#4f46e5;flex-shrink:0;margin-top:5px;"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script>
function notBellOpen(){
    fetch('<?= APP_URL ?>/api/notificacoes/marcar_lidas.php').catch(()=>{});
    const b=document.getElementById('not-badge'); if(b) b.style.display='none';
}
function notMarcarTodas(){
    fetch('<?= APP_URL ?>/api/notificacoes/marcar_lidas.php').catch(()=>{});
    const b=document.getElementById('not-badge'); if(b) b.style.display='none';
    document.querySelectorAll('#dropdown-sino .d-flex.align-items-start').forEach(function(el){
        el.style.background='';
        el.querySelectorAll('[style*="border-radius:50%"]').forEach(s=>s.remove());
        const t=el.querySelector('.fw-semibold'); if(t){t.classList.remove('fw-semibold');t.classList.add('text-muted');}
    });
}
// Polling: atualiza badge a cada 30s sem precisar de refresh
(function(){
    var _notUrl = '<?= APP_URL ?>/api/notificacoes/contar.php';
    setInterval(function(){
        fetch(_notUrl, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                var n = d.count || 0;
                var sino = document.getElementById('dropdown-sino');
                if (!sino) return;
                var btn = sino.querySelector('button');
                var badge = document.getElementById('not-badge');
                if (n > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.id = 'not-badge';
                        badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                        badge.style.cssText = 'font-size:.6rem;padding:.2em .4em;';
                        btn.appendChild(badge);
                    }
                    badge.textContent = Math.min(n, 99);
                    badge.style.display = '';
                } else if (badge) {
                    badge.style.display = 'none';
                }
            })
            .catch(function(){});
    }, 30000);
})();
</script>
