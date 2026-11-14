<?php
// api/get_data.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

// Require login for all operations
require_login();

// Debug: Check if $pdo exists
if (!isset($pdo)) {
    echo json_encode(['error' => 'Database connection not initialized']);
    exit;
}

$type = $_GET['type'] ?? '';
$typeParam = $_GET['typeParam'] ?? '';

if ($type == 'internships_for_form') {
    try {
        $stmt = $pdo->query("SELECT request_id as id, name, gender, to_branch, work_branch as branch, job, start_date as start FROM hr_internship_requests WHERE reference_status != 'Done' OR reference_status IS NULL ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll());
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} elseif ($type == 'qualifications_for_form') {
    try {
        $stmt = $pdo->query("SELECT request_id as id, name, gender, branch, job, probation_start FROM hr_qualifications WHERE reference_status != 'Done' OR reference_status IS NULL ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll());
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
 } elseif ($type == 'all_requests') {
    try {
        $results = [];
        
        $internships = $pdo->query("SELECT 'Internship' as type, request_id as id, created_at as date, name, to_branch, work_branch, job, start_date, pdf_path as pdf, reference_status as status, 'Internship' as source FROM hr_internship_requests")->fetchAll();
        $qualifications = $pdo->query("SELECT 'Qualification' as type, request_id as id, created_at as date, name, branch, '' as work_branch, job, probation_start as start_date, pdf_path as pdf, reference_status as status, 'Qualification' as source FROM hr_qualifications")->fetchAll();
        $recruitments = $pdo->query("SELECT 'Recruitment' as type, request_id as id, created_at as date, name, branch, '' as work_branch, requested_job as req_job, assigned_job as assign_job, start_date, pdf_path as pdf, 'Submitted' as status, 'Recruitment' as source FROM hr_recruitments")->fetchAll();
        
        $results = array_merge($internships, $qualifications, $recruitments);
        usort($results, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        echo json_encode($results);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} elseif ($type == 'edit_data') {
    $id = $_GET['id'] ?? '';
    $requestType = $typeParam ?: ($_GET['requestType'] ?? '');
    
    try {
        if ($requestType == 'Internship') {
            $stmt = $pdo->prepare("SELECT * FROM hr_internship_requests WHERE request_id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if ($data) {
                echo json_encode([
                    'type' => 'Internship',
                    'id' => $data['request_id'],
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'to_branch' => $data['to_branch'],
                    'work_branch' => $data['work_branch'],
                    'job' => $data['job'],
                    'start_date' => $data['start_date']
                ]);
            } else {
                echo json_encode(['error' => 'Record not found']);
            }
        } elseif ($requestType == 'Qualification') {
            $stmt = $pdo->prepare("SELECT * FROM hr_qualifications WHERE request_id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if ($data) {
                echo json_encode([
                    'type' => 'Qualification',
                    'id' => $data['request_id'],
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'branch' => $data['branch'],
                    'job' => $data['job'],
                    'probation_start' => $data['probation_start'],
                    'intern_start' => ''
                ]);
            } else {
                echo json_encode(['error' => 'Record not found']);
            }
        } elseif ($requestType == 'Recruitment') {
            $stmt = $pdo->prepare("SELECT * FROM hr_recruitments WHERE request_id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            if ($data) {
                echo json_encode([
                    'type' => 'Recruitment',
                    'id' => $data['request_id'],
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'branch' => $data['branch'],
                    'req_job' => $data['requested_job'],
                    'job' => $data['assigned_job'],
                    'start_date' => $data['start_date']
                ]);
            } else {
                echo json_encode(['error' => 'Record not found']);
            }
        } else {
            echo json_encode(['error' => 'Invalid request type']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} elseif ($type == 'delete') {
    // Only Admin and Superadmin can delete
    enforce_role(['Admin', 'Superadmin']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? $_POST['id'] ?? '';
    $requestType = $input['type'] ?? $_POST['type'] ?? '';
    
    $table = '';
    if ($requestType == 'Internship') $table = 'hr_internship_requests';
    elseif ($requestType == 'Qualification') $table = 'hr_qualifications';
    elseif ($requestType == 'Recruitment') $table = 'hr_recruitments';
    
    try {
        if ($table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE request_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request type']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} elseif ($type == 'reject') {
    // All authenticated users can reject
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Check if input is valid
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data']);
            exit;
        }
        
        $id = $input['id'] ?? '';
        $requestType = $input['type'] ?? '';
        
        if (empty($id) || empty($requestType)) {
            echo json_encode(['success' => false, 'message' => 'Missing id or type parameter']);
            exit;
        }
        
        // Map request type to table name
        $table = '';
        if ($requestType == 'Internship') $table = 'hr_internship_requests';
        elseif ($requestType == 'Qualification') $table = 'hr_qualifications';
        elseif ($requestType == 'Recruitment') $table = 'hr_recruitments';
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid request type']);
            exit;
        }
        
        // Get request details before updating
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE request_id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        // Update status to Rejected
        $stmt = $pdo->prepare("UPDATE $table SET reference_status = 'Rejected' WHERE request_id = ?");
        $stmt->execute([$id]);
        
        // Send Telegram notification (optional, don't fail if this doesn't work)
        try {
            $settings = getSettings($pdo);
            $botTokenStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'bot_token'");
            $botTokenRow = $botTokenStmt->fetch(PDO::FETCH_ASSOC);
            $botToken = $botTokenRow['config_value'] ?? '';
            
            if ($botToken) {
                $chatIds = [];
                $userName = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
                $message = "<b>❌ Request Rejected</b>\n";
                $message .= "<b>Request ID:</b> {$id}\n";
                $message .= "<b>Rejected By:</b> {$userName}\n";
                $message .= "<b>Name:</b> " . ($request['name'] ?? '') . "\n";
                $message .= "<b>Type:</b> {$requestType}";
                
                // Send to appropriate chats based on request type
                if ($requestType == 'Internship' && !empty($request['to_branch'])) {
                    if (isset($settings['branch_chat_ids'][$request['to_branch']])) {
                        $chatIds[] = $settings['branch_chat_ids'][$request['to_branch']];
                    }
                } elseif ($requestType == 'Qualification' && !empty($request['q_destination'])) {
                    if (isset($settings['dest_chat_ids'][$request['q_destination']])) {
                        $chatIds[] = $settings['dest_chat_ids'][$request['q_destination']];
                    }
                } elseif ($requestType == 'Recruitment' && !empty($request['r_destination'])) {
                    if (isset($settings['dest_chat_ids'][$request['r_destination']])) {
                        $chatIds[] = $settings['dest_chat_ids'][$request['r_destination']];
                    }
                }
                
                foreach ($chatIds as $chatId) {
                    sendTelegramMessage($chatId, $message, $botToken);
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the rejection
            error_log("Telegram notification failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} elseif ($type == 'next') {
    // Only Admin and Superadmin can do next
    enforce_role(['Admin', 'Superadmin']);
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data']);
            exit;
        }
        
        $id = $input['id'] ?? '';
        $requestType = $input['type'] ?? '';
        
        if (empty($id) || empty($requestType)) {
            echo json_encode(['success' => false, 'message' => 'Missing id or type parameter']);
            exit;
        }
        
        $table = '';
        if ($requestType == 'Internship') $table = 'hr_internship_requests';
        elseif ($requestType == 'Qualification') $table = 'hr_qualifications';
        elseif ($requestType == 'Recruitment') $table = 'hr_recruitments';
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid request type']);
            exit;
        }
        
        // Get current status
        $stmt = $pdo->prepare("SELECT reference_status FROM $table WHERE request_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        $currentStatus = $result['reference_status'] ?? 'Pending';
        
        // Define status flow
        $statusFlow = ['Pending' => 'Approved', 'Approved' => 'Done'];
        $nextStatus = $statusFlow[$currentStatus] ?? $currentStatus;
        
        $stmt = $pdo->prepare("UPDATE $table SET reference_status = ? WHERE request_id = ?");
        $stmt->execute([$nextStatus, $id]);
        
        echo json_encode(['success' => true, 'message' => "Request status updated to: $nextStatus"]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Invalid type parameter']);
}
?>