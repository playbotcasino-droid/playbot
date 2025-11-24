<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLAY BOT</title>
    <link rel="stylesheet" href="/playbot_php/assets/css/style.css">
    <script src="/playbot_php/assets/js/theme.js" defer></script>
    <script src="/playbot_php/assets/js/search.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="/playbot_php/index.php" class="logo">
            <span class="logo-icon">🎮</span>
            <span class="logo-text"><strong>PLAY</strong>BOT</span>
        </a>
        <nav class="main-nav">
            <?php if (is_logged_in()): ?>
                <a href="/playbot_php/index.php">Início</a>
                <a href="/playbot_php/user.php">Dashboard</a>
                <a href="/playbot_php/favorites.php">Favoritos</a>
                <?php if (is_admin()): ?>
                    <a href="/playbot_php/admin/index.php">Admin</a>
                <?php endif; ?>
                <button class="theme-toggle" id="theme-toggle" title="Alternar Modo Claro/Escuro">🌓</button>
                <a href="/playbot_php/logout.php" class="logout-btn">Sair</a>
            <?php else: ?>
                <a href="/playbot_php/index.php">Início</a>
                <button class="theme-toggle" id="theme-toggle" title="Alternar Modo Claro/Escuro">🌓</button>
                <a href="/playbot_php/login.php" class="login-btn">Entrar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-content">