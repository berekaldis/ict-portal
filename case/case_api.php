<?php
/**
 * Case Management API for Kaldis Coffee
 * PHP 5.4+ Compatible
 */

require_once __DIR__ . '/case_config.php';
session_start();

// ==================== DATABASE INITIALIZATION ====================
caseInitDatabase();

// ==================== ROUTER ====================
 $action = isset($_GET['action']) ? $_GET['action'] : '';

// ==================== AUTHENTICATION ENDPOINTS ====================

if ($action === 'authenticateUser') {
    $data = casePostBody();
    $username = isset($data['username']) ? trim($data['username']) : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if (empty($username) || empty($password)) {
        caseJsonResponse(array('error' => 'Username and password are required'), 400);
    }

    try {
        $db = caseDB();
        $stmt = $db->prepare("SELECT * FROM case_users WHERE username = ? AND status = 'active'");
        $stmt->execute(array($username));
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['case_user'] = array(
                'username'    => $user['username'],
                'role'        => $user['role'],
                'telegram_id' => isset($user['telegram_id']) ? $user['telegram_id'] : ''
            );

            $db->prepare("UPDATE case_users SET last_login = NOW() WHERE username = ?")->execute(array($username));

            caseJsonResponse(array('success' => true, 'user' => $_SESSION['case_user']));
        } else {
            caseJsonResponse(array('error' => 'Invalid username or password'), 401);
        }
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Login failed: ' . $e->getMessage()), 500);
    }
}

if ($action === 'logout') {
    session_destroy();
    caseJsonResponse(array('success' => true));
}

if ($action === 'getCurrentUser') {
    if (isset($_SESSION['case_user'])) {
        caseJsonResponse($_SESSION['case_user']);
    } else {
        caseJsonResponse(array('error' => 'Not authenticated'), 401);
    }
}

// ==================== DASHBOARD ENDPOINTS ====================

if ($action === 'getDashboardData') {
    caseRequireAuth();

    try {
        $db = caseDB();
        $stats = array(
            'total' => 0, 'notStarted' => 0, 'inProgress' => 0,
            'fixed' => 0, 'return' => 0, 'reject' => 0,
            'completed' => 0, 'overdue' => 0,
            'matrix' => array('Critical' => 0, 'Medium' => 0, 'Low' => 0)
        );

        $today = date('Y-m-d');
        $terminalStatuses = array('Completed', 'Reject');

        $stmt = $db->query("SELECT status, priority, due_date FROM case_cases");

        while ($row = $stmt->fetch()) {
            $stats['total']++;

            switch ($row['status']) {
                case 'Not Started': $stats['notStarted']++; break;
                case 'In Progress': $stats['inProgress']++; break;
                case 'Fixed':       $stats['fixed']++;      break;
                case 'Return':      $stats['return']++;     break;
                case 'Reject':      $stats['reject']++;     break;
                case 'Completed':   $stats['completed']++;  break;
            }

            if (!in_array($row['status'], $terminalStatuses) && $row['due_date'] && $row['due_date'] < $today) {
                $stats['overdue']++;
            }

            if (!in_array($row['status'], $terminalStatuses) && isset($stats['matrix'][$row['priority']])) {
                $stats['matrix'][$row['priority']]++;
            }
        }

        caseJsonResponse(array('success' => true, 'details' => $stats, 'matrix' => $stats['matrix']));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => $e->getMessage()), 500);
    }
}

if ($action === 'getRecentActivity') {
    caseRequireAuth();

    try {
        $db = caseDB();
        $stmt = $db->query("SELECT * FROM case_history ORDER BY timestamp DESC LIMIT 15");
        $activities = $stmt->fetchAll();
        caseJsonResponse($activities ? $activities : array());
    } catch (Exception $e) {
        caseJsonResponse(array());
    }
}

// ==================== CASE LIST FIXED ====================

if ($action === 'getCaseList') {
    caseRequireAuth();

    $offset = 0;
    $limit = 200;
    $filters = array();
    
    if (isset($_GET['offset'])) {
        $offset = max(0, (int)$_GET['offset']);
    }
    if (isset($_GET['limit'])) {
        $limit = max(1, min(500, (int)$_GET['limit']));
    }
    if (isset($_GET['filters'])) {
        $filters = json_decode($_GET['filters'], true);
        if (!is_array($filters)) $filters = array();
    }

    try {
        $db = caseDB();

        $sql = "SELECT * FROM case_cases WHERE 1=1";
        $params = array();

        if (!empty($filters['term'])) {
            $sql .= " AND (case_id LIKE ? OR description LIKE ? OR remarks LIKE ?)";
            $like = "%" . $filters['term'] . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['assignedTo'])) {
            $sql .= " AND assigned_to = ?";
            $params[] = $filters['assignedTo'];
        }

        if (!empty($filters['assignedToMe']) && $filters['assignedToMe'] === true) {
            $sql .= " AND assigned_to = ?";
            $params[] = $_SESSION['case_user']['username'];
        }

        if (!empty($filters['startDate'])) {
            $sql .= " AND created_date >= ?";
            $params[] = $filters['startDate'];
        }
        if (!empty($filters['endDate'])) {
            $sql .= " AND created_date <= ?";
            $params[] = $filters['endDate'];
        }

        $sql .= " ORDER BY created_date DESC, id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cases = $stmt->fetchAll();

        caseJsonResponse(array('success' => true, 'cases' => $cases));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => $e->getMessage()), 500);
    }
}

// ==================== CREATE CASE ====================

if ($action === 'createCase') {
    caseRequireKaldis();

    $data = casePostBody();
    $caseType = isset($data['caseType']) ? trim($data['caseType']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $createdDate = isset($data['createdDate']) ? trim($data['createdDate']) : '';

    if (empty($caseType)) {
        caseJsonResponse(array('error' => 'Case type is required'), 400);
    }
    if (empty($description)) {
        caseJsonResponse(array('error' => 'Description is required'), 400);
    }
    if (empty($createdDate)) {
        caseJsonResponse(array('error' => 'Start date is required'), 400);
    }

    try {
        $db = caseDB();

        $typeCheck = $db->prepare("SELECT name FROM case_types WHERE name = ?");
        $typeCheck->execute(array($caseType));
        if (!$typeCheck->fetch()) {
            caseJsonResponse(array('error' => "Invalid case type: " . $caseType), 400);
        }

        $lastId = $db->query("SELECT case_id FROM case_cases ORDER BY id DESC LIMIT 1")->fetch();
        if ($lastId && preg_match('/CASE-(\d+)/', $lastId['case_id'], $matches)) {
            $num = (int)$matches[1] + 1;
            $caseId = 'CASE-' . str_pad($num, 4, '0', STR_PAD_LEFT);
        } else {
            $caseId = 'CASE-0001';
        }

        $assignedTo = isset($data['assignedTo']) && !empty($data['assignedTo']) ? trim($data['assignedTo']) : null;
        $remarks = isset($data['remarks']) && !empty($data['remarks']) ? trim($data['remarks']) : null;
        $dueDate = isset($data['dueDate']) && !empty($data['dueDate']) ? trim($data['dueDate']) : null;
        $status = isset($data['status']) ? $data['status'] : 'Not Started';
        $priority = isset($data['priority']) ? $data['priority'] : 'Medium';
        $username = $_SESSION['case_user']['username'];
        $attachments = isset($data['attachments']) ? $data['attachments'] : '';

        $stmt = $db->prepare("INSERT INTO case_cases 
            (case_id, case_type, description, created_date, due_date, status, priority, assigned_to, created_by, remarks, attachments, last_modified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute(array($caseId, $caseType, $description, $createdDate, $dueDate, $status, $priority, $assignedTo, $username, $remarks, $attachments));

        $logDetails = "Case created by " . $username;
        if ($assignedTo) $logDetails .= " | Assigned to: " . $assignedTo;
        if ($remarks) $logDetails .= " | Remarks: " . mb_substr($remarks, 0, 200);
        logCaseActivity($caseId, 'Case Created', $logDetails);

        // Send detailed notification
        $notificationData = array(
            'caseId' => $caseId,
            'caseType' => $caseType,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'createdDate' => $createdDate,
            'dueDate' => $dueDate,
            'assignedTo' => $assignedTo,
            'createdBy' => $username,
            'remarks' => $remarks,
            'attachments' => $attachments
        );
        caseSendDetailedTelegramNotification('Case Created', $notificationData);

        if ($assignedTo && !empty($assignedTo)) {
            $assigneeMsg = "<b>📋 New Case Assigned to You</b>\n\n"
                . "<b>Case ID:</b> " . $caseId . "\n"
                . "<b>Type:</b> " . $caseType . "\n"
                . "<b>Priority:</b> " . $priority . "\n"
                . "<b>Status:</b> " . $status . "\n"
                . "<b>Created:</b> " . $createdDate . "\n"
                . "<b>Due Date:</b> " . ($dueDate ? $dueDate : 'Not set') . "\n\n"
                . "<b>Description:</b>\n" . $description;
            if ($remarks) {
                $assigneeMsg .= "\n\n<b>Remarks:</b>\n" . $remarks;
            }
            if ($attachments) {
                $assigneeMsg .= "\n\n<b>Attachments:</b>\n" . $attachments;
            }
            caseNotifyAssignee($assignedTo, $caseId, $assigneeMsg);
        }

        caseJsonResponse(array('success' => true, 'caseId' => $caseId));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to create case: ' . $e->getMessage()), 500);
    }
}

// ==================== UPDATE CASE FIXED ====================

if ($action === 'updateCase') {
    caseRequireAuth();

    $data = casePostBody();
    $caseId = isset($data['caseId']) ? trim($data['caseId']) : '';
    $userRole = $_SESSION['case_user']['role'];
    $username = $_SESSION['case_user']['username'];

    if (empty($caseId)) {
        caseJsonResponse(array('error' => 'Case ID is required. Please refresh the page and try again.'), 400);
    }

    try {
        $db = caseDB();
        $currentCase = caseGetById($db, $caseId);

        if (!$currentCase) {
            caseJsonResponse(array('error' => 'Case not found. The case may have been deleted.'), 404);
        }

        // KALDIS ROLE
        if ($userRole === 'kaldis') {
            $newStatus = isset($data['status']) ? $data['status'] : '';
            $remarks = isset($data['remarks']) ? trim($data['remarks']) : '';

            if (!in_array($newStatus, array('Fixed', 'Return', 'Reject'))) {
                caseJsonResponse(array('error' => 'Kaldis users can only set status to Fixed, Return, or Reject'), 403);
            }
            if (empty($remarks)) {
                caseJsonResponse(array('error' => 'Remarks are required when updating case status. Please provide details about your update.'), 400);
            }

            $stmt = $db->prepare("UPDATE case_cases SET status = ?, remarks = CONCAT(IFNULL(remarks, ''), ?), last_modified = NOW() WHERE case_id = ?");
            $updatedRemarks = "\n\n[" . date('Y-m-d H:i:s') . "] " . $username . ": " . $remarks;
            $stmt->execute(array($newStatus, $updatedRemarks, $caseId));

            $logDetails = "Status changed to <b>" . $newStatus . "</b> by " . $username . "\n<b>Remarks:</b> " . mb_substr($remarks, 0, 300);
            logCaseActivity($caseId, "Status → " . $newStatus, $logDetails);

            // Send detailed notification
            $notificationData = array(
                'caseId' => $caseId,
                'caseType' => $currentCase['case_type'],
                'description' => $currentCase['description'],
                'status' => $newStatus,
                'priority' => $currentCase['priority'],
                'createdDate' => $currentCase['created_date'],
                'dueDate' => $currentCase['due_date'],
                'assignedTo' => $currentCase['assigned_to'],
                'createdBy' => $currentCase['created_by'],
                'remarks' => $currentCase['remarks'] . "\n\n[" . date('Y-m-d H:i:s') . "] " . $username . ": " . $remarks,
                'oldStatus' => $currentCase['status']
            );
            caseSendDetailedTelegramNotification('Case Status Updated', $notificationData);

            if (!empty($currentCase['created_by']) && $currentCase['created_by'] !== $username) {
                $msg = "<b>🔄 Case Status Update</b>\n\n"
                    . "<b>Case ID:</b> " . $caseId . "\n"
                    . "<b>Type:</b> " . $currentCase['case_type'] . "\n"
                    . "<b>Priority:</b> " . $currentCase['priority'] . "\n"
                    . "<b>Old Status:</b> " . $currentCase['status'] . "\n"
                    . "<b>New Status:</b> " . $newStatus . "\n\n"
                    . "<b>Description:</b>\n" . $currentCase['description'] . "\n\n"
                    . "<b>Remarks:</b>\n" . mb_substr($remarks, 0, 300);
                caseNotifyAssignee($currentCase['created_by'], $caseId, $msg);
            }
        }

        // CNET ROLE
        elseif ($userRole === 'cnet') {
            $newStatus = isset($data['status']) ? $data['status'] : null;

            if ($newStatus === 'Completed') {
                caseJsonResponse(array('error' => 'CNET users cannot set status to Completed'), 403);
            }
            if ($newStatus === 'Reject') {
                caseJsonResponse(array('error' => 'CNET users cannot set status to Reject'), 403);
            }

            $updateFields = array();
            $updateParams = array();
            $changes = array();

            $fieldMap = array(
                'caseType' => 'case_type', 'description' => 'description',
                'dueDate' => 'due_date', 'status' => 'status',
                'priority' => 'priority', 'assignedTo' => 'assigned_to'
            );

            $nullableCols = array('due_date', 'assigned_to');

            foreach ($fieldMap as $jsonKey => $dbCol) {
                if (array_key_exists($jsonKey, $data)) {
                    $val = $data[$jsonKey];
                    if (in_array($dbCol, $nullableCols) && $val === '') {
                        $val = null;
                    }
                    $updateFields[] = $dbCol . " = ?";
                    $updateParams[] = $val;

                    $oldVal = isset($currentCase[$dbCol]) ? $currentCase[$dbCol] : null;
                    $newVal = $val;
                    if ($oldVal !== $newVal) {
                        $label = str_replace('_', ' ', $dbCol);
                        $changes[] = "<b>" . $label . ":</b> " . ($oldVal ? $oldVal : '—') . " → " . ($newVal ? $newVal : '—');
                    }
                }
            }

            if (isset($data['remarks']) && !empty($data['remarks'])) {
                $newRemark = "\n\n[" . date('Y-m-d H:i:s') . "] " . $username . ": " . trim($data['remarks']);
                $updateFields[] = "remarks = CONCAT(IFNULL(remarks, ''), ?)";
                $updateParams[] = $newRemark;
                $changes[] = "<b>Remarks:</b> Added new note";
            }

            if (empty($updateFields)) {
                caseJsonResponse(array('error' => 'No changes to update. Please modify at least one field.'), 400);
            }

            $updateFields[] = "last_modified = NOW()";
            $updateParams[] = $caseId;

            $sql = "UPDATE case_cases SET " . implode(", ", $updateFields) . " WHERE case_id = ?";
            $db->prepare($sql)->execute($updateParams);

            if (!empty($changes)) {
                $logDetails = "Case updated by " . $username . "\n" . implode("\n", $changes);
                logCaseActivity($caseId, 'Case Updated', $logDetails);
            }

            // Send detailed notification
            $notificationData = array(
                'caseId' => $caseId,
                'caseType' => isset($data['caseType']) ? $data['caseType'] : $currentCase['case_type'],
                'description' => isset($data['description']) ? $data['description'] : $currentCase['description'],
                'status' => isset($data['status']) ? $data['status'] : $currentCase['status'],
                'priority' => isset($data['priority']) ? $data['priority'] : $currentCase['priority'],
                'createdDate' => $currentCase['created_date'],
                'dueDate' => isset($data['dueDate']) ? $data['dueDate'] : $currentCase['due_date'],
                'assignedTo' => isset($data['assignedTo']) ? $data['assignedTo'] : $currentCase['assigned_to'],
                'createdBy' => $currentCase['created_by'],
                'remarks' => isset($data['remarks']) ? $currentCase['remarks'] . "\n\n[" . date('Y-m-d H:i:s') . "] " . $username . ": " . $data['remarks'] : $currentCase['remarks'],
                'changes' => $changes
            );
            caseSendDetailedTelegramNotification('Case Updated', $notificationData);

            if (isset($data['assignedTo']) && $data['assignedTo'] !== $currentCase['assigned_to'] && !empty($data['assignedTo'])) {
                $msg = "<b>📋 Case Assigned to You</b>\n\n"
                    . "<b>Case ID:</b> " . $caseId . "\n"
                    . "<b>Type:</b> " . (isset($data['caseType']) ? $data['caseType'] : $currentCase['case_type']) . "\n"
                    . "<b>Priority:</b> " . (isset($data['priority']) ? $data['priority'] : $currentCase['priority']) . "\n"
                    . "<b>Status:</b> " . (isset($data['status']) ? $data['status'] : $currentCase['status']) . "\n"
                    . "<b>Created:</b> " . $currentCase['created_date'] . "\n"
                    . "<b>Due Date:</b> " . (isset($data['dueDate']) && !empty($data['dueDate']) ? $data['dueDate'] : (!empty($currentCase['due_date']) ? $currentCase['due_date'] : 'Not set')) . "\n\n"
                    . "<b>Description:</b>\n" . (isset($data['description']) ? $data['description'] : $currentCase['description']);
                if (isset($data['remarks']) && !empty($data['remarks'])) {
                    $msg .= "\n\n<b>Remarks:</b>\n" . $data['remarks'];
                }
                caseNotifyAssignee($data['assignedTo'], $caseId, $msg);
            }
        }

        // ADMIN ROLE
        else {
            $updateFields = array();
            $updateParams = array();
            $changes = array();

            $fieldMap = array(
                'caseType' => 'case_type', 'description' => 'description',
                'dueDate' => 'due_date', 'status' => 'status',
                'priority' => 'priority', 'assignedTo' => 'assigned_to'
            );

            $nullableCols = array('due_date', 'assigned_to');

            foreach ($fieldMap as $jsonKey => $dbCol) {
                if (array_key_exists($jsonKey, $data)) {
                    $val = $data[$jsonKey];
                    if (in_array($dbCol, $nullableCols) && $val === '') {
                        $val = null;
                    }
                    $updateFields[] = $dbCol . " = ?";
                    $updateParams[] = $val;

                    $oldVal = isset($currentCase[$dbCol]) ? $currentCase[$dbCol] : null;
                    $newVal = $val;
                    if ($oldVal !== $newVal) {
                        $label = str_replace('_', ' ', $dbCol);
                        $changes[] = "<b>" . $label . ":</b> " . ($oldVal ? $oldVal : '—') . " → " . ($newVal ? $newVal : '—');
                    }
                }
            }

            if (isset($data['remarks']) && !empty($data['remarks'])) {
                $newRemark = "\n\n[" . date('Y-m-d H:i:s') . "] Admin " . $username . ": " . trim($data['remarks']);
                $updateFields[] = "remarks = CONCAT(IFNULL(remarks, ''), ?)";
                $updateParams[] = $newRemark;
                $changes[] = "<b>Remarks:</b> Added admin note";
            }

            if (empty($updateFields)) {
                caseJsonResponse(array('error' => 'No changes to update. Please modify at least one field.'), 400);
            }

            $updateFields[] = "last_modified = NOW()";
            $updateParams[] = $caseId;

            $sql = "UPDATE case_cases SET " . implode(", ", $updateFields) . " WHERE case_id = ?";
            $db->prepare($sql)->execute($updateParams);

            if (!empty($changes)) {
                $logDetails = "Case updated by Admin " . $username . "\n" . implode("\n", $changes);
                logCaseActivity($caseId, 'Case Updated (Admin)', $logDetails);
            }

            // Send detailed notification
            $notificationData = array(
                'caseId' => $caseId,
                'caseType' => isset($data['caseType']) ? $data['caseType'] : $currentCase['case_type'],
                'description' => isset($data['description']) ? $data['description'] : $currentCase['description'],
                'status' => isset($data['status']) ? $data['status'] : $currentCase['status'],
                'priority' => isset($data['priority']) ? $data['priority'] : $currentCase['priority'],
                'createdDate' => $currentCase['created_date'],
                'dueDate' => isset($data['dueDate']) ? $data['dueDate'] : $currentCase['due_date'],
                'assignedTo' => isset($data['assignedTo']) ? $data['assignedTo'] : $currentCase['assigned_to'],
                'createdBy' => $currentCase['created_by'],
                'remarks' => isset($data['remarks']) ? $currentCase['remarks'] . "\n\n[" . date('Y-m-d H:i:s') . "] Admin " . $username . ": " . $data['remarks'] : $currentCase['remarks'],
                'changes' => $changes,
                'updatedBy' => 'Admin ' . $username
            );
            caseSendDetailedTelegramNotification('Case Updated (Admin)', $notificationData);

            if (isset($data['assignedTo']) && $data['assignedTo'] !== $currentCase['assigned_to'] && !empty($data['assignedTo'])) {
                $msg = "<b>📋 Case Assigned to You (Admin Update)</b>\n\n"
                    . "<b>Case ID:</b> " . $caseId . "\n"
                    . "<b>Type:</b> " . (isset($data['caseType']) ? $data['caseType'] : $currentCase['case_type']) . "\n"
                    . "<b>Priority:</b> " . (isset($data['priority']) ? $data['priority'] : $currentCase['priority']) . "\n"
                    . "<b>Status:</b> " . (isset($data['status']) ? $data['status'] : $currentCase['status']) . "\n"
                    . "<b>Created:</b> " . $currentCase['created_date'] . "\n"
                    . "<b>Due Date:</b> " . (isset($data['dueDate']) && !empty($data['dueDate']) ? $data['dueDate'] : (!empty($currentCase['due_date']) ? $currentCase['due_date'] : 'Not set')) . "\n\n"
                    . "<b>Description:</b>\n" . (isset($data['description']) ? $data['description'] : $currentCase['description']);
                if (isset($data['remarks']) && !empty($data['remarks'])) {
                    $msg .= "\n\n<b>Remarks:</b>\n" . $data['remarks'];
                }
                caseNotifyAssignee($data['assignedTo'], $caseId, $msg);
            }
        }

        caseJsonResponse(array('success' => true, 'message' => 'Case updated successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to update case: ' . $e->getMessage()), 500);
    }
}

// ==================== BULK UPDATE FIXED ====================

if ($action === 'bulkUpdateCases') {
    caseRequireAuth();

    $data = casePostBody();
    $caseIds = isset($data['caseIds']) ? $data['caseIds'] : array();
    $newStatus = isset($data['newStatus']) ? trim($data['newStatus']) : '';
    $remarks = isset($data['remarks']) ? trim($data['remarks']) : '';
    $userRole = $_SESSION['case_user']['role'];
    $username = $_SESSION['case_user']['username'];

    if (empty($caseIds) || !is_array($caseIds) || count($caseIds) === 0) {
        caseJsonResponse(array('error' => 'Please select at least one case to update', 'code' => 'NO_CASES_SELECTED'), 400);
    }
    
    if (empty($newStatus)) {
        caseJsonResponse(array('error' => 'Please select a status to apply to the selected cases', 'code' => 'NO_STATUS_SELECTED'), 400);
    }

    if ($userRole === 'kaldis' && !in_array($newStatus, array('Fixed', 'Return', 'Reject'))) {
        caseJsonResponse(array('error' => 'Kaldis users can only bulk update to Fixed, Return, or Reject', 'code' => 'INVALID_STATUS'), 403);
    }
    if ($userRole === 'cnet' && in_array($newStatus, array('Completed', 'Return', 'Reject'))) {
        caseJsonResponse(array('error' => 'CNET users cannot bulk update to Completed, Return, or Reject', 'code' => 'INVALID_STATUS'), 403);
    }
    if ($userRole === 'kaldis' && empty($remarks)) {
        caseJsonResponse(array('error' => 'Remarks are required when updating case status. Please provide details about the bulk update.', 'code' => 'REMARKS_REQUIRED'), 400);
    }

    try {
        $db = caseDB();
        $count = 0;
        $errors = array();

        foreach ($caseIds as $cid) {
            $cid = trim($cid);
            if (empty($cid)) continue;

            $currentCase = caseGetById($db, $cid);
            if (!$currentCase) {
                $errors[] = "Case " . $cid . " not found";
                continue;
            }

            if ($userRole === 'kaldis' && !empty($remarks)) {
                $updatedRemarks = "\n\n[" . date('Y-m-d H:i:s') . "] " . $username . " (Bulk Update): " . $remarks;
                $stmt = $db->prepare("UPDATE case_cases SET status = ?, remarks = CONCAT(IFNULL(remarks, ''), ?), last_modified = NOW() WHERE case_id = ?");
                $stmt->execute(array($newStatus, $updatedRemarks, $cid));
            } else {
                $stmt = $db->prepare("UPDATE case_cases SET status = ?, last_modified = NOW() WHERE case_id = ?");
                $stmt->execute(array($newStatus, $cid));
            }

            $logDetails = "Bulk status change to <b>" . $newStatus . "</b> by " . $username;
            if ($remarks) $logDetails .= "\n<b>Remarks:</b> " . mb_substr($remarks, 0, 200);
            logCaseActivity($cid, 'Bulk Status Update', $logDetails);

            // Send detailed notification
            $notificationData = array(
                'caseId' => $cid,
                'caseType' => $currentCase['case_type'],
                'description' => $currentCase['description'],
                'status' => $newStatus,
                'priority' => $currentCase['priority'],
                'createdDate' => $currentCase['created_date'],
                'dueDate' => $currentCase['due_date'],
                'assignedTo' => $currentCase['assigned_to'],
                'createdBy' => $currentCase['created_by'],
                'remarks' => $currentCase['remarks'] . "\n\n[" . date('Y-m-d H:i:s') . "] " . $username . " (Bulk Update): " . $remarks,
                'oldStatus' => $currentCase['status']
            );
            caseSendDetailedTelegramNotification('Bulk Case Status Update', $notificationData);
            
            $count++;
        }

        if ($count === 0 && !empty($errors)) {
            caseJsonResponse(array('error' => 'Failed to update cases: ' . implode(', ', $errors)), 500);
        }

        $message = $count . " case(s) successfully updated to " . $newStatus;
        if (!empty($errors)) {
            $message .= ". Errors: " . implode(', ', $errors);
        }
        
        caseJsonResponse(array('success' => true, 'count' => $count, 'message' => $message));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to bulk update: ' . $e->getMessage()), 500);
    }
}

// ==================== DELETE CASE ====================

if ($action === 'deleteCase') {
    caseRequireAdmin();

    $data = casePostBody();
    $caseId = isset($data['caseId']) ? trim($data['caseId']) : '';

    if (empty($caseId)) {
        caseJsonResponse(array('error' => 'Case ID is required'), 400);
    }

    try {
        $db = caseDB();
        
        // Get case details before deletion
        $case = caseGetById($db, $caseId);
        
        $db->prepare("DELETE FROM case_comments WHERE case_id = ?")->execute(array($caseId));
        $db->prepare("DELETE FROM case_history WHERE case_id = ?")->execute(array($caseId));
        $db->prepare("DELETE FROM case_cases WHERE case_id = ?")->execute(array($caseId));

        // Send notification about case deletion
        if ($case) {
            $notificationData = array(
                'caseId' => $caseId,
                'caseType' => $case['case_type'],
                'description' => $case['description'],
                'status' => $case['status'],
                'priority' => $case['priority'],
                'createdDate' => $case['created_date'],
                'dueDate' => $case['due_date'],
                'assignedTo' => $case['assigned_to'],
                'createdBy' => $case['created_by'],
                'deletedBy' => $_SESSION['case_user']['username']
            );
            caseSendDetailedTelegramNotification('Case Deleted', $notificationData);
        }

        caseJsonResponse(array('success' => true, 'message' => 'Case deleted successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to delete case: ' . $e->getMessage()), 500);
    }
}

// ==================== COMMENTS ====================

if ($action === 'getComments') {
    caseRequireAuth();

    $caseId = isset($_GET['caseId']) ? trim($_GET['caseId']) : '';
    if (empty($caseId)) {
        caseJsonResponse(array());
    }

    try {
        $db = caseDB();
        $stmt = $db->prepare("SELECT * FROM case_comments WHERE case_id = ? ORDER BY created_at ASC");
        $stmt->execute(array($caseId));
        $comments = $stmt->fetchAll();

        foreach ($comments as &$c) {
            $c['date'] = date('M j, Y g:i A', strtotime($c['created_at']));
        }

        caseJsonResponse($comments);
    } catch (Exception $e) {
        caseJsonResponse(array());
    }
}

if ($action === 'addComment') {
    caseRequireAuth();

    $data = casePostBody();
    $caseId = isset($data['caseId']) ? trim($data['caseId']) : '';
    $comment = isset($data['comment']) ? trim($data['comment']) : '';

    if (empty($caseId)) {
        caseJsonResponse(array('error' => 'Case ID is required'), 400);
    }
    if (empty($comment)) {
        caseJsonResponse(array('error' => 'Comment cannot be empty'), 400);
    }

    try {
        $db = caseDB();

        if (!caseGetById($db, $caseId)) {
            caseJsonResponse(array('error' => 'Case not found'), 404);
        }

        $username = $_SESSION['case_user']['username'];
        $stmt = $db->prepare("INSERT INTO case_comments (case_id, author, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute(array($caseId, $username, $comment));

        logCaseActivity($caseId, 'Comment Added', "Comment by " . $username . ": " . mb_substr($comment, 0, 200));

        // Send notification about new comment
        $case = caseGetById($db, $caseId);
        if ($case) {
            $notificationData = array(
                'caseId' => $caseId,
                'caseType' => $case['case_type'],
                'description' => $case['description'],
                'status' => $case['status'],
                'priority' => $case['priority'],
                'createdDate' => $case['created_date'],
                'dueDate' => $case['due_date'],
                'assignedTo' => $case['assigned_to'],
                'createdBy' => $case['created_by'],
                'comment' => $comment,
                'commentedBy' => $username
            );
            caseSendDetailedTelegramNotification('New Comment Added', $notificationData);
        }

        caseJsonResponse(array('success' => true, 'message' => 'Comment added successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to add comment: ' . $e->getMessage()), 500);
    }
}

if ($action === 'getCaseHistory') {
    caseRequireAuth();

    $caseId = isset($_GET['caseId']) ? trim($_GET['caseId']) : '';
    if (empty($caseId)) {
        caseJsonResponse(array());
    }

    try {
        $db = caseDB();
        $stmt = $db->prepare("SELECT * FROM case_history WHERE case_id = ? ORDER BY timestamp DESC");
        $stmt->execute(array($caseId));
        $history = $stmt->fetchAll();

        foreach ($history as &$h) {
            $h['date'] = date('M j, Y g:i A', strtotime($h['timestamp']));
        }

        caseJsonResponse($history);
    } catch (Exception $e) {
        caseJsonResponse(array());
    }
}

// ==================== ASSIGNEES ====================

if ($action === 'getAssignees') {
    caseRequireAuth();

    try {
        $db = caseDB();
        $stmt = $db->query("SELECT name, telegram_id AS telegramId, status FROM case_assignees ORDER BY name");
        $result = $stmt->fetchAll();
        caseJsonResponse($result ? $result : array());
    } catch (Exception $e) {
        caseJsonResponse(array());
    }
}

if ($action === 'saveAssignee') {
    caseRequireAdmin();

    $data = casePostBody();
    $name = isset($data['name']) ? trim($data['name']) : '';
    $telegramId = isset($data['telegramId']) ? trim($data['telegramId']) : '';
    $status = isset($data['status']) ? $data['status'] : 'active';

    if (empty($name)) {
        caseJsonResponse(array('error' => 'Assignee name is required'), 400);
    }
    if (!in_array($status, array('active', 'inactive'))) {
        caseJsonResponse(array('error' => 'Status must be active or inactive'), 400);
    }

    try {
        $db = caseDB();
        $stmt = $db->prepare("INSERT INTO case_assignees (name, telegram_id, status) VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE telegram_id = VALUES(telegram_id), status = VALUES(status)");
        $stmt->execute(array($name, !empty($telegramId) ? $telegramId : null, $status));

        caseJsonResponse(array('success' => true, 'message' => 'Assignee saved successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to save assignee: ' . $e->getMessage()), 500);
    }
}

if ($action === 'deleteAssignee') {
    caseRequireAdmin();

    $data = casePostBody();
    $name = isset($data['name']) ? trim($data['name']) : '';

    if (empty($name)) {
        caseJsonResponse(array('error' => 'Assignee name is required'), 400);
    }

    try {
        $db = caseDB();

        $check = $db->prepare("SELECT COUNT(*) FROM case_cases WHERE assigned_to = ? AND status NOT IN ('Completed', 'Reject')");
        $check->execute(array($name));
        $activeCount = (int)$check->fetchColumn();

        if ($activeCount > 0) {
            caseJsonResponse(array('error' => "Cannot delete: " . $name . " is assigned to " . $activeCount . " active case(s)"), 400);
        }

        $stmt = $db->prepare("DELETE FROM case_assignees WHERE name = ?");
        $stmt->execute(array($name));

        caseJsonResponse(array('success' => true, 'message' => 'Assignee deleted successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to delete assignee: ' . $e->getMessage()), 500);
    }
}

// ==================== USERS ====================

if ($action === 'getUsers') {
    caseRequireAdmin();

    try {
        $db = caseDB();
        $stmt = $db->query("SELECT username, role, status, last_login FROM case_users ORDER BY username");
        $result = $stmt->fetchAll();
        caseJsonResponse($result ? $result : array());
    } catch (Exception $e) {
        caseJsonResponse(array());
    }
}

if ($action === 'saveUser') {
    caseRequireAdmin();

    $data = casePostBody();
    $username = isset($data['username']) ? trim($data['username']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $role = isset($data['role']) ? $data['role'] : 'kaldis';
    $status = isset($data['status']) ? $data['status'] : 'active';

    if (empty($username)) {
        caseJsonResponse(array('error' => 'Username is required'), 400);
    }
    if (strlen($username) < 3) {
        caseJsonResponse(array('error' => 'Username must be at least 3 characters'), 400);
    }
    if (!in_array($role, array('admin', 'kaldis', 'cnet'))) {
        caseJsonResponse(array('error' => 'Role must be admin, kaldis, or cnet'), 400);
    }
    if (!in_array($status, array('active', 'inactive'))) {
        caseJsonResponse(array('error' => 'Status must be active or inactive'), 400);
    }

    try {
        $db = caseDB();

        if (!empty($password)) {
            if (strlen($password) < 4) {
                caseJsonResponse(array('error' => 'Password must be at least 4 characters'), 400);
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO case_users (username, password, role, status) VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), status = VALUES(status)");
            $stmt->execute(array($username, $hash, $role, $status));
        } else {
            $stmt = $db->prepare("INSERT INTO case_users (username, role, status) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status)");
            $stmt->execute(array($username, $role, $status));
        }

        caseJsonResponse(array('success' => true, 'message' => 'User saved successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to save user: ' . $e->getMessage()), 500);
    }
}

if ($action === 'deleteUser') {
    caseRequireAdmin();

    $data = casePostBody();
    $username = isset($data['username']) ? trim($data['username']) : '';

    if (empty($username)) {
        caseJsonResponse(array('error' => 'Username is required'), 400);
    }
    if ($username === $_SESSION['case_user']['username']) {
        caseJsonResponse(array('error' => 'You cannot delete your own account'), 400);
    }

    try {
        $db = caseDB();
        $stmt = $db->prepare("DELETE FROM case_users WHERE username = ?");
        $stmt->execute(array($username));

        caseJsonResponse(array('success' => true, 'message' => 'User deleted successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to delete user: ' . $e->getMessage()), 500);
    }
}

// ==================== CASE TYPES ====================

if ($action === 'getCaseTypes') {
    caseRequireAuth();

    try {
        $db = caseDB();
        $stmt = $db->query("SELECT name FROM case_types ORDER BY name");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        caseJsonResponse($types ? $types : array('Support', 'Project', 'Maintenance'));
    } catch (Exception $e) {
        caseJsonResponse(array('Support', 'Project', 'Maintenance'));
    }
}

if ($action === 'addCaseType') {
    caseRequireAdmin();

    $data = casePostBody();
    $typeName = isset($data['name']) ? trim($data['name']) : '';

    if (empty($typeName)) {
        caseJsonResponse(array('error' => 'Case type name is required'), 400);
    }
    if (strlen($typeName) > 100) {
        caseJsonResponse(array('error' => 'Case type name is too long (max 100 characters)'), 400);
    }

    try {
        $db = caseDB();

        $check = $db->prepare("SELECT name FROM case_types WHERE name = ?");
        $check->execute(array($typeName));
        if ($check->fetch()) {
            caseJsonResponse(array('error' => "Case type '" . $typeName . "' already exists"), 400);
        }

        $stmt = $db->prepare("INSERT INTO case_types (name) VALUES (?)");
        $stmt->execute(array($typeName));

        caseJsonResponse(array('success' => true, 'message' => 'Case type added successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to add case type: ' . $e->getMessage()), 500);
    }
}

if ($action === 'deleteCaseType') {
    caseRequireAdmin();

    $data = casePostBody();
    $typeName = isset($data['name']) ? trim($data['name']) : '';

    if (empty($typeName)) {
        caseJsonResponse(array('error' => 'Case type name is required'), 400);
    }

    try {
        $db = caseDB();

        $check = $db->prepare("SELECT COUNT(*) FROM case_cases WHERE case_type = ? AND status NOT IN ('Completed', 'Reject')");
        $check->execute(array($typeName));
        $activeCount = (int)$check->fetchColumn();

        if ($activeCount > 0) {
            caseJsonResponse(array('error' => "Cannot delete: " . $activeCount . " active case(s) use this type"), 400);
        }

        $stmt = $db->prepare("DELETE FROM case_types WHERE name = ?");
        $stmt->execute(array($typeName));

        caseJsonResponse(array('success' => true, 'message' => 'Case type deleted successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to delete case type: ' . $e->getMessage()), 500);
    }
}

// ==================== SETTINGS ====================

if ($action === 'getSettings') {
    caseRequireAdmin();

    try {
        $db = caseDB();
        $defaults = array(
            'companyName' => 'Kaldis Coffee', 'companyPhone' => '',
            'logoUrl' => '', 'telegramBotToken' => '', 'telegramGroupChatId' => ''
        );

        $stmt = $db->query("SELECT setting_key, setting_value FROM case_settings");
        while ($row = $stmt->fetch()) {
            if (isset($defaults[$row['setting_key']])) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        }

        caseJsonResponse($defaults);
    } catch (Exception $e) {
        caseJsonResponse(array(
            'companyName' => 'Kaldis Coffee', 'companyPhone' => '',
            'logoUrl' => '', 'telegramBotToken' => '', 'telegramGroupChatId' => ''
        ));
    }
}

if ($action === 'saveSettings') {
    caseRequireAdmin();

    $data = casePostBody();
    $allowedKeys = array('companyName', 'companyPhone', 'logoUrl', 'telegramBotToken', 'telegramGroupChatId');

    try {
        $db = caseDB();

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedKeys)) continue;

            $stmt = $db->prepare("INSERT INTO case_settings (setting_key, setting_value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(array($key, $value));
        }

        caseJsonResponse(array('success' => true, 'message' => 'Settings saved successfully'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to save settings: ' . $e->getMessage()), 500);
    }
}

// ==================== FILE UPLOAD ====================

if ($action === 'uploadFileToDrive') {
    caseRequireKaldis();

    $data = casePostBody();
    $base64 = isset($data['base64']) ? $data['base64'] : '';
    $filename = isset($data['fileName']) ? $data['fileName'] : '';

    if (empty($base64) || empty($filename)) {
        caseJsonResponse(array('error' => 'File data and filename are required'), 400);
    }

    if (strlen($base64) > 14000000) {
        caseJsonResponse(array('error' => 'File size exceeds 10MB limit'), 400);
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'xlsx', 'xls', 'docx', 'doc', 'txt', 'csv');

    if (!in_array($ext, $allowedExtensions)) {
        caseJsonResponse(array('error' => "File type ." . $ext . " is not allowed"), 400);
    }

    try {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFilename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        $filePath = $uploadDir . $newFilename;

        $base64Clean = preg_replace('#^data:[^;]+;base64,#', '', $base64);
        $fileData = base64_decode($base64Clean);
        if ($fileData === false) {
            caseJsonResponse(array('error' => 'Failed to decode file data'), 400);
        }

        file_put_contents($filePath, $fileData);

        $url = caseBaseUrl() . "/uploads/" . $newFilename;

        caseJsonResponse(array('success' => true, 'url' => $url, 'filename' => $newFilename));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to upload file: ' . $e->getMessage()), 500);
    }
}

// ==================== SLA ====================

if ($action === 'getSLAData') {
    caseRequireAuth();

    $filters = array();
    if (isset($_GET['filters'])) {
        $filters = json_decode($_GET['filters'], true);
        if (!is_array($filters)) $filters = array();
    }

    try {
        $db = caseDB();

        $activeStatuses = array('Not Started', 'In Progress', 'Fixed', 'Return');

        $sql = "SELECT * FROM case_cases WHERE status IN ('" . implode("','", $activeStatuses) . "')";
        $params = array();

        if (!empty($filters['priority']) && $filters['priority'] !== 'All') {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cases = $stmt->fetchAll();

        $activeCases = array();
        $overdueCount = 0;
        $criticalCount = 0;
        $today = date('Y-m-d');

        foreach ($cases as $case) {
            if ($case['priority'] === 'Critical') $criticalCount++;

            $daysLeft = null;
            if (!empty($case['due_date'])) {
                $diff = (strtotime($case['due_date']) - strtotime($today)) / 86400;
                $daysLeft = (int)round($diff);
                if ($daysLeft < 0) $overdueCount++;
            }

            if (!empty($filters['overdueOnly']) && $daysLeft >= 0) continue;

            $activeCases[] = array(
                'case_id' => $case['case_id'],
                'caseId' => $case['case_id'],
                'description' => $case['description'],
                'remarks' => !empty($case['remarks']) ? $case['remarks'] : '',
                'dueDate' => $case['due_date'],
                'daysLeft' => $daysLeft,
                'priority' => $case['priority'],
                'status' => $case['status'],
                'assignedTo' => !empty($case['assigned_to']) ? $case['assigned_to'] : ''
            );
        }

        usort($activeCases, function ($a, $b) {
            $aVal = isset($a['daysLeft']) ? $a['daysLeft'] : 999;
            $bVal = isset($b['daysLeft']) ? $b['daysLeft'] : 999;
            return $aVal - $bVal;
        });

        caseJsonResponse(array(
            'overdueCount' => $overdueCount,
            'criticalCount' => $criticalCount,
            'activeCases' => array_slice($activeCases, 0, 200)
        ));
    } catch (Exception $e) {
        caseJsonResponse(array('overdueCount' => 0, 'criticalCount' => 0, 'activeCases' => array()));
    }
}

if ($action === 'sendFilteredSLAReport') {
    caseRequireAdmin();

    $filters = casePostBody();
    if (!is_array($filters)) $filters = array();

    try {
        $db = caseDB();

        $activeStatuses = array('Not Started', 'In Progress', 'Fixed', 'Return');

        $sql = "SELECT * FROM case_cases WHERE status IN ('" . implode("','", $activeStatuses) . "')";
        $params = array();

        if (!empty($filters['priority']) && $filters['priority'] !== 'All') {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cases = $stmt->fetchAll();

        $active = array();
        $today = date('Y-m-d');
        $overdueCount = 0;

        foreach ($cases as $case) {
            $daysLeft = null;
            if (!empty($case['due_date'])) {
                $diff = (strtotime($case['due_date']) - strtotime($today)) / 86400;
                $daysLeft = (int)round($diff);
                if ($daysLeft < 0) $overdueCount++;
            }
            if (!empty($filters['overdueOnly']) && $daysLeft >= 0) continue;
            $active[] = $case;
        }

        if (empty($active)) {
            caseJsonResponse(array('message' => 'No cases match the selected filters', 'code' => 'NO_CASES'));
        } else {
            $priorityEmoji = array('Critical' => '🔴', 'Medium' => '🟡', 'Low' => '🟢');
            $totalCases = count($active);

            $message = "📊 <b>SLA Report</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━\n";
            $message .= "📅 Generated: " . date('M j, Y H:i') . "\n";
            $message .= "📋 Filtered Cases: " . $totalCases . "\n";
            $message .= "⚠️ Overdue: " . $overdueCount . "\n";
            $message .= "━━━━━━━━━━━━━━━━━━\n\n";

            $displayCases = array_slice($active, 0, 25);
            $displayCount = count($displayCases);

            for ($i = 0; $i < $displayCount; $i++) {
                $c = $displayCases[$i];
                $emoji = isset($priorityEmoji[$c['priority']]) ? $priorityEmoji[$c['priority']] : '⚪';
                $overdueTag = '';

                if (!empty($c['due_date'])) {
                    $diff = (int)round((strtotime($c['due_date']) - strtotime($today)) / 86400);
                    if ($diff < 0) $overdueTag = " | ⚠️ <b>" . abs($diff) . "d OVERDUE</b>";
                    elseif ($diff <= 2) $overdueTag = " | ⏰ " . $diff . "d left";
                }

                $message .= $emoji . " <b>" . $c['case_id'] . "</b> — " . $c['status'] . $overdueTag . "\n";
                $desc = mb_substr($c['description'], 0, 80);
                if (mb_strlen($c['description']) > 80) $desc .= '...';
                $message .= "   📌 " . $desc . "\n";

                if (!empty($c['remarks'])) {
                    $rem = mb_substr($c['remarks'], 0, 80);
                    if (mb_strlen($c['remarks']) > 80) $rem .= '...';
                    $message .= "   💬 " . $rem . "\n";
                }

                $assigned = !empty($c['assigned_to']) ? $c['assigned_to'] : 'Unassigned';
                $due = !empty($c['due_date']) ? $c['due_date'] : 'N/A';
                $message .= "   👤 " . $assigned . " | 📅 " . $due . "\n";

                if ($i < $displayCount - 1) $message .= "\n";
            }

            if ($totalCases > 25) {
                $remaining = $totalCases - 25;
                $message .= "\n\n... and " . $remaining . " more cases.";
            }

            $sent = caseTelegramSend($message);

            if ($sent) {
                caseJsonResponse(array('message' => "SLA report sent to Telegram (" . $totalCases . " cases, " . $overdueCount . " overdue)", 'success' => true));
            } else {
                $tg = caseTelegramSettings();
                $reason = (!$tg['token'] || !$tg['chatId']) ? 'Telegram not configured' : 'Failed to send';
                caseJsonResponse(array('message' => $totalCases . " cases found, but: " . $reason, 'error' => true));
            }
        }
    } catch (Exception $e) {
        caseJsonResponse(array('message' => 'Error generating report: ' . $e->getMessage(), 'error' => true));
    }
}

// ==================== EXPORT ====================

if ($action === 'exportFilteredCases') {
    caseRequireAuth();

    $filtersRaw = isset($_GET['filters']) ? $_GET['filters'] : '{}';
    $filters = json_decode($filtersRaw, true);

    if (!is_array($filters) || empty($filters)) {
        $filters = array();
        if (!empty($_GET['term'])) $filters['term'] = $_GET['term'];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];
        if (!empty($_GET['assignedTo'])) $filters['assignedTo'] = $_GET['assignedTo'];
        if (!empty($_GET['startDate'])) $filters['startDate'] = $_GET['startDate'];
        if (!empty($_GET['endDate'])) $filters['endDate'] = $_GET['endDate'];
    }

    try {
        $db = caseDB();

        $sql = "SELECT case_id, case_type, description, created_date, due_date, status, priority, assigned_to, created_by, remarks, last_modified FROM case_cases WHERE 1=1";
        $params = array();

        if (!empty($filters['term'])) {
            $sql .= " AND (case_id LIKE ? OR description LIKE ? OR remarks LIKE ?)";
            $like = "%" . $filters['term'] . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['assignedTo'])) {
            $sql .= " AND assigned_to = ?";
            $params[] = $filters['assignedTo'];
        }
        if (!empty($filters['startDate'])) {
            $sql .= " AND created_date >= ?";
            $params[] = $filters['startDate'];
        }
        if (!empty($filters['endDate'])) {
            $sql .= " AND created_date <= ?";
            $params[] = $filters['endDate'];
        }

        $sql .= " ORDER BY created_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cases = $stmt->fetchAll();

        if (empty($cases)) {
            caseJsonResponse(array('error' => 'No cases match the current filters to export'), 404);
        }

        $csv = "\xEF\xBB\xBF";
        $csv .= "\"Case ID\",\"Case Type\",\"Description\",\"Created Date\",\"Due Date\",\"Status\",\"Priority\",\"Assigned To\",\"Created By\",\"Remarks\",\"Last Modified\"\n";

        foreach ($cases as $c) {
            $csv .= "\"" . str_replace('"', '""', isset($c['case_id']) ? $c['case_id'] : '') . "\",";
            $csv .= "\"" . str_replace('"', '""', isset($c['case_type']) ? $c['case_type'] : '') . "\",";
            $csv .= "\"" . str_replace('"', '""', isset($c['description']) ? $c['description'] : '') . "\",";
            $csv .= "\"" . (isset($c['created_date']) ? $c['created_date'] : '') . "\",";
            $csv .= "\"" . (isset($c['due_date']) ? $c['due_date'] : '') . "\",";
            $csv .= "\"" . (isset($c['status']) ? $c['status'] : '') . "\",";
            $csv .= "\"" . (isset($c['priority']) ? $c['priority'] : '') . "\",";
            $csv .= "\"" . str_replace('"', '""', isset($c['assigned_to']) ? $c['assigned_to'] : '') . "\",";
            $csv .= "\"" . str_replace('"', '""', isset($c['created_by']) ? $c['created_by'] : '') . "\",";
            $csv .= "\"" . str_replace('"', '""', isset($c['remarks']) ? $c['remarks'] : '') . "\",";
            $csv .= "\"" . (isset($c['last_modified']) ? $c['last_modified'] : '') . "\"\n";
        }

        $exportDir = __DIR__ . '/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = 'cases_export_' . date('Y-m-d_H-i-s') . '.csv';
        file_put_contents($exportDir . $filename, $csv);

        $url = caseBaseUrl() . "/exports/" . $filename;

        caseJsonResponse(array('success' => true, 'url' => $url, 'count' => count($cases), 'filename' => $filename));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Export failed: ' . $e->getMessage()), 500);
    }
}

// ==================== TEST TELEGRAM ====================

if ($action === 'testTelegram') {
    caseRequireAdmin();

    try {
        $tg = caseTelegramSettings();

        if (!$tg['token'] || !$tg['chatId']) {
            caseJsonResponse(array('error' => 'Telegram not configured. Please set Bot Token and Group Chat ID in settings.'), 400);
        }

        $message = "🧪 <b>Test Message</b>\n\n"
            . "✅ Kaldis CNET Case Management System\n"
            . "✅ Telegram integration is working correctly!\n"
            . "🕐 " . date('M j, Y H:i:s');

        $sent = caseTelegramSend($message);

        if ($sent) {
            caseJsonResponse(array('success' => true, 'message' => 'Test message sent successfully'));
        } else {
            caseJsonResponse(array('error' => 'Failed to send. Please verify your Bot Token and Chat ID are correct.'), 500);
        }
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Test failed: ' . $e->getMessage()), 500);
    }
}

if ($action === 'getAvailableUsernames') {
    try {
        $db = caseDB();
        $stmt = $db->query("SELECT username FROM case_users WHERE status = 'active' ORDER BY username");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        caseJsonResponse(array('success' => true, 'users' => $users));
    } catch (Exception $e) {
        caseJsonResponse(array('success' => true, 'users' => array('admin', 'kaldis_user', 'cnet_user')));
    }
}

// ==================== PASSWORD RESET ENDPOINTS ====================

if ($action === 'requestPasswordReset') {
    $data = casePostBody();
    $username = isset($data['username']) ? trim($data['username']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    
    if (empty($username) || empty($email)) {
        caseJsonResponse(array('error' => 'Username and email are required'), 400);
    }
    
    try {
        $db = caseDB();
        $stmt = $db->prepare("SELECT * FROM case_users WHERE username = ? AND email = ? AND status = 'active'");
        $stmt->execute(array($username, $email));
        $user = $stmt->fetch();
        
        if (!$user) {
            caseJsonResponse(array('error' => 'User not found or email mismatch'), 404);
        }
        
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $db->prepare("INSERT INTO case_password_resets (username, otp, expires_at) VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE otp = ?, expires_at = ?");
        $stmt->execute(array($username, $otp, $expires, $otp, $expires));
        
        error_log("Password reset OTP for {$username}: {$otp}");
        
        caseJsonResponse(array('success' => true, 'message' => 'Verification code sent'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Failed to process request: ' . $e->getMessage()), 500);
    }
}

if ($action === 'verifyOTP') {
    $data = casePostBody();
    $username = isset($data['username']) ? trim($data['username']) : '';
    $otp = isset($data['otp']) ? trim($data['otp']) : '';
    
    if (empty($username) || empty($otp)) {
        caseJsonResponse(array('error' => 'Username and OTP are required'), 400);
    }
    
    try {
        $db = caseDB();
        $stmt = $db->prepare("SELECT * FROM case_password_resets WHERE username = ? AND otp = ? AND expires_at > NOW()");
        $stmt->execute(array($username, $otp));
        $reset = $stmt->fetch();
        
        if (!$reset) {
            caseJsonResponse(array('error' => 'Invalid or expired verification code'), 400);
        }
        
        $token = bin2hex(random_bytes(32));
        $stmt = $db->prepare("UPDATE case_password_resets SET token = ? WHERE username = ?");
        $stmt->execute(array($token, $username));
        
        caseJsonResponse(array('success' => true, 'token' => $token));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Verification failed: ' . $e->getMessage()), 500);
    }
}

if ($action === 'resetPassword') {
    $data = casePostBody();
    $username = isset($data['username']) ? trim($data['username']) : '';
    $token = isset($data['token']) ? trim($data['token']) : '';
    $newPassword = isset($data['newPassword']) ? $data['newPassword'] : '';
    $securityQuestion = isset($data['securityQuestion']) ? trim($data['securityQuestion']) : '';
    $securityAnswer = isset($data['securityAnswer']) ? trim($data['securityAnswer']) : '';
    
    if (empty($username) || empty($token) || empty($newPassword)) {
        caseJsonResponse(array('error' => 'Missing required fields'), 400);
    }
    
    if (strlen($newPassword) < 6) {
        caseJsonResponse(array('error' => 'Password must be at least 6 characters'), 400);
    }
    
    try {
        $db = caseDB();
        
        $stmt = $db->prepare("SELECT * FROM case_password_resets WHERE username = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute(array($username, $token));
        $reset = $stmt->fetch();
        
        if (!$reset) {
            caseJsonResponse(array('error' => 'Invalid or expired reset token'), 400);
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE case_users SET password = ?, security_question = ?, security_answer = ? WHERE username = ?");
        $stmt->execute(array($hashedPassword, $securityQuestion, $securityAnswer, $username));
        
        $stmt = $db->prepare("DELETE FROM case_password_resets WHERE username = ?");
        $stmt->execute(array($username));
        
        caseJsonResponse(array('success' => true, 'message' => 'Password reset successful'));
    } catch (Exception $e) {
        caseJsonResponse(array('error' => 'Password reset failed: ' . $e->getMessage()), 500);
    }
}

// ==================== DETAILED TELEGRAM NOTIFICATION FUNCTION ====================

function caseSendDetailedTelegramNotification($action, $caseData) {
    $tg = caseTelegramSettings();
    if (!$tg['token'] || !$tg['chatId']) return false;

    $priorityEmoji = array('Critical' => '🔴', 'Medium' => '🟡', 'Low' => '🟢');
    $statusEmoji = array(
        'Not Started' => '⏳', 'In Progress' => '🔄', 'Fixed' => '✅',
        'Return' => '🔙', 'Reject' => '❌', 'Completed' => '✅'
    );
    
    $emoji = isset($priorityEmoji[$caseData['priority']]) ? $priorityEmoji[$caseData['priority']] : '⚪';
    $statusEmoji = isset($statusEmoji[$caseData['status']]) ? $statusEmoji[$caseData['status']] : '📋';
    
    $message = "<b>📋 " . htmlspecialchars($action) . "</b>\n";
    $message .= "━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "<b>Case ID:</b> " . $caseData['caseId'] . "\n";
    $message .= "<b>Type:</b> " . $caseData['caseType'] . "\n";
    $message .= "<b>Priority:</b> " . $emoji . " " . $caseData['priority'] . "\n";
    $message .= "<b>Status:</b> " . $statusEmoji . " " . $caseData['status'] . "\n\n";
    
    $message .= "<b>Created:</b> " . $caseData['createdDate'] . "\n";
    $message .= "<b>Due Date:</b> " . ($caseData['dueDate'] ? $caseData['dueDate'] : 'Not set') . "\n";
    $message .= "<b>Assigned To:</b> " . ($caseData['assignedTo'] ? $caseData['assignedTo'] : 'Unassigned') . "\n";
    $message .= "<b>Created By:</b> " . $caseData['createdBy'] . "\n\n";
    
    $message .= "<b>Description:</b>\n" . $caseData['description'] . "\n\n";
    
    if (!empty($caseData['remarks'])) {
        $message .= "<b>Remarks:</b>\n" . $caseData['remarks'] . "\n\n";
    }
    
    if (!empty($caseData['attachments'])) {
        $message .= "<b>Attachments:</b>\n" . $caseData['attachments'] . "\n\n";
    }
    
    if (isset($caseData['oldStatus'])) {
        $message .= "<b>Previous Status:</b> " . $caseData['oldStatus'] . "\n\n";
    }
    
    if (isset($caseData['changes']) && is_array($caseData['changes'])) {
        $message .= "<b>Changes Made:</b>\n";
        foreach ($caseData['changes'] as $change) {
            $message .= "• " . $change . "\n";
        }
        $message .= "\n";
    }
    
    if (isset($caseData['commentedBy']) && isset($caseData['comment'])) {
        $message .= "<b>New Comment by " . $caseData['commentedBy'] . ":</b>\n";
        $message .= $caseData['comment'] . "\n\n";
    }
    
    if (isset($caseData['updatedBy'])) {
        $message .= "<b>Updated By:</b> " . $caseData['updatedBy'] . "\n\n";
    }
    
    if (isset($caseData['deletedBy'])) {
        $message .= "<b>Deleted By:</b> " . $caseData['deletedBy'] . "\n\n";
    }
    
    $message .= "🕐 " . date('M j, Y H:i:s');
    
    return caseTelegramSend($message);
}

// ==================== FALLBACK ====================
caseJsonResponse(array('error' => 'Unknown action: ' . $action), 400);