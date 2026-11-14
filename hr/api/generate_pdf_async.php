<?php
// api/generate_pdf_async.php
// Generates PDF asynchronously for a request
require_once '../config/database.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['type']) || !isset($data['id'])) {
    echo json_encode(['error' => 'Missing type or id']);
    exit;
}

$type = $data['type'];
$id = $data['id'];

try {
    $settings = getSettings($pdo);
    
    if ($type === 'internship') {
        $stmt = $pdo->prepare("SELECT * FROM internship_requests WHERE request_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['error' => 'Record not found']);
            exit;
        }
        
        $formData = [
            'date' => date('Y-m-d'),
            'to_branch' => $row['to_branch'],
            'name' => $row['name'],
            'gender' => $row['gender'],
            'work_branch' => $row['work_branch'],
            'job' => $row['job'],
            'start_date' => $row['start_date']
        ];
        
        require_once '../api/save_internship.php';
        $html = generateInternshipPDFHTML($formData, $id, $settings['logo_url']);
        
    } elseif ($type === 'qualification') {
        $stmt = $pdo->prepare("SELECT * FROM qualifications WHERE request_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['error' => 'Record not found']);
            exit;
        }
        
        $formData = [
            'q_date' => date('Y-m-d'),
            'q_branch' => $row['branch'],
            'q_name' => $row['name'],
            'q_gender' => $row['gender'],
            'q_job' => $row['job'],
            'q_intern_start' => $row['internship_ref_id'] ? date('Y-m-d') : '',
            'q_probation_start' => $row['probation_start'],
            'q_manager_name' => $row['manager_name'],
            'q_manager_rank' => $row['manager_rank'],
            'q_destination' => $row['destination']
        ];
        
        require_once '../api/save_qualification.php';
        $html = generateQualificationPDFHTML($formData, $id, $settings['logo_url']);
        
    } elseif ($type === 'recruitment') {
        $stmt = $pdo->prepare("SELECT * FROM recruitments WHERE request_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['error' => 'Record not found']);
            exit;
        }
        
        $formData = [
            'r_date' => date('Y-m-d'),
            'r_branch' => $row['branch'],
            'r_name' => $row['name'],
            'r_gender' => $row['gender'],
            'r_req_job' => $row['requested_job'],
            'r_job' => $row['assigned_job'],
            'r_start_date' => $row['start_date'],
            'r_destination' => $row['destination']
        ];
        
        require_once '../api/save_recruitment.php';
        $html = generateRecruitmentPDFHTML($formData, $id, $settings['logo_url']);
        
    } else {
        echo json_encode(['error' => 'Invalid type']);
        exit;
    }
    
    // Generate PDF
    $filename = $type . '_' . $id;
    $pdfPath = createPDF($html, $filename);
    
    // Update database with PDF path
    if ($type === 'internship') {
        $stmt = $pdo->prepare("UPDATE internship_requests SET pdf_path = ? WHERE request_id = ?");
    } elseif ($type === 'qualification') {
        $stmt = $pdo->prepare("UPDATE qualifications SET pdf_path = ? WHERE request_id = ?");
    } elseif ($type === 'recruitment') {
        $stmt = $pdo->prepare("UPDATE recruitments SET pdf_path = ? WHERE request_id = ?");
    }
    $stmt->execute([$pdfPath, $id]);
    
    echo json_encode(['success' => true, 'pdf_path' => $pdfPath]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
