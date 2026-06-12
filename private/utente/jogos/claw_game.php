<?php
require_once __DIR__.'/../../../config/app.php';
requirePerfil('utente');
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Jogo</title></head>
<body>
<div style="padding:10px;background:#8B0000;color:#fff;font-family:sans-serif;">
  <a href="../jogos_reabilitacao.php" style="color:#fff;text-decoration:none;">← Voltar</a>
  <span style="margin-left:20px;font-weight:bold;">Claw Game</span>
</div>
<iframe src="<?php echo APP_URL; ?>/private/utente/jogos/claw_game.html?v=<?php echo filemtime(__DIR__.'/claw_game.html'); ?>" style="width:100%;height:calc(100vh - 42px);border:none;"></iframe>
</body></html>
