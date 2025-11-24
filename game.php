<?php
// PLAYBOT V8 - game.php (dashboard limpo + quadro de bolinhas)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// proteger rota: apenas logado
if (!is_logged_in()) {
    redirect('login.php');
}

$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($gameId <= 0) {
    redirect('index.php');
}

$pdo = getPDO();

// carregar dados do jogo + regra (inclui código JS)
$stmt = $pdo->prepare('
    SELECT g.*, r.name AS rule_name, r.code AS rule_code
    FROM games g
    LEFT JOIN game_rules r ON g.rule_id = r.id
    WHERE g.id = ?
');
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    redirect('index.php');
}

$tableName = $game['table_name'];

// segurança para nome de tabela
if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
    die('Nome de tabela inválido configurado para este jogo.');
}

// função para buscar últimos 500 registos da tabela do jogo
function load_game_rows(PDO $pdo, string $tableName): array {
    $sql = "SELECT id, raw_message, created_at, dia, hora FROM `{$tableName}` ORDER BY id DESC LIMIT 1000";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    return $rows ?: [];
}

// AJAX: devolve JSON com até 1000 resultados já formatados
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $rows  = load_game_rows($pdo, $tableName);
    $lastN = array_slice($rows, 0, 1000); // até 1000 mais recentes

    $payload = [
        'total'   => count($rows),
        'results' => array_map(function ($r) {
            // formatação de data/hora (created_at ou dia/hora)
            if (!empty($r['created_at'])) {
                $dt = (new DateTime($r['created_at']))->format('d/m/Y H:i');
            } elseif (!empty($r['dia']) && !empty($r['hora'])) {
                $dt = $r['dia'] . ' ' . $r['hora'];
            } else {
                $dt = '';
            }
            return [
                'id'         => $r['id'],
                'raw'        => $r['raw_message'],
                'created_at' => $dt,
            ];
        }, $lastN),
    ];
    echo json_encode($payload);
    exit;
}

// primeira pintura (PHP) só usa contagem total
$rows           = load_game_rows($pdo, $tableName);
$totalDashboard = count($rows);

include __DIR__ . '/header.php';
?>
<div class="container game-page">
    <h2><?php e($game['display_name'] ?: $game['table_name']); ?></h2>
    <p style="margin-bottom:0.5rem;">
        Regra de parser: <strong><?php e($game['rule_name'] ?: 'Nenhuma'); ?></strong>
    </p>

    <div class="grid-cards">
        <div class="card metric-card">
            <h3>Últimas 1000 jogadas</h3>
            <p class="metric-value" id="metric-total"><?php echo $totalDashboard; ?></p>
            <p class="metric-sub">Registos carregados desta mesa</p>
        </div>
        <div class="card metric-card">
            <h3>Distribuição (parser)</h3>
            <p class="metric-sub">Calculada no browser a partir da regra activa.</p>
            <div id="metric-distribution" class="distribution-tags"></div>
        </div>
    </div>

    <div class="bead-header">
        <h3>Quadro Geral <span id="bead-count">(0)</span></h3>
        <button type="button" id="btn-more" class="btn-more">Carregar +100</button>
    </div>
    <div id="bead-board" class="bead-board"></div>

    <p class="hint-text">
        Este dashboard é actualizado automaticamente a cada 5 segundos.
        Cada bolinha representa uma jogada (máximo 500).
    </p>
</div>

<script>
// ---- Regra JS vinda da BD ----
<?php if (!empty($game['rule_code'])): ?>
// O código abaixo foi escrito no painel de Regras (game_rules.code)
<?php echo $game['rule_code']; ?>

window.playbotHasRule = true;
<?php else: ?>
window.playbotHasRule = false;
function parseLastPlay(rawMessage) { return null; }
<?php endif; ?>

// Helpers JS
function updateDistribution(rows) {
    var counts = {};
    if (!window.playbotHasRule || typeof parseLastPlay !== 'function') {
        var cont = document.getElementById('metric-distribution');
        if (cont) cont.innerHTML = '<span class="tag">Sem regra activa</span>';
        return;
    }
    rows.forEach(function(r){
        var p = parseLastPlay(r.raw);
        if (p === null || typeof p === 'undefined') return;
        var key = String(p);
        counts[key] = (counts[key] || 0) + 1;
    });
    var container = document.getElementById('metric-distribution');
    if (!container) return;
    container.innerHTML = '';
    var total = 0;
    for (var k in counts) total += counts[k];
    Object.keys(counts).sort(function(a,b){ return counts[b]-counts[a]; }).forEach(function(k){
        var span = document.createElement('span');
        var pct = total ? ((counts[k] / total) * 100).toFixed(1) : 0;
        span.className = 'tag';
        span.textContent = k + ' • ' + counts[k] + ' (' + pct + '%)';
        container.appendChild(span);
    });
}

function classifyBeadColor(code) {
    if (code === null || typeof code === 'undefined') return 'blue';
    var v = String(code).toLowerCase();
    if (v === 'r' || v === 'red' || v === 'b' || v === 'banker') return 'red';
    if (v === 'l' || v === 'blue' || v === 'p' || v === 'player') return 'blue';
    if (v === 't' || v === 'tie' || v === 'g' || v === 'green' || v === '0' || v === 'zero') return 'green';
    return 'blue';
}

// Estado global
var playbotResults = [];
var beadShown      = 0;
var BEAD_STEP      = 100;
var BEAD_MAX       = 500;

function renderBeadBoard() {
    var container = document.getElementById('bead-board');
    if (!container) return;
    container.innerHTML = '';

    if (!playbotResults || !playbotResults.length || beadShown <= 0) return;

    var toShow = playbotResults.slice(0, beadShown);

    toShow.forEach(function(r) {
        var parsed = null;
        if (window.playbotHasRule && typeof parseLastPlay === 'function') {
            parsed = parseLastPlay(r.raw);
        }

        var wrap = document.createElement('div');
        wrap.className = 'bead-item';

        var time = document.createElement('div');
        time.className = 'bead-time';
        if (r.created_at) {
            var parts = r.created_at.split(' ');
            time.textContent = parts.length > 1 ? parts[1] : r.created_at;
        } else {
            time.textContent = '';
        }
        wrap.appendChild(time);

        var circle = document.createElement('div');
        var color = classifyBeadColor(parsed);
        circle.className = 'bead-circle bead-' + color;
        circle.textContent = parsed === null || typeof parsed === 'undefined' ? '' : String(parsed);
        wrap.appendChild(circle);

        container.appendChild(wrap);
    });
}

function updateMoreButton() {
    var btn = document.getElementById('btn-more');
    if (!btn) return;
    if (!playbotResults.length || beadShown >= playbotResults.length || beadShown >= BEAD_MAX) {
        btn.disabled = true;
        btn.textContent = 'Máximo alcançado';
    } else {
        btn.disabled = false;
        btn.textContent = 'Carregar +100';
    }
}

function refreshDashboard() {
    fetch('game.php?id=<?php echo $gameId; ?>&ajax=1')
        .then(function(resp){ return resp.json(); })
        .then(function(data){
            if (!data || !Array.isArray(data.results)) return;

            // actualizar métrica total
            var metricTotal = document.getElementById('metric-total');
            if (metricTotal) metricTotal.textContent = data.total;

            playbotResults = data.results || [];

            var beadCountEl = document.getElementById('bead-count');
            if (beadCountEl) {
                beadCountEl.textContent = '(' + playbotResults.length + ')';
            }

            if (playbotResults.length === 0) {
                beadShown = 0;
                renderBeadBoard();
                updateDistribution([]);
                updateMoreButton();
                return;
            }

            beadShown = Math.min(BEAD_STEP, playbotResults.length, BEAD_MAX);
            renderBeadBoard();
            updateDistribution(playbotResults.slice(0, beadShown));
            updateMoreButton();
        })
        .catch(function(e){
            console.error('Falha ao actualizar dashboard', e);
        });
}

document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('btn-more');
    if (btn) {
        btn.addEventListener('click', function(){
            if (!playbotResults.length) return;
            beadShown = Math.min(beadShown + BEAD_STEP, playbotResults.length, BEAD_MAX);
            renderBeadBoard();
            updateDistribution(playbotResults.slice(0, beadShown));
            updateMoreButton();
        });
    }

    // primeira carga + auto-refresh 5s
    refreshDashboard();
    setInterval(refreshDashboard, 5000);
});
</script>
<?php
include __DIR__ . '/footer.php';
?>
