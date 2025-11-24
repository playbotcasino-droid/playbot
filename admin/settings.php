<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$message = '';
if (isset($_POST['test_connection'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $message = 'Ligação estabelecida com sucesso!';
    } catch (PDOException $e) {
        $message = 'Falha na ligação: ' . $e->getMessage();
    }
}

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Configurações do Sistema</h2>
    <?php if ($message): ?>
        <div style="margin-bottom:1rem; color: <?php echo strpos($message, 'sucesso') !== false ? '#27ae60' : '#e74c3c'; ?>;">
            <?php e($message); ?>
        </div>
    <?php endif; ?>
    <form method="post" action="settings.php">
        <label for="host">Host</label>
        <input type="text" id="host" value="<?php e(DB_HOST); ?>" disabled>
        <label for="database">Base de Dados</label>
        <input type="text" id="database" value="<?php e(DB_NAME); ?>" disabled>
        <label for="user">Utilizador</label>
        <input type="text" id="user" value="<?php e(DB_USER); ?>" disabled>
        <label for="pass">Password</label>
        <input type="password" id="pass" value="<?php e(DB_PASS); ?>" disabled>
        <button type="submit" name="test_connection" value="1">Testar Conexão</button>
    </form>
    <p style="margin-top:1rem;">Estas configurações são definidas no ficheiro <code>config.php</code>. Edite esse ficheiro para modificar a ligação.</p>
</div>

<?php
include __DIR__ . '/../footer.php';
?>