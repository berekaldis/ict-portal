<?php
// includes/functions.php - COMPLETE CLEAN FILE

// ============ LOGGING ============
function logAction($username, $action, $details) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO sales_history (username, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$username, $action, $details]);
    } catch(PDOException $e) {
        // Silent fail for logging
    }
}

// ============ CASE ID GENERATION ============
function generateCaseId() {
    try {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        $stmt = $db->query("SELECT case_number FROM sales_case_sequence ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $result = $stmt->fetch();
        $nextNum = ($result['case_number'] ?? 0) + 1;
        
        $stmt = $db->prepare("INSERT INTO sales_case_sequence (case_number) VALUES (?)");
        $stmt->execute([$nextNum]);
        $db->commit();
        
        return "CASE-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT) . "-FIN";
        
    } catch (PDOException $e) {
        if (isset($db)) $db->rollBack();
        return "CASE-" . date('YmdHis') . "-FIN";
    }
}

// ============ FILE UPLOAD FUNCTIONS ============
function getCaseAttachments($caseId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM sales_case_attachments WHERE case_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$caseId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function deleteCaseAttachment($attachmentId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT file_path FROM sales_case_attachments WHERE id = ?");
        $stmt->execute([$attachmentId]);
        $attachment = $stmt->fetch();
        
        if ($attachment) {
            if (file_exists($attachment['file_path'])) unlink($attachment['file_path']);
            $stmt = $db->prepare("DELETE FROM sales_case_attachments WHERE id = ?");
            $stmt->execute([$attachmentId]);
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Attachment not found'];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


// ============ GET BRANCHES - FIXED ============
function getBranches() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM sales_branches ORDER BY name ASC");
        $result = $stmt->fetchAll();
        
        // Debug: Log the result
        error_log("getBranches result: " . print_r($result, true));
        
        return $result;
    } catch(PDOException $e) {
        error_log("getBranches Error: " . $e->getMessage());
        return [];
    }
}

// ============ GET BRANCHES FOR SELECT DROPDOWN ============
function getBranchesForSelect() {
    $branches = getBranches();
    if (empty($branches)) {
        return '<option value="">No branches available. Please add branches in Settings.</option>';
    }
    
    $options = '';
    foreach ($branches as $branch) {
        $options .= '<option value="' . htmlspecialchars($branch['id']) . '">' 
                    . htmlspecialchars($branch['name']) . '</option>';
    }
    return $options;
}

function addBranch($name) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO sales_branches (name) VALUES (?)");
        $stmt->execute([$name]);
        logAction($_SESSION['username'] ?? 'System', 'Add Branch', "Added branch $name");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function deleteBranch($id) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM sales_branches WHERE id = ?");
        $stmt->execute([$id]);
        logAction($_SESSION['username'] ?? 'System', 'Delete Branch', "Deleted branch ID: $id");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============ TECHNICIAN FUNCTIONS ============
function getTechnicians() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM sales_technicians ORDER BY name");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function addTechnician($name) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO sales_technicians (name) VALUES (?)");
        $stmt->execute([$name]);
        logAction($_SESSION['username'] ?? 'System', 'Add Technician', "Added technician $name");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function deleteTechnician($id) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM sales_technicians WHERE id = ?");
        $stmt->execute([$id]);
        logAction($_SESSION['username'] ?? 'System', 'Delete Technician', "Deleted technician ID: $id");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============ USER FUNCTIONS ============
function getUsers() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT id, username, role, first_login, last_activity FROM sales_users ORDER BY username");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// ============ ADD USER - FIXED ============
function addUser($username, $password, $role) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if username already exists
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales_users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()['count'] > 0) {
            return ['success' => false, 'error' => 'Username already exists'];
        }
        
        // Get table columns to determine correct insert
        $stmt = $db->query("DESCRIBE sales_users");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        // Build insert query based on existing columns
        if (in_array('first_login', $columnNames) && in_array('last_activity', $columnNames)) {
            // Full table with all columns
            $stmt = $db->prepare("INSERT INTO sales_users (username, password, role, first_login, last_activity, created_at) VALUES (?, MD5(?), ?, 1, NOW(), NOW())");
            $stmt->execute([$username, $password, $role]);
        } elseif (in_array('first_login', $columnNames)) {
            // Table has first_login but not last_activity
            $stmt = $db->prepare("INSERT INTO sales_users (username, password, role, first_login) VALUES (?, MD5(?), ?, 1)");
            $stmt->execute([$username, $password, $role]);
        } else {
            // Minimal table
            $stmt = $db->prepare("INSERT INTO sales_users (username, password, role) VALUES (?, MD5(?), ?)");
            $stmt->execute([$username, $password, $role]);
        }
        
        logAction($_SESSION['username'] ?? 'System', 'Add User', "Added user $username");
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("addUser Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}


// ============ UPDATE USER - FIXED ============
function updateUser($userId, $username, $password = null, $role) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get table columns
        $stmt = $db->query("DESCRIBE sales_users");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        if ($password) {
            // Update with password reset
            if (in_array('first_login', $columnNames) && in_array('last_activity', $columnNames)) {
                $stmt = $db->prepare("UPDATE sales_users SET username = ?, password = MD5(?), role = ?, first_login = 1, last_activity = NOW() WHERE id = ?");
                $stmt->execute([$username, $password, $role, $userId]);
            } elseif (in_array('first_login', $columnNames)) {
                $stmt = $db->prepare("UPDATE sales_users SET username = ?, password = MD5(?), role = ?, first_login = 1 WHERE id = ?");
                $stmt->execute([$username, $password, $role, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE sales_users SET username = ?, password = MD5(?), role = ? WHERE id = ?");
                $stmt->execute([$username, $password, $role, $userId]);
            }
        } else {
            // Update without password change
            if (in_array('last_activity', $columnNames)) {
                $stmt = $db->prepare("UPDATE sales_users SET username = ?, role = ?, last_activity = NOW() WHERE id = ?");
                $stmt->execute([$username, $role, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE sales_users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $role, $userId]);
            }
        }
        
        logAction($_SESSION['username'] ?? 'System', 'Update User', "Updated user $username");
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("updateUser Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteUser($userId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM sales_users WHERE role = 'Admin'");
        $adminCount = $stmt->fetch()['count'];
        
        $stmt = $db->prepare("SELECT role FROM sales_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user['role'] === 'Admin' && $adminCount <= 1) {
            return ['success' => false, 'error' => 'Cannot delete the last admin user'];
        }
        
        $stmt = $db->prepare("DELETE FROM sales_users WHERE id = ?");
        $stmt->execute([$userId]);
        logAction($_SESSION['username'] ?? 'System', 'Delete User', "Deleted user ID: $userId");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============ RESET PASSWORD - FIXED ============
function resetPassword($userId, $newPassword) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id, username FROM sales_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Get table columns
        $stmt = $db->query("DESCRIBE sales_users");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        // Update password
        if (in_array('first_login', $columnNames) && in_array('last_activity', $columnNames)) {
            $stmt = $db->prepare("UPDATE sales_users SET password = MD5(?), first_login = 1, last_activity = NOW() WHERE id = ?");
            $stmt->execute([$newPassword, $userId]);
        } elseif (in_array('first_login', $columnNames)) {
            $stmt = $db->prepare("UPDATE sales_users SET password = MD5(?), first_login = 1 WHERE id = ?");
            $stmt->execute([$newPassword, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE sales_users SET password = MD5(?) WHERE id = ?");
            $stmt->execute([$newPassword, $userId]);
        }
        
        logAction($_SESSION['username'] ?? 'System', 'Reset Password', "Reset password for user: " . $user['username']);
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("resetPassword Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

// ============ CASE FUNCTIONS ============
function parseDateRangePreset($preset, &$startDate, &$endDate) {
    if (empty($preset) || $preset === 'all') {
        return;
    }
    
    $today = date('Y-m-d');
    switch ($preset) {
        case 'today':
            $startDate = $today;
            $endDate = $today;
            break;
        case 'yesterday':
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $startDate = $yesterday;
            $endDate = $yesterday;
            break;
        case 'this_week':
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = $today;
            break;
        case 'this_month':
            $startDate = date('Y-m-01');
            $endDate = $today;
            break;
        case 'custom':
            // Keep user provided start_date and end_date
            break;
    }
}

function getCases($startDate = null, $endDate = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "
            SELECT c.*, b.name as branch_name 
            FROM sales_cases c 
            LEFT JOIN sales_branches b ON c.branch_id = b.id
        ";
        $where = [];
        $params = [];
        
        if (!empty($startDate)) {
            $where[] = "DATE(c.date) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(c.date) <= ?";
            $params[] = $endDate;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function getCompletedCases($startDate = null, $endDate = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "
            SELECT c.*, b.name as branch_name 
            FROM sales_completed_cases c 
            LEFT JOIN sales_branches b ON c.branch_id = b.id
        ";
        $where = [];
        $params = [];
        
        if (!empty($startDate)) {
            $where[] = "DATE(c.date) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(c.date) <= ?";
            $params[] = $endDate;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// ============ SAVE CASE WITH MULTIPLE FILE UPLOADS ============
function saveCase($data) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Generate unique case ID
        $maxRetries = 5;
        $attempt = 0;
        $caseId = null;
        
        while ($attempt < $maxRetries) {
            $caseId = generateCaseId();
            
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales_cases WHERE case_id = ?");
            $stmt->execute([$caseId]);
            $count = $stmt->fetch()['count'];
            
            if ($count == 0) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales_completed_cases WHERE case_id = ?");
                $stmt->execute([$caseId]);
                $count2 = $stmt->fetch()['count'];
                if ($count2 == 0) break;
            }
            $attempt++;
            if ($attempt >= $maxRetries) throw new Exception("Failed to generate unique case ID");
        }
        
        $zReport = floatval($data['z_report'] ?? 0);
        $cashSales = floatval($data['cash_sales'] ?? 0);
        $difference = $zReport - $cashSales;
        
        // Handle multiple file uploads
        $attachmentPaths = [];
        $attachmentNames = [];
        $attachmentTypes = 'file';
        $totalSize = 0;
        
        if (isset($_FILES['attachment_files'])) {
            $totalFiles = count($_FILES['attachment_files']['name']);
            
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['attachment_files']['error'][$i] === UPLOAD_ERR_OK) {
                    // Create a single file array for upload
                    $file = [
                        'name' => $_FILES['attachment_files']['name'][$i],
                        'type' => $_FILES['attachment_files']['type'][$i],
                        'tmp_name' => $_FILES['attachment_files']['tmp_name'][$i],
                        'error' => $_FILES['attachment_files']['error'][$i],
                        'size' => $_FILES['attachment_files']['size'][$i]
                    ];
                    
                    $uploadResult = handleFileUpload($file, $caseId);
                    if ($uploadResult['success']) {
                        $attachmentPaths[] = $uploadResult['file_path'];
                        $attachmentNames[] = $uploadResult['file_name'];
                        $totalSize += $uploadResult['file_size'];
                    }
                }
            }
        }
        
        // Single file fallback
        if (empty($attachmentPaths) && isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleFileUpload($_FILES['attachment_file'], $caseId);
            if ($uploadResult['success']) {
                $attachmentPaths[] = $uploadResult['file_path'];
                $attachmentNames[] = $uploadResult['file_name'];
                $totalSize += $uploadResult['file_size'];
            }
        }
        
        // Store first attachment as primary
        $primaryPath = !empty($attachmentPaths) ? $attachmentPaths[0] : null;
        $primaryName = !empty($attachmentNames) ? $attachmentNames[0] : null;
        
        // Insert case
        $stmt = $db->prepare("
            INSERT INTO sales_cases (
                case_id, date, branch_id, z_report, cash_sales, difference,
                progress, remark, submitted_by, technical_name, 
                attachments, attachment_type, attachment_file, attachment_name,
                escalated_to
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $caseId, $data['date'], $data['branch_id'], $zReport, $cashSales, $difference,
            $data['progress'], $data['remark'] ?? '', $data['submitted_by'],
            $data['technical_name'] ?? '', 
            $primaryPath, $attachmentTypes, $primaryPath, $primaryName,
            $data['escalated_to'] ?? ''
        ]);
        
        logAction($data['submitted_by'], 'Create Case', "Created case $caseId with " . count($attachmentPaths) . " attachments");
        if ($data['progress'] === 'Done') moveToCompleted($caseId);
        
        return ['success' => true, 'case_id' => $caseId, 'attachments' => count($attachmentPaths)];
        
    } catch (PDOException $e) {
        error_log("Save Case Error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("Save Case Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


// ============ UPDATE CASE ============
function updateCase($caseId, $data) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $zReport = floatval($data['z_report'] ?? 0);
        $cashSales = floatval($data['cash_sales'] ?? 0);
        $difference = $zReport - $cashSales;
        $currentProgress = $data['current_progress'] ?? '';
        $newProgress = $data['progress'] ?? '';
        
        // Handle file upload (multiple files or single)
        $attachmentPaths = [];
        $attachmentNames = [];
        $attachmentType = 'file';
        
        if (isset($_FILES['attachment_files'])) {
            $totalFiles = count($_FILES['attachment_files']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['attachment_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['attachment_files']['name'][$i],
                        'type' => $_FILES['attachment_files']['type'][$i],
                        'tmp_name' => $_FILES['attachment_files']['tmp_name'][$i],
                        'error' => $_FILES['attachment_files']['error'][$i],
                        'size' => $_FILES['attachment_files']['size'][$i]
                    ];
                    $uploadResult = handleFileUpload($file, $caseId);
                    if ($uploadResult['success']) {
                        $attachmentPaths[] = $uploadResult['file_path'];
                        $attachmentNames[] = $uploadResult['file_name'];
                    }
                }
            }
        }
        
        if (empty($attachmentPaths) && isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleFileUpload($_FILES['attachment_file'], $caseId);
            if ($uploadResult['success']) {
                $attachmentPaths[] = $uploadResult['file_path'];
                $attachmentNames[] = $uploadResult['file_name'];
            }
        }
        
        $attachmentPath = !empty($attachmentPaths) ? $attachmentPaths[0] : (!empty($data['attachments']) ? $data['attachments'] : null);
        $attachmentName = !empty($attachmentNames) ? $attachmentNames[0] : null;
        if (empty($attachmentPath)) {
            $attachmentType = 'url';
        }
        
        // Build update query - REMOVED attachment_size
        $sql = "
            UPDATE sales_cases SET
                date = ?, branch_id = ?, z_report = ?, cash_sales = ?, difference = ?,
                progress = ?, remark = ?, technical_name = ?,
                attachments = ?, attachment_type = ?, attachment_file = ?, 
                attachment_name = ?,
                escalated_to = ?, last_updated = NOW()
        ";
        
        $params = [
            $data['date'], $data['branch_id'], $zReport, $cashSales, $difference,
            $newProgress, $data['remark'] ?? '', $data['technical_name'] ?? '',
            $attachmentPath, $attachmentType, $attachmentPath, $attachmentName,
            $data['escalated_to'] ?? ''
        ];
        
        if ($newProgress === 'Done' && $currentProgress !== 'Done') $sql .= ", case_fixed_date = NOW()";
        if ($newProgress === 'Escalated' && $currentProgress !== 'Escalated') $sql .= ", escalation_date = NOW()";
        
        $sql .= " WHERE case_id = ?";
        $params[] = $caseId;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        logAction($_SESSION['username'] ?? 'System', 'Update Case', "Updated case $caseId");
        if ($newProgress === 'Done' && $currentProgress !== 'Done') moveToCompleted($caseId);
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Update Case Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============ SETTINGS FUNCTIONS ============

function getSetting($key, $default = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM sales_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function updateSetting($key, $value) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO sales_settings (setting_key, setting_value) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        error_log("updateSetting Error: " . $e->getMessage());
        return false;
    }
}

function handleLogoUpload($file) {
    try {
        $uploadDir = __DIR__ . '/../uploads/logos/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, SVG, WebP'];
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2097152) {
            return ['success' => false, 'error' => 'File size exceeds 2MB limit'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Return the URL path
            $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            $url = $baseUrl . '/uploads/logos/' . $filename;
            
            // Save to database
            updateSetting('logo_file', $url);
            
            return ['success' => true, 'file_path' => $url, 'filename' => $filename];
        }
        
        return ['success' => false, 'error' => 'Failed to upload file'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
// ============ MOVE TO COMPLETED ============
function moveToCompleted($caseId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if case exists and get data
        $stmt = $db->prepare("SELECT * FROM sales_cases WHERE case_id = ?");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        
        if (!$case) return true;
        
        // Check if already in completed
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales_completed_cases WHERE case_id = ?");
        $stmt->execute([$caseId]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $stmt = $db->prepare("DELETE FROM sales_cases WHERE case_id = ?");
            $stmt->execute([$caseId]);
            return true;
        }
        
        $db->beginTransaction();
        
        // Insert into completed - REMOVED attachment_size
        $stmt = $db->prepare("
            INSERT INTO sales_completed_cases (
                case_id, date, branch_id, z_report, cash_sales, difference,
                progress, case_fixed_date, remark, submitted_by, technical_name,
                approved, attachments, attachment_type, attachment_file, attachment_name,
                escalated_to, escalation_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $case['case_id'],
            $case['date'],
            $case['branch_id'],
            $case['z_report'],
            $case['cash_sales'],
            $case['difference'],
            'Done',
            $case['remark'],
            $case['submitted_by'],
            $case['technical_name'],
            $case['approved'] ? 1 : 0,
            $case['attachments'],
            $case['attachment_type'] ?? 'url',
            $case['attachment_file'] ?? null,
            $case['attachment_name'] ?? null,
            $case['escalated_to'],
            $case['escalation_date']
        ]);
        
        // Delete from cases
        $stmt = $db->prepare("DELETE FROM sales_cases WHERE case_id = ?");
        $stmt->execute([$caseId]);
        
        $db->commit();
        return true;
        
    } catch (PDOException $e) {
        if (isset($db)) $db->rollBack();
        error_log("Move to Completed Error: " . $e->getMessage());
        return false;
    }
}

// ============ DELETE CASE ============
function deleteCase($caseId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Delete attachments first
        $attachments = getCaseAttachments($caseId);
        foreach ($attachments as $att) {
            if (file_exists($att['file_path'])) unlink($att['file_path']);
        }
        
        $stmt = $db->prepare("DELETE FROM sales_cases WHERE case_id = ?");
        $stmt->execute([$caseId]);
        logAction($_SESSION['username'] ?? 'System', 'Delete Case', "Deleted case $caseId");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============ APPROVE & ESCALATE ============
function approveCase($caseId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM sales_cases WHERE case_id = ?");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        
        if ($case) {
            $stmt = $db->prepare("UPDATE sales_cases SET approved = TRUE, last_updated = NOW() WHERE case_id = ?");
            $stmt->execute([$caseId]);
        } else {
            $stmt = $db->prepare("UPDATE sales_completed_cases SET approved = TRUE, last_updated = NOW() WHERE case_id = ?");
            $stmt->execute([$caseId]);
        }
        logAction($_SESSION['username'] ?? 'System', 'Approve Case', "Approved case $caseId");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function escalateCase($caseId, $escalatedTo) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE sales_cases SET 
                progress = 'Escalated',
                escalated_to = ?,
                escalation_date = NOW(),
                last_updated = NOW()
            WHERE case_id = ?
        ");
        $stmt->execute([$escalatedTo, $caseId]);
        logAction($_SESSION['username'] ?? 'System', 'Escalate Case', "Escalated case $caseId to $escalatedTo");
        return ['success' => true];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============ COMMENT FUNCTIONS ============
function getComments($caseId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM sales_comments WHERE case_id = ? ORDER BY timestamp DESC");
        $stmt->execute([$caseId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function addComment($caseId, $comment, $username) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO sales_comments (case_id, comment, username) VALUES (?, ?, ?)");
        $stmt->execute([$caseId, $comment, $username]);
        logAction($username, 'Add Comment', "Added comment to case $caseId");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// ============ TELEGRAM FUNCTIONS ============
function getTelegramDestinations() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM sales_telegram_destinations ORDER BY id DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("getTelegramDestinations error: " . $e->getMessage());
        return [];
    }
}

function getTelegramDestination($id) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM sales_telegram_destinations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function addTelegramDestination($name, $chatId, $botToken) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales_telegram_destinations WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()['count'] > 0) {
            return ['success' => false, 'error' => 'A destination with this name already exists'];
        }
        
        $stmt = $db->prepare("INSERT INTO sales_telegram_destinations (name, chat_id, bot_token) VALUES (?, ?, ?)");
        $stmt->execute([$name, $chatId, $botToken]);
        logAction($_SESSION['username'] ?? 'System', 'Add Telegram Destination', "Added destination $name");
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("addTelegramDestination error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function updateTelegramDestination($id, $name, $chatId, $botToken) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sales_telegram_destinations WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()['count'] > 0) {
            return ['success' => false, 'error' => 'A destination with this name already exists'];
        }
        
        $stmt = $db->prepare("UPDATE sales_telegram_destinations SET name = ?, chat_id = ?, bot_token = ? WHERE id = ?");
        $stmt->execute([$name, $chatId, $botToken, $id]);
        logAction($_SESSION['username'] ?? 'System', 'Update Telegram Destination', "Updated destination $name");
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("updateTelegramDestination error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteTelegramDestination($id) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT name FROM sales_telegram_destinations WHERE id = ?");
        $stmt->execute([$id]);
        $name = $stmt->fetch()['name'] ?? 'Unknown';
        
        $stmt = $db->prepare("DELETE FROM sales_telegram_destinations WHERE id = ?");
        $stmt->execute([$id]);
        logAction($_SESSION['username'] ?? 'System', 'Delete Telegram Destination', "Deleted destination: $name");
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("deleteTelegramDestination error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function validateTelegramBot($botToken) {
    try {
        $botToken = trim($botToken);
        $botToken = preg_replace('/[^a-zA-Z0-9:_-]/', '', $botToken);
        if (empty($botToken)) return ['success' => false, 'error' => 'Invalid bot token format'];
        
        $url = "https://api.telegram.org/bot{$botToken}/getMe";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
        if ($httpCode !== 200) return ['success' => false, 'error' => "HTTP Error: $httpCode - Bot token may be invalid"];
        
        $result = json_decode($response, true);
        if ($result && isset($result['ok']) && $result['ok'] === true) {
            return ['success' => true, 'bot_info' => $result['result']];
        }
        return ['success' => false, 'error' => $result['description'] ?? 'Invalid bot token'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


// ============ TEST TELEGRAM CONNECTION WITH INLINE BUTTONS ============
function testTelegramConnection($chatId, $botToken) {
    try {
        if (empty($chatId) || empty($botToken)) {
            return ['success' => false, 'error' => 'Chat ID and Bot Token are required'];
        }
        
        $botToken = trim($botToken);
        $chatId = trim($chatId);
        
        // Get base URL
        $baseUrl = getBaseUrl();
        $dashboardUrl = rtrim($baseUrl, '/') . '/dashboard.php';
        $casesUrl = rtrim($baseUrl, '/') . '/cases.php';
        
        $message = "<b>[TEST MESSAGE] Kaldis Coffee CMS</b>\n\n" .
                   "Telegram connection successful!\n" .
                   "Date: " . date('Y-m-d H:i:s') . "\n" .
                   "User: " . ($_SESSION['username'] ?? 'System') . "\n\n" .
                   "----------------------------------------\n\n" .
                   "<b>Dashboard:</b> <a href='{$dashboardUrl}'>Open Dashboard</a>\n" .
                   "<b>All Cases:</b> <a href='{$casesUrl}'>View All Cases</a>\n\n" .
                   "----------------------------------------\n\n" .
                   "<i>Sent from Kaldis Coffee CMS</i>\n" .
                   "Date: " . date('Y-m-d H:i:s');
        
        // Create inline keyboard buttons
        $inlineKeyboard = [
            [
                ['text' => 'Dashboard', 'url' => $dashboardUrl],
                ['text' => 'View Cases', 'url' => $casesUrl]
            ],
            [
                ['text' => 'Refresh Data', 'callback_data' => 'refresh_data'],
                ['text' => 'Statistics', 'callback_data' => 'get_stats']
            ],
            [
                ['text' => 'Help', 'callback_data' => 'help']
            ]
        ];
        
        return sendTelegramMessage($chatId, $botToken, $message, $inlineKeyboard);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


// ============ SEND TELEGRAM MESSAGE WITH INLINE KEYBOARD ============
function sendTelegramMessage($chatId, $botToken, $message, $inlineKeyboard = null) {
    try {
        if (empty($chatId) || empty($botToken)) {
            return ['success' => false, 'error' => 'Chat ID and Bot Token are required'];
        }
        
        $chatId = trim($chatId);
        $botToken = trim($botToken);
        $botToken = preg_replace('/[^a-zA-Z0-9:_-]/', '', $botToken);
        
        // Validate bot token format
        if (!preg_match('/^\d+:[a-zA-Z0-9_-]+$/', $botToken)) {
            return ['success' => false, 'error' => 'Invalid bot token format'];
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
            'disable_notification' => false
        ];
        
        // Add inline keyboard if provided
        if ($inlineKeyboard && !empty($inlineKeyboard)) {
            $cleanKeyboard = sanitizeInlineKeyboard($inlineKeyboard);
            if (!empty($cleanKeyboard)) {
                $data['reply_markup'] = json_encode([
                    'inline_keyboard' => $cleanKeyboard
                ]);
            }
        }
        
        if (function_exists('curl_init')) {
            return sendTelegramCurl($url, $data);
        } else {
            return sendTelegramFileGetContents($url, $data);
        }
        
    } catch (Exception $e) {
        error_log("sendTelegramMessage Exception: " . $e->getMessage());
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}

// ============ VALIDATE PUBLIC URL ============
function isValidPublicUrl($url) {
    if (empty($url)) return false;
    
    // Check if URL is valid format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Check host
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;
    
    // Telegram API specifically rejects literal string 'localhost', '127.0.0.1', '::1', '0.0.0.0'
    if (in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) {
        return false;
    }
    
    return true;
}

// ============ GET BASE URL ============
function getBaseUrl() {
    $appUrl = getSetting('app_url', '');
    if (!empty($appUrl)) {
        return rtrim($appUrl, '/');
    }
    
    $protocol = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $subPath = '';
    if (!empty($scriptName)) {
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ($dir !== '/' && $dir !== '.' && $dir !== '\\') {
            $subPath = '/' . trim($dir, '/');
        }
    }
    
    // If host is empty or localhost/127.0.0.1, resolve local LAN IP / hostname so Telegram API permits URL buttons
    if (empty($host) || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $localIp = gethostbyname(gethostname());
        if (!empty($localIp) && $localIp !== '127.0.0.1') {
            $portStr = '';
            if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
                $portStr = ':' . $_SERVER['SERVER_PORT'];
            } elseif (!empty($host) && strpos($host, ':') !== false) {
                $parts = explode(':', $host);
                $portStr = ':' . $parts[1];
            }
            return $protocol . $localIp . $portStr . $subPath;
        }
    }
    
    return $protocol . $host . $subPath;
}

// ============ SANITIZE INLINE KEYBOARD FOR TELEGRAM ============
function sanitizeInlineKeyboard($inlineKeyboard) {
    if (!is_array($inlineKeyboard) || empty($inlineKeyboard)) {
        return null;
    }
    
    $baseUrl = getBaseUrl();
    
    $sanitized = [];
    foreach ($inlineKeyboard as $row) {
        if (!is_array($row)) continue;
        $sanitizedRow = [];
        foreach ($row as $button) {
            if (!is_array($button)) continue;
            
            if (isset($button['url']) && !empty($button['url'])) {
                $url = $button['url'];
                
                // If URL uses localhost or 127.0.0.1, replace host part with resolved LAN IP / app_url
                if (preg_match('#^https?://(localhost|127\.0\.0\.1|::1)(:\d+)?#i', $url)) {
                    $parsed = parse_url($url);
                    $path = $parsed['path'] ?? '';
                    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                    
                    $baseParsed = parse_url($baseUrl);
                    $basePath = $baseParsed['path'] ?? '';
                    
                    if (!empty($basePath) && strpos($path, $basePath) === 0) {
                        $button['url'] = rtrim($baseUrl, '/') . substr($path, strlen($basePath)) . $query;
                    } else {
                        $button['url'] = rtrim($baseUrl, '/') . '/' . ltrim($path, '/') . $query;
                    }
                }
                
                // If URL is still not valid for Telegram (e.g. invalid string), fallback safely
                if (!isValidPublicUrl($button['url'])) {
                    if (!isset($button['callback_data'])) {
                        $button['callback_data'] = 'local_url_notice';
                    }
                    unset($button['url']);
                }
            }
            
            if (isset($button['url']) || isset($button['callback_data']) || isset($button['switch_inline_query'])) {
                $sanitizedRow[] = $button;
            }
        }
        if (!empty($sanitizedRow)) {
            $sanitized[] = $sanitizedRow;
        }
    }
    
    return !empty($sanitized) ? $sanitized : null;
}

// ============ SEND TELEGRAM CURL WITH DETAILED ERROR LOGGING ============
function sendTelegramCurl($url, $data) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        
        // Log curl info
        error_log("cURL Request URL: " . $url);
        error_log("cURL POST Data: " . http_build_query($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        
        // Log response details
        error_log("cURL HTTP Code: " . $httpCode);
        error_log("cURL Response: " . substr($response, 0, 500));
        
        if ($curlError) {
            error_log("cURL Error: " . $curlError);
            return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("HTTP Error: " . $httpCode . " - Response: " . $response);
            return ['success' => false, 'error' => "HTTP Error: $httpCode - " . $response];
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            error_log("Invalid JSON Response: " . $response);
            return ['success' => false, 'error' => 'Invalid JSON response from Telegram'];
        }
        
        if ($result && isset($result['ok']) && $result['ok'] === true) {
            return ['success' => true, 'response' => $result];
        } else {
            $errorMsg = $result['description'] ?? 'Unknown error';
            $errorCode = $result['error_code'] ?? 'N/A';
            error_log("Telegram API Error: [$errorCode] $errorMsg");
            
            // Return more specific error messages
            if ($errorCode == 400) {
                return ['success' => false, 'error' => 'Bad Request: ' . $errorMsg];
            } elseif ($errorCode == 401) {
                return ['success' => false, 'error' => 'Unauthorized: Invalid bot token'];
            } elseif ($errorCode == 403) {
                return ['success' => false, 'error' => 'Forbidden: Bot blocked or chat not found'];
            } elseif ($errorCode == 404) {
                return ['success' => false, 'error' => 'Not Found: Invalid chat ID or bot token'];
            } elseif ($errorCode == 429) {
                return ['success' => false, 'error' => 'Too Many Requests: Rate limit exceeded'];
            } else {
                return ['success' => false, 'error' => "Telegram API Error: [$errorCode] $errorMsg"];
            }
        }
        
    } catch (Exception $e) {
        error_log("cURL Exception: " . $e->getMessage());
        return ['success' => false, 'error' => 'cURL Exception: ' . $e->getMessage()];
    }
}

// ============ SEND TELEGRAM FILE GET CONTENTS (FALLBACK) ============
function sendTelegramFileGetContents($url, $data) {
    try {
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            return ['success' => false, 'error' => 'File get contents error: ' . ($error['message'] ?? 'Unknown error')];
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['ok']) && $result['ok'] === true) {
            return ['success' => true, 'response' => $result];
        } else {
            $errorMsg = $result['description'] ?? 'Unknown error';
            return ['success' => false, 'error' => 'Telegram API Error: ' . $errorMsg];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}

// ============ SEND TELEGRAM MESSAGE WITH FILE AND INLINE KEYBOARD ============
function sendTelegramMessageWithFile($chatId, $botToken, $message, $filePath = null, $inlineKeyboard = null) {
    try {
        if (empty($chatId) || empty($botToken)) {
            return ['success' => false, 'error' => 'Chat ID and Bot Token are required'];
        }
        
        $chatId = trim($chatId);
        $botToken = trim($botToken);
        $botToken = preg_replace('/[^a-zA-Z0-9:_-]/', '', $botToken);
        
        // If no file, just send text message
        if (!$filePath || !file_exists($filePath)) {
            return sendTelegramMessage($chatId, $botToken, $message, $inlineKeyboard);
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/sendDocument";
        
        // Get file info
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Check file size (Telegram max is 50MB)
        $fileSize = filesize($filePath);
        if ($fileSize > 52428800) {
            return ['success' => false, 'error' => 'File too large for Telegram (max 50MB)'];
        }
        
        if (!is_readable($filePath)) {
            return ['success' => false, 'error' => 'File is not readable'];
        }
        
        $fileData = new CURLFile($filePath, $mimeType, basename($filePath));
        
        $postData = [
            'chat_id' => $chatId,
            'document' => $fileData,
            'caption' => $message,
            'parse_mode' => 'HTML'
        ];
        
        // Add inline keyboard if provided
        if ($inlineKeyboard && !empty($inlineKeyboard)) {
            $cleanKeyboard = sanitizeInlineKeyboard($inlineKeyboard);
            if (!empty($cleanKeyboard)) {
                $postData['reply_markup'] = json_encode([
                    'inline_keyboard' => $cleanKeyboard
                ]);
            }
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL Error (File): " . $curlError);
            return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP Error (File): " . $httpCode . " - Response: " . $response);
            return ['success' => false, 'error' => "HTTP Error: $httpCode - " . $response];
        }
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['ok']) && $result['ok'] === true) {
            return ['success' => true, 'response' => $result];
        } else {
            $errorMsg = $result['description'] ?? 'Unknown error';
            $errorCode = $result['error_code'] ?? 'N/A';
            error_log("Telegram API Error (File): [$errorCode] $errorMsg");
            return ['success' => false, 'error' => "Telegram API Error: [$errorCode] $errorMsg"];
        }
        
    } catch (Exception $e) {
        error_log("sendTelegramMessageWithFile Exception: " . $e->getMessage());
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}

// ============ SIMPLE SEND - NO INLINE BUTTONS ============
function sendTelegramMessageSimple($chatId, $botToken, $message) {
    try {
        if (empty($chatId) || empty($botToken)) {
            return ['success' => false, 'error' => 'Chat ID and Bot Token are required'];
        }
        
        $chatId = trim($chatId);
        $botToken = trim($botToken);
        $botToken = preg_replace('/[^a-zA-Z0-9:_-]/', '', $botToken);
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
            'disable_notification' => false
        ];
        
        if (function_exists('curl_init')) {
            return sendTelegramCurl($url, $data);
        } else {
            return sendTelegramFileGetContents($url, $data);
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
    }
}
// ============ DEBUG TELEGRAM CONNECTION ============
function debugTelegramConnection($chatId, $botToken) {
    $results = [];
    
    // Test 1: Validate Bot Token
    $results[] = "Testing Bot Token...";
    if (empty($botToken)) {
        $results[] = "❌ Bot Token is empty";
        return $results;
    }
    
    $botToken = trim($botToken);
    if (!preg_match('/^\d+:[a-zA-Z0-9_-]+$/', $botToken)) {
        $results[] = "❌ Bot Token format is invalid. Expected format: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz";
        return $results;
    }
    $results[] = "✅ Bot Token format is valid";
    
    // Test 2: Test getMe
    $results[] = "Testing getMe...";
    $url = "https://api.telegram.org/bot{$botToken}/getMe";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $results[] = "❌ cURL Error: " . $curlError;
        return $results;
    }
    
    if ($httpCode !== 200) {
        $results[] = "❌ HTTP Error: $httpCode - Bot token may be invalid";
        return $results;
    }
    
    $result = json_decode($response, true);
    if ($result && isset($result['ok']) && $result['ok'] === true) {
        $results[] = "✅ Bot is valid! Name: " . ($result['result']['first_name'] ?? 'Unknown');
        $results[] = "   Username: @" . ($result['result']['username'] ?? 'N/A');
    } else {
        $results[] = "❌ Invalid Bot Token: " . ($result['description'] ?? 'Unknown error');
        return $results;
    }
    
    // Test 3: Test sendMessage
    $results[] = "Testing sendMessage to chat_id: $chatId...";
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => "🟢 <b>Debug Test Message</b>\n\nThis is a test message from the debug function.",
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $results[] = "❌ cURL Error: " . $curlError;
        return $results;
    }
    
    $result = json_decode($response, true);
    if ($httpCode === 200 && $result && isset($result['ok']) && $result['ok'] === true) {
        $results[] = "✅ Message sent successfully!";
    } else {
        $errorMsg = $result['description'] ?? 'Unknown error';
        $errorCode = $result['error_code'] ?? 'N/A';
        $results[] = "❌ Failed to send message: [$errorCode] $errorMsg";
        
        if ($errorCode === 403) {
            $results[] = "   ❌ Bot was blocked by the user or chat not found";
        } elseif ($errorCode === 400) {
            $results[] = "   ❌ Invalid chat_id format or bot cannot message this user";
        } elseif ($errorCode === 404) {
            $results[] = "   ❌ Chat not found or bot token is invalid";
        }
    }
    
    return $results;
}



// ============ SEND MULTIPLE CASES TO TELEGRAM WITH INLINE BUTTONS ============
function sendMultipleCasesToTelegram($caseIds, $destinationId, $customMessage = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $destination = getTelegramDestination($destinationId);
        
        if (!$destination) {
            error_log("Destination not found: " . $destinationId);
            return ['success' => false, 'error' => 'Destination not found'];
        }
        
        // Get all cases with attachments
        $cases = [];
        $failedCases = [];
        $allAttachments = [];
        
        foreach ($caseIds as $caseId) {
            $stmt = $db->prepare("
                SELECT c.*, b.name as branch_name 
                FROM sales_cases c 
                LEFT JOIN sales_branches b ON c.branch_id = b.id 
                WHERE c.case_id = ?
            ");
            $stmt->execute([$caseId]);
            $case = $stmt->fetch();
            
            if (!$case) {
                $stmt = $db->prepare("
                    SELECT c.*, b.name as branch_name 
                    FROM sales_completed_cases c 
                    LEFT JOIN sales_branches b ON c.branch_id = b.id 
                    WHERE c.case_id = ?
                ");
                $stmt->execute([$caseId]);
                $case = $stmt->fetch();
            }
            
            if ($case) {
                $stmt = $db->prepare("SELECT * FROM sales_case_attachments WHERE case_id = ?");
                $stmt->execute([$caseId]);
                $attachments = $stmt->fetchAll();
                
                $case['attachments'] = $attachments;
                $cases[] = $case;
                
                foreach ($attachments as $att) {
                    if (file_exists($att['file_path'])) {
                        $allAttachments[] = $att['file_path'];
                    }
                }
            } else {
                $failedCases[] = $caseId;
            }
        }
        
        if (empty($cases)) {
            return ['success' => false, 'error' => 'No valid cases found'];
        }
        
        $message = generateMergedCaseMessageWithFiles($cases, $customMessage);
        
        $baseUrl = getBaseUrl();
        $dashboardUrl = rtrim($baseUrl, '/') . '/dashboard.php';
        $casesUrl = rtrim($baseUrl, '/') . '/cases.php';
        
        // Create inline keyboard buttons
        $inlineKeyboard = [
            [
                ['text' => 'Dashboard', 'url' => $dashboardUrl],
                ['text' => 'View Cases', 'url' => $casesUrl]
            ],
            [
                ['text' => 'Approve All', 'callback_data' => 'approve_all_' . implode(',', $caseIds)],
                ['text' => 'View Files', 'callback_data' => 'view_files_' . implode(',', $caseIds)]
            ],
            [
                ['text' => 'Refresh', 'callback_data' => 'refresh_data'],
                ['text' => 'Stats', 'callback_data' => 'get_stats']
            ],
            [
                ['text' => 'Help', 'callback_data' => 'help']
            ]
        ];
        
        // Send messages with attachments
        $results = [];
        $sentCount = 0;
        $attachmentsSent = 0;
        
        if (!empty($allAttachments)) {
            // Send first file with the main message
            $result = sendTelegramMessageWithFile(
                $destination['chat_id'],
                $destination['bot_token'],
                $message,
                $allAttachments[0],
                $inlineKeyboard
            );
            $results[] = $result;
            if ($result['success']) {
                $sentCount++;
                $attachmentsSent++;
            }
            
            // Send remaining attachments
            for ($i = 1; $i < count($allAttachments); $i++) {
                $fileName = basename($allAttachments[$i]);
                $caseId = basename(dirname($allAttachments[$i]));
                
                $caption = "<b>Additional Attachment</b>\n\n" .
                           "<b>File:</b> " . htmlspecialchars($fileName) . "\n" .
                           "<b>Case ID:</b> " . htmlspecialchars($caseId) . "\n\n" .
                           "<b>Dashboard:</b> <a href='{$dashboardUrl}'>Open Dashboard</a>\n\n" .
                           "<i>Sent from Kaldis Coffee CMS</i>";
                
                $attachmentKeyboard = [
                    [
                        ['text' => 'Dashboard', 'url' => $dashboardUrl],
                        ['text' => 'View Case', 'url' => rtrim($baseUrl, '/') . '/edit_case.php?id=' . urlencode($caseId)]
                    ],
                    [
                        ['text' => 'Download', 'callback_data' => 'download_file_' . $caseId],
                        ['text' => 'Refresh', 'callback_data' => 'refresh_data']
                    ]
                ];
                
                $result = sendTelegramMessageWithFile(
                    $destination['chat_id'],
                    $destination['bot_token'],
                    $caption,
                    $allAttachments[$i],
                    $attachmentKeyboard
                );
                $results[] = $result;
                if ($result['success']) {
                    $sentCount++;
                    $attachmentsSent++;
                }
                
                usleep(300000);
            }
        } else {
            // No attachments, send just the message
            $result = sendTelegramMessage($destination['chat_id'], $destination['bot_token'], $message, $inlineKeyboard);
            $results[] = $result;
            if ($result['success']) {
                $sentCount++;
            }
        }
        
        $allSuccess = true;
        $errors = [];
        foreach ($results as $r) {
            if (!$r['success']) {
                $allSuccess = false;
                $errors[] = $r['error'] ?? 'Unknown error';
            }
        }
        
        logAction(
            $_SESSION['username'] ?? 'System', 
            'Send Telegram', 
            "Sent " . count($cases) . " cases with " . count($allAttachments) . " attachments to " . $destination['name']
        );
        
        return [
            'success' => $allSuccess,
            'sent' => $sentCount,
            'failed' => $failedCases,
            'total' => count($caseIds),
            'attachments_sent' => $attachmentsSent,
            'total_attachments' => count($allAttachments),
            'errors' => $errors,
            'results' => $results
        ];
        
    } catch (Exception $e) {
        error_log("sendMultipleCasesToTelegram Exception: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
// ============ GENERATE MERGED CASE MESSAGE WITH INLINE BUTTONS ============
function generateMergedCaseMessageWithFiles($cases, $customMessage = null) {
    // Get the base URL dynamically
    $baseUrl = getBaseUrl();
    $dashboardUrl = rtrim($baseUrl, '/') . '/dashboard.php';
    $casesUrl = rtrim($baseUrl, '/') . '/cases.php';
    
    // Header
    $message = "<b>Case Report - Multiple Cases</b>\n\n";
    
    // Add custom message if provided
    if ($customMessage && !empty(trim($customMessage))) {
        $message .= "<b>Message:</b> " . trim($customMessage) . "\n\n";
    }
    
    // Count total cases and attachments
    $totalCases = count($cases);
    $totalAttachments = 0;
    foreach ($cases as $case) {
        $totalAttachments += count($case['attachments'] ?? []);
    }
    
    $message .= "<b>Total Cases:</b> {$totalCases}\n";
    $message .= "<b>Total Attachments:</b> {$totalAttachments}\n\n";
    $message .= "----------------------------------------\n\n";
    
    // Add each case
    foreach ($cases as $index => $case) {
        $progressStatus = [
            'Done' => '[Done]',
            'In Progress' => '[In Progress]',
            'Not Start' => '[Not Start]',
            'Not Done' => '[Not Done]',
            'Escalated' => '[Escalated]'
        ];
        $statusStr = $progressStatus[$case['progress']] ?? '[' . $case['progress'] . ']';
        
        $caseNumber = $index + 1;
        $message .= "<b>Case #{$caseNumber}</b>\n";
        $message .= "<b>Case ID:</b> {$case['case_id']}\n";
        $message .= "<b>Date:</b> " . date('Y-m-d', strtotime($case['date'])) . "\n";
        $message .= "<b>Branch:</b> " . ($case['branch_name'] ?? 'N/A') . "\n";
        $message .= "<b>Z Report:</b> " . number_format($case['z_report'], 2) . " ETB\n";
        $message .= "<b>Cash Sales:</b> " . number_format($case['cash_sales'], 2) . " ETB\n";
        $message .= "<b>Difference:</b> " . number_format($case['difference'], 2) . " ETB\n";
        $message .= "<b>Progress:</b> {$statusStr}\n";
        
        if ($case['technical_name']) {
            $message .= "<b>Technician:</b> {$case['technical_name']}\n";
        }
        if ($case['remark']) {
            $message .= "<b>Remark:</b> {$case['remark']}\n";
        }
        if ($case['escalated_to']) {
            $message .= "<b>Escalated To:</b> {$case['escalated_to']}\n";
        }
        if ($case['approved']) {
            $message .= "<b>Status:</b> Approved\n";
        }
        
        // Add case direct link
        $caseLink = rtrim($baseUrl, '/') . '/edit_case.php?id=' . urlencode($case['case_id']);
        $message .= "<b>View Case:</b> <a href='{$caseLink}'>Open Case</a>\n";
        
        // Show attachment count for this case
        if (!empty($case['attachments'])) {
            $attCount = count($case['attachments']);
            $message .= "<b>Attachments:</b> {$attCount} file(s)\n";
        }
        
        // Add separator between cases
        if ($index < count($cases) - 1) {
            $message .= "\n----------------------------------------\n\n";
        }
    }
    
    // Footer with dashboard and cases links
    $message .= "\n----------------------------------------\n\n";
    
    // Dashboard Link
    $message .= "<b>Dashboard:</b> <a href='{$dashboardUrl}'>Open Dashboard</a>\n";
    
    // Cases List Link
    $message .= "<b>All Cases:</b> <a href='{$casesUrl}'>View All Cases</a>\n\n";
    
    // Quick Stats
    $message .= "<b>Quick Stats</b>\n";
    $message .= "- Total Cases: {$totalCases}\n";
    
    // Count cases by progress
    $progressCounts = [];
    foreach ($cases as $case) {
        $progress = $case['progress'] ?? 'Unknown';
        $progressCounts[$progress] = ($progressCounts[$progress] ?? 0) + 1;
    }
    
    foreach ($progressCounts as $progress => $count) {
        $message .= "- {$progress}: {$count}\n";
    }
    
    if ($totalAttachments > 0) {
        $message .= "- Total Attachments: {$totalAttachments}\n\n";
    } else {
        $message .= "- No attachments\n\n";
    }
    
    // Attachment note
    if ($totalAttachments > 0) {
        $message .= "<b>Note:</b> {$totalAttachments} file(s) attached to this message\n";
        $message .= "<i>Files are being sent as separate messages after this report</i>\n\n";
    }
    
    // Footer
    $message .= "----------------------------------------\n\n";
    $message .= "<i>Sent from Kaldis Coffee CMS</i>\n";
    $message .= "Date: " . date('Y-m-d H:i:s');
    
    return $message;
}
// ============ GENERATE MERGED CASE MESSAGE WITH FILES ============
function sendCaseToTelegram($caseId, $destinationId, $customMessage = null) {
    return sendMultipleCasesToTelegram([$caseId], $destinationId, $customMessage);
}


// ============ GENERATE SINGLE CASE MESSAGE WITH DASHBOARD LINK ============

// ============ GENERATE SINGLE CASE MESSAGE WITH INLINE KEYBOARD ============
function generateCaseMessage($case) {
    // Get the base URL dynamically
    $baseUrl = getBaseUrl();
    $dashboardUrl = rtrim($baseUrl, '/') . '/dashboard.php';
    $casesUrl = rtrim($baseUrl, '/') . '/cases.php';
    $caseLink = rtrim($baseUrl, '/') . '/edit_case.php?id=' . urlencode($case['case_id'] ?? '');

    $progressStatus = [
        'Done' => '[Done]',
        'In Progress' => '[In Progress]',
        'Not Start' => '[Not Start]',
        'Not Done' => '[Not Done]',
        'Escalated' => '[Escalated]'
    ];
    $statusStr = $progressStatus[$case['progress'] ?? ''] ?? '[' . ($case['progress'] ?? 'N/A') . ']';
    
    $message = "<b>Case Report</b>\n\n" .
               "<b>Case ID:</b> " . htmlspecialchars($case['case_id'] ?? 'N/A') . "\n" .
               "<b>Date:</b> " . date('Y-m-d', strtotime($case['date'] ?? 'now')) . "\n" .
               "<b>Branch:</b> " . htmlspecialchars($case['branch_name'] ?? 'N/A') . "\n" .
               "<b>Z Report:</b> " . number_format($case['z_report'] ?? 0, 2) . " ETB\n" .
               "<b>Cash Sales:</b> " . number_format($case['cash_sales'] ?? 0, 2) . " ETB\n" .
               "<b>Difference:</b> " . number_format($case['difference'] ?? 0, 2) . " ETB\n" .
               "<b>Progress:</b> {$statusStr}\n";
    
    if (!empty($case['technical_name'])) {
        $message .= "<b>Technician:</b> " . htmlspecialchars($case['technical_name']) . "\n";
    }
    if (!empty($case['remark'])) {
        $message .= "<b>Remark:</b> " . htmlspecialchars($case['remark']) . "\n";
    }
    if (!empty($case['escalated_to'])) {
        $message .= "<b>Escalated To:</b> " . htmlspecialchars($case['escalated_to']) . "\n";
    }
    if (!empty($case['approved'])) {
        $message .= "<b>Status:</b> Approved\n";
    }
    
    $message .= "\n----------------------------------------\n\n";
    $message .= "<b>View Case:</b> <a href='{$caseLink}'>Open Case</a>\n";
    $message .= "<b>Dashboard:</b> <a href='{$dashboardUrl}'>Open Dashboard</a>\n";
    $message .= "<b>All Cases:</b> <a href='{$casesUrl}'>View All Cases</a>\n\n";
    $message .= "----------------------------------------\n\n";
    $message .= "<i>Sent from Kaldis Coffee CMS</i>\n";
    $message .= "Date: " . date('Y-m-d H:i:s');
    
    return $message;
}
?>
<?php
// ============ HANDLE FILE UPLOAD WITH ZIP SUPPORT ============
function handleFileUpload($file, $caseId) {
    try {
        $uploadDir = __DIR__ . '/../uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create case subdirectory
        $caseDir = $uploadDir . $caseId . '/';
        if (!file_exists($caseDir)) {
            mkdir($caseDir, 0755, true);
        }
        
        // Validate file type - Updated with ZIP support
        $allowedTypes = [
            'image/jpeg', 'image/png', 'application/pdf', 'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip', 'application/x-zip-compressed',
            'application/x-rar-compressed', 'application/x-7z-compressed'
        ];
        
        // Check file extension for archive files
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $archiveExtensions = ['zip', 'rar', '7z'];
        
        if (!in_array($file['type'], $allowedTypes) && !in_array($fileExtension, $archiveExtensions)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, PDF, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR, 7Z'];
        }
        
        // Validate file size (20MB max for archives)
        $maxSize = in_array($fileExtension, $archiveExtensions) ? 20971520 : 10485760; // 20MB for archives, 10MB for others
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds limit. Max: ' . ($maxSize / 1048576) . 'MB'];
        }
        
        // Generate unique filename
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $filePath = $caseDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Save to database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO sales_case_attachments (case_id, file_name, file_path, file_size, file_type, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $caseId,
                $file['name'],
                $filePath,
                $file['size'],
                $file['type'],
                $_SESSION['username'] ?? 'System'
            ]);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'file_type' => $file['type']
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
