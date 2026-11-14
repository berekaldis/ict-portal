<?php
// export.php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$type = $_GET['type'] ?? 'cases';
$preset = $_GET['preset'] ?? '';
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

parseDateRangePreset($preset, $startDate, $endDate);

try {
    if ($type === 'completed') {
        $data = getCompletedCases($startDate, $endDate);
        $filename = "completed_cases_export.csv";
    } else {
        $data = getCases($startDate, $endDate);
        $filename = "cases_export.csv";
    }
    
    if (empty($data)) {
        die('No data to export');
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Get column headers
    $headers = array_keys($data[0]);
    // Remove id and branch_id from headers
    $headers = array_diff($headers, ['id', 'branch_id']);
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($data as $row) {
        $rowData = [];
        foreach ($headers as $header) {
            $value = $row[$header] ?? '';
            // Format date fields
            if (in_array($header, ['date', 'case_fixed_date', 'escalation_date', 'last_updated', 'created_at']) && $value) {
                $value = date('Y-m-d', strtotime($value));
            }
            $rowData[] = $value;
        }
        fputcsv($output, $rowData);
    }
    
    fclose($output);
    
    logAction($_SESSION['username'], 'Export Data', "Exported $type data to CSV");
    
} catch(Exception $e) {
    die('Export failed: ' . $e->getMessage());
}
?>
