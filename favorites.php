<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$pdo = getPDO();
$userId = current_user()['id'];
$favStmt = $pdo->prepare('SELECT g.* FROM games g JOIN favorites f ON g.id = f.game_id WHERE f.user_id = ?');
$favStmt->execute([$userId]);
$favGames = $favStmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="container">
    <h2>Meus Favoritos</h2>
    <?php if ($favGames): ?>
        <div class="games-grid">
            <?php foreach ($favGames as $game): ?>
                <?php $name = $game['display_name'] ?: $game['table_name']; ?>
                <div class="game-card" data-name="<?php e($name); ?>">
                    <button class="favorite-btn" onclick="location.href='favorite.php?id=<?php echo $game['id']; ?>'" title="Remover Favorito">★</button>
                    <h3><?php e($name); ?></h3>
                    <span class="table-name"><?php e($game['table_name']); ?></span>
                    <div class="restricted">
                        <span class="lock-icon">🔒</span>
                        <span>Área restrita</span>
                    </div>
                    <div class="actions">
                        <a href="/playbot_php/game.php?id=<?php echo $game['id']; ?>">Ver Detalhes →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Não tem jogos favoritos.</p>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/footer.php';
?>