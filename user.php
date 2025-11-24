<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$pdo = getPDO();
$user = current_user();
$success = '';
$errors = [];

// Processar submissão do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update-profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$name) {
        $errors[] = 'Nome não pode ser vazio.';
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }
    // Verificar se email pertence a outro utilizador
    $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
    $check->execute([$email, $user['id']]);
    if ($check->fetchColumn() > 0) {
        $errors[] = 'Email já em uso por outro utilizador.';
    }
    if (empty($errors)) {
        $upd = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $upd->execute([$name, $email, $user['id']]);
        // Actualizar sessão
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $success = 'Perfil actualizado com sucesso!';
    }
}

// Alterar password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change-password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    // Obter utilizador
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $hash = $stmt->fetchColumn();
    if (!password_verify($current, $hash)) {
        $errors[] = 'Password actual incorreta.';
    } elseif (strlen($new) < 6) {
        $errors[] = 'Nova password deve ter no mínimo 6 caracteres.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Nova password e confirmação não coincidem.';
    } else {
        $updatePass = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $updatePass->execute([password_hash($new, PASSWORD_BCRYPT), $user['id']]);
        $success = 'Password actualizada com sucesso!';
    }
}

// Obter jogos favoritos
$favStmt = $pdo->prepare('SELECT g.* FROM games g JOIN favorites f ON g.id = f.game_id WHERE f.user_id = ?');
$favStmt->execute([$user['id']]);
$favGames = $favStmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="container">
    <h2>Perfil do Utilizador</h2>
    <?php if ($success): ?>
        <div style="color:#27ae60; margin-bottom:1rem;"> <?php e($success); ?> </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="color:#e74c3c; margin-bottom:1rem;">
            <?php foreach ($errors as $err): ?>
                <div><?php e($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <h3>Dados Pessoais</h3>
    <form method="post" action="user.php">
        <input type="hidden" name="action" value="update-profile">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" value="<?php e($user['name']); ?>" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php e($user['email']); ?>" required>
        <button type="submit">Actualizar Perfil</button>
    </form>

    <h3>Alterar Password</h3>
    <form method="post" action="user.php">
        <input type="hidden" name="action" value="change-password">
        <label for="current_password">Password Actual</label>
        <input type="password" id="current_password" name="current_password" required>
        <label for="new_password">Nova Password</label>
        <input type="password" id="new_password" name="new_password" required>
        <label for="confirm_password">Confirmar Nova Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <button type="submit">Alterar Password</button>
    </form>

    <h3>Meus Favoritos</h3>
    <?php if ($favGames): ?>
        <div class="games-grid">
            <?php foreach ($favGames as $game): ?>
                <?php $name = $game['display_name'] ?: $game['table_name']; ?>
                <div class="game-card" data-name="<?php e($name); ?>">
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