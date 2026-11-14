<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in (either admin or survey user)
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isSurveyUser = isset($_SESSION['user_id']);

if (!$isAdmin && !$isSurveyUser) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reportId) {
    echo json_encode(['error' => 'Invalid report ID']);
    exit;
}

$db = getDB();

// Get report details with proper JOINs
$stmt = $db->prepare("
    SELECT 
        r.*,
        b.name as branch_name,
        b.address as branch_address,
        b.phone as branch_phone,
        b.manager_name as branch_manager,
        t.name as technician_name,
        t.phone as technician_phone
    FROM " . TABLE_PREFIX . "reports r
    LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id
    LEFT JOIN " . TABLE_PREFIX . "technicians t ON r.technician_id = t.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $reportId);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    echo json_encode(['error' => 'Report not found']);
    exit;
}

// Get all responses with category and item details - LEFT JOIN to show all active checklist items
$responsesStmt = $db->prepare("
    SELECT 
        rs.response,
        rs.notes,
        ci.question,
        ci.help_text,
        c.id as category_id,
        c.name as category_name,
        c.icon as category_icon
    FROM " . TABLE_PREFIX . "checklist_items ci
    INNER JOIN " . TABLE_PREFIX . "categories c ON ci.category_id = c.id
    LEFT JOIN " . TABLE_PREFIX . "report_responses rs ON rs.checklist_item_id = ci.id AND rs.report_id = ?
    WHERE c.type = ? AND ci.is_active = 1 AND c.is_active = 1
    ORDER BY c.display_order, ci.display_order
");
$responsesStmt->bind_param("is", $reportId, $report['type']);
$responsesStmt->execute();
$responsesResult = $responsesStmt->get_result();
$responses = [];

while ($row = $responsesResult->fetch_assoc()) {
    // Format response text for display
    $responseText = '';
    $responseClass = '';
    
    $responseVal = $row['response'] !== null ? strtolower($row['response']) : 'not_answered';
    
    switch($responseVal) {
        case 'ok':
        case 'good':
        case 'yes':
        case 'pass':
        case 'working':
            $responseText = '✅ OK';
            $responseClass = 'success';
            break;
        case 'issue':
        case 'needs_attention':
        case 'no':
        case 'fail':
        case 'not_working':
        case 'broken':
            $responseText = '❌ Issue';
            $responseClass = 'danger';
            break;
        case 'skip':
        case 'skipped':
        case 'na':
        case 'n/a':
        case 'not_applicable':
            $responseText = '➖ N/A';
            $responseClass = 'secondary';
            break;
        case 'not_answered':
            $responseText = '⚪ Not Answered';
            $responseClass = 'secondary';
            break;
        default:
            $responseText = ucfirst($row['response']);
            $responseClass = 'secondary';
    }
    
    $responses[] = [
        'question' => $row['question'],
        'help_text' => $row['help_text'],
        'response' => $row['response'],
        'response_text' => $responseText,
        'response_class' => $responseClass,
        'notes' => $row['notes'],
        'category_name' => $row['category_name'],
        'category_icon' => $row['category_icon'] ?: ($row['category_name'] == 'Hardware' ? '🔧' : '💻')
    ];
}

// Get photos
$photosStmt = $db->prepare("
    SELECT photo_path, caption 
    FROM " . TABLE_PREFIX . "report_photos 
    WHERE report_id = ? 
    ORDER BY id ASC
");
$photosStmt->bind_param("i", $reportId);
$photosStmt->execute();
$photosResult = $photosStmt->get_result();
$photos = [];

while ($row = $photosResult->fetch_assoc()) {
    $photoPath = $row['photo_path'];
    // Make path absolute if it's relative
    if (!preg_match('/^https?:\/\//', $photoPath) && !empty($photoPath)) {
        $photoPath = UPLOAD_URL . ltrim($photoPath, '/');
    }
    $photos[] = [
        'path' => $photoPath,
        'caption' => $row['caption']
    ];
}

// Calculate statistics
$totalItems = count($responses);
$okItems = 0;
$issueItems = 0;
$skippedItems = 0;

foreach ($responses as $response) {
    $responseVal = strtolower($response['response']);
    if (in_array($responseVal, ['ok', 'good', 'yes', 'pass', 'working'])) {
        $okItems++;
    } elseif (in_array($responseVal, ['issue', 'needs_attention', 'no', 'fail', 'not_working', 'broken'])) {
        $issueItems++;
    } elseif (in_array($responseVal, ['skip', 'skipped', 'na', 'n/a', 'not_applicable'])) {
        $skippedItems++;
    }
}

$completionRate = $totalItems > 0 ? round((($okItems + $issueItems) / $totalItems) * 100) : 0;
$healthScore = $report['health_score'] ?? 0;

// Prepare response data with the correct structure for the frontend
$responseData = [
    'id' => $report['id'],
    'branch' => $report['branch_name'] ?? 'Unknown Branch',
    'technician' => $report['technician_name'] ?? 'Not assigned',
    'branch_manager' => $report['branch_manager'] ?? 'Not specified',
    'type' => $report['type'] ?? 'unknown',
    'score' => intval($healthScore),
    'submitted_at' => date('M d, Y H:i', strtotime($report['submitted_at'])),
    'issues' => $report['issues_found'] ?? '',
    'actions' => $report['actions_taken'] ?? '',
    'recommendations' => $report['recommendations'] ?? '',
    'responses' => $responses,
    'photos' => $photos,
    'statistics' => [
        'total_items' => $totalItems,
        'ok_items' => $okItems,
        'issue_items' => $issueItems,
        'skipped_items' => $skippedItems,
        'completion_rate' => $completionRate
    ]
];

// Log view activity
if ($isAdmin) {
    logActivity('view_report', 'report', $reportId, "Viewed report #{$reportId}");
}

echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
?>