<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// verificar admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$pdo = getPDO();
// contadores simples
$gamesCount = $pdo->query('SELECT COUNT(*) FROM games')->fetchColumn();
$usersCount = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$todayCount = $pdo->query("SELECT COUNT(*) FROM game_results WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$activeCount = $pdo->query('SELECT COUNT(*) FROM games WHERE active = 1')->fetchColumn();
$inactiveCount = $pdo->query('SELECT COUNT(*) FROM games WHERE active = 0')->fetchColumn();

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Painel Administrativo</h2>
    
    <div class="admin-cards">
        <div class="admin-card">
            <h4>Jogos Totais</h4>
            <div class="stat"><?php e($gamesCount); ?></div>
        </div>
        <div class="admin-card">
            <h4>Utilizadores Totais</h4>
            <div class="stat"><?php e($usersCount); ?></div>
        </div>
        <div class="admin-card">
            <h4>Resultados Hoje</h4>
            <div class="stat"><?php e($todayCount); ?></div>
        </div>
        <div class="admin-card">
            <h4>Jogos Ativos</h4>
            <div class="stat"><?php e($activeCount); ?> / <?php e($gamesCount); ?></div>
        </div>
        <div class="admin-card">
            <h4>Jogos Inativos</h4>
            <div class="stat"><?php e($inactiveCount); ?></div>
        </div>
    </div>

    <div class="admin-nav-grid">
        <a href="games.php" class="login-btn">Gerir Jogos</a>
        <a href="rules.php" class="login-btn">Gerir Regras</a>
        <a href="users.php" class="login-btn">Gerir Utilizadores</a>
        <a href="debug-raw.php" class="login-btn">Debug Raw</a>
        <a href="settings.php" class="login-btn">Configurações</a>
    </div>
</div>

<?php
include __DIR__ . '/../footer.php';
?>