<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$pdo = getPDO();

// Buscar jogos
$games = $pdo->query("SELECT * FROM games ORDER BY display_name ASC")->fetchAll();

/**
 * Função para obter o último resultado real da tabela de cada jogo
 */
function get_last_result(PDO $pdo, $tableName) {

    if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
        return null;
    }

    try {
        $stmt = $pdo->query("SELECT raw_message, created_at FROM `$tableName` ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
        if (!$row) return null;

        $raw = $row['raw_message'];
        $data = json_decode($raw, true);

        // Extrair última letra baseado no formato TopCard/BacBo/Baccara
        if (isset($data['args'])) {
            $key = array_key_first($data['args']);
            if (isset($data['args'][$key]['results'])) {
                $results = $data['args'][$key]['results'];
                $last = end($results);
                return [
                    'parsed' => $last,
                    'created_at' => $row['created_at'],
                    'raw' => $raw
                ];
            }
        }

        return null;

    } catch (Exception $e) {
        return null;
    }
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1 class="page-title">Jogos Disponíveis</h1>

    <div class="games-grid">
        <?php foreach ($games as $game): ?>

            <?php $result = get_last_result($pdo, $game['table_name']); ?>

            <div class="game-card" data-name="<?= e($game['display_name']); ?>">
                <div class="game-header">
                    <h3><?= e($game['display_name']); ?></h3>
                </div>

                <div class="game-body">
                    <p><strong>Tabela:</strong> <?= e($game['table_name']); ?></p>

                    <?php if ($result): ?>
                        <p><strong>Último Resultado:</strong> <?= e($result['parsed']); ?></p>
                        <p><strong>Hora:</strong> <?= e($result['created_at']); ?></p>
                    <?php else: ?>
                        <p><strong>Último Resultado:</strong> Sem resultados</p>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <?php if (is_logged_in()): ?>
                        <a href="/playbot_php/game.php?id=<?= $game['id']; ?>">Ver Detalhes →</a>
                    <?php else: ?>
                        <a href="/playbot_php/login.php">Acessar Dados →</a>
                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
