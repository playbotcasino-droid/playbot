<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// Validar id
$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($gameId <= 0) {
    redirect('index.php');
}

$pdo = getPDO();
$userId = current_user()['id'];

// Verificar se já é favorito
$stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND game_id = ?");
$stmt->execute([$userId, $gameId]);
$exists = $stmt->fetchColumn() > 0;

if ($exists) {
    // Remover favorito
    $del = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND game_id = ?");
    $del->execute([$userId, $gameId]);
} else {
    // Adicionar favorito
    $ins = $pdo->prepare("INSERT INTO favorites (user_id, game_id) VALUES (?, ?)");
    $ins->execute([$userId, $gameId]);
}

// Redireccionar de volta
header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
exit;
