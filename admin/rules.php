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

// handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $code = trim($_POST['code'] ?? '');
    if (!$name) {
        $errors[] = 'Nome da regra é obrigatório.';
    }
    if (empty($errors)) {
        if (isset($_POST['rule_id']) && $_POST['rule_id']) {
            // update existente
            $id = intval($_POST['rule_id']);
            $upd = $pdo->prepare('UPDATE game_rules SET name = ?, type = ?, description = ?, code = ? WHERE id = ?');
            $upd->execute([$name, $type, $description, $code, $id]);
            $success = 'Regra actualizada!';
        } else {
            $ins = $pdo->prepare('INSERT INTO game_rules (name, type, description, code) VALUES (?, ?, ?, ?)');
            $ins->execute([$name, $type, $description, $code]);
            $success = 'Regra criada com sucesso!';
        }
    }
}

// handle edit
$editingRule = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM game_rules WHERE id = ?');
    $stmt->execute([$editId]);
    $editingRule = $stmt->fetch();
}

// handle delete
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $pdo->prepare('DELETE FROM game_rules WHERE id = ?')->execute([$delId]);
    $success = 'Regra removida.';
}

// obter todas regras
$rules = $pdo->query('SELECT * FROM game_rules ORDER BY name')->fetchAll();

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Gestão de Regras de Parser</h2>
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
    <h3><?php echo $editingRule ? 'Editar Regra' : 'Criar Nova Regra'; ?></h3>
    <form method="post" action="rules.php">
        <?php if ($editingRule): ?>
            <input type="hidden" name="rule_id" value="<?php e($editingRule['id']); ?>">
        <?php endif; ?>
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" value="<?php e($editingRule['name'] ?? ''); ?>" required>
        <label for="type">Tipo de Jogo</label>
        <input type="text" id="type" name="type" value="<?php e($editingRule['type'] ?? ''); ?>">
        <label for="description">Descrição</label>
        <textarea id="description" name="description"><?php e($editingRule['description'] ?? ''); ?></textarea>
        <label for="code">Código (JS / configuração)</label>
        <textarea id="code" name="code" style="min-height:150px;font-family:monospace;"><?php e($editingRule['code'] ?? ''); ?></textarea>
        <button type="submit"><?php echo $editingRule ? 'Actualizar Regra' : 'Criar Regra'; ?></button>
    </form>

    <h3>Regras Existentes</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rules as $rule): ?>
                <tr>
                    <td><?php e($rule['id']); ?></td>
                    <td><?php e($rule['name']); ?></td>
                    <td><?php e($rule['type']); ?></td>
                    <td><?php e(substr($rule['description'], 0, 50)); ?></td>
                    <td>
                        <a href="rules.php?edit=<?php e($rule['id']); ?>">Editar</a> |
                        <a href="rules.php?delete=<?php e($rule['id']); ?>" onclick="return confirm('Tem a certeza que deseja remover esta regra?');">Apagar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Testar Regra (placeholder)</h3>
    <p>Esta funcionalidade irá permitir inserir um raw_message e ver o resultado parseado de acordo com o código JavaScript da regra selecionada. Implementação futura.</p>
</div>

<?php
include __DIR__ . '/../footer.php';
?>