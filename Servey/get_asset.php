<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$assetId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$assetId) {
    echo json_encode(['error' => 'Invalid asset ID']);
    exit;
}

$asset = getHardwareAsset($assetId);

if (!$asset) {
    echo json_encode(['error' => 'Asset not found']);
    exit;
}

echo json_encode($asset);
?>