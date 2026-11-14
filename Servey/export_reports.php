<?php
require_once 'config.php';

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Unauthorized');
}

 $db = getDB();

// Build query with filters
 $where = ["1=1"];
 $params = [];
 $types = "";

 $search = $_GET['search'] ?? '';
 $type = $_GET['type'] ?? '';
 $branch = intval($_GET['branch'] ?? 0);
 $dateFrom = $_GET['date_from'] ?? '';
 $dateTo = $_GET['date_to'] ?? '';

 if ($search) {
     $where[] = "(r.branch_manager LIKE ? OR b.name LIKE ? OR t.name LIKE ?)";
     $searchTerm = "%$search%";
     $params[] = $searchTerm;
     $params[] = $searchTerm;
     $params[] = $searchTerm;
     $types .= "sss";
 }

 if ($type) {
     $where[] = "r.type = ?";
     $params[] = $type;
     $types .= "s";
 }

 if ($branch) {
     $where[] = "r.branch_id = ?";
     $params[] = $branch;
     $types .= "i";
 }

 if ($dateFrom) {
     $where[] = "r.submitted_at >= ?";
     $params[] = $dateFrom . ' 00:00:00';
     $types .= "s";
 }

 if ($dateTo) {
     $where[] = "r.submitted_at <= ?";
     $params[] = $dateTo . ' 23:59:59';
     $types .= "s";
 }

  $whereClause = implode(" AND ", $where);

  $stmt = $db->prepare("
     SELECT r.id, r.submitted_at, b.name as branch_name, t.name as tech_name, 
            r.branch_manager, r.type, r.health_score, r.health_status,
            r.issues_found, r.actions_taken, r.recommendations
     FROM " . TABLE_PREFIX . "reports r 
     LEFT JOIN " . TABLE_PREFIX . "branches b ON r.branch_id = b.id 
     LEFT JOIN " . TABLE_PREFIX . "technicians t ON r.technician_id = t.id 
     WHERE $whereClause
     ORDER BY r.id DESC
 ");

 if (!empty($params)) {
     $stmt->bind_param($types, ...$params);
 }
  $stmt->execute();
  $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all active checklist items for headers
$checklistStmt = $db->query("
    SELECT ci.id, ci.question, c.name as category_name, c.type as category_type
    FROM " . TABLE_PREFIX . "checklist_items ci
    INNER JOIN " . TABLE_PREFIX . "categories c ON ci.category_id = c.id
    WHERE ci.is_active = 1 AND c.is_active = 1
    ORDER BY c.type, c.display_order, ci.display_order
");
$checklist_items = $checklistStmt ? $checklistStmt->fetch_all(MYSQLI_ASSOC) : [];

// Generate CSV
 $filename = 'kaldis_reports_export_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

 $output = fopen('php://output', 'w');

// BOM for Excel UTF-8
fwrite($output, "\xEF\xBB\xBF");

// Headers
$headers = [
    'ID', 'Date', 'Branch', 'Technician', 'Manager', 
    'Type', 'Health Score', 'Status', 'Issues', 'Actions Taken', 'Recommendations'
];

foreach ($checklist_items as $item) {
    $typeLabel = ucfirst($item['category_type']);
    $headers[] = "[{$typeLabel}][{$item['category_name']}] {$item['question']}";
}

fputcsv($output, $headers);

// Data rows
foreach ($reports as $r) {
    // Get all responses for this report
    $respStmt = $db->prepare("SELECT checklist_item_id, response, notes FROM " . TABLE_PREFIX . "report_responses WHERE report_id = ?");
    $responsesMap = [];
    if ($respStmt) {
        $respStmt->bind_param("i", $r['id']);
        $respStmt->execute();
        $respRes = $respStmt->get_result();
        while ($row = $respRes->fetch_assoc()) {
            $responsesMap[$row['checklist_item_id']] = [
                'response' => $row['response'],
                'notes' => $row['notes']
            ];
        }
        $respStmt->close();
    }

    $rowData = [
        $r['id'],
        $r['submitted_at'],
        $r['branch_name'],
        $r['tech_name'],
        $r['branch_manager'],
        $r['type'],
        $r['health_score'] . '%',
        $r['health_status'] ?? '',
        $r['issues_found'] ?? '',
        $r['actions_taken'] ?? '',
        $r['recommendations'] ?? ''
    ];

    foreach ($checklist_items as $item) {
        if (isset($responsesMap[$item['id']])) {
            $resp = $responsesMap[$item['id']]['response'];
            $note = $responsesMap[$item['id']]['notes'];
            $val = $resp;
            if ($note) {
                $val .= " (Note: {$note})";
            }
            $rowData[] = $val;
        } else {
            // Check if this item is applicable to this report type
            if ($item['category_type'] === $r['type']) {
                $rowData[] = 'Not Answered';
            } else {
                $rowData[] = 'N/A (Type Mismatch)';
            }
        }
    }

    fputcsv($output, $rowData);
}

fclose($output);
exit;
?>