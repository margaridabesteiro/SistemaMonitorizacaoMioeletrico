<?php
require_once __DIR__.'/../../../config/app.php';
requirePerfil('tecnico');
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Jogo</title></head>
<body>
<div style="padding:10px;background:#1a5f8a;color:#fff;font-family:sans-serif;">
  <a href="jogos_disponiveis.php" style="color:#fff;text-decoration:none;">← Voltar</a>
  <span style="margin-left:20px;font-weight:bold;"><?php echo ucwords('prosthesis trainer'); ?></span>
</div>
<iframe src="<?php echo APP_URL; ?>/private/tecnico/jogos/prosthesis_trainer.html" style="width:100%;height:calc(100vh - 42px);border:none;"></iframe>
</body></html>
