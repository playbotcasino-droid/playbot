<?php
// PLAYBOT – game.php (V16 TURBO SMART • Refresh 1 segundo)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) redirect('login.php');

$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($gameId <= 0) redirect('index.php');

$pdo = getPDO();

$stmt = $pdo->prepare('
    SELECT g.*, r.name AS rule_name, r.code AS rule_code
    FROM games g
    LEFT JOIN game_rules r ON g.rule_id = r.id
    WHERE g.id = ?
');
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) redirect('index.php');

$tableName = $game['table_name'];

if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
    die('Nome de tabela inválido configurado para este jogo.');
}

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

    try {
        $countSql = "SELECT COUNT(*) FROM `{$tableName}`";
        $total = (int)$pdo->query($countSql)->fetchColumn();
    } catch (Exception $e) { $total = 0; }

    if ($sinceId > 0) {
        $sql = "SELECT id, raw_message, created_at, dia, hora
                FROM `{$tableName}`
                WHERE id > :since
                ORDER BY id DESC
                LIMIT 1000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':since' => $sinceId]);
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $sql = "SELECT id, raw_message, created_at, dia, hora
                FROM `{$tableName}`
                ORDER BY id DESC
                LIMIT 500";
        $rows = $pdo->query($sql)->fetchAll() ?: [];
    }

    echo json_encode([
        'total' => $total,
        'results' => array_map(function ($r) {
            if (!empty($r['created_at'])) {
                $dt = (new DateTime($r['created_at']))->format('d/m/Y H:i');
            } elseif (!empty($r['dia']) && !empty($r['hora'])) {
                $dt = $r['dia'] . ' ' . $r['hora'];
            } else $dt = '';

            return [
                'id'         => (int)$r['id'],
                'raw'        => $r['raw_message'],
                'created_at' => $dt,
            ];
        }, $rows),
    ]);
    exit;
}

include __DIR__ . '/header.php';
?>
<div class="container game-page">
    <h2><?php e($game['display_name'] ?: $game['table_name']); ?></h2>

    <div class="grid-cards">
        <div class="card metric-card">
            <h3>Distribuição</h3>
            <div id="metric-distribution" class="distribution-tags"></div>
        </div>
        <div class="card metric-card">
            <h3>Maior Sequência</h3>
            <p>🔴 R: <span id="streak-r">0</span></p>
            <p>🔵 L: <span id="streak-l">0</span></p>
            <p>🟢 Tie: <span id="streak-t">0</span></p>
        </div>
    </div>

    <div class="bead-header">
        <h3>Quadro Geral <span id="bead-count">(0)</span></h3>
    </div>
    <div id="bead-board" class="bead-board"></div>

    <p class="hint-text">
        Mostrando até 500 jogadas. Atualização a cada <strong>1 segundo</strong>, só novas jogadas.
    </p>
</div>

<script>
<?php if (!empty($game['rule_code'])): ?>
<?php echo $game['rule_code']; ?>
window.playbotHasRule = true;
<?php else: ?>
window.playbotHasRule = false;
function parseLastPlay(){ return null; }
<?php endif; ?>

function classifyBeadColor(code){
    if (!code) return 'blue';
    let v = String(code).toLowerCase();
    if(['r','red','banker','b'].includes(v)) return 'red';
    if(['l','blue','player','p'].includes(v)) return 'blue';
    if(['t','tie','green','g','zero','0'].includes(v)) return 'green';
    return 'blue';
}

function updateDistribution(rows){
    let counts = {};
    let cont = document.getElementById('metric-distribution');
    if(!window.playbotHasRule){
        cont.innerHTML = '<span class="tag">Sem regra</span>';
        return;
    }
    rows.forEach(r=>{
        let p = parseLastPlay(r.raw);
        if(!p) return;
        p = String(p);
        counts[p] = (counts[p]||0)+1;
    });
    cont.innerHTML = '';
    let total = Object.values(counts).reduce((a,b)=>a+b,0);
    Object.keys(counts).sort((a,b)=>counts[b]-counts[a]).forEach(k=>{
        let pct = ((counts[k]/total)*100).toFixed(1);
        let span = document.createElement('span');
        span.className='tag';
        span.textContent = `${k} • ${counts[k]} (${pct}%)`;
        cont.appendChild(span);
    });
}

function updateStreak(rows){
    let maxR=0,maxL=0,maxT=0;
    let curR=0,curL=0,curT=0;

    rows.forEach(r=>{
        let p=parseLastPlay(r.raw);
        if(!p) return;
        p=String(p).toLowerCase();

        if(['r','red','banker','b'].includes(p)){curR++;curL=0;curT=0;}
        else if(['l','blue','player','p'].includes(p)){curL++;curR=0;curT=0;}
        else if(['t','tie','green','g','zero','0'].includes(p)){curT++;curR=0;curL=0;}
        else{curR=0;curL=0;curT=0;}

        if(curR>maxR)maxR=curR;
        if(curL>maxL)maxL=curL;
        if(curT>maxT)maxT=curT;
    });

    document.getElementById('streak-r').textContent=maxR;
    document.getElementById('streak-l').textContent=maxL;
    document.getElementById('streak-t').textContent=maxT;
}

// estado
var playbotResults=[];
var playbotLastId=0;
var BEAD_MAX=500;

// buffer
var newBuffer=[];
var processing=false;

function processBuffer(){
    if(processing) return;
    processing=true;

    function next(){
        if(newBuffer.length===0){
            processing=false;
            return;
        }
        let item=newBuffer.shift();
        playbotResults.unshift(item);
        if(playbotResults.length>BEAD_MAX){
            playbotResults=playbotResults.slice(0,BEAD_MAX);
        }
        renderBeadBoard();
        updateDistribution(playbotResults);
        updateStreak(playbotResults);
        setTimeout(next,85);
    }
    next();
}

function renderBeadBoard(){
    let c=document.getElementById('bead-board');
    c.innerHTML='';
    playbotResults.forEach(r=>{
        let p=parseLastPlay(r.raw);
        let wrap=document.createElement('div');
        wrap.className='bead-item';
        let time=document.createElement('div');
        time.className='bead-time';
        if(r.created_at){
            let parts=r.created_at.split(' ');
            time.textContent=parts[1]||r.created_at;
        }
        wrap.appendChild(time);
        let circle=document.createElement('div');
        circle.className='bead-circle bead-'+classifyBeadColor(p);
        circle.textContent=p?p:'';
        wrap.appendChild(circle);
        c.appendChild(wrap);
    });
}

function refreshDashboard(){
    let url='game.php?id=<?php echo $gameId;?>&ajax=1';
    if(playbotLastId) url+='&since_id='+playbotLastId;

    fetch(url)
    .then(r=>r.json())
    .then(data=>{
        let fresh=data.results||[];
        if(fresh.length>0){
            let maxId=fresh[0].id;
            if(maxId>playbotLastId) playbotLastId=maxId;
            fresh.forEach(i=>newBuffer.push(i));
            processBuffer();
        }
        document.getElementById('bead-count').textContent=`(${playbotResults.length})`;
    })
    .catch(e=>console.log(e));
}

refreshDashboard();
setInterval(refreshDashboard,1000);
</script>
<?php include __DIR__ . '/footer.php'; ?>
