<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$pdo = getPDO();
$success = '';
// handle promote/demote
if (isset($_GET['toggle_role'])) {
    $id = intval($_GET['toggle_role']);
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $role = $stmt->fetchColumn();
    if ($role) {
        $newRole = ($role === 'admin') ? 'user' : 'admin';
        $upd = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $upd->execute([$newRole, $id]);
        $success = 'Role atualizado.';
    }
}

// handle block/unblock (assume column 'active' in users)
if (isset($_GET['toggle_active'])) {
    $id = intval($_GET['toggle_active']);
    $stmt = $pdo->prepare('SELECT active FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $active = $stmt->fetchColumn();
    if ($active !== false) {
        $new = $active ? 0 : 1;
        $pdo->prepare('UPDATE users SET active = ? WHERE id = ?')->execute([$new, $id]);
        $success = 'Estado de conta atualizado.';
    }
}

// list users
$users = $pdo->query('SELECT id, name, email, role, active, created_at FROM users ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Gestão de Utilizadores</h2>
    <?php if ($success): ?>
        <div style="color:#27ae60; margin-bottom:1rem;"> <?php e($success); ?> </div>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Role</th>
                <th>Estado</th>
                <th>Data de Criação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php e($user['id']); ?></td>
                    <td><?php e($user['name']); ?></td>
                    <td><?php e($user['email']); ?></td>
                    <td><?php e($user['role']); ?></td>
                    <td><?php echo $user['active'] ? '<span class="badge live">Ativo</span>' : '<span class="badge offline">Bloqueado</span>'; ?></td>
                    <td><?php e((new DateTime($user['created_at']))->format('d/m/Y')); ?></td>
                    <td>
                        <?php if ($user['id'] != current_user()['id']): ?>
                            <a href="users.php?toggle_role=<?php echo $user['id']; ?>"><?php echo $user['role'] === 'admin' ? 'Rebaixar' : 'Promover'; ?></a> |
                            <a href="users.php?toggle_active=<?php echo $user['id']; ?>"><?php echo $user['active'] ? 'Bloquear' : 'Desbloquear'; ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
include __DIR__ . '/../footer.php';
?>