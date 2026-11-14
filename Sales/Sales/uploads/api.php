<?php
// api.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add_comment':
        $caseId = $_POST['case_id'] ?? '';
        $comment = $_POST['comment'] ?? '';
        
        if ($caseId && $comment) {
            $result = addComment($caseId, $comment, $_SESSION['username']);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing data']);
        }
        break;
        
    case 'delete_case':
        $input = json_decode(file_get_contents('php://input'), true);
        $caseId = $input['case_id'] ?? '';
        
        if (!empty($caseId)) {
            $result = deleteCase($caseId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing case ID']);
        }
        break;
        
    case 'approve_case':
        $input = json_decode(file_get_contents('php://input'), true);
        $caseId = $input['case_id'] ?? '';
        
        if (!empty($caseId)) {
            $result = approveCase($caseId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing case ID']);
        }
        break;
        
    case 'delete_attachment':
        $input = json_decode(file_get_contents('php://input'), true);
        $attachmentId = $input['attachment_id'] ?? 0;
        
        if ($attachmentId) {
            $result = deleteCaseAttachment($attachmentId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing attachment ID']);
        }
        break;
        
    case 'get_attachment_info':
        $input = json_decode(file_get_contents('php://input'), true);
        $attachmentId = $input['attachment_id'] ?? 0;
        
        if ($attachmentId) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM sales_case_attachments WHERE id = ?");
            $stmt->execute([$attachmentId]);
            $attachment = $stmt->fetch();
            
            if ($attachment) {
                echo json_encode([
                    'success' => true,
                    'file_name' => $attachment['file_name'],
                    'file_size' => $attachment['file_size'],
                    'file_type' => $attachment['file_type'],
                    'file_path' => $attachment['file_path'],
                    'download_url' => 'download.php?file=' . urlencode($attachment['id'])
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Attachment not found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing attachment ID']);
        }
        break;


// api.php - Add these cases

// ============ APPROVE COMPLETED CASE ============
case 'approve_completed_case':
    $input = json_decode(file_get_contents('php://input'), true);
    $caseId = $input['case_id'] ?? '';
    $approved = isset($input['approved']) ? (int)$input['approved'] : 0;
    
    if (empty($caseId)) {
        echo json_encode(['success' => false, 'error' => 'Missing case ID']);
        exit();
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE sales_completed_cases SET approved = ?, last_updated = NOW() WHERE case_id = ?");
        $stmt->execute([$approved, $caseId]);
        
        $action = $approved ? 'Approved' : 'Unapproved';
        logAction($_SESSION['username'] ?? 'System', 'Approve Completed Case', "$action completed case $caseId");
        
        echo json_encode(['success' => true, 'message' => "Case $action successfully"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    break;

// ============ GET COMPLETED CASE DETAILS ============
case 'get_completed_case':
    $input = json_decode(file_get_contents('php://input'), true);
    $caseId = $input['case_id'] ?? '';
    
    if (empty($caseId)) {
        echo json_encode(['success' => false, 'error' => 'Missing case ID']);
        exit();
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT c.*, b.name as branch_name 
            FROM sales_completed_cases c 
            LEFT JOIN sales_branches b ON c.branch_id = b.id 
            WHERE c.case_id = ?
        ");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        
        if ($case) {
            // Format dates
            $case['date'] = date('Y-m-d', strtotime($case['date']));
            $case['case_fixed_date'] = $case['case_fixed_date'] ? date('Y-m-d', strtotime($case['case_fixed_date'])) : null;
            $case['approved'] = (bool)$case['approved'];
            
            echo json_encode(['success' => true, 'case' => $case]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Case not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>

