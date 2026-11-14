<?php
// download.php - File download handler
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

// Get attachment ID
$attachmentId = $_GET['file'] ?? 0;

if (!$attachmentId) {
    die('No file specified');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get attachment details
    $stmt = $db->prepare("SELECT * FROM sales_case_attachments WHERE id = ?");
    $stmt->execute([$attachmentId]);
    $attachment = $stmt->fetch();
    
    if (!$attachment) {
        die('File not found');
    }
    
    // Check if file exists
    if (!file_exists($attachment['file_path'])) {
        die('File not found on server');
    }
    
    // Get file info
    $filePath = $attachment['file_path'];
    $fileName = $attachment['file_name'];
    $fileSize = filesize($filePath);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Set content type based on file extension
    $contentTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed'
    ];
    
    $contentType = $contentTypes[$fileExtension] ?? 'application/octet-stream';
    
    // Set headers for download
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: public, max-age=86400');
    header('Pragma: public');
    
    // For images, allow inline display
    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
        header('Content-Disposition: inline; filename="' . $fileName . '"');
    }
    
    // Read and output the file
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<?php
// Add this to download.php for security
function validateFileAccess($attachmentId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if user has access to this file
        $stmt = $db->prepare("
            SELECT c.*, ca.* 
            FROM sales_case_attachments ca
            LEFT JOIN sales_cases c ON ca.case_id = c.case_id
            WHERE ca.id = ?
        ");
        $stmt->execute([$attachmentId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Check if user is admin or owner
        if ($_SESSION['role'] === 'Admin') {
            return true;
        }
        
        // Check if user submitted the case
        if ($result['submitted_by'] === $_SESSION['username']) {
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}
