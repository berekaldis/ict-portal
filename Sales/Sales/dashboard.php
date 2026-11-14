<?php
// dashboard.php
$page_title = 'Dashboard';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Get database connection
$db = Database::getInstance()->getConnection();

// ============ STATISTICS ============
// Total Cases (from both tables)
$totalCasesQuery = $db->query("SELECT COUNT(*) as count FROM sales_cases");
$totalCases = $totalCasesQuery->fetch()['count'] ?? 0;

$totalCompletedQuery = $db->query("SELECT COUNT(*) as count FROM sales_completed_cases");
$totalCompleted = $totalCompletedQuery->fetch()['count'] ?? 0;

// Total Cases = Active Cases + Completed Cases
$totalAllCases = $totalCases + $totalCompleted;

// Completed Cases (same as above)
$completedCases = $totalCompleted;

// Pending Cases (not Done)
$pendingCases = $db->query("SELECT COUNT(*) as count FROM sales_cases WHERE progress != 'Done'")->fetch()['count'] ?? 0;

// Approved Cases
$approvedCases = $db->query("SELECT COUNT(*) as count FROM sales_completed_cases WHERE approved = TRUE")->fetch()['count'] ?? 0;

// In Progress Cases
$inProgressCases = $db->query("SELECT COUNT(*) as count FROM sales_cases WHERE progress = 'In Progress'")->fetch()['count'] ?? 0;

// Escalated Cases
$escalatedCases = $db->query("SELECT COUNT(*) as count FROM sales_cases WHERE progress = 'Escalated'")->fetch()['count'] ?? 0;

// Not Started Cases
$notStartedCases = $db->query("SELECT COUNT(*) as count FROM sales_cases WHERE progress = 'Not Start'")->fetch()['count'] ?? 0;

// Active Cases (in cases table)
$activeCases = $totalCases;

// ============ CHART DATA ============
// Progress Distribution
$progressData = [
    'Not Start' => $notStartedCases,
    'In Progress' => $inProgressCases,
    'Escalated' => $escalatedCases,
    'Done' => $completedCases
];

// Monthly Cases (Last 6 months)
$monthlyCases = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM sales_cases 
        WHERE DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $count = $stmt->fetch()['count'] ?? 0;
    
    $stmt2 = $db->prepare("
        SELECT COUNT(*) as count FROM sales_completed_cases 
        WHERE DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt2->execute([$month]);
    $count2 = $stmt2->fetch()['count'] ?? 0;
    
    $monthlyCases[] = [
        'month' => $monthName,
        'cases' => $count,
        'completed' => $count2
    ];
}

// Branch Statistics
$branchStats = [];
$branches = getBranches();
foreach ($branches as $branch) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN progress = 'Done' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN progress = 'Escalated' THEN 1 ELSE 0 END) as escalated
        FROM sales_cases 
        WHERE branch_id = ?
    ");
    $stmt->execute([$branch['id']]);
    $stats = $stmt->fetch();
    
    // Also get completed cases for this branch
    $stmt2 = $db->prepare("
        SELECT COUNT(*) as count FROM sales_completed_cases WHERE branch_id = ?
    ");
    $stmt2->execute([$branch['id']]);
    $completedCount = $stmt2->fetch()['count'] ?? 0;
    
    $branchStats[] = [
        'name' => $branch['name'],
        'total' => ($stats['total'] ?? 0) + $completedCount,
        'active' => $stats['total'] ?? 0,
        'completed' => $stats['completed'] ?? 0,
        'completed_total' => $completedCount,
        'escalated' => $stats['escalated'] ?? 0
    ];
}

// Weekly Trend (Last 7 days)
$weeklyTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime("-$i days"));
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM sales_cases 
        WHERE DATE(date) = ?
    ");
    $stmt->execute([$date]);
    $count = $stmt->fetch()['count'] ?? 0;
    
    $weeklyTrend[] = [
        'day' => $dayName,
        'count' => $count
    ];
}

// ============ RECENT CASES ============
$recentCases = getCases();
$recentCases = array_slice($recentCases, 0, 5);

include 'includes/header.php';
?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="mb-0">Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
            </div>
            <div class="mt-2 mt-md-0 d-flex gap-2 flex-wrap">
                <button class="btn btn-info" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-warning" onclick="exportDashboard()">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ HORIZONTAL STATISTICS CARDS ============ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card-horizontal">
            <div class="stat-icon-horizontal bg-primary-gradient">
                <i class="fas fa-folder-open"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $totalAllCases ?></div>
                <div class="stat-label">All Cases</div>
                <small class="text-muted">Active: <?= $activeCases ?> | Completed: <?= $completedCases ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card-horizontal">
            <div class="stat-icon-horizontal bg-warning-gradient">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $pendingCases ?></div>
                <div class="stat-label">Pending Cases</div>
                <small class="text-muted">Not yet completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card-horizontal">
            <div class="stat-icon-horizontal bg-success-gradient">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $completedCases ?></div>
                <div class="stat-label">Completed Cases</div>
                <small class="text-muted"><?= $approvedCases ?> approved</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card-horizontal">
            <div class="stat-icon-horizontal bg-info-gradient">
                <i class="fas fa-award"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= $approvedCases ?></div>
                <div class="stat-label">Approved Cases</div>
                <small class="text-muted">Out of <?= $completedCases ?> completed</small>
            </div>
        </div>
    </div>
</div>

<!-- ============ QUICK STATS ============ -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> Quick Stats</h5>
                <span class="badge bg-secondary">Summary</span>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-2 col-md-2">
                        <div class="small text-muted">All Cases</div>
                        <div class="h5 mb-0"><?= $totalAllCases ?></div>
                    </div>
                    <div class="col-2 col-md-2">
                        <div class="small text-muted">Active</div>
                        <div class="h5 mb-0 text-primary"><?= $activeCases ?></div>
                    </div>
                    <div class="col-2 col-md-2">
                        <div class="small text-muted">Completed</div>
                        <div class="h5 mb-0 text-success"><?= $completedCases ?></div>
                    </div>
                    <div class="col-2 col-md-2">
                        <div class="small text-muted">Pending</div>
                        <div class="h5 mb-0 text-warning"><?= $pendingCases ?></div>
                    </div>
                    <div class="col-2 col-md-2">
                        <div class="small text-muted">Approved</div>
                        <div class="h5 mb-0 text-info"><?= $approvedCases ?></div>
                    </div>
                    <div class="col-2 col-md-2">
                        <div class="small text-muted">Rate</div>
                        <div class="h5 mb-0 text-success">
                            <?= $totalAllCases > 0 ? round(($completedCases / $totalAllCases) * 100) : 0 ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ CHARTS ROW ============ -->
<div class="row">
    <!-- Progress Distribution Chart -->
    <div class="col-lg-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Case Progress Distribution</h5>
                <span class="badge bg-primary">Overview</span>
            </div>
            <div class="card-body">
                <canvas id="progressChart" style="max-height: 280px;"></canvas>
                <div class="row mt-3">
                    <?php foreach ($progressData as $label => $value): ?>
                    <div class="col-6 col-md-3 text-center">
                        <div class="small text-muted"><?= $label ?></div>
                        <div class="fw-bold"><?= $value ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Trend Chart -->
    <div class="col-lg-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Monthly Trend</h5>
                <span class="badge bg-success">Last 6 Months</span>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============ ADDITIONAL CHARTS ROW ============ -->
<div class="row">
    <!-- Branch Performance -->
    <div class="col-lg-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-store"></i> Branch Performance</h5>
                <span class="badge bg-info">By Branch</span>
            </div>
            <div class="card-body">
                <canvas id="branchChart" style="max-height: 250px;"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Total</th>
                                <th>Active</th>
                                <th>Completed</th>
                                <th>Escalated</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branchStats as $branch): ?>
                            <tr>
                                <td><?= htmlspecialchars($branch['name']) ?></td>
                                <td><?= $branch['total'] ?></td>
                                <td><?= $branch['active'] ?></td>
                                <td><?= $branch['completed_total'] ?></td>
                                <td><?= $branch['escalated'] ?></td>
                                <td>
                                    <?php 
                                    $rate = $branch['total'] > 0 ? round(($branch['completed_total'] / $branch['total']) * 100) : 0;
                                    ?>
                                    <div class="progress" style="height: 6px; width: 60px; display: inline-block;">
                                        <div class="progress-bar bg-success" style="width: <?= $rate ?>%;"></div>
                                    </div>
                                    <span class="ms-1 small"><?= $rate ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weekly Trend -->
    <div class="col-lg-6 col-md-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-calendar-week"></i> Weekly Case Activity</h5>
                <span class="badge bg-warning">Last 7 Days</span>
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" style="max-height: 250px;"></canvas>
                <div class="mt-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Total this week: 
                            <strong><?= array_sum(array_column($weeklyTrend, 'count')) ?></strong>
                        </span>
                        <span class="text-muted small">
                            Avg: <strong><?= round(array_sum(array_column($weeklyTrend, 'count')) / 7, 1) ?></strong> / day
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ============ CHARTS INITIALIZATION ============ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color palette
    const colors = {
        primary: '#6F4E37',
        success: '#2D8A4E',
        warning: '#F0A500',
        danger: '#DC3545',
        info: '#17A2B8',
        secondary: '#6C757D'
    };
    
    // ========== Progress Distribution Chart (Doughnut) ==========
    const ctx1 = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['Not Start', 'In Progress', 'Escalated', 'Done'],
            datasets: [{
                data: [
                    <?= $notStartedCases ?>,
                    <?= $inProgressCases ?>,
                    <?= $escalatedCases ?>,
                    <?= $completedCases ?>
                ],
                backgroundColor: [
                    colors.secondary,
                    colors.warning,
                    colors.danger,
                    colors.success
                ],
                borderWidth: 3,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 13
                    },
                    formatter: (value, ctx) => {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        return total > 0 ? Math.round((value / total) * 100) + '%' : '0%';
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    
    // ========== Monthly Trend Chart (Bar) ==========
    const ctx2 = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($monthlyCases, 'month')) ?>,
            datasets: [
                {
                    label: 'New Cases',
                    data: <?= json_encode(array_column($monthlyCases, 'cases')) ?>,
                    backgroundColor: 'rgba(111, 78, 55, 0.7)',
                    borderColor: colors.primary,
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Completed',
                    data: <?= json_encode(array_column($monthlyCases, 'completed')) ?>,
                    backgroundColor: 'rgba(45, 138, 78, 0.7)',
                    borderColor: colors.success,
                    borderWidth: 2,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // ========== Branch Chart (Bar) ==========
    const ctx3 = document.getElementById('branchChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($branchStats, 'name')) ?>,
            datasets: [
                {
                    label: 'Total Cases',
                    data: <?= json_encode(array_column($branchStats, 'total')) ?>,
                    backgroundColor: 'rgba(111, 78, 55, 0.7)',
                    borderColor: colors.primary,
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Completed',
                    data: <?= json_encode(array_column($branchStats, 'completed_total')) ?>,
                    backgroundColor: 'rgba(45, 138, 78, 0.7)',
                    borderColor: colors.success,
                    borderWidth: 2,
                    borderRadius: 4
                },
                {
                    label: 'Escalated',
                    data: <?= json_encode(array_column($branchStats, 'escalated')) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: colors.danger,
                    borderWidth: 2,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // ========== Weekly Trend Chart (Line) ==========
    const ctx4 = document.getElementById('weeklyChart').getContext('2d');
    new Chart(ctx4, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($weeklyTrend, 'day')) ?>,
            datasets: [{
                label: 'Cases',
                data: <?= json_encode(array_column($weeklyTrend, 'count')) ?>,
                borderColor: colors.primary,
                backgroundColor: 'rgba(111, 78, 55, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.primary,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});

// ========== Export Dashboard ==========
function exportDashboard() {
    if (typeof html2canvas === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
        script.onload = function() {
            captureDashboard();
        };
        document.head.appendChild(script);
    } else {
        captureDashboard();
    }
}

function captureDashboard() {
    html2canvas(document.querySelector('.page-content'), {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#f5f5f5'
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'dashboard-report.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }).catch(err => {
        alert('Error exporting dashboard. Please try again.');
        console.error(err);
    });
}
</script>

<!-- Add html2canvas for export functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<?php include 'includes/footer.php'; ?>
