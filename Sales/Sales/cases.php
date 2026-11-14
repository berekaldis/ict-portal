<?php
// cases.php - COMPLETE FIXED FILE WITH ATTACHMENTS
$page_title = 'Case Management';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$cases = getCases($startDate, $endDate);
$branches = getBranches();
$technicians = getTechnicians();
$telegramDestinations = getTelegramDestinations();

// Check for success messages
$successMessage = '';
if (isset($_GET['updated'])) {
    $successMessage = 'Case updated successfully!';
    if (isset($_GET['case_id'])) {
        $successMessage .= ' Case ID: ' . htmlspecialchars($_GET['case_id']);
    }
}
if (isset($_GET['deleted'])) {
    $successMessage = 'Case deleted successfully!';
}
if (isset($_GET['telegram_sent'])) {
    $sent = $_GET['sent'] ?? 0;
    $failed = $_GET['failed'] ?? 0;
    $total = $_GET['total'] ?? 0;
    $attachments = $_GET['attachments'] ?? 0;
    $successMessage = "Telegram messages sent: $sent successful";
    if ($failed > 0) {
        $successMessage .= ", $failed failed (Total: $total)";
    }
    if ($attachments > 0) {
        $successMessage .= " ($attachments file(s) attached)";
    }
}

// Handle Telegram Send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_telegram') {
    // Debug: Log the POST data
    error_log("=== TELEGRAM SEND REQUEST ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Get selected cases - check both POST and the hidden field
    $selectedCases = [];
    
    // Check if we have the hidden field with JSON data
    if (isset($_POST['selected_cases_json']) && !empty($_POST['selected_cases_json'])) {
        $selectedCases = json_decode($_POST['selected_cases_json'], true);
        error_log("Selected cases from JSON: " . print_r($selectedCases, true));
    } 
    // Fallback: check regular POST array
    elseif (isset($_POST['selected_cases']) && is_array($_POST['selected_cases'])) {
        $selectedCases = $_POST['selected_cases'];
        error_log("Selected cases from POST array: " . print_r($selectedCases, true));
    }
    
    $destinationId = $_POST['destination_id'] ?? null;
    $customMessage = $_POST['custom_message'] ?? '';
    
    error_log("Destination ID: " . $destinationId);
    error_log("Custom Message: " . $customMessage);
    
    if (empty($selectedCases)) {
        $_SESSION['error'] = 'Please select at least one case to send.';
        error_log("ERROR: No cases selected");
    } elseif (!$destinationId) {
        $_SESSION['error'] = 'Please select a Telegram destination.';
        error_log("ERROR: No destination selected");
    } else {
        error_log("Sending " . count($selectedCases) . " cases to Telegram");
        $result = sendMultipleCasesToTelegram($selectedCases, $destinationId, $customMessage);
        
        if ($result['success']) {
            $sent = $result['sent'] ?? 0;
            $total = $result['total'] ?? 0;
            $failed = count($result['failed'] ?? []);
            $attachments = $result['attachments_sent'] ?? 0;
            
            error_log("Result: $sent sent, $failed failed out of $total");
            header("Location: cases.php?telegram_sent=1&sent=$sent&failed=$failed&total=$total&attachments=$attachments");
            exit();
        } else {
            $_SESSION['error'] = 'Failed to send messages: ' . ($result['error'] ?? 'Unknown error');
            error_log("ERROR: " . ($result['error'] ?? 'Unknown error'));
        }
    }
}

// Get session messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$viewCase = null;
if (isset($_GET['view'])) {
    foreach ($cases as $c) {
        if ($c['case_id'] === $_GET['view']) {
            $viewCase = $c;
            break;
        }
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="mb-0">Case Management</h2>
                <p class="text-muted mb-0">Manage all cases</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="add_case.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Case
                </a>
                <a href="export.php?type=cases<?= $startDate ? '&start_date='.urlencode($startDate) : '' ?><?= $endDate ? '&end_date='.urlencode($endDate) : '' ?>" class="btn btn-outline-success">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============ SUCCESS ALERT ============ -->
<?php if ($successMessage): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= $successMessage ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ============ DATE RANGE FILTER ============ -->
<div class="card date-filter-card">
    <form method="GET" action="cases.php" class="row g-3 align-items-end">
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-muted small fw-bold mb-1">
                <i class="fas fa-calendar-alt text-primary me-1"></i> Start Date
            </label>
            <input type="date" name="start_date" class="form-control" 
                   value="<?= htmlspecialchars($startDate ?? '') ?>">
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-muted small fw-bold mb-1">
                <i class="fas fa-calendar-alt text-primary me-1"></i> End Date
            </label>
            <input type="date" name="end_date" class="form-control" 
                   value="<?= htmlspecialchars($endDate ?? '') ?>">
        </div>
        <div class="col-md-6 col-sm-12 d-flex gap-2 align-items-end flex-wrap">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Apply Filter
            </button>
            <a href="cases.php" class="btn btn-outline-secondary">
                <i class="fas fa-undo me-1"></i> Reset
            </a>
            <?php if (!empty($startDate) || !empty($endDate)): ?>
            <div class="filter-active-pill ms-auto">
                <i class="fas fa-check-circle text-success me-1"></i>
                Filtering: 
                <strong><?= htmlspecialchars(($startDate ?: '...') . ' to ' . ($endDate ?: '...')) ?></strong>
                <a href="cases.php" class="text-danger ms-1 text-decoration-none" title="Clear Filter">
                    <i class="fas fa-times-circle"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ============ TELEGRAM SEND SECTION ============ -->
<?php if (!empty($telegramDestinations)): ?>
<div class="card mb-4" style="border-left: 4px solid #0088cc;">
    <div class="card-header">
        <h5><i class="fab fa-telegram-plane" style="color: #0088cc;"></i> Send Selected Cases to Telegram</h5>
        <span class="badge bg-primary" id="selectedCount">0 cases selected</span>
    </div>
    <div class="card-body">
        <form method="POST" id="telegramForm">
            <input type="hidden" name="action" value="send_telegram">
            <input type="hidden" name="selected_cases_json" id="selectedCasesJson">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="destination_id" class="form-label">Select Destination</label>
                        <select class="form-select" id="destination_id" name="destination_id" required>
                            <option value="">Choose Destination...</option>
                            <?php foreach ($telegramDestinations as $dest): ?>
                            <option value="<?= $dest['id'] ?>">
                                <?= htmlspecialchars($dest['name']) ?> 
                                (Chat ID: <?= htmlspecialchars($dest['chat_id']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="custom_message" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="custom_message" name="custom_message" rows="2" 
                                  placeholder="Add a custom message to include with the case details..."></textarea>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-info" id="sendTelegramBtn">
                    <i class="fab fa-telegram-plane"></i> Send Selected Cases
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="selectAllCases()">
                    <i class="fas fa-check-double"></i> Select All
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="deselectAllCases()">
                    <i class="fas fa-times"></i> Deselect All
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============ VIEW CASE DETAILS ============ -->
<?php if ($viewCase): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5>Case Details: <?= htmlspecialchars($viewCase['case_id']) ?></h5>
        <a href="cases.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date:</strong> <?= date('Y-m-d', strtotime($viewCase['date'])) ?></p>
                <p><strong>Branch:</strong> <?= htmlspecialchars($viewCase['branch_name']) ?></p>
                <p><strong>Z Report:</strong> <?= number_format($viewCase['z_report'], 2) ?></p>
                <p><strong>Cash Sales:</strong> <?= number_format($viewCase['cash_sales'], 2) ?></p>
                <p><strong>Difference:</strong> <?= number_format($viewCase['difference'], 2) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Progress:</strong> 
                    <span class="badge badge-progress bg-<?= 
                        $viewCase['progress'] === 'Done' ? 'success' : 
                        ($viewCase['progress'] === 'In Progress' ? 'warning' : 
                        ($viewCase['progress'] === 'Escalated' ? 'danger' : 'secondary')) 
                    ?>">
                        <?= htmlspecialchars($viewCase['progress']) ?>
                    </span>
                </p>
                <p><strong>Submitted By:</strong> <?= htmlspecialchars($viewCase['submitted_by']) ?></p>
                <p><strong>Technical Name:</strong> <?= htmlspecialchars($viewCase['technical_name']) ?></p>
                <p><strong>Remark:</strong> <?= nl2br(htmlspecialchars($viewCase['remark'])) ?></p>
                <div class="mt-3">
                    <strong><i class="fas fa-paperclip text-primary me-1"></i> Attachments:</strong>
                    <?php 
                    $viewAttachments = getCaseAttachments($viewCase['case_id']);
                    if (!empty($viewAttachments)): 
                    ?>
                    <div class="row g-2 mt-1">
                        <?php 
                        foreach ($viewAttachments as $att):
                            $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $icon = 'fa-file';
                            if ($isImage) $icon = 'fa-file-image';
                            elseif ($ext == 'pdf') $icon = 'fa-file-pdf';
                            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                            elseif (in_array($ext, ['xls', 'xlsx'])) $icon = 'fa-file-excel';
                            elseif (in_array($ext, ['zip', 'rar', '7z'])) $icon = 'fa-file-archive';
                            elseif ($ext == 'txt') $icon = 'fa-file-alt';
                        ?>
                        <div class="col-auto">
                            <div class="card h-100 shadow-sm border p-2" style="max-width: 220px; background: #f8f9fa;">
                                <?php if ($isImage): ?>
                                <a href="download.php?file=<?= urlencode($att['id']) ?>" target="_blank" class="d-block mb-1 text-center">
                                    <img src="download.php?file=<?= urlencode($att['id']) ?>" alt="<?= htmlspecialchars($att['file_name']) ?>" 
                                         class="img-fluid rounded border" style="max-height: 100px; object-fit: cover; width: 100%;">
                                </a>
                                <?php endif; ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas <?= $icon ?> me-2 text-primary fa-lg"></i>
                                    <div class="text-truncate flex-grow-1" style="font-size: 0.85rem;">
                                        <a href="download.php?file=<?= urlencode($att['id']) ?>" target="_blank" class="fw-bold text-dark text-decoration-none text-truncate d-block" title="<?= htmlspecialchars($att['file_name']) ?>">
                                            <?= htmlspecialchars($att['file_name']) ?>
                                        </a>
                                        <span class="text-muted extra-small"><?= round($att['file_size'] / 1024, 1) ?> KB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-muted ms-2">No attachments</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============ CASES TABLE ============ -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="casesTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                        </th>
                        <th>Case ID</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Z Report</th>
                        <th>Cash Sales</th>
                        <th>Difference</th>
                        <th>Progress</th>
                        <th>Approved</th>
                        <th>Files</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="case-checkbox" 
                                   data-case-id="<?= htmlspecialchars($case['case_id']) ?>" 
                                   onchange="updateSelectedCount()">
                        </td>
                        <td><strong><?= htmlspecialchars($case['case_id']) ?></strong></td>
                        <td><?= date('Y-m-d', strtotime($case['date'])) ?></td>
                        <td><?= htmlspecialchars($case['branch_name']) ?></td>
                        <td><?= number_format($case['z_report'], 2) ?></td>
                        <td><?= number_format($case['cash_sales'], 2) ?></td>
                        <td><?= number_format($case['difference'], 2) ?></td>
                        <td>
                            <span class="badge badge-progress bg-<?= 
                                $case['progress'] === 'Done' ? 'success' : 
                                ($case['progress'] === 'In Progress' ? 'warning' : 
                                ($case['progress'] === 'Escalated' ? 'danger' : 'secondary')) 
                            ?>">
                                <?= htmlspecialchars($case['progress']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($case['approved']): ?>
                            <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                            <i class="fas fa-times-circle text-danger"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            // Get attachments for this case
                            $attachments = getCaseAttachments($case['case_id']);
                            if (!empty($attachments)): 
                                // Display each attachment as an icon
                                foreach ($attachments as $att):
                                    $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                                    $icon = 'fa-file';
                                    $color = 'text-primary';
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                        $icon = 'fa-file-image';
                                        $color = 'text-success';
                                    } elseif ($ext == 'pdf') {
                                        $icon = 'fa-file-pdf';
                                        $color = 'text-danger';
                                    } elseif (in_array($ext, ['doc', 'docx'])) {
                                        $icon = 'fa-file-word';
                                        $color = 'text-primary';
                                    } elseif (in_array($ext, ['xls', 'xlsx'])) {
                                        $icon = 'fa-file-excel';
                                        $color = 'text-success';
                                    } elseif (in_array($ext, ['zip', 'rar', '7z'])) {
                                        $icon = 'fa-file-archive';
                                        $color = 'text-warning';
                                    } elseif ($ext == 'txt') {
                                        $icon = 'fa-file-alt';
                                        $color = 'text-secondary';
                                    }
                            ?>
                            <a href="download.php?file=<?= urlencode($att['id']) ?>" target="_blank" 
                               class="text-decoration-none" title="Download <?= htmlspecialchars($att['file_name']) ?>">
                                <i class="fas <?= $icon ?> <?= $color ?> me-1"></i>
                            </a>
                            <?php 
                                endforeach;
                                // Show count badge
                                if (count($attachments) > 1):
                            ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= count($attachments) ?></span>
                            <?php 
                                endif;
                            elseif (!empty($case['attachments'])): 
                            ?>
                            <a href="<?= htmlspecialchars($case['attachments']) ?>" target="_blank" title="View attachment">
                                <i class="fas fa-link text-info"></i>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_case.php?id=<?= $case['case_id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="cases.php?view=<?= $case['case_id'] ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (isAdmin()): ?>
                            <button onclick="deleteCase('<?= $case['case_id'] ?>')" class="btn btn-sm btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (!empty($telegramDestinations)): ?>
                            <button onclick="sendSingleCase('<?= $case['case_id'] ?>')" class="btn btn-sm btn-info" title="Send to Telegram" style="background: #0088cc; border-color: #0088cc;">
                                <i class="fab fa-telegram-plane"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cases)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <h5>No Cases Found</h5>
                            <p class="text-muted">Click "Add New Case" to create your first case.</p>
                            <a href="add_case.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Case
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ============ GLOBAL VARIABLES ============
let selectedCases = [];

// ============ CHECKBOX FUNCTIONS ============

// Toggle all checkboxes
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.case-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectedCount();
}

// Update selected count and store selected cases
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.case-checkbox:checked');
    const count = checkboxes.length;
    
    // Update the count display
    const countDisplay = document.getElementById('selectedCount');
    if (countDisplay) {
        countDisplay.textContent = count + ' cases selected';
    }
    
    // Store selected case IDs
    selectedCases = [];
    checkboxes.forEach(cb => {
        const caseId = cb.getAttribute('data-case-id');
        if (caseId) {
            selectedCases.push(caseId);
        }
    });
    
    // Update hidden field
    document.getElementById('selectedCasesJson').value = JSON.stringify(selectedCases);
    
    // Enable/disable send button
    const sendBtn = document.getElementById('sendTelegramBtn');
    if (sendBtn) {
        sendBtn.disabled = count === 0;
    }
    
    // Update select all checkbox state
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.case-checkbox');
    if (selectAll && allCheckboxes.length > 0) {
        const allChecked = allCheckboxes.length === document.querySelectorAll('.case-checkbox:checked').length;
        selectAll.checked = allChecked;
    }
}

// Select all cases
function selectAllCases() {
    document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAll').checked = true;
    updateSelectedCount();
}

// Deselect all cases
function deselectAllCases() {
    document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateSelectedCount();
}

// ============ TELEGRAM FORM HANDLING ============

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const telegramForm = document.getElementById('telegramForm');
    
    if (telegramForm) {
        telegramForm.addEventListener('submit', function(e) {
            // Get all checked checkboxes
            const checkedBoxes = document.querySelectorAll('.case-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one case to send.');
                return false;
            }
            
            const destination = document.getElementById('destination_id');
            if (!destination.value) {
                e.preventDefault();
                alert('Please select a Telegram destination.');
                return false;
            }
            
            // Update the hidden field with selected cases
            const caseIds = [];
            checkedBoxes.forEach(cb => {
                const caseId = cb.getAttribute('data-case-id');
                if (caseId) {
                    caseIds.push(caseId);
                }
            });
            document.getElementById('selectedCasesJson').value = JSON.stringify(caseIds);
            
            // Show confirmation with case count
            const destName = destination.options[destination.selectedIndex].text;
            const caseList = caseIds.map((id, i) => `  ${i+1}. ${id}`).join('\n');
            
            const confirmMsg = `Send ${checkedBoxes.length} case(s) to Telegram?\n\n` +
                               `Destination: ${destName}\n\n` +
                               `Cases:\n${caseList}\n\n` +
                               `All cases will be sent as a single message.`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
    
    // Initial count
    updateSelectedCount();
});

// ============ SEND SINGLE CASE ============

function sendSingleCase(caseId) {
    const destinations = <?= json_encode($telegramDestinations) ?>;
    
    if (destinations.length === 0) {
        alert('No Telegram destinations configured. Please add one in Settings.');
        return;
    }
    
    // Build destination list
    let options = destinations.map((d, i) => 
        `${i+1}. ${d.name} (Chat ID: ${d.chat_id})`
    ).join('\n');
    
    const choice = prompt(
        'Select destination by number:\n\n' +
        options + '\n\n' +
        'Enter the number:',
        '1'
    );
    
    if (choice === null) return;
    
    let destinationId = null;
    const numChoice = parseInt(choice);
    
    if (!isNaN(numChoice) && numChoice >= 1 && numChoice <= destinations.length) {
        destinationId = destinations[numChoice - 1].id;
    }
    
    if (!destinationId) {
        alert('Invalid destination selected.');
        return;
    }
    
    // Show confirmation with case details
    const destName = destinations.find(d => d.id === destinationId)?.name || 'Unknown';
    const confirmMsg = `Send case ${caseId} to Telegram?\n\n` +
                       `Destination: ${destName}`;
    
    if (!confirm(confirmMsg)) return;
    
    // Create and submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="send_telegram">
        <input type="hidden" name="destination_id" value="${destinationId}">
        <input type="hidden" name="selected_cases_json" value='${JSON.stringify([caseId])}'>
    `;
    document.body.appendChild(form);
    form.submit();
}

// ============ DELETE CASE ============

function deleteCase(caseId) {
    if (confirm('Are you sure you want to delete this case?')) {
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

function toggleCustomDateRange(elementId) {
    const box = document.getElementById(elementId);
    if (box) {
        if (box.style.display === 'none' || box.style.display === '') {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }
}

// ============ DATATABLE INITIALIZATION ============

$(document).ready(function() {
    // Check if DataTable is already initialized
    if ($.fn.DataTable.isDataTable('#casesTable')) {
        $('#casesTable').DataTable().destroy();
    }
    
    // Initialize DataTable
    var table = $('#casesTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [0, 10] }
        ],
        drawCallback: function() {
            // Update selected count after table redraw
            updateSelectedCount();
            
            // Re-bind checkbox events
            $('.case-checkbox').off('change').on('change', function() {
                updateSelectedCount();
            });
        },
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ cases",
            infoEmpty: "No cases found",
            infoFiltered: "(filtered from _MAX_ total cases)",
            zeroRecords: "No matching cases found"
        }
    });
    
    // Initial count
    updateSelectedCount();
});

// Also update when DataTable redraws
$(document).on('draw.dt', function() {
    updateSelectedCount();
});

// Re-bind after any DOM changes
document.addEventListener('DOMContentLoaded', function() {
    // Initial count
    setTimeout(updateSelectedCount, 500);

});

// In cases.php, update the completed cases section if you have one

// Check for completed cases approval status
if (isset($_GET['completed_approved'])) {
    $successMessage = 'Case approved successfully!';
}
if (isset($_GET['completed_unapproved'])) {
    $successMessage = 'Case unapproved successfully!';
}
</script>

<style>
/* Additional styles */
.case-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #6F4E37;
}

#selectAll {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #6F4E37;
}

#selectedCount {
    font-size: 0.9rem;
    font-weight: 500;
}

.btn-info {
    background: linear-gradient(135deg, #0088cc 0%, #00a3e0 100%);
    border-color: #0088cc;
    color: #fff;
}

.btn-info:hover {
    background: #0088cc;
    border-color: #0088cc;
    color: #fff;
    box-shadow: 0 4px 20px rgba(0, 136, 204, 0.35);
}

.btn-info:disabled {
    background: #6c757d;
    border-color: #6c757d;
    cursor: not-allowed;
}

/* Telegram send section border */
.card[style*="border-left"] {
    border-left: 4px solid #0088cc !important;
}

/* DataTable responsive fixes */
.dataTables_wrapper .dataTables_filter input {
    border: 2px solid #E8E3DE;
    border-radius: 8px;
    padding: 6px 12px;
    margin-left: 8px;
    transition: all 0.3s ease;
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: #6F4E37;
    box-shadow: 0 0 0 4px rgba(111, 78, 55, 0.1);
    outline: none;
}

.dataTables_wrapper .dataTables_length select {
    border: 2px solid #E8E3DE;
    border-radius: 8px;
    padding: 4px 8px;
    margin: 0 4px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px;
    padding: 4px 12px;
    margin: 0 2px;
    border: 1px solid #E8E3DE;
    background: #fff;
    transition: all 0.3s ease;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #6F4E37, #8B6B50);
    border-color: #6F4E37;
    color: #fff !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #6F4E37;
    border-color: #6F4E37;
    color: #fff !important;
}
</style>

<?php include 'includes/footer.php'; ?>
