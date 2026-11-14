<?php
// edit_case.php - COMPLETE WITH BRANCH INPUT ENTER SUPPORT
$page_title = 'Edit Case';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Get case ID from URL
$caseId = $_GET['id'] ?? '';

if (!$caseId) {
    $_SESSION['error'] = 'No case ID provided';
    header('Location: cases.php');
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // First check if case exists in cases table
    $stmt = $db->prepare("SELECT c.*, b.name as branch_name, b.id as branch_id 
                          FROM sales_cases c 
                          LEFT JOIN sales_branches b ON c.branch_id = b.id 
                          WHERE c.case_id = ?");
    $stmt->execute([$caseId]);
    $case = $stmt->fetch();
    
    // If not found in cases, check completed cases
    if (!$case) {
        $stmt = $db->prepare("SELECT c.*, b.name as branch_name, b.id as branch_id 
                              FROM sales_completed_cases c 
                              LEFT JOIN sales_branches b ON c.branch_id = b.id 
                              WHERE c.case_id = ?");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
    }
    
    // If still not found, redirect with error
    if (!$case) {
        $_SESSION['error'] = 'Case not found';
        header('Location: cases.php');
        exit();
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: cases.php');
    exit();
}

$branches = getBranches();
$technicians = getTechnicians();
$attachments = getCaseAttachments($caseId);
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_case') {
    try {
        $data = [
            'date' => $_POST['date'] ?? $case['date'],
            'branch_id' => (int)($_POST['branch_id'] ?? $case['branch_id']),
            'z_report' => (float)($_POST['z_report'] ?? $case['z_report']),
            'cash_sales' => (float)($_POST['cash_sales'] ?? $case['cash_sales']),
            'progress' => $_POST['progress'] ?? $case['progress'],
            'current_progress' => $case['progress'],
            'remark' => $_POST['remark'] ?? $case['remark'],
            'technical_name' => $_POST['technical_name'] ?? $case['technical_name'],
            'escalated_to' => $_POST['escalated_to'] ?? $case['escalated_to']
        ];
        
        // Handle branch input from text field
        if (isset($_POST['branch_input']) && !empty($_POST['branch_input'])) {
            $branchName = trim($_POST['branch_input']);
            // Try to find matching branch
            $found = false;
            foreach ($branches as $branch) {
                if (strtolower($branch['name']) === strtolower($branchName)) {
                    $data['branch_id'] = $branch['id'];
                    $found = true;
                    break;
                }
            }
            // If not found, check if we should create it
            if (!$found && isset($_POST['create_new_branch']) && $_POST['create_new_branch'] === 'on') {
                $result = addBranch($branchName);
                if ($result['success']) {
                    $branches = getBranches();
                    foreach ($branches as $branch) {
                        if (strtolower($branch['name']) === strtolower($branchName)) {
                            $data['branch_id'] = $branch['id'];
                            break;
                        }
                    }
                    $message = "New branch '$branchName' created!";
                }
            }
        }
        
        // Validate file upload if present
        if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = pathinfo($_FILES['attachment_file']['name']);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar', '7z'];
            
            if (!in_array(strtolower($fileInfo['extension']), $allowedExtensions)) {
                $error = 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions);
            }
            
            $fileSize = $_FILES['attachment_file']['size'] / 1024 / 1024;
            if ($fileSize > 20) {
                $error = 'File size exceeds 20MB limit.';
            }
        }
        
        if ($data['branch_id'] <= 0) {
            $error = 'Please select or enter a valid branch';
        }
        
        if (!$error) {
            $result = updateCase($caseId, $data);
            if ($result['success']) {
                $_SESSION['success'] = "Case updated successfully!";
                header("Location: cases.php?updated=1&case_id=" . urlencode($caseId));
                exit();
            } else {
                $error = $result['error'];
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get session messages
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']);
}

include 'includes/header.php';
?>

<style>
/* ============================================
   BRANCH INPUT WITH ENTER SUPPORT - STYLES
   ============================================ */

/* Main container */
.branch-input-wrapper {
    position: relative;
}

/* Input field */
.branch-input-wrapper .form-control {
    padding: 12px 16px 12px 44px;
    border: 2px solid #E8EEEE;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #FAFCFC;
    height: 48px;
    color: #1A2E2E;
}

.branch-input-wrapper .form-control:focus {
    border-color: #006A6A;
    box-shadow: 0 0 0 4px rgba(0, 106, 106, 0.08);
    background: #ffffff;
}

.branch-input-wrapper .form-control.is-invalid {
    border-color: #DC3545;
    box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.08);
}

.branch-input-wrapper .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #B0C0C0;
    font-size: 1rem;
    pointer-events: none;
    transition: all 0.3s ease;
}

.branch-input-wrapper .form-control:focus + .input-icon {
    color: #006A6A;
}

/* Suggestions dropdown */
.branch-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #FFFFFF;
    border: 2px solid #E8EEEE;
    border-top: none;
    border-radius: 0 0 12px 12px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    margin-top: -2px;
}

.branch-suggestions.show {
    display: block;
    animation: slideDownSuggestions 0.2s ease;
}

@keyframes slideDownSuggestions {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.branch-suggestions .suggestion-item {
    padding: 10px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s ease;
    border-bottom: 1px solid #F0F4F4;
}

.branch-suggestions .suggestion-item:last-child {
    border-bottom: none;
}

.branch-suggestions .suggestion-item:hover {
    background: #F0F7F7;
}

.branch-suggestions .suggestion-item.active {
    background: linear-gradient(135deg, #006A6A, #008888);
    color: #fff;
}

.branch-suggestions .suggestion-item.active i {
    color: #fff;
}

.branch-suggestions .suggestion-item i {
    color: #006A6A;
    width: 18px;
}

.branch-suggestions .suggestion-item .highlight {
    font-weight: 700;
    color: #006A6A;
}

.branch-suggestions .suggestion-item.active .highlight {
    color: #fff;
}

.branch-suggestions .suggestion-item .suggestion-name {
    flex: 1;
}

.branch-suggestions .suggestion-item .suggestion-badge {
    font-size: 0.7rem;
    background: #F0F7F7;
    padding: 2px 10px;
    border-radius: 12px;
    color: #006A6A;
}

.branch-suggestions .suggestion-item.active .suggestion-badge {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.branch-suggestions .no-results {
    padding: 16px;
    text-align: center;
    color: #8B9A9A;
}

/* Selected branch display */
.selected-branch-display {
    display: none;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
    padding: 8px 14px;
    background: #F0F7F7;
    border-radius: 10px;
    border: 1px solid rgba(0, 106, 106, 0.1);
}

.selected-branch-display.show {
    display: flex;
    animation: fadeInBadge 0.3s ease;
}

@keyframes fadeInBadge {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.selected-branch-display .branch-name {
    font-weight: 600;
    color: #006A6A;
}

.selected-branch-display .branch-id {
    font-size: 0.7rem;
    color: #8B9A9A;
    background: #FFFFFF;
    padding: 2px 10px;
    border-radius: 12px;
}

.selected-branch-display .btn-clear-branch {
    background: none;
    border: none;
    color: #DC3545;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.3s ease;
    margin-left: auto;
}

.selected-branch-display .btn-clear-branch:hover {
    background: rgba(220, 53, 69, 0.1);
}

/* Create new branch option */
.create-new-option {
    padding: 12px 16px;
    background: #F0FDF4;
    border-top: 1px solid #E8EEEE;
    cursor: pointer;
    transition: all 0.2s ease;
}

.create-new-option:hover {
    background: #E8F8EC;
}

.create-new-option i {
    color: #2D8A4E;
    margin-right: 8px;
}

.create-new-option .new-branch-name {
    font-weight: 600;
    color: #006A6A;
}

/* Enter key hint */
.enter-hint {
    font-size: 0.7rem;
    color: #B0C0C0;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}

.enter-hint kbd {
    background: #F0F4F4;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #5A6A6A;
    border: 1px solid #D0D8D8;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .branch-input-wrapper .form-control {
        background: #1A2E2E;
        border-color: #3A4E4E;
        color: #E8F0F0;
    }
    
    .branch-input-wrapper .form-control:focus {
        background: #1A2E2E;
        border-color: #006A6A;
    }
    
    .branch-suggestions {
        background: #243838;
        border-color: #3A4E4E;
    }
    
    .branch-suggestions .suggestion-item {
        border-bottom-color: #3A4E4E;
        color: #E8F0F0;
    }
    
    .branch-suggestions .suggestion-item:hover {
        background: #1A2E2E;
    }
    
    .branch-suggestions .suggestion-item .suggestion-badge {
        background: #1A2E2E;
        color: #00AAAA;
    }
    
    .selected-branch-display {
        background: #1A2E2E;
        border-color: rgba(0, 170, 170, 0.2);
    }
    
    .selected-branch-display .branch-name {
        color: #00AAAA;
    }
    
    .selected-branch-display .branch-id {
        background: #243838;
        color: #8BAAAA;
    }
    
    .create-new-option {
        background: #1A2E2E;
        border-top-color: #3A4E4E;
    }
    
    .create-new-option:hover {
        background: #243838;
    }
    
    .enter-hint {
        color: #6A8A8A;
    }
    
    .enter-hint kbd {
        background: #1A2E2E;
        border-color: #3A4E4E;
        color: #8BAAAA;
    }
}

/* Responsive */
@media (max-width: 576px) {
    .branch-input-wrapper .form-control {
        height: 44px;
        padding: 10px 14px 10px 38px;
        font-size: 0.9rem;
    }
    
    .branch-input-wrapper .input-icon {
        left: 12px;
        font-size: 0.9rem;
    }
    
    .branch-suggestions .suggestion-item {
        padding: 8px 12px;
        font-size: 0.85rem;
    }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="mb-0">Edit Case</h2>
                <p class="text-muted mb-0">Update case information</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="cases.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Cases
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============ SUCCESS ALERT ============ -->
<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    <strong>Case ID: <?= htmlspecialchars($case['case_id']) ?></strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit"></i> Edit Case Information</h5>
        <div>
            <span class="badge bg-<?= 
                ($case['progress'] ?? '') === 'Done' ? 'success' : 
                (($case['progress'] ?? '') === 'In Progress' ? 'warning' : 
                (($case['progress'] ?? '') === 'Escalated' ? 'danger' : 'secondary')) 
            ?>">
                <?= htmlspecialchars($case['progress'] ?? 'Unknown') ?>
            </span>
            <?php if ($case['approved'] ?? false): ?>
            <span class="badge bg-success"><i class="fas fa-check"></i> Approved</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_case">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag"></i> Case ID
                        </label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($case['case_id'] ?? '') ?>" disabled>
                        <small class="text-muted">Case ID cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Date
                        </label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?= isset($case['date']) ? date('Y-m-d', strtotime($case['date'])) : '' ?>" required>
                    </div>
                    
                    <!-- ============ BRANCH INPUT WITH ENTER SUPPORT ============ -->
                    <div class="mb-3">
                        <label for="branch_input" class="form-label">
                            <i class="fas fa-store"></i> Branch <span class="text-danger">*</span>
                        </label>
                        <div class="branch-input-wrapper">
                            <?php 
                            // Get the current branch name
                            $currentBranchName = '';
                            if (isset($case['branch_id'])) {
                                foreach ($branches as $b) {
                                    if ($b['id'] == $case['branch_id']) {
                                        $currentBranchName = $b['name'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            <input type="text" 
                                   class="form-control" 
                                   id="branch_input" 
                                   name="branch_input" 
                                   placeholder="Type branch name and press Enter..."
                                   autocomplete="off"
                                   value="<?= htmlspecialchars($currentBranchName) ?>">
                            <i class="fas fa-store input-icon"></i>
                            
                            <!-- Hidden field for branch ID -->
                            <input type="hidden" id="branch_id" name="branch_id" value="<?= $case['branch_id'] ?? '' ?>">
                            
                            <!-- Suggestions dropdown -->
                            <div class="branch-suggestions" id="branchSuggestions"></div>
                        </div>
                        
                        <div class="enter-hint">
                            <i class="fas fa-info-circle"></i>
                            Type branch name and press <kbd>Enter</kbd> to select, or use arrow keys
                        </div>
                        
                        <!-- Selected branch display -->
                        <div id="selectedBranchDisplay" class="selected-branch-display <?= isset($case['branch_id']) && $case['branch_id'] ? 'show' : '' ?>">
                            <i class="fas fa-check-circle text-success"></i>
                            <span class="branch-name" id="selectedBranchName"><?= htmlspecialchars($currentBranchName) ?></span>
                            <span class="branch-id" id="selectedBranchId">ID: <?= $case['branch_id'] ?? '' ?></span>
                            <button type="button" class="btn-clear-branch" id="clearBranch" title="Clear selection">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Create new branch option (hidden by default) -->
                        <div id="createNewBranchContainer" style="display: none; margin-top: 8px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="create_new_branch" name="create_new_branch">
                                <label class="form-check-label" for="create_new_branch">
                                    <i class="fas fa-plus-circle text-success"></i>
                                    Create new branch: <strong id="newBranchNameDisplay"></strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="z_report" class="form-label">
                            <i class="fas fa-receipt"></i> Z Report
                        </label>
                        <input type="number" step="0.01" class="form-control" id="z_report" name="z_report" 
                               value="<?= htmlspecialchars($case['z_report'] ?? 0) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cash_sales" class="form-label">
                            <i class="fas fa-money-bill-wave"></i> Cash Sales
                        </label>
                        <input type="number" step="0.01" class="form-control" id="cash_sales" name="cash_sales" 
                               value="<?= htmlspecialchars($case['cash_sales'] ?? 0) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calculator"></i> Difference
                        </label>
                        <input type="text" class="form-control" id="difference" 
                               value="<?= number_format(($case['z_report'] ?? 0) - ($case['cash_sales'] ?? 0), 2) ?>" 
                               disabled>
                        <small class="text-muted">Automatically calculated (Z Report - Cash Sales)</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="progress" class="form-label">
                            <i class="fas fa-tasks"></i> Progress
                        </label>
                        <select class="form-select" id="progress" name="progress">
                            <option value="Not Start" <?= ($case['progress'] ?? '') === 'Not Start' ? 'selected' : '' ?>>Not Start</option>
                            <option value="In Progress" <?= ($case['progress'] ?? '') === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Done" <?= ($case['progress'] ?? '') === 'Done' ? 'selected' : '' ?>>Done</option>
                            <option value="Not Done" <?= ($case['progress'] ?? '') === 'Not Done' ? 'selected' : '' ?>>Not Done</option>
                            <option value="Escalated" <?= ($case['progress'] ?? '') === 'Escalated' ? 'selected' : '' ?>>Escalated</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="technical_name" class="form-label">
                            <i class="fas fa-user-cog"></i> Technical Name
                        </label>
                        <select class="form-select" id="technical_name" name="technical_name">
                            <option value="">Select Technician</option>
                            <?php foreach ($technicians as $tech): ?>
                            <option value="<?= htmlspecialchars($tech['name']) ?>" 
                                    <?= ($tech['name'] ?? '') === ($case['technical_name'] ?? '') ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tech['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="escalated_to" class="form-label">
                            <i class="fas fa-arrow-up"></i> Escalate To
                        </label>
                        <input type="text" class="form-control" id="escalated_to" name="escalated_to" 
                               value="<?= htmlspecialchars($case['escalated_to'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="remark" class="form-label">
                            <i class="fas fa-comment"></i> Remark
                        </label>
                        <textarea class="form-control" id="remark" name="remark" rows="3"><?= htmlspecialchars($case['remark'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- ============ FILE ATTACHMENT SECTION ============ -->
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3" style="border: 2px dashed #006A6A; background: #FAFCFC;">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-paperclip text-primary"></i> File Attachment
                            </h6>
                            
                            <?php if (!empty($attachments)): ?>
                            <div class="mb-3">
                                <label class="form-label">Current Attachments</label>
                                <ul class="list-group">
                                    <?php foreach ($attachments as $att): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php 
                                            $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                                            $icon = 'fa-file';
                                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'fa-file-image';
                                            elseif ($ext == 'pdf') $icon = 'fa-file-pdf';
                                            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                                            elseif (in_array($ext, ['xls', 'xlsx'])) $icon = 'fa-file-excel';
                                            elseif (in_array($ext, ['zip', 'rar', '7z'])) $icon = 'fa-file-archive';
                                            elseif ($ext == 'txt') $icon = 'fa-file-alt';
                                            ?>
                                            <a href="download.php?file=<?= urlencode($att['id']) ?>" target="_blank" class="text-primary">
                                                <i class="fas <?= $icon ?>"></i>
                                                <?= htmlspecialchars($att['file_name']) ?>
                                            </a>
                                            <span class="badge bg-secondary ms-2"><?= round($att['file_size'] / 1024, 2) ?> KB</span>
                                            <span class="badge bg-info ms-1"><?= strtoupper($ext) ?></span>
                                        </div>
                                        <button onclick="deleteAttachment(<?= $att['id'] ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0 fw-bold">
                                    <i class="fas fa-upload text-primary me-1"></i> Upload Additional Files
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addMoreFilesBtn">
                                    <i class="fas fa-plus-circle me-1"></i> Add Another File
                                </button>
                            </div>

                            <div id="fileInputsContainer">
                                <div class="mb-3 file-input-row">
                                    <input type="file" class="form-control attachment-file-input" name="attachment_files[]" multiple 
                                           accept=".jpg,.jpeg,.png,.pdf,.gif,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z">
                                </div>
                            </div>
                            
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-info-circle"></i> 
                                Max size: 20MB for archives, 10MB for documents. Allowed: JPG, PNG, PDF, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR, 7Z
                            </small>

                            <!-- Dynamic Multi-File Preview List -->
                            <div id="filePreviewList" class="mt-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <span class="fw-bold small text-dark">
                                        <i class="fas fa-file-circle-check text-success me-1"></i> New Files to Upload (<span id="totalFileCount">0</span>)
                                    </span>
                                    <span class="badge bg-primary" id="totalFileSizeBadge">0 MB</span>
                                </div>
                                <div class="list-group" id="fileListItems"></div>
                            </div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i> Uploading new files will add them as additional attachments to this case.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary" id="updateBtn">
                    <i class="fas fa-save"></i> Update Case
                </button>
                <a href="cases.php" class="btn btn-secondary">Cancel</a>
                <a href="cases.php" class="btn btn-info">
                    <i class="fas fa-list"></i> View All Cases
                </a>
                <?php if (isAdmin()): ?>
                <button type="button" onclick="deleteCase('<?= htmlspecialchars($case['case_id'] ?? '') ?>')" 
                        class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Comments Section -->
<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-comments"></i> Comments</h5>
        <span class="badge bg-primary"><?= count(getComments($case['case_id'] ?? '')) ?></span>
    </div>
    <div class="card-body">
        <div id="comments-container" class="mb-3">
            <?php 
            $comments = getComments($case['case_id'] ?? '');
            foreach ($comments as $comment): 
            ?>
            <div class="comment-box">
                <div class="comment-meta">
                    <span class="avatar"><?= strtoupper(substr(htmlspecialchars($comment['username'] ?? 'U'), 0, 1)) ?></span>
                    <strong><?= htmlspecialchars($comment['username'] ?? 'Unknown') ?></strong>
                    <span>â€¢</span>
                    <span><?= isset($comment['timestamp']) ? date('Y-m-d H:i', strtotime($comment['timestamp'])) : '' ?></span>
                </div>
                <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'] ?? '')) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?>
            <div class="text-center text-muted py-3">
                <i class="fas fa-comment-slash fa-2x mb-2 d-block"></i>
                No comments yet
            </div>
            <?php endif; ?>
        </div>
        
        <form id="comment-form" class="mt-3" onsubmit="addComment(event)">
            <input type="hidden" name="case_id" value="<?= htmlspecialchars($case['case_id'] ?? '') ?>">
            <div class="input-group">
                <input type="text" class="form-control" id="comment-input" name="comment" 
                       placeholder="Add a comment..." required>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============ BRANCH DATA ============
    const branches = <?= json_encode(array_map(function($b) { 
        return ['id' => $b['id'], 'name' => $b['name']]; 
    }, $branches)) ?>;
    
    // ============ DOM ELEMENTS ============
    const branchInput = document.getElementById('branch_input');
    const branchIdInput = document.getElementById('branch_id');
    const suggestions = document.getElementById('branchSuggestions');
    const selectedDisplay = document.getElementById('selectedBranchDisplay');
    const selectedName = document.getElementById('selectedBranchName');
    const selectedId = document.getElementById('selectedBranchId');
    const clearBtn = document.getElementById('clearBranch');
    const createNewContainer = document.getElementById('createNewBranchContainer');
    const newBranchNameDisplay = document.getElementById('newBranchNameDisplay');
    const createNewCheckbox = document.getElementById('create_new_branch');
    
    let selectedIndex = -1;
    let currentSuggestions = [];
    
    // ============ FILTER BRANCHES ============
    function filterBranches(query) {
        if (!query || query.trim() === '') {
            return [];
        }
        const q = query.toLowerCase().trim();
        return branches.filter(b => b.name.toLowerCase().includes(q));
    }
    
    // ============ SHOW SUGGESTIONS ============
    function showSuggestions(query) {
        const filtered = filterBranches(query);
        currentSuggestions = filtered;
        selectedIndex = -1;
        
        if (filtered.length === 0 && query.trim() !== '') {
            suggestions.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-plus-circle"></i> No matching branches found.
                </div>
            `;
            if (query.trim() !== '') {
                newBranchNameDisplay.textContent = `"${query.trim()}"`;
                createNewContainer.style.display = 'block';
            } else {
                createNewContainer.style.display = 'none';
            }
            suggestions.classList.add('show');
            return;
        }
        
        if (filtered.length > 0) {
            createNewContainer.style.display = 'none';
        }
        
        suggestions.innerHTML = filtered.map((b, index) => `
            <div class="suggestion-item" data-index="${index}" data-id="${b.id}">
                <i class="fas fa-store"></i>
                <span class="suggestion-name">${highlightMatch(b.name, query)}</span>
                <span class="suggestion-badge">ID: ${b.id}</span>
            </div>
        `).join('');
        
        suggestions.classList.add('show');
        
        document.querySelectorAll('.suggestion-item').forEach(el => {
            el.addEventListener('click', function() {
                const id = parseInt(this.dataset.id);
                selectBranch(id);
            });
            
            el.addEventListener('mouseenter', function() {
                document.querySelectorAll('.suggestion-item').forEach(e => e.classList.remove('active'));
                this.classList.add('active');
                selectedIndex = parseInt(this.dataset.index);
            });
        });
    }
    
    // ============ HIGHLIGHT MATCH ============
    function highlightMatch(text, query) {
        if (!query || query.trim() === '') return text;
        const q = query.trim();
        const regex = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }
    
    // ============ SELECT BRANCH ============
    function selectBranch(id) {
        const branch = branches.find(b => b.id === id);
        if (branch) {
            branchInput.value = branch.name;
            branchIdInput.value = branch.id;
            selectedName.textContent = branch.name;
            selectedId.textContent = 'ID: ' + branch.id;
            selectedDisplay.classList.add('show');
            suggestions.classList.remove('show');
            createNewContainer.style.display = 'none';
            branchInput.classList.remove('is-invalid');
        }
    }
    
    // ============ CLEAR SELECTION ============
    function clearSelection() {
        branchInput.value = '';
        branchIdInput.value = '';
        selectedDisplay.classList.remove('show');
        suggestions.classList.remove('show');
        createNewContainer.style.display = 'none';
        branchInput.focus();
        branchInput.classList.remove('is-invalid');
    }
    
    // ============ EVENT: INPUT ============
    branchInput.addEventListener('input', function() {
        const value = this.value;
        const selectedBranch = branches.find(b => b.name === value);
        
        if (selectedBranch) {
            branchIdInput.value = selectedBranch.id;
            selectedName.textContent = selectedBranch.name;
            selectedId.textContent = 'ID: ' + selectedBranch.id;
            selectedDisplay.classList.add('show');
            suggestions.classList.remove('show');
            createNewContainer.style.display = 'none';
            this.classList.remove('is-invalid');
        } else {
            branchIdInput.value = '';
            selectedDisplay.classList.remove('show');
            if (value.trim() !== '') {
                showSuggestions(value);
            } else {
                suggestions.classList.remove('show');
                createNewContainer.style.display = 'none';
            }
        }
    });
    
    // ============ EVENT: KEYDOWN ============
    branchInput.addEventListener('keydown', function(e) {
        const items = document.querySelectorAll('.suggestion-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                selectedIndex = (selectedIndex + 1) % items.length;
                items.forEach((el, i) => {
                    el.classList.toggle('active', i === selectedIndex);
                });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                items.forEach((el, i) => {
                    el.classList.toggle('active', i === selectedIndex);
                });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && items.length > 0) {
                const activeItem = items[selectedIndex];
                if (activeItem) {
                    const id = parseInt(activeItem.dataset.id);
                    selectBranch(id);
                }
            } else if (this.value.trim() !== '') {
                const value = this.value.trim();
                const exactMatch = branches.find(b => b.name.toLowerCase() === value.toLowerCase());
                if (exactMatch) {
                    selectBranch(exactMatch.id);
                } else {
                    newBranchNameDisplay.textContent = `"${value}"`;
                    createNewContainer.style.display = 'block';
                    createNewCheckbox.checked = true;
                }
            }
        } else if (e.key === 'Escape') {
            suggestions.classList.remove('show');
            createNewContainer.style.display = 'none';
        } else if (e.key === 'Tab') {
            suggestions.classList.remove('show');
        }
    });
    
    // ============ EVENT: BLUR ============
    branchInput.addEventListener('blur', function() {
        setTimeout(() => {
            suggestions.classList.remove('show');
        }, 200);
    });
    
    // ============ EVENT: FOCUS ============
    branchInput.addEventListener('focus', function() {
        if (this.value.trim() !== '' && !branches.find(b => b.name === this.value)) {
            showSuggestions(this.value);
        }
    });
    
    // ============ EVENT: CLEAR BUTTON ============
    clearBtn.addEventListener('click', function(e) {
        e.preventDefault();
        clearSelection();
    });
    
    // ============ DIFFERENCE CALCULATOR ============
    const zReport = document.getElementById('z_report');
    const cashSales = document.getElementById('cash_sales');
    const difference = document.getElementById('difference');
    
    function calculateDifference() {
        const z = parseFloat(zReport.value) || 0;
        const c = parseFloat(cashSales.value) || 0;
        difference.value = (z - c).toFixed(2);
    }
    
    zReport.addEventListener('input', calculateDifference);
    cashSales.addEventListener('input', calculateDifference);
    
    // ============ MULTI-FILE ATTACHMENT HANDLER ============
    const fileInputsContainer = document.getElementById('fileInputsContainer');
    const addMoreFilesBtn = document.getElementById('addMoreFilesBtn');
    const filePreviewList = document.getElementById('filePreviewList');
    const fileListItems = document.getElementById('fileListItems');
    const totalFileCount = document.getElementById('totalFileCount');
    const totalFileSizeBadge = document.getElementById('totalFileSizeBadge');
    
    function getFileIconInfo(extension) {
        const ext = extension.toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return { icon: 'fa-file-image', color: 'text-success' };
        if (ext === 'pdf') return { icon: 'fa-file-pdf', color: 'text-danger' };
        if (['doc', 'docx'].includes(ext)) return { icon: 'fa-file-word', color: 'text-primary' };
        if (['xls', 'xlsx'].includes(ext)) return { icon: 'fa-file-excel', color: 'text-success' };
        if (['zip', 'rar', '7z'].includes(ext)) return { icon: 'fa-file-archive', color: 'text-warning' };
        if (ext === 'txt') return { icon: 'fa-file-alt', color: 'text-secondary' };
        return { icon: 'fa-file', color: 'text-primary' };
    }

    function updateFilePreviewList() {
        fileListItems.innerHTML = '';
        const inputs = document.querySelectorAll('.attachment-file-input');
        let totalCount = 0;
        let totalSizeBytes = 0;

        inputs.forEach((input) => {
            if (input.files && input.files.length > 0) {
                Array.from(input.files).forEach((file, fileIdx) => {
                    totalCount++;
                    totalSizeBytes += file.size;

                    const fileSizeMB = (file.size / 1048576).toFixed(2);
                    const ext = file.name.split('.').pop() || '';
                    const iconInfo = getFileIconInfo(ext);

                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3 mb-1 rounded border';

                    const leftCol = document.createElement('div');
                    leftCol.className = 'd-flex align-items-center me-3 text-truncate';
                    
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.className = 'rounded me-3';
                        img.style.width = '40px';
                        img.style.height = '40px';
                        img.style.objectFit = 'cover';
                        const reader = new FileReader();
                        reader.onload = function(e) { img.src = e.target.result; };
                        reader.readAsDataURL(file);
                        leftCol.appendChild(img);
                    } else {
                        leftCol.innerHTML = `<i class="fas ${iconInfo.icon} ${iconInfo.color} fa-2x me-3"></i>`;
                    }

                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'text-truncate';
                    infoDiv.innerHTML = `
                        <div class="fw-bold text-dark text-truncate small">${escapeHtml(file.name)}</div>
                        <div class="text-muted extra-small">${fileSizeMB} MB &bull; ${ext.toUpperCase()}</div>
                    `;
                    leftCol.appendChild(infoDiv);

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm btn-outline-danger ms-2';
                    removeBtn.title = 'Remove this file';
                    removeBtn.innerHTML = '<i class="fas fa-trash-can"></i>';
                    removeBtn.onclick = function() {
                        if (inputs.length > 1 && input.files.length === 1) {
                            input.closest('.file-input-row').remove();
                        } else {
                            const dt = new DataTransfer();
                            Array.from(input.files).forEach((f, i) => {
                                if (i !== fileIdx) dt.items.add(f);
                            });
                            input.files = dt.files;
                        }
                        updateFilePreviewList();
                    };

                    item.appendChild(leftCol);
                    item.appendChild(removeBtn);
                    fileListItems.appendChild(item);
                });
            }
        });

        if (totalCount > 0) {
            totalFileCount.textContent = totalCount;
            totalFileSizeBadge.textContent = (totalSizeBytes / 1048576).toFixed(2) + ' MB';
            filePreviewList.style.display = 'block';
        } else {
            filePreviewList.style.display = 'none';
        }
    }

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    addMoreFilesBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'mb-2 file-input-row input-group';
        row.innerHTML = `
            <input type="file" class="form-control attachment-file-input" name="attachment_files[]" multiple
                   accept=".jpg,.jpeg,.png,.pdf,.gif,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z">
            <button type="button" class="btn btn-outline-danger remove-row-btn"><i class="fas fa-times"></i></button>
        `;
        fileInputsContainer.appendChild(row);

        const newInput = row.querySelector('.attachment-file-input');
        newInput.addEventListener('change', updateFilePreviewList);

        row.querySelector('.remove-row-btn').addEventListener('click', function() {
            row.remove();
            updateFilePreviewList();
        });
    });

    document.querySelectorAll('.attachment-file-input').forEach(input => {
        input.addEventListener('change', updateFilePreviewList);
    });
    
    // ============ FORM SUBMIT ============
    const form = document.querySelector('form');
    const updateBtn = document.getElementById('updateBtn');
    
    form.addEventListener('submit', function(e) {
        const branchId = branchIdInput.value;
        const branchName = branchInput.value.trim();
        
        if (!branchId && !branchName) {
            e.preventDefault();
            alert('âš ï¸ Please enter or select a branch.');
            branchInput.focus();
            branchInput.classList.add('is-invalid');
            return false;
        }
        
        if (!branchId && branchName) {
            if (!createNewCheckbox.checked) {
                e.preventDefault();
                alert('âš ï¸ Please select an existing branch or check "Create new branch".');
                createNewCheckbox.focus();
                return false;
            }
        }
        
        updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Updating...';
        updateBtn.disabled = true;
        return true;
    });
});

// ============ DELETE ATTACHMENT ============
function deleteAttachment(attachmentId) {
    if (confirm('Are you sure you want to delete this attachment?')) {
        fetch('api.php?action=delete_attachment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ attachment_id: attachmentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete attachment: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}

// ============ ADD COMMENT ============
function addComment(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    fetch('api.php?action=add_comment', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to add comment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// ============ DELETE CASE ============
function deleteCase(caseId) {
    if (confirm('Are you sure you want to delete this case? This action cannot be undone!')) {
        fetch('api.php?action=delete_case', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ case_id: caseId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'cases.php?deleted=1';
            } else {
                alert('Failed to delete case: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
