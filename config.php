<?php
// Configuração da ligação à base de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'api-evolution');
define('DB_USER', 'root');
define('DB_PASS', 'f96fcdc6a326b7c6');

// Iniciar sessão PHP para gestão de autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}