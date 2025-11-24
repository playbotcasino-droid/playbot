<?php
/**
 * Establish a PDO connection to the MySQL database using the
 * constants defined in config.php. If the connection fails the
 * script will halt execution with an error message. This file
 * centralises the DB connection so other modules can include it.
 */
require_once __DIR__ . '/config.php';

function getPDO(): PDO
{
    static $pdo;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Erro ao conectar à base de dados: " . $e->getMessage());
        }
    }
    return $pdo;
}
