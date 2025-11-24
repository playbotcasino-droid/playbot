<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Se já estiver logado, redireccionar para home
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Preencha email e password.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Remover password do array
            unset($user['password']);
            $_SESSION['user'] = $user;
            redirect('index.php');
        } else {
            $error = 'Credenciais inválidas.';
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container" style="max-width: 500px;">
    <h2>Iniciar Sessão</h2>
    <?php if ($error): ?>
        <div style="color: #e74c3c; margin-bottom: 1rem;"> <?php e($error); ?> </div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Entrar</button>
    </form>
    <p style="margin-top:1rem;">Não tem conta? <a href="register.php">Registe-se aqui</a>.</p>
</div>

<?php
include __DIR__ . '/footer.php';
?>