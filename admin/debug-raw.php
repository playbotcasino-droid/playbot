<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$pdo = getPDO();

// obter jogos para dropdown
$games = $pdo->query('SELECT id, display_name, table_name FROM games ORDER BY display_name')->fetchAll();

$selectedGame = isset($_GET['game']) ? intval($_GET['game']) : 0;
$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';
$results = [];

if ($selectedGame) {
    // construir query
    $query = 'SELECT * FROM game_results WHERE game_id = ?';
    $params = [$selectedGame];
    if ($startDate) {
        $query .= ' AND created_at >= ?';
        $params[] = $startDate . ' 00:00:00';
    }
    if ($endDate) {
        $query .= ' AND created_at <= ?';
        $params[] = $endDate . ' 23:59:59';
    }
    $query .= ' ORDER BY id DESC LIMIT 100';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Debug de Raw Messages</h2>
    <form method="get" action="debug-raw.php">
        <label for="game">Jogo</label>
        <select id="game" name="game">
            <option value="0">Selecione um jogo</option>
            <?php foreach ($games as $game): ?>
                <option value="<?php e($game['id']); ?>" <?php echo $selectedGame == $game['id'] ? 'selected' : ''; ?>><?php e($game['display_name'] ?: $game['table_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="start">Data Início</label>
        <input type="date" id="start" name="start" value="<?php e($startDate); ?>">
        <label for="end">Data Fim</label>
        <input type="date" id="end" name="end" value="<?php e($endDate); ?>">
        <button type="submit">Filtrar</button>
    </form>

    <?php if ($results): ?>
        <h3>Resultados Encontrados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Raw Message</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php e($row['id']); ?></td>
                        <td><pre style="white-space:pre-wrap; word-wrap:break-word; max-width:400px;"><?php e($row['raw_message']); ?></pre></td>
                        <td><?php e((new DateTime($row['created_at']))->format('d/m/Y H:i')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedGame): ?>
        <p>Nenhum resultado encontrado para os filtros seleccionados.</p>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../footer.php';
?>