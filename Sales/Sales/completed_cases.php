<?php
// completed_cases.php - COMPLETE FIXED FILE
$page_title = 'Completed Cases';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$completedCases = getCompletedCases($startDate, $endDate);
$message = '';
$error = '';

// ============ HANDLE FORM-BASED APPROVAL (FALLBACK) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_action'])) {
    $caseId = $_POST['case_id'] ?? '';
    $approved = isset($_POST['approved']) ? (int)$_POST['approved'] : 0;
    
    if ($caseId) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE sales_completed_cases SET approved = ?, last_updated = NOW() WHERE case_id = ?");
            $stmt->execute([$approved, $caseId]);
            
            $action = $approved ? 'Approved' : 'Unapproved';
            logAction($_SESSION['username'] ?? 'System', 'Approve Completed Case', "$action completed case $caseId");
            
            $message = "âœ… Case $caseId $action successfully!";
            $completedCases = getCompletedCases($startDate, $endDate); // Refresh the list
            
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Invalid case ID';
    }
}

// ============ HANDLE AJAX APPROVAL (via POST) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_case') {
    $caseId = $_POST['case_id'] ?? '';
    $isApproved = isset($_POST['approved']) ? (int)$_POST['approved'] : 0;
    
    if ($caseId) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE sales_completed_cases SET approved = ?, last_updated = NOW() WHERE case_id = ?");
            $stmt->execute([$isApproved, $caseId]);
            
            if ($isApproved) {
                logAction($_SESSION['username'] ?? 'System', 'Approve Completed Case', "Approved completed case $caseId");
                $message = "âœ… Case $caseId approved successfully!";
            } else {
                logAction($_SESSION['username'] ?? 'System', 'Unapprove Completed Case', "Unapproved completed case $caseId");
                $message = "Case $caseId unapproved successfully!";
            }
            
            $completedCases = getCompletedCases($startDate, $endDate);
            
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Invalid case ID';
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Completed Cases</h2>
            <p class="text-muted mb-0">All completed and approved cases</p>
        </div>
        <div class="d-flex gap-2">
            <a href="export.php?type=completed<?= $startDate ? '&start_date='.urlencode($startDate) : '' ?><?= $endDate ? '&end_date='.urlencode($endDate) : '' ?>" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export
            </a>
        </div>
    </div>
</div>

<!-- ============ SUCCESS/ERROR MESSAGES ============ -->
<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ============ DATE RANGE FILTER ============ -->
<div class="card date-filter-card">
    <form method="GET" action="completed_cases.php" class="row g-3 align-items-end">
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
            <a href="completed_cases.php" class="btn btn-outline-secondary">
                <i class="fas fa-undo me-1"></i> Reset
            </a>
            <?php if (!empty($startDate) || !empty($endDate)): ?>
            <div class="filter-active-pill ms-auto">
                <i class="fas fa-check-circle text-success me-1"></i>
                Filtering: 
                <strong><?= htmlspecialchars(($startDate ?: '...') . ' to ' . ($endDate ?: '...')) ?></strong>
                <a href="completed_cases.php" class="text-danger ms-1 text-decoration-none" title="Clear Filter">
                    <i class="fas fa-times-circle"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="completedTable">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Z Report</th>
                        <th>Cash Sales</th>
                        <th>Difference</th>
                        <th>Fixed Date</th>
                        <th>Approved</th>
                        <th>Files</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedCases as $case): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($case['case_id']) ?></strong></td>
                        <td><?= date('Y-m-d', strtotime($case['date'])) ?></td>
                        <td><?= htmlspecialchars($case['branch_name']) ?></td>
                        <td><?= number_format($case['z_report'], 2) ?></td>
                        <td><?= number_format($case['cash_sales'], 2) ?></td>
                        <td><?= number_format($case['difference'], 2) ?></td>
                        <td><?= $case['case_fixed_date'] ? date('Y-m-d', strtotime($case['case_fixed_date'])) : '-' ?></td>
                        <td>
                            <?php if ($case['approved']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Approved
                            </span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $attachments = getCaseAttachments($case['case_id']);
                            if (!empty($attachments)): 
                                foreach ($attachments as $att):
                                    $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
                                    $icon = 'fa-file';
                                    $color = 'text-primary';
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
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
                            <!-- ============ APPROVAL BUTTONS ============ -->
                            <?php if ($case['approved']): ?>
                            <!-- Form-based Unapprove -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to Unapprove this case?')">
                                <input type="hidden" name="approve_action" value="1">
                                <input type="hidden" name="case_id" value="<?= htmlspecialchars($case['case_id']) ?>">
                                <input type="hidden" name="approved" value="0">
                                <button type="submit" class="btn btn-sm btn-warning" title="Unapprove">
                                    <i class="fas fa-undo"></i> Unapprove
                                </button>
                            </form>
                            <!-- AJAX Unapprove -->
                            <button onclick="toggleApproval('<?= $case['case_id'] ?>', 0)" 
                                    class="btn btn-sm btn-outline-warning" title="Unapprove (AJAX)">
                                <i class="fas fa-sync"></i>
                            </button>
                            <?php else: ?>
                            <!-- Form-based Approve -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to Approve this case?')">
                                <input type="hidden" name="approve_action" value="1">
                                <input type="hidden" name="case_id" value="<?= htmlspecialchars($case['case_id']) ?>">
                                <input type="hidden" name="approved" value="1">
                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <!-- AJAX Approve -->
                            <button onclick="toggleApproval('<?= $case['case_id'] ?>', 1)" 
                                    class="btn btn-sm btn-outline-success" title="Approve (AJAX)">
                                <i class="fas fa-sync"></i>
                            </button>
                            <?php endif; ?>
                            
                            <!-- View Details -->
                            <button onclick="viewCompletedCase('<?= $case['case_id'] ?>')" 
                                    class="btn btn-sm btn-info" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($completedCases)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <h5>No completed cases found</h5>
                            <p class="text-muted">Cases marked as "Done" will appear here automatically.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Case Modal -->
<div class="modal fade" id="viewCaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Case Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="caseDetailsBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============ TOGGLE APPROVAL (AJAX METHOD) ============
function toggleApproval(caseId, approved) {
    const action = approved ? 'Approve' : 'Unapprove';
    const confirmMsg = `Are you sure you want to ${action} this case?`;
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    // Show loading state on all buttons for this case
    const buttons = document.querySelectorAll(`button[onclick*="toggleApproval('${caseId}'"]`);
    buttons.forEach(btn => {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        btn.disabled = true;
    });
    
    // Send AJAX request
    fetch('api.php?action=approve_completed_case', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            case_id: caseId, 
            approved: approved 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show updated status
            window.location.reload();
        } else {
            alert('Failed to ' + action.toLowerCase() + ' case: ' + (data.error || 'Unknown error'));
            // Reset buttons
            buttons.forEach(btn => {
                btn.innerHTML = approved ? '<i class="fas fa-check"></i>' : '<i class="fas fa-undo"></i>';
                btn.disabled = false;
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        // Reset buttons
        buttons.forEach(btn => {
            btn.innerHTML = approved ? '<i class="fas fa-check"></i>' : '<i class="fas fa-undo"></i>';
            btn.disabled = false;
        });
    });
}

// ============ VIEW COMPLETED CASE ============
function viewCompletedCase(caseId) {
    // Show modal with loading
    const modal = new bootstrap.Modal(document.getElementById('viewCaseModal'));
    const body = document.getElementById('caseDetailsBody');
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading case details...</p>
        </div>
    `;
    modal.show();
    
    // Fetch case details
    fetch('api.php?action=get_completed_case', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ case_id: caseId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const c = data.case;
            let attachmentsHtml = '<span class="text-muted">No attachments</span>';
            
            if (c.attachments && c.attachments.length > 0) {
                attachmentsHtml = '<div class="row g-2 mt-1">';
                c.attachments.forEach(att => {
                    const ext = (att.file_name.split('.').pop() || '').toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                    let icon = 'fa-file';
                    if (isImage) icon = 'fa-file-image';
                    else if (ext === 'pdf') icon = 'fa-file-pdf';
                    else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word';
                    else if (['xls', 'xlsx'].includes(ext)) icon = 'fa-file-excel';
                    else if (['zip', 'rar', '7z'].includes(ext)) icon = 'fa-file-archive';
                    else if (ext === 'txt') icon = 'fa-file-alt';

                    const downloadUrl = `download.php?file=${encodeURIComponent(att.id)}`;
                    const fileSizeKB = (att.file_size / 1024).toFixed(1);

                    attachmentsHtml += `
                        <div class="col-auto">
                            <div class="card h-100 shadow-sm border p-2" style="max-width: 220px; background: #f8f9fa;">
                                ${isImage ? `
                                <a href="${downloadUrl}" target="_blank" class="d-block mb-1 text-center">
                                    <img src="${downloadUrl}" alt="${escapeHtml(att.file_name)}" 
                                         class="img-fluid rounded border" style="max-height: 100px; object-fit: cover; width: 100%;">
                                </a>` : ''}
                                <div class="d-flex align-items-center">
                                    <i class="fas ${icon} me-2 text-primary fa-lg"></i>
                                    <div class="text-truncate flex-grow-1" style="font-size: 0.85rem;">
                                        <a href="${downloadUrl}" target="_blank" class="fw-bold text-dark text-decoration-none text-truncate d-block" title="${escapeHtml(att.file_name)}">
                                            ${escapeHtml(att.file_name)}
                                        </a>
                                        <span class="text-muted extra-small">${fileSizeKB} KB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                attachmentsHtml += '</div>';
            }

            body.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Case ID:</strong> ${escapeHtml(c.case_id)}</p>
                        <p><strong>Date:</strong> ${escapeHtml(c.date)}</p>
                        <p><strong>Branch:</strong> ${escapeHtml(c.branch_name || '')}</p>
                        <p><strong>Z Report:</strong> ${parseFloat(c.z_report).toFixed(2)} ETB</p>
                        <p><strong>Cash Sales:</strong> ${parseFloat(c.cash_sales).toFixed(2)} ETB</p>
                        <p><strong>Difference:</strong> ${parseFloat(c.difference).toFixed(2)} ETB</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Progress:</strong> <span class="badge bg-success">${escapeHtml(c.progress)}</span></p>
                        <p><strong>Fixed Date:</strong> ${c.case_fixed_date || 'N/A'}</p>
                        <p><strong>Submitted By:</strong> ${escapeHtml(c.submitted_by || 'N/A')}</p>
                        <p><strong>Technical Name:</strong> ${escapeHtml(c.technical_name || 'N/A')}</p>
                        <p><strong>Remark:</strong> ${escapeHtml(c.remark || 'N/A')}</p>
                        <p><strong>Approved:</strong> ${c.approved ? '✅ Yes' : '❌ No'}</p>
                    </div>
                    <div class="col-12 mt-3 pt-3 border-top">
                        <strong><i class="fas fa-paperclip text-primary me-1"></i> Attachments:</strong>
                        ${attachmentsHtml}
                    </div>
                </div>
            `;
        } else {
            body.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    Failed to load case details: ${data.error || 'Unknown error'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        body.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                An error occurred while loading case details.
            </div>
        `;
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
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
    $('#completedTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [8, 9] }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ completed cases",
            infoEmpty: "No completed cases found",
            infoFiltered: "(filtered from _MAX_ total cases)",
            zeroRecords: "No matching cases found"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
