<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$name) {
        $errors[] = 'Nome é obrigatório.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password deve ter no mínimo 6 caracteres.';
    }
    if ($password !== $confirm) {
        $errors[] = 'As passwords não coincidem.';
    }

    if (empty($errors)) {
        $pdo = getPDO();
        // verificar se email já existe
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Já existe utilizador com esse email.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $ins->execute([$name, $email, $hash, 'user']);
            // logar automaticamente
            $userId = $pdo->lastInsertId();
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'user',
            ];
            redirect('index.php');
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container" style="max-width: 500px;">
    <h2>Registar</h2>
    <?php if ($errors): ?>
        <div style="color:#e74c3c; margin-bottom: 1rem;">
            <?php foreach ($errors as $err): ?>
                <div><?php e($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="register.php">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm">Confirmar Password</label>
        <input type="password" id="confirm" name="confirm" required>
        <button type="submit">Registar</button>
    </form>
    <p style="margin-top:1rem;">Já tem conta? <a href="login.php">Faça login</a>.</p>
</div>

<?php
include __DIR__ . '/footer.php';
?>