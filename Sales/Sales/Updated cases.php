<?php
// cases.php - Add this at the top after includes
$page_title = 'Case Management';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$cases = getCases();
$branches = getBranches();
$technicians = getTechnicians();

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
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Case Management</h2>
            <p class="text-muted mb-0">Manage all cases</p>
        </div>
        <a href="add_case.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Case
        </a>
    </div>
</div>

<!-- ============ SUCCESS ALERT ============ -->
<?php if ($successMessage): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= $successMessage ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ============ VIEW CASE MODAL ============ -->
<?php if ($viewCase): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5>Case Details: <?= htmlspecialchars($viewCase['case_id']) ?></h5>
        <a href="cases.php" class="btn btn-sm btn-secondary">Back to List</a>
    </div>
    <div class="card-body">
        <!-- Case details here -->
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="casesTable">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Z Report</th>
                        <th>Cash Sales</th>
                        <th>Difference</th>
                        <th>Progress</th>
                        <th>Approved</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                    <tr>
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
                            <a href="edit_case.php?id=<?= $case['case_id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="cases.php?view=<?= $case['case_id'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (isAdmin()): ?>
                            <button onclick="deleteCase('<?= $case['case_id'] ?>')" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cases)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                            No cases found
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
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
</script>

<?php include 'includes/footer.php'; ?>
