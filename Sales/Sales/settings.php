<?php
// settings.php
$page_title = 'Settings';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$branches = getBranches();
$technicians = getTechnicians();
$telegramDestinations = getTelegramDestinations();

// Get logo settings
$logoUrl = getSetting('logo_url', '');
$logoFile = getSetting('logo_file', '');
$logoPosition = getSetting('logo_position', 'left');
$logoSize = getSetting('logo_size', 'medium');
$appUrl = getSetting('app_url', '');

$message = '';
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Prevent duplicate submissions
    if (isset($_SESSION['last_action']) && $_SESSION['last_action'] === $action) {
        if (isset($_SESSION['last_submit']) && (time() - $_SESSION['last_submit'] < 5)) {
            header("Location: settings.php");
            exit();
        }
    }
    
    $_SESSION['last_action'] = $action;
    $_SESSION['last_submit'] = time();
    
    switch ($action) {
        // ============ LOGO SETTINGS ============
        case 'update_logo':
            $logoUrl = trim($_POST['logo_url'] ?? '');
            $logoPosition = $_POST['logo_position'] ?? 'left';
            $logoSize = $_POST['logo_size'] ?? 'medium';
            
            // Handle logo file upload
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $fileInfo = pathinfo($_FILES['logo_file']['name']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
                
                if (!in_array(strtolower($fileInfo['extension']), $allowedExtensions)) {
                    $error = 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions);
                } else {
                    $uploadResult = handleLogoUpload($_FILES['logo_file']);
                    if ($uploadResult['success']) {
                        $logoFile = $uploadResult['file_path'];
                        updateSetting('logo_file', $logoFile);
                        $message = "Logo uploaded successfully!";
                    } else {
                        $error = $uploadResult['error'];
                    }
                }
            }
            
            // Update URL if provided
            if (!empty($logoUrl)) {
                updateSetting('logo_url', $logoUrl);
                $message = $message ? $message . " URL updated!" : "Logo URL updated!";
            }
            
            // Update position and size
            updateSetting('logo_position', $logoPosition);
            updateSetting('logo_size', $logoSize);
            
            if (empty($error) && empty($message)) {
                $message = "Logo settings updated successfully!";
            }
            
            // Refresh values
            $logoUrl = getSetting('logo_url', '');
            $logoFile = getSetting('logo_file', '');
            $logoPosition = getSetting('logo_position', 'left');
            $logoSize = getSetting('logo_size', 'medium');
            break;
            
        case 'update_app_url':
            $newAppUrl = trim($_POST['app_url'] ?? '');
            if (!empty($newAppUrl) && !filter_var($newAppUrl, FILTER_VALIDATE_URL)) {
                $error = 'Invalid App URL format. Please include http:// or https://';
            } else {
                updateSetting('app_url', rtrim($newAppUrl, '/'));
                $message = "App Base URL updated successfully!";
            }
            $appUrl = getSetting('app_url', '');
            break;
            
        case 'remove_logo':
            // Remove logo
            updateSetting('logo_url', '');
            updateSetting('logo_file', '');
            $logoUrl = '';
            $logoFile = '';
            $message = "Logo removed successfully!";
            break;
            
        // ============ TELEGRAM DESTINATIONS ============
        case 'add_telegram_destination':
            $name = trim($_POST['name'] ?? '');
            $chatId = trim($_POST['chat_id'] ?? '');
            $botToken = trim($_POST['bot_token'] ?? '');
            
            if (empty($name) || empty($chatId) || empty($botToken)) {
                $error = 'All fields are required';
            } else {
                $result = addTelegramDestination($name, $chatId, $botToken);
                if ($result['success']) {
                    $message = "Telegram destination added successfully!";
                    unset($_SESSION['form_data']);
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'update_telegram_destination':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $chatId = trim($_POST['chat_id'] ?? '');
            $botToken = trim($_POST['bot_token'] ?? '');
            
            if ($id <= 0 || empty($name) || empty($chatId) || empty($botToken)) {
                $error = 'All fields are required';
            } else {
                $result = updateTelegramDestination($id, $name, $chatId, $botToken);
                if ($result['success']) {
                    $message = "Telegram destination updated successfully!";
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'delete_telegram_destination':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'Invalid destination ID';
            } else {
                $result = deleteTelegramDestination($id);
                if ($result['success']) {
                    $message = "Telegram destination deleted successfully!";
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'test_telegram':
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $destination = getTelegramDestination($id);
                if ($destination) {
                    $validation = validateTelegramBot($destination['bot_token']);
                    if (!$validation['success']) {
                        $error = "Bot Validation Failed: " . $validation['error'];
                    } else {
                        $result = testTelegramConnection($destination['chat_id'], $destination['bot_token']);
                        if ($result['success']) {
                            $message = "âœ… Telegram connection successful! Test message sent to " . 
                                       htmlspecialchars($destination['name']);
                        } else {
                            $error = "âŒ Telegram connection failed: " . $result['error'];
                        }
                    }
                } else {
                    $error = "Destination not found";
                }
            } else {
                $error = "Invalid destination ID";
            }
            break;
            
        // ============ BRANCHES ============
        case 'add_branch':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                $error = 'Branch name is required';
            } else {
                $result = addBranch($name);
                if ($result['success']) {
                    $message = "Branch added successfully!";
                    $branches = getBranches();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'delete_branch':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'Invalid branch ID';
            } else {
                $result = deleteBranch($id);
                if ($result['success']) {
                    $message = "Branch deleted successfully!";
                    $branches = getBranches();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        // ============ TECHNICIANS ============
        case 'add_technician':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                $error = 'Technician name is required';
            } else {
                $result = addTechnician($name);
                if ($result['success']) {
                    $message = "Technician added successfully!";
                    $technicians = getTechnicians();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'delete_technician':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'Invalid technician ID';
            } else {
                $result = deleteTechnician($id);
                if ($result['success']) {
                    $message = "Technician deleted successfully!";
                    $technicians = getTechnicians();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        default:
            $error = 'Invalid action';
    }
    
    // Redirect to prevent form resubmission
    $redirectUrl = "settings.php";
    if ($message) {
        $redirectUrl .= "?message=" . urlencode($message);
    }
    if ($error) {
        $redirectUrl .= "?error=" . urlencode($error);
    }
    header("Location: " . $redirectUrl);
    exit();
}

// Get messages from URL parameters
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get the current logo URL for display
$currentLogo = !empty($logoFile) ? $logoFile : $logoUrl;

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2>Settings</h2>
        <hr>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- ============ APP BASE URL SETTINGS ============ -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-globe"></i> Application Base URL (Telegram Integration)</h5>
                <span class="badge bg-light text-dark">URL Config</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_app_url">
                    <div class="mb-3">
                        <label for="app_url" class="form-label font-weight-bold">
                            <i class="fas fa-link text-primary"></i> Public App URL / Domain
                        </label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="app_url" name="app_url" 
                                   value="<?= htmlspecialchars($appUrl) ?>" 
                                   placeholder="https://yourdomain.com or https://your-ngrok-subdomain.ngrok-free.app">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save URL
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> 
                            Telegram Bot API requires a valid HTTP/HTTPS URL (e.g. your local network IP <code><?= htmlspecialchars(getBaseUrl()) ?></code>, ngrok, or production domain) for clickable inline keyboard buttons.
                        </small>
                    </div>
                </form>
                
                <div class="mt-3 border-top pt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong><i class="fas fa-plug text-success"></i> Telegram Webhook Setup</strong>
                        <div class="small text-muted">Register your webhook URL with Telegram API for interactive callback buttons.</div>
                    </div>
                    <a href="set_webhook.php" target="_blank" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-satellite-dish"></i> Set Telegram Webhook
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ LOGO SETTINGS ============ -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-image"></i> Logo Settings</h5>
                <span class="badge bg-primary">Branding</span>
            </div>
            <div class="card-body">
                <!-- Current Logo Preview -->
                <?php if ($currentLogo): ?>
                <div class="text-center mb-3">
                    <div class="logo-preview-container">
                        <img src="<?= htmlspecialchars($currentLogo) ?>" 
                             alt="Current Logo" 
                             class="img-fluid logo-preview <?= $logoSize ?>"
                             style="max-height: <?= $logoSize === 'small' ? '50px' : ($logoSize === 'medium' ? '80px' : '120px') ?>;">
                        <div class="mt-2">
                            <span class="badge bg-secondary">Position: <?= ucfirst($logoPosition) ?></span>
                            <span class="badge bg-secondary">Size: <?= ucfirst($logoSize) ?></span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center text-muted mb-3">
                    <i class="fas fa-image fa-3x"></i>
                    <p>No logo uploaded yet</p>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_logo">
                    
                    <div class="mb-3">
                        <label for="logo_url" class="form-label">
                            <i class="fas fa-link"></i> Logo URL (Postimages)
                        </label>
                        <input type="url" class="form-control" id="logo_url" name="logo_url" 
                               value="<?= htmlspecialchars($logoUrl) ?>" 
                               placeholder="https://postimages.org/your-image.jpg">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Upload your logo to <a href="https://postimages.org" target="_blank">Postimages.org</a> 
                            and paste the direct link here.
                        </small>
                    </div>
                    
                    <div class="mb-3">
                      
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="logo_position" class="form-label">Logo Position</label>
                                <select class="form-select" id="logo_position" name="logo_position">
                                    <option value="left" <?= $logoPosition === 'left' ? 'selected' : '' ?>>Left</option>
                                    <option value="center" <?= $logoPosition === 'center' ? 'selected' : '' ?>>Center</option>
                                    <option value="right" <?= $logoPosition === 'right' ? 'selected' : '' ?>>Right</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="logo_size" class="form-label">Logo Size</label>
                                <select class="form-select" id="logo_size" name="logo_size">
                                    <option value="small" <?= $logoSize === 'small' ? 'selected' : '' ?>>Small (50px)</option>
                                    <option value="medium" <?= $logoSize === 'medium' ? 'selected' : '' ?>>Medium (80px)</option>
                                    <option value="large" <?= $logoSize === 'large' ? 'selected' : '' ?>>Large (120px)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Logo
                        </button>
                        <?php if ($currentLogo): ?>
                        <button type="submit" name="action" value="remove_logo" 
                                class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to remove the logo?')">
                            <i class="fas fa-trash"></i> Remove Logo
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Postimages Upload Instructions -->
                <div class="mt-3 p-3 bg-light rounded">
                    
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============ TELEGRAM DESTINATIONS ============ -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fab fa-telegram-plane"></i> Telegram Destinations</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTelegramModal">
                    <i class="fas fa-plus"></i> Add Destination
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Chat ID</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($telegramDestinations)): ?>
                                <?php foreach ($telegramDestinations as $index => $dest): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($dest['name']) ?></td>
                                    <td><code><?= htmlspecialchars($dest['chat_id']) ?></code></td>
                                    <td>
                                        <button onclick="editTelegram(<?= htmlspecialchars(json_encode($dest)) ?>)" 
                                                class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteTelegram(<?= $dest['id'] ?>)" 
                                                class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button onclick="testTelegram(<?= $dest['id'] ?>)" 
                                                class="btn btn-sm btn-success" title="Test Connection">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i class="fas fa-telegram fa-2x d-block mb-2"></i>
                                        No Telegram destinations added yet
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- ============ BRANCHES ============ -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-store"></i> Branches</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3" onsubmit="return confirm('Add this branch?')">
                    <input type="hidden" name="action" value="add_branch">
                    <div class="input-group">
                        <input type="text" class="form-control" name="name" placeholder="Branch name" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
                
                <ul class="list-group">
                    <?php if (!empty($branches)): ?>
                        <?php foreach ($branches as $branch): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($branch['name']) ?>
                            <button onclick="deleteBranch(<?= $branch['id'] ?>)" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">No branches added yet</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- ============ TECHNICIANS ============ -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-cog"></i> Technicians</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3" onsubmit="return confirm('Add this technician?')">
                    <input type="hidden" name="action" value="add_technician">
                    <div class="input-group">
                        <input type="text" class="form-control" name="name" placeholder="Technician name" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
                
                <ul class="list-group">
                    <?php if (!empty($technicians)): ?>
                        <?php foreach ($technicians as $tech): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($tech['name']) ?>
                            <button onclick="deleteTechnician(<?= $tech['id'] ?>)" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">No technicians added yet</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODALS ============ -->
<!-- Add Telegram Destination Modal -->
<div class="modal fade" id="addTelegramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" onsubmit="return confirm('Add this Telegram destination?')">
                <div class="modal-header">
                    <h5 class="modal-title">Add Telegram Destination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_telegram_destination">
                    <div class="mb-3">
                        <label for="name" class="form-label">Destination Name</label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Support Team" required>
                    </div>
                    <div class="mb-3">
                        <label for="chat_id" class="form-label">Chat ID</label>
                        <input type="text" class="form-control" name="chat_id" placeholder="Enter Telegram Chat ID" required>
                        <small class="text-muted">Get your chat ID from @userinfobot or @getmyid_bot</small>
                    </div>
                    <div class="mb-3">
                        <label for="bot_token" class="form-label">Bot Token</label>
                        <input type="text" class="form-control" name="bot_token" placeholder="Enter Bot Token" required>
                        <small class="text-muted">Get your bot token from @BotFather</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Destination</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Telegram Destination Modal -->
<div class="modal fade" id="editTelegramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" onsubmit="return confirm('Update this Telegram destination?')">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Telegram Destination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_telegram_destination">
                    <input type="hidden" name="id" id="edit_telegram_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Destination Name</label>
                        <input type="text" class="form-control" name="name" id="edit_telegram_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_chat_id" class="form-label">Chat ID</label>
                        <input type="text" class="form-control" name="chat_id" id="edit_telegram_chat_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_bot_token" class="form-label">Bot Token</label>
                        <input type="text" class="form-control" name="bot_token" id="edit_telegram_bot_token" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Destination</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============ TELEGRAM FUNCTIONS ============
function editTelegram(dest) {
    document.getElementById('edit_telegram_id').value = dest.id;
    document.getElementById('edit_telegram_name').value = dest.name;
    document.getElementById('edit_telegram_chat_id').value = dest.chat_id;
    document.getElementById('edit_telegram_bot_token').value = dest.bot_token;
    new bootstrap.Modal(document.getElementById('editTelegramModal')).show();
}

function deleteTelegram(id) {
    if (confirm('Are you sure you want to delete this Telegram destination?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_telegram_destination">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function testTelegram(id) {
    if (confirm('Send a test message to this Telegram destination?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="test_telegram">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============ BRANCH FUNCTIONS ============
function deleteBranch(id) {
    if (confirm('Delete this branch?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_branch">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============ TECHNICIAN FUNCTIONS ============
function deleteTechnician(id) {
    if (confirm('Delete this technician?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_technician">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============ LOGO PREVIEW ============
document.addEventListener('DOMContentLoaded', function() {
    const logoUrlInput = document.getElementById('logo_url');
    const logoFileInput = document.getElementById('logo_file');
    const previewContainer = document.querySelector('.logo-preview-container');
    
    // Preview URL changes
    logoUrlInput.addEventListener('input', function() {
        const url = this.value.trim();
        if (url) {
            previewLogo(url);
        }
    });
    
    // Preview file upload
    logoFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewLogo(e.target.result);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    function previewLogo(url) {
        let img = previewContainer.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.className = 'img-fluid logo-preview';
            previewContainer.prepend(img);
        }
        img.src = url;
        img.style.maxHeight = '80px';
        img.onerror = function() {
            this.style.display = 'none';
        };
        img.style.display = 'block';
    }
});
</script>

<style>
/* Logo preview styles */
.logo-preview {
    max-height: 80px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #e8e3de;
    padding: 8px;
    background: #fff;
}

.logo-preview.small {
    max-height: 50px !important;
}

.logo-preview.medium {
    max-height: 80px !important;
}

.logo-preview.large {
    max-height: 120px !important;
}

.logo-preview-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100px;
    background: #faf9f8;
    border-radius: 8px;
    border: 2px dashed #e8e3de;
    padding: 15px;
}
</style>

<?php include 'includes/footer.php'; ?>
