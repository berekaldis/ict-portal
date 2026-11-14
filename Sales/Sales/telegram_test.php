<?php
// telegram_test.php - Quick diagnostic page
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$results = [];
if (isset($_POST['test'])) {
    $chatId = $_POST['chat_id'] ?? '';
    $botToken = $_POST['bot_token'] ?? '';
    $results = debugTelegramConnection($chatId, $botToken);
}

$telegramDestinations = getTelegramDestinations();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2>Telegram Debug Tool</h2>
        <p class="text-muted">Test your Telegram connection and diagnose issues</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plug"></i> Connection Test</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="chat_id" class="form-label">Chat ID</label>
                        <select class="form-select" id="chat_id" name="chat_id" required>
                            <option value="">Select Destination...</option>
                            <?php foreach ($telegramDestinations as $dest): ?>
                            <option value="<?= $dest['chat_id'] ?>">
                                <?= htmlspecialchars($dest['name']) ?> (<?= htmlspecialchars($dest['chat_id']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="bot_token" class="form-label">Bot Token</label>
                        <select class="form-select" id="bot_token" name="bot_token" required>
                            <option value="">Select Destination...</option>
                            <?php foreach ($telegramDestinations as $dest): ?>
                            <option value="<?= $dest['bot_token'] ?>">
                                <?= htmlspecialchars($dest['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="test" value="1" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run Test
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Test Results</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $result): ?>
                        <?php if (strpos($result, '✅') !== false): ?>
                            <div class="alert alert-success mb-1 py-1"><?= $result ?></div>
                        <?php elseif (strpos($result, '❌') !== false): ?>
                            <div class="alert alert-danger mb-1 py-1"><?= $result ?></div>
                        <?php else: ?>
                            <div class="alert alert-info mb-1 py-1"><?= $result ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Run the test to see results...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

