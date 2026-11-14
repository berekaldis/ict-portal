<?php
// Clean any output buffers that might have been started
if (ob_get_level()) ob_clean();
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Clear any output from included files
ob_clean();

if (!isset($pdo)) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $settings = getSettings($pdo);

    $response = [
        'branches' => $settings['branches'] ?? [],
        'jobs' => $settings['jobs'] ?? [],
        'ranks' => $settings['ranks'] ?? [],
        'destinations' => $settings['destinations'] ?? [],
        'logo_url' => $settings['logo_url'] ?? '',
        'branchChatIds' => $settings['branch_chat_ids'] ?? [],
        'destChatIds' => $settings['dest_chat_ids'] ?? []
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
ob_end_flush();
?>