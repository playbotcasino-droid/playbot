<?php
/**
 * Funções auxiliares usadas em todo o site.
 */

/**
 * Verifica se o utilizador está autenticado.
 * @return bool
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Devolve o utilizador actual ou null se não estiver logado.
 * @return array|null
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Verifica se o utilizador actual tem role de administrador.
 * @return bool
 */
function is_admin(): bool
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Redirecciona para uma página e termina a execução.
 * @param string $url
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Realiza o output sanitizado para evitar XSS.
 * @param string|null $string
 */
function e(?string $string)
{
    echo htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
