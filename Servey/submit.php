<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

 $db = getDB();
 $type = $_POST['type'] ?? '';
 $techId = intval($_POST['technician_id'] ?? 0);
 $branchId = intval($_POST['branch_id'] ?? 0);
 $manager = trim($_POST['branch_manager'] ?? '');
 $issues = trim($_POST['issues_summary'] ?? '');
 $actions = trim($_POST['actions_taken'] ?? '');
 $recos = trim($_POST['recommendations'] ?? '');
 $checklistState = json_decode($_POST['checklist_state'] ?? '{}', true);

// Validation
if (!$type || !$techId || !$branchId || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ── Calculate score (Skip-aware: matches frontend logic) ──
 $total = 0;
 $ok = 0;
 $issueCount = 0;
 $skipCount = 0;

foreach ($checklistState as $item) {
    $total++;
    if ($item['status'] === 'OK') $ok++;
    elseif ($item['status'] === 'Issue') $issueCount++;
    elseif ($item['status'] === 'Skip') $skipCount++;
}

// Score is based only on evaluated (non-skipped) items
 $evaluatedNonSkipped = $ok + $issueCount;
 $score = $evaluatedNonSkipped > 0 ? round(($ok / $evaluatedNonSkipped) * 100) : 0;

 $statusText = $score >= 85 ? '🟢 Good' : ($score >= 60 ? '🟡 Warning' : '🔴 Critical');
 $criticalCount = $issueCount; // Now accurately counts only "Issue" items

 $reportUuid = uniqid() . '_' . bin2hex(random_bytes(8));

// ── Insert report ──
 $stmt = $db->prepare("
    INSERT INTO " . TABLE_PREFIX . "reports 
    (report_uuid, branch_id, technician_id, branch_manager, type, health_score, health_status, critical_count, issues_summary, actions_taken, recommendations, checklist_data, submitted_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
 $checklistJson = json_encode($checklistState);
 $stmt->bind_param("siissiisssss", $reportUuid, $branchId, $techId, $manager, $type, $score, $statusText, $criticalCount, $issues, $actions, $recos, $checklistJson);
 $stmt->execute();
 $reportId = $db->insert_id;

// ── Save photos (base64 → files) ──
 $photoPaths = [];
foreach ($_POST as $key => $val) {
    if (strpos($key, 'photo_') === 0 && !empty($val)) {
        // Decode base64 and save as actual image file
        $base64Data = $val;
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $imageType = strtolower($matches[1]); // jpeg, png, gif, etc.
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            $imageData = base64_decode($base64Data);
            
            if ($imageData !== false) {
                $filename = $reportUuid . '_' . str_replace('photo_', '', $key) . '.' . $imageType;
                $filepath = UPLOAD_PATH . $filename;
                
                if (file_put_contents($filepath, $imageData)) {
                    $photoPaths[] = UPLOAD_URL . $filename;
                }
            }
        }
    }
}

// Store photo URLs in database
if (!empty($photoPaths)) {
    $photoJson = json_encode($photoPaths);
    // Ensure column exists
    $db->query("ALTER TABLE " . TABLE_PREFIX . "reports ADD COLUMN IF NOT EXISTS photos TEXT");
    $stmtPhoto = $db->prepare("UPDATE " . TABLE_PREFIX . "reports SET photos = ? WHERE id = ?");
    $stmtPhoto->bind_param("si", $photoJson, $reportId);
    $stmtPhoto->execute();
}

// ── Log activity ──
logActivity('survey_submitted', 'report', $reportId, "Score: {$score}% | Branch ID: {$branchId}");

// ── Fetch related info for notifications ──
 $branch = $db->query("SELECT name FROM " . TABLE_PREFIX . "branches WHERE id = $branchId")->fetch_assoc();
 $tech = $db->query("SELECT name FROM " . TABLE_PREFIX . "technicians WHERE id = $techId")->fetch_assoc();
 $branchName = $branch['name'] ?? 'Unknown';
 $techName = $tech['name'] ?? 'Unknown';

// ── Send detailed Telegram notification (MarkdownV2 format) ──
sendSurveyNotification(
    $reportId,
    $score,
    $checklistState,
    $branchName,
    $techName,
    $manager,
    $issues,
    $actions
);

// ── Also send a quick summary to a group chat if configured ──
// (Optional secondary notification channel)
 $shortMsg = "☕ *New " . ucfirst($type) . " Survey*\n";
 $shortMsg .= "🏢 $branchName | 👨‍🔧 $techName\n";
 $shortMsg .= "📊 Score: $score% $statusText | ⚠️ Issues: $criticalCount | ⏭️ Skipped: $skipCount\n";
 $shortMsg .= "🆔 `$reportUuid`";
sendTelegram($shortMsg);

echo json_encode(['success' => true, 'message' => "Report #$reportUuid submitted successfully!"]);
?>