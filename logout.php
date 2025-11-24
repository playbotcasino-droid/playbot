<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Terminar sessão
session_unset();
session_destroy();
// Limpar cookie de sessão se existir
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
redirect('login.php');
