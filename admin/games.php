<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$pdo = getPDO();
$errors = [];
$success = '';

// Processar ativar/desativar
if (isset($_GET['toggle'])) {
    $gameId = intval($_GET['toggle']);
    $stmt = $pdo->prepare('SELECT active FROM games WHERE id = ?');
    $stmt->execute([$gameId]);
    $active = $stmt->fetchColumn();
    if ($active !== false) {
        $newStatus = $active ? 0 : 1;
        $upd = $pdo->prepare('UPDATE games SET active = ? WHERE id = ?');
        $upd->execute([$newStatus, $gameId]);
        $success = 'Estado do jogo atualizado.';
    }
}

// Processar criação de novo jogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_game'])) {
    $displayName = trim($_POST['display_name'] ?? '');
    $tableName = trim($_POST['table_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $ruleId = intval($_POST['rule_id'] ?? 0);
    if (!$displayName || !$tableName) {
        $errors[] = 'Nome e table_name são obrigatórios.';
    }
    if (empty($errors)) {
        $insert = $pdo->prepare('INSERT INTO games (display_name, table_name, description, type, image_url, rule_id, active) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $insert->execute([$displayName, $tableName, $description, $type, $imageUrl, $ruleId]);
        $success = 'Jogo criado com sucesso!';
    }
}

// listar todos jogos e regras disponíveis
$games = $pdo->query('SELECT g.*, r.name as rule_name FROM games g LEFT JOIN game_rules r ON g.rule_id = r.id ORDER BY g.display_name')->fetchAll();
$rules = $pdo->query('SELECT id, name FROM game_rules ORDER BY name')->fetchAll();

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Gestão de Jogos</h2>
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
    <h3>Todos os Jogos</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Table</th>
                <th>Tipo</th>
                <th>Regra</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($games as $game): ?>
                <tr>
                    <td><?php e($game['id']); ?></td>
                    <td><?php e($game['display_name']); ?></td>
                    <td><?php e($game['table_name']); ?></td>
                    <td><?php e($game['type']); ?></td>
                    <td><?php e($game['rule_name']); ?></td>
                    <td><?php echo $game['active'] ? '<span class="badge live">Ativo</span>' : '<span class="badge offline">Inativo</span>'; ?></td>
                    <td><a href="games.php?toggle=<?php echo $game['id']; ?>"><?php echo $game['active'] ? 'Desativar' : 'Ativar'; ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Criar Novo Jogo</h3>
    <form method="post" action="games.php">
        <input type="hidden" name="create_game" value="1">
        <label for="display_name">Nome para exibição</label>
        <input type="text" id="display_name" name="display_name" required>
        <label for="table_name">Table Name / ID</label>
        <input type="text" id="table_name" name="table_name" required>
        <label for="description">Descrição</label>
        <textarea id="description" name="description"></textarea>
        <label for="type">Tipo de jogo</label>
        <input type="text" id="type" name="type">
        <label for="image_url">URL da Imagem</label>
        <input type="text" id="image_url" name="image_url">
        <label for="rule_id">Regra de Parser</label>
        <select id="rule_id" name="rule_id">
            <option value="0">Nenhuma</option>
            <?php foreach ($rules as $rule): ?>
                <option value="<?php e($rule['id']); ?>"><?php e($rule['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Criar Jogo</button>
    </form>
</div>

<?php
include __DIR__ . '/../footer.php';
?>