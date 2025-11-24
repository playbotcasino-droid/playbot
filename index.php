<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Obter jogos ativos da base de dados com o último resultado
$pdo = getPDO();

$stmt = $pdo->prepare("SELECT g.*, gr.raw_message, gr.created_at as result_time
    FROM games g
    LEFT JOIN (
        SELECT game_id, raw_message, created_at
        FROM game_results
        WHERE id IN (
            SELECT MAX(id) FROM game_results GROUP BY game_id
        )
    ) gr ON g.id = gr.game_id
    WHERE g.active = 1
    ORDER BY g.display_name");
$stmt->execute();
$games = $stmt->fetchAll();

// Obter favoritos do utilizador caso esteja logado
$favorites = [];
if (is_logged_in()) {
    $userId = current_user()['id'];
    $favStmt = $pdo->prepare("SELECT game_id FROM favorites WHERE user_id = ?");
    $favStmt->execute([$userId]);
    $favorites = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}

include __DIR__ . '/header.php';
?>

<div class="hero">
    <h2>Inteligência artificial aplicada a estratégias de jogos em tempo real.</h2>
    <div class="search-bar">
        <input type="text" id="search-input" placeholder="Procurar jogo..." />
    </div>
</div>

<div class="games-grid">
    <?php foreach ($games as $game): ?>
        <?php
        $name = $game['display_name'] ?: $game['table_name'];
        $latestTime = $game['result_time'] ? new DateTime($game['result_time']) : null;
        $isLive = false;
        $statusLabel = '';
        if ($latestTime) {
            $now = new DateTime();
            $interval = $now->getTimestamp() - $latestTime->getTimestamp();
            // online se último resultado nas últimas 60*60 segundos (1 hora)
            if ($interval < 3600) {
                $isLive = true;
                $statusLabel = 'Online';
            } else {
                $statusLabel = 'Offline';
            }
        }
        // Parâmetro do resultado parseado (placeholder): mostrar raw_message truncado
        $raw = $game['raw_message'];
        $parsed = $raw ? substr(trim($raw), 0, 25) . (strlen($raw) > 25 ? '...' : '') : 'Sem resultados';
        $timeStr = $latestTime ? $latestTime->format('d/m/Y H:i') : '';
        $fav = in_array($game['id'], $favorites);
        ?>
        <div class="game-card" data-name="<?php e($name); ?>">
            <button class="favorite-btn" onclick="location.href='favorite.php?id=<?php echo $game['id']; ?>'" title="<?php echo $fav ? 'Remover Favorito' : 'Adicionar Favorito'; ?>">
                <?php echo $fav ? '★' : '☆'; ?>
            </button>
            <h3><?php e($name); ?></h3>
            <span class="table-name"><?php e($game['table_name']); ?></span>
            <?php if ($game['raw_message']): ?>
                <span class="badge live">Tempo Real</span>
            <?php else: ?>
                <span class="badge offline">Sem Dados</span>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <div class="last-result">
                    <strong>Último Resultado:</strong> <?php e($parsed); ?><br>
                    <small><?php e($timeStr); ?> | <?php e($statusLabel); ?></small>
                </div>
            <?php else: ?>
                <div class="restricted">
                    <span class="lock-icon">🔒</span>
                    <span>Área restrita</span>
                </div>
            <?php endif; ?>
            <div class="actions">
                <?php if (is_logged_in()): ?>
                    <a href="/playbot_php/game.php?id=<?php echo $game['id']; ?>">Ver Detalhes →</a>
                <?php else: ?>
                    <a href="/playbot_php/login.php">Acessar Dados →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
include __DIR__ . '/footer.php';
?>