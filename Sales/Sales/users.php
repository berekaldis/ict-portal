<?php
// users.php
$page_title = 'User Management';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$users = getUsers();
$message = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'Finance Team';
            
            if (empty($username) || empty($password)) {
                $error = 'Username and password are required';
            } else {
                $result = addUser($username, $password, $role);
                if ($result['success']) {
                    $message = "âœ… User '$username' added successfully!";
                    $users = getUsers();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'update':
            $userId = intval($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? null;
            $role = $_POST['role'] ?? 'Finance Team';
            
            if (empty($username) || $userId <= 0) {
                $error = 'Invalid user data';
            } else {
                $result = updateUser($userId, $username, $password, $role);
                if ($result['success']) {
                    $message = "âœ… User '$username' updated successfully!";
                    $users = getUsers();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'delete':
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId <= 0) {
                $error = 'Invalid user ID';
            } else {
                $result = deleteUser($userId);
                if ($result['success']) {
                    $message = "âœ… User deleted successfully!";
                    $users = getUsers();
                } else {
                    $error = $result['error'];
                }
            }
            break;
            
        case 'reset_password':
            $userId = intval($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            
            if ($userId <= 0 || empty($newPassword)) {
                $error = 'Invalid user ID or password';
            } else {
                $result = resetPassword($userId, $newPassword);
                if ($result['success']) {
                    $message = "âœ… Password reset successfully!";
                } else {
                    $error = $result['error'];
                }
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">User Management</h2>
            <p class="text-muted mb-0">Manage system users and their roles</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Add User
        </button>
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>First Login</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                        <td>
                            <span class="badge bg-<?= $user['role'] === 'Admin' ? 'danger' : ($user['role'] === 'Support Team' ? 'info' : 'primary') ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['first_login']): ?>
                            <span class="badge bg-warning text-dark">Yes</span>
                            <?php else: ?>
                            <span class="badge bg-success">No</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['last_activity'] ? date('Y-m-d H:i', strtotime($user['last_activity'])) : '-' ?></td>
                        <td>
                            <button onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)" 
                                    class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="resetPassword(<?= $user['id'] ?>)" 
                                    class="btn btn-sm btn-warning" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <button onclick="deleteUser(<?= $user['id'] ?>)" 
                                    class="btn btn-sm btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                            <h5>No Users Found</h5>
                            <p class="text-muted">Click "Add User" to create your first user.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============ ADD USER MODAL ============ -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="Finance Team">Finance Team</option>
                            <option value="Support Team">Support Team</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============ EDIT USER MODAL ============ -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (optional)</label>
                        <input type="password" class="form-control" name="password" id="edit_password" minlength="6">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" name="role" id="edit_role" required>
                            <option value="Finance Team">Finance Team</option>
                            <option value="Support Team">Support Team</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============ RESET PASSWORD MODAL ============ -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key"></i> Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" required>
                        <small class="text-muted" id="passwordMatch"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============ EDIT USER ============
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

// ============ DELETE USER ============
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone!')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============ RESET PASSWORD ============
function resetPassword(userId) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('passwordMatch').textContent = '';
    
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

// ============ PASSWORD CONFIRMATION ============
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('passwordMatch');
    
    if (newPassword && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value === newPassword.value) {
                passwordMatch.textContent = 'âœ… Passwords match';
                passwordMatch.style.color = 'green';
            } else {
                passwordMatch.textContent = 'âŒ Passwords do not match';
                passwordMatch.style.color = 'red';
            }
        });
    }
});

// ============ DATATABLE INITIALIZATION ============
$(document).ready(function() {
    $('#usersTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [5] }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users found",
            infoFiltered: "(filtered from _MAX_ total users)",
            zeroRecords: "No matching users found"
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
