<?php
require_once __DIR__.'/../../../config/app.php';
require_once __DIR__.'/../../../config/database.php';
requirePerfil('utente');

$db  = getDB();
$uid = (int)$_SESSION['utilizador_id'];
$stmt = $db->prepare("SELECT ut.id, ut.tecnico_id FROM utentes ut WHERE ut.utilizador_id=?");
$stmt->execute([$uid]); $utente = $stmt->fetch();
$utid       = $utente ? (int)$utente['id']        : 0;
$tecnico_id = $utente ? (int)$utente['tecnico_id'] : 0;
$stmt2 = $db->prepare("SELECT id FROM jogos WHERE nome='catch_game'");
$stmt2->execute(); $jogo_id = (int)$stmt2->fetchColumn();
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catch! – RehabLink</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Outfit:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#0f0e17; --surface:#1a1826; --accent:#f7c948; --accent2:#ff6b6b;
    --good:#5cdb95; --text:#fffffe; --text2:#a7a9be; --border:rgba(255,255,255,0.07); --radius:16px;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);
    min-height:100vh;display:flex;flex-direction:column;align-items:center;
    padding:16px;user-select:none;overflow:hidden;}
  .topbar{width:100%;max-width:600px;display:flex;align-items:center;
    justify-content:space-between;margin-bottom:10px;}
  .brand{font-size:1.4rem;font-weight:900;letter-spacing:-.04em;color:var(--accent);}
  .brand span{color:var(--text2);font-weight:400;font-size:.75rem;display:block;
    font-family:'Space Mono',monospace;text-transform:uppercase;}
  .back-btn{color:var(--text2);text-decoration:none;font-size:.85rem;
    font-family:'Space Mono',monospace;opacity:.7;transition:opacity .2s;}
  .back-btn:hover{opacity:1;color:var(--text);}
  .timer{font-family:'Space Mono',monospace;font-size:1.4rem;font-weight:700;
    background:var(--surface);border:1px solid var(--border);border-radius:50px;
    padding:6px 16px;transition:color .3s;}
  .timer.urgent{color:var(--accent2);}
  .ip-bar{width:100%;max-width:600px;display:flex;gap:8px;margin-bottom:8px;}
  .ip-bar input{flex:1;background:var(--surface);border:1px solid var(--border);
    border-radius:10px;color:var(--text);font-family:'Space Mono',monospace;
    font-size:.8rem;padding:8px 12px;outline:none;}
  .ip-bar button{background:var(--accent);color:var(--bg);border:none;border-radius:10px;
    font-family:'Outfit',sans-serif;font-weight:700;font-size:.85rem;padding:8px 16px;cursor:pointer;}
  .status-bar{width:100%;max-width:600px;display:flex;align-items:center;
    justify-content:space-between;margin-bottom:8px;}
  .ws-status{display:flex;align-items:center;gap:6px;font-family:'Space Mono',monospace;
    font-size:.6rem;color:var(--text2);text-transform:uppercase;}
  .ws-dot{width:8px;height:8px;border-radius:50%;background:var(--accent2);transition:background .3s;}
  .ws-dot.connected{background:var(--good);}
  .stats{width:100%;max-width:600px;display:grid;grid-template-columns:repeat(3,1fr);
    gap:8px;margin-bottom:10px;}
  .stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:8px 10px;text-align:center;}
  .stat-lbl{font-family:'Space Mono',monospace;font-size:.5rem;color:var(--text2);
    text-transform:uppercase;letter-spacing:.1em;margin-bottom:2px;}
  .stat-val{font-size:1.4rem;font-weight:900;letter-spacing:-.04em;line-height:1;}
  .force-wrap{width:100%;max-width:600px;margin-bottom:10px;}
  .force-lbl{font-family:'Space Mono',monospace;font-size:.55rem;color:var(--text2);
    text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;
    display:flex;justify-content:space-between;}
  .force-track{width:100%;height:10px;background:var(--surface);border-radius:99px;
    border:1px solid var(--border);overflow:hidden;}
  .force-fill{height:100%;width:0%;border-radius:99px;
    background:linear-gradient(90deg,var(--good),var(--accent));transition:width .05s linear;}
  canvas{width:100%;max-width:600px;border-radius:20px;border:1px solid var(--border);
    background:var(--surface);display:block;touch-action:none;}
  .hint{margin-top:8px;font-family:'Space Mono',monospace;font-size:.6rem;color:var(--text2);
    text-transform:uppercase;letter-spacing:.1em;text-align:center;}
  .overlay{display:none;position:fixed;inset:0;background:rgba(15,14,23,.88);
    backdrop-filter:blur(12px);z-index:100;align-items:center;justify-content:center;}
  .overlay.show{display:flex;}
  .modal{background:var(--surface);border:1px solid var(--border);border-radius:28px;
    padding:36px 40px;text-align:center;max-width:380px;width:90%;
    animation:popIn .4s cubic-bezier(.34,1.56,.64,1);}
  @keyframes popIn{from{transform:scale(.75);opacity:0}to{transform:scale(1);opacity:1}}
  .modal-emoji{font-size:3rem;margin-bottom:10px;}
  .modal-title{font-size:1.7rem;font-weight:900;letter-spacing:-.04em;margin-bottom:4px;}
  .modal-sub{font-family:'Space Mono',monospace;font-size:.6rem;color:var(--text2);
    letter-spacing:.08em;text-transform:uppercase;margin-bottom:20px;}
  .modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;}
  .modal-item{background:var(--bg);border-radius:12px;padding:12px;}
  .m-lbl{font-family:'Space Mono',monospace;font-size:.5rem;color:var(--text2);
    text-transform:uppercase;letter-spacing:.1em;margin-bottom:3px;}
  .m-val{font-size:1.4rem;font-weight:900;letter-spacing:-.04em;}
  .btn-save{width:100%;font-family:'Outfit',sans-serif;font-size:1rem;font-weight:700;
    padding:14px;border:none;border-radius:12px;background:var(--good);color:#0f0e17;
    cursor:pointer;transition:all .2s;margin-bottom:8px;}
  .btn-save:hover{opacity:.88;transform:translateY(-1px);}
  .btn-save:disabled{opacity:.45;cursor:not-allowed;transform:none;}
  .btn-play{width:100%;font-family:'Outfit',sans-serif;font-size:1rem;font-weight:700;
    padding:14px;border:none;border-radius:12px;background:var(--accent);color:var(--bg);
    cursor:pointer;transition:all .2s;}
  .btn-play:hover{opacity:.88;transform:translateY(-1px);}
  .saved-msg{font-family:'Space Mono',monospace;font-size:.65rem;letter-spacing:.05em;
    padding:8px 12px;border-radius:10px;margin-bottom:10px;display:none;}
  .saved-msg.ok{background:rgba(92,219,149,.15);color:var(--good);border:1px solid var(--good);}
  .saved-msg.err{background:rgba(255,107,107,.15);color:var(--accent2);border:1px solid var(--accent2);}
</style>
</head>
<body>

<div class="topbar">
  <div>
    <a href="../jogos_reabilitacao.php" class="back-btn">← Jogos</a>
    <div class="brand" style="margin-top:4px;">Catch Game<span>Nível 2</span></div>
  </div>
  <div class="timer" id="timer">1:00</div>
</div>

<div class="ip-bar">
  <input type="text" id="ipInput" placeholder="IP do sensor" value="10.198.1.130"/>
  <button onclick="connectFSR()">Ligar</button>
</div>

<div class="status-bar">
  <div class="ws-status">
    <div class="ws-dot" id="wsDot"></div>
    <span id="wsLabel">Desligado</span>
  </div>
</div>

<div class="stats">
  <div class="stat"><div class="stat-lbl">Pontos</div><div class="stat-val" id="scoreVal" style="color:var(--accent)">0</div></div>
  <div class="stat"><div class="stat-lbl">Apanhadas</div><div class="stat-val" id="caughtVal" style="color:var(--good)">0</div></div>
  <div class="stat"><div class="stat-lbl">Perdidas</div><div class="stat-val" id="missedVal" style="color:var(--accent2)">0</div></div>
</div>

<div class="force-wrap">
  <div class="force-lbl"><span>Força</span><span id="forcePct">0%</span></div>
  <div class="force-track"><div class="force-fill" id="forceFill"></div></div>
</div>

<canvas id="canvas"></canvas>
<div class="hint" id="hint"></div>

<!-- Overlay (ecrã inicial + fim de jogo) -->
<div class="overlay" id="overlay">
  <div class="modal">
    <div class="modal-emoji" id="mEmoji">🧠</div>
    <div class="modal-title" id="mTitle">Catch Game</div>
    <div class="modal-sub" id="mSub">1m · Nível 2</div>
    <div class="modal-grid">
      <div class="modal-item"><div class="m-lbl">Pontos</div><div class="m-val" style="color:var(--accent)" id="mScore">—</div></div>
      <div class="modal-item"><div class="m-lbl">Apanhadas</div><div class="m-val" style="color:var(--good)" id="mCaught">—</div></div>
      <div class="modal-item"><div class="m-lbl">Perdidas</div><div class="m-val" style="color:var(--accent2)" id="mMissed">—</div></div>
      <div class="modal-item"><div class="m-lbl">Precisão</div><div class="m-val" style="color:var(--text2)" id="mAcc">—</div></div>
    </div>
    <!-- IP input — só no ecrã inicial -->
    <div id="ipBlock" style="margin-bottom:14px;">
      <div style="font-family:'Space Mono',monospace;font-size:.58rem;color:var(--text2);text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;">IP do sensor</div>
      <div style="display:flex;gap:8px;">
        <input id="ipModal" type="text" placeholder="ex: 192.168.1.130"
          style="flex:1;background:var(--bg);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'Space Mono',monospace;font-size:.8rem;padding:10px 12px;outline:none;"/>
        <button onclick="connectFromModal()"
          style="background:var(--accent);color:var(--bg);border:none;border-radius:10px;font-family:'Outfit',sans-serif;font-weight:700;font-size:.85rem;padding:10px 16px;cursor:pointer;">Ligar</button>
      </div>
    </div>
    <!-- Botão Guardar (só visível no fim do jogo) -->
    <div id="savedMsg" class="saved-msg"></div>
    <button class="btn-save" id="btnSave" style="display:none;" onclick="saveResult()">
      💾 Guardar Resultado
    </button>
    <button class="btn-play" id="btnPlay" onclick="startGame()">Começar</button>
  </div>
</div>

<script>
// ── Dados PHP injetados ─────────────────────────────────
const APP_URL    = <?= json_encode(APP_URL) ?>;
const JOGO_ID    = <?= $jogo_id ?>;
const TEM_TECNICO = <?= $tecnico_id ? 'true' : 'false' ?>;

// ── Canvas setup ────────────────────────────────────────
const canvas = document.getElementById('canvas');
const ctx    = canvas.getContext('2d');
function resize() {
  var maxW = Math.min(window.innerWidth - 32, 600);
  canvas.width  = maxW;
  canvas.height = Math.min(window.innerHeight - 280, 380);
}
resize(); window.addEventListener('resize', resize);

// ── Sinal FSR ───────────────────────────────────────────
var FILTER_SIZE=8, filterBuf=[], filterSum=0;
var baseline=0, maxVal=3000, DEADZONE=0.05, THRESHOLD=0.15;
var forceNorm=0;
function addSample(raw) {
  filterBuf.push(raw); filterSum+=raw;
  if(filterBuf.length>FILTER_SIZE) filterSum-=filterBuf.shift();
  var n=Math.max(0,Math.min(1,(filterSum/filterBuf.length-baseline)/Math.max(1,maxVal-baseline)));
  forceNorm=n<DEADZONE?0:n;
  var pct=Math.round(forceNorm*100);
  document.getElementById('forceFill').style.width=pct+'%';
  document.getElementById('forcePct').textContent=pct+'%';
}

// ── WebSocket ESP32 ─────────────────────────────────────
var ws=null, wsReconnectTimer=null, wsReconnectDelay=2000, wsIntentionalClose=false;
function connectFSR() {
  if(wsReconnectTimer){clearTimeout(wsReconnectTimer);wsReconnectTimer=null;}
  var ip=document.getElementById('ipInput').value.trim(); if(!ip)return;
  localStorage.setItem('esp32_ip',ip);
  var mi=document.getElementById('ipModal'); if(mi)mi.value=ip;
  if(ws&&(ws.readyState===WebSocket.OPEN||ws.readyState===WebSocket.CONNECTING)){wsIntentionalClose=true;try{ws.close();}catch(e){}}
  wsIntentionalClose=false; wsReconnectDelay=2000; _openWS(ip);
}
function _openWS(ip) {
  ws=new WebSocket('ws://'+ip+':81');
  ws.onopen=function(){wsReconnectDelay=2000;document.getElementById('wsDot').classList.add('connected');document.getElementById('wsLabel').textContent='Sensor ligado · '+ip;};
  ws.onmessage=function(e){var r=parseInt(e.data);if(!isNaN(r))addSample(r);};
  ws.onclose=function(){document.getElementById('wsDot').classList.remove('connected');if(wsIntentionalClose)return;var d=wsReconnectDelay;wsReconnectDelay=Math.min(wsReconnectDelay*1.5,15000);document.getElementById('wsLabel').textContent='Desligado — a reconectar em '+Math.round(d/1000)+'s…';wsReconnectTimer=setTimeout(function(){_openWS(ip);},d);};
  ws.onerror=function(){document.getElementById('wsLabel').textContent='Erro de ligação';};
}

// ── Jogo ─────────────────────────────────────────────────
const GAME_DURATION=60, BASKET_W=120, BASKET_H=40, MAX_SPEED=14;
var gameRunning=false, timeLeft=GAME_DURATION, timerInt=null;
var score=0, caught=0, missed=0, animId=null, basketX=0;
var balls=[], particles=[], flashFrames=0, flashColor='', spawnCounter=0;
var gameStartTime=null;

function startTimer(){
  timeLeft=GAME_DURATION; updateTimer();
  timerInt=setInterval(function(){timeLeft--;updateTimer();if(timeLeft<=0){clearInterval(timerInt);endGame();}},1000);
}
function updateTimer(){
  var m=Math.floor(timeLeft/60),s=timeLeft%60,el=document.getElementById('timer');
  el.textContent=m+':'+(s<10?'0':'')+s;
  el.className='timer'+(timeLeft<=10?' urgent':'');
}

var COLORS=['#f7c948','#ff6b6b','#5cdb95','#7eb8f7','#d4a5f5','#ff9f43'];
function spawnBall(){
  balls.push({x:20+Math.random()*(canvas.width-40),y:-20,vy:1.0+Math.random()*.8,
    r:20,color:COLORS[Math.floor(Math.random()*COLORS.length)],angle:0,spin:(Math.random()-.5)*.08});
}
function burst(x,y,color,n){
  for(var i=0;i<n;i++){var a=Math.random()*Math.PI*2,spd=1.5+Math.random()*3;
    particles.push({x,y,vx:Math.cos(a)*spd,vy:Math.sin(a)*spd-2,r:3+Math.random()*4,color,life:1,decay:.035+Math.random()*.03});}
}

// Audio
var audioCtx;
function getAudio(){if(!audioCtx)audioCtx=new(window.AudioContext||window.webkitAudioContext)();return audioCtx;}
function playTone(f,t,d,v){try{var a=getAudio(),o=a.createOscillator(),g=a.createGain();o.connect(g);g.connect(a.destination);o.type=t;o.frequency.value=f;g.gain.setValueAtTime(v||.18,a.currentTime);g.gain.exponentialRampToValueAtTime(.001,a.currentTime+d);o.start();o.stop(a.currentTime+d);}catch(e){}}
function playCatch(){playTone(660,'sine',.12,.22);setTimeout(function(){playTone(880,'sine',.1,.18);},80);}
function playMiss(){playTone(150,'sawtooth',.28,.2);}

function drawBasket(x,y){
  var w=BASKET_W,h=BASKET_H,left=x-w/2,right=x+w/2;
  ctx.shadowBlur=18;ctx.shadowColor='rgba(247,201,72,.35)';
  ctx.fillStyle='#c8952a';ctx.beginPath();ctx.moveTo(left,y);ctx.lineTo(right,y);ctx.lineTo(right-6,y+h);ctx.lineTo(left+6,y+h);ctx.closePath();ctx.fill();
  ctx.strokeStyle='#f7c948';ctx.lineWidth=5;ctx.lineCap='round';ctx.beginPath();ctx.moveTo(left-4,y);ctx.lineTo(right+4,y);ctx.stroke();
  ctx.shadowBlur=0;ctx.strokeStyle='rgba(0,0,0,.18)';ctx.lineWidth=1.5;
  for(var i=1;i<3;i++){var yy=y+(h/3)*i,ins=6*(i/3);ctx.beginPath();ctx.moveTo(left+ins,yy);ctx.lineTo(right-ins,yy);ctx.stroke();}
  for(var j=0;j<5;j++){var xx=left+6+(w-12)*(j/4);ctx.beginPath();ctx.moveTo(xx,y);ctx.lineTo(xx-2,y+h);ctx.stroke();}
  ctx.shadowBlur=0;
}
function drawBall(b){
  ctx.save();ctx.translate(b.x,b.y);ctx.rotate(b.angle);
  ctx.shadowBlur=14;ctx.shadowColor=b.color+'88';
  ctx.beginPath();ctx.arc(0,0,b.r,0,Math.PI*2);ctx.fillStyle=b.color;ctx.fill();
  ctx.beginPath();ctx.arc(-b.r*.28,-b.r*.28,b.r*.32,0,Math.PI*2);ctx.fillStyle='rgba(255,255,255,.35)';ctx.fill();
  ctx.shadowBlur=0;ctx.restore();
}
function drawParticles(){particles.forEach(function(p){ctx.globalAlpha=p.life;ctx.beginPath();ctx.arc(p.x,p.y,p.r*p.life,0,Math.PI*2);ctx.fillStyle=p.color;ctx.fill();});ctx.globalAlpha=1;}

function gameLoop(){
  if(!gameRunning)return;
  var W=canvas.width,H=canvas.height;
  ctx.clearRect(0,0,W,H);
  if(flashFrames>0){ctx.fillStyle=flashColor;ctx.fillRect(0,0,W,H);flashFrames--;}
  ctx.strokeStyle='rgba(255,255,255,.025)';ctx.lineWidth=1;
  for(var gx=0;gx<W;gx+=40){ctx.beginPath();ctx.moveTo(gx,0);ctx.lineTo(gx,H);ctx.stroke();}
  for(var gy=0;gy<H;gy+=40){ctx.beginPath();ctx.moveTo(0,gy);ctx.lineTo(W,gy);ctx.stroke();}

  // Controlo por força FSR (ou fallback rato/teclado)
  var velocity=(forceNorm-0.5)*2*MAX_SPEED;
  basketX+=velocity;
  basketX=Math.max(BASKET_W/2,Math.min(W-BASKET_W/2,basketX));
  var basketY=H-BASKET_H-16;

  spawnCounter++; if(spawnCounter>=140){spawnBall();spawnCounter=0;}

  for(var i=balls.length-1;i>=0;i--){
    var b=balls[i];b.y+=b.vy;b.angle+=b.spin;
    var inX=Math.abs(b.x-basketX)<BASKET_W/2+b.r*.4;
    var inY=b.y+b.r>=basketY&&b.y-b.r<basketY+BASKET_H;
    if(inX&&inY&&b.y>basketY-b.r*2){balls.splice(i,1);caught++;score+=10;document.getElementById('scoreVal').textContent=score;document.getElementById('caughtVal').textContent=caught;burst(b.x,basketY,b.color,12);flashFrames=3;flashColor='rgba(92,219,149,.08)';playCatch();continue;}
    if(b.y-b.r>H){balls.splice(i,1);missed++;document.getElementById('missedVal').textContent=missed;burst(b.x,H-10,'#ff6b6b',7);flashFrames=5;flashColor='rgba(255,107,107,.12)';playMiss();continue;}
    drawBall(b);
  }
  for(var j=particles.length-1;j>=0;j--){var p=particles[j];p.x+=p.vx;p.y+=p.vy;p.vy+=.18;p.life-=p.decay;if(p.life<=0){particles.splice(j,1);continue;}}
  drawParticles();
  drawBasket(basketX,basketY);

  ctx.fillStyle=forceNorm>THRESHOLD?'#f7c948':'rgba(255,255,255,.15)';
  ctx.font='bold 12px Space Mono';ctx.textAlign='center';
  var centered=(forceNorm-.5)/.5;
  var dir=forceNorm>THRESHOLD?(centered>.1?'→ DIREITA':centered<-.1?'← ESQUERDA':'■ PARADO'):'RELAXA';
  ctx.fillText(forceNorm>THRESHOLD?('FORÇA: '+Math.round(forceNorm*100)+'% '+dir):'RELAXA',W/2,H-4);

  animId=requestAnimationFrame(gameLoop);
}

function startGame(){
  cancelAnimationFrame(animId);clearInterval(timerInt);
  gameRunning=true;score=0;caught=0;missed=0;spawnCounter=0;balls=[];particles=[];flashFrames=0;
  basketX=canvas.width/2;gameStartTime=Date.now();
  document.getElementById('scoreVal').textContent='0';
  document.getElementById('caughtVal').textContent='0';
  document.getElementById('missedVal').textContent='0';
  document.getElementById('savedMsg').style.display='none';
  document.getElementById('btnSave').style.display='none';
  document.getElementById('btnSave').disabled=false;
  document.getElementById('hint').textContent='';
  document.getElementById('overlay').classList.remove('show');
  startTimer();
  animId=requestAnimationFrame(gameLoop);
}

function endGame(){
  gameRunning=false;cancelAnimationFrame(animId);
  var acc=Math.round(caught/Math.max(1,caught+missed)*100);
  var emoji=caught>=20?'🏆':caught>=10?'💪':caught>=5?'👍':'😅';
  document.getElementById('mEmoji').textContent=emoji;
  document.getElementById('mTitle').textContent='Sessão terminada!';
  document.getElementById('mSub').textContent='1m · Nível 2';
  document.getElementById('mScore').textContent=score;
  document.getElementById('mCaught').textContent=caught;
  document.getElementById('mMissed').textContent=missed;
  document.getElementById('mAcc').textContent=acc+'%';
  document.getElementById('btnPlay').textContent='Jogar novamente';
  document.getElementById('ipBlock').style.display='none';
  document.getElementById('btnSave').style.display= TEM_TECNICO ? 'block' : 'none';
  if(!TEM_TECNICO){
    var sm=document.getElementById('savedMsg');
    sm.className='saved-msg err';sm.textContent='Sem técnico associado — contacta o administrador.';sm.style.display='block';
  }
  window._gameResult={score,caught,missed,acc,duracao_min:Math.max(1,Math.ceil((Date.now()-gameStartTime)/60000))};
  setTimeout(function(){document.getElementById('overlay').classList.add('show');},400);
}

// ── Guardar resultado ───────────────────────────────────
function saveResult(){
  var btn=document.getElementById('btnSave');
  btn.disabled=true; btn.textContent='A guardar…';
  var r=window._gameResult;
  fetch(APP_URL+'/api/utente/guardar_resultado_jogo.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({jogo_id:JOGO_ID,score:r.score,percentagem:r.acc,
      passou:r.acc>=70,n_tentativas:r.caught+r.missed,duracao_min:r.duracao_min})
  })
  .then(function(res){return res.json();})
  .then(function(data){
    var sm=document.getElementById('savedMsg');
    if(data.ok){
      var tend={'melhoria':'📈 Melhoria','estavel':'➡️ Estável','regressao':'📉 Regressão'};
      sm.className='saved-msg ok';
      sm.textContent='✅ Guardado!'+(data.tendencia?' · '+tend[data.tendencia]:'');
      sm.style.display='block'; btn.style.display='none';
    } else {
      sm.className='saved-msg err';
      sm.textContent='❌ '+(data.erro||'Erro ao guardar.');
      sm.style.display='block'; btn.disabled=false; btn.textContent='💾 Guardar Resultado';
    }
  })
  .catch(function(){
    var sm=document.getElementById('savedMsg');
    sm.className='saved-msg err'; sm.textContent='❌ Erro de ligação.';
    sm.style.display='block'; btn.disabled=false; btn.textContent='💾 Guardar Resultado';
  });
}

// ── Input fallback (rato/teclado sem ESP32) ─────────────
canvas.addEventListener('mousedown',  function(e){if(!ws||ws.readyState!==1){var r=canvas.getBoundingClientRect();forceNorm=(e.clientX-r.left)/r.width;}});
canvas.addEventListener('mousemove',  function(e){if((!ws||ws.readyState!==1)&&e.buttons){var r=canvas.getBoundingClientRect();forceNorm=(e.clientX-r.left)/r.width;}});
canvas.addEventListener('mouseup',    function(){if(!ws||ws.readyState!==1)forceNorm=0;});
canvas.addEventListener('mouseleave', function(){if(!ws||ws.readyState!==1)forceNorm=0;});
canvas.addEventListener('touchstart', function(e){e.preventDefault();if(!ws||ws.readyState!==1){var r=canvas.getBoundingClientRect();forceNorm=(e.touches[0].clientX-r.left)/r.width;}},{passive:false});
canvas.addEventListener('touchmove',  function(e){e.preventDefault();if(!ws||ws.readyState!==1){var r=canvas.getBoundingClientRect();forceNorm=(e.touches[0].clientX-r.left)/r.width;}},{passive:false});
canvas.addEventListener('touchend',   function(e){e.preventDefault();if(!ws||ws.readyState!==1)forceNorm=0;},{passive:false});
document.addEventListener('keydown',  function(e){if(!ws||ws.readyState!==1){if(e.key==='ArrowRight'||e.key==='l'||e.key==='L')forceNorm=.85;if(e.key==='ArrowLeft'||e.key==='j'||e.key==='J')forceNorm=.15;}});
document.addEventListener('keyup',    function(e){if(e.key==='ArrowRight'||e.key==='l'||e.key==='L'||e.key==='ArrowLeft'||e.key==='j'||e.key==='J')forceNorm=0;});

// ── IP sync ──────────────────────────────────────────────
(function(){
  var stored=localStorage.getItem('esp32_ip')||'10.198.1.130';
  document.getElementById('ipInput').value=stored;
  document.getElementById('ipModal').value=stored;
})();
function connectFromModal(){
  var ip=document.getElementById('ipModal').value.trim();if(!ip)return;
  document.getElementById('ipInput').value=ip;localStorage.setItem('esp32_ip',ip);connectFSR();
}

// ── Ecrã inicial ─────────────────────────────────────────
document.getElementById('overlay').classList.add('show');
</script>
</body></html>
