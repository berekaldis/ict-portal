<?php
// login.php - FULL SCREEN MODERN SPLIT-LAYOUT DESIGN
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Get logo from settings
$logoUrl = getSetting('logo_url', '');
$logoFile = getSetting('logo_file', '');
$logo = !empty($logoFile) ? $logoFile : $logoUrl;
$logoSize = getSetting('logo_size', 'medium');

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = login($username, $password);
        if ($result['success']) {
            if (isset($result['isFirstLogin']) && $result['isFirstLogin']) {
                $_SESSION['first_login'] = true;
                $_SESSION['temp_username'] = $username;
                header('Location: change_password.php');
                exit();
            }
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['error'] ?? 'Invalid username or password';
        }
    }
}

// Check for session messages
$sessionMessage = '';
if (isset($_GET['session']) && $_GET['session'] === 'expired') {
    $sessionMessage = 'Your session has expired. Please login again.';
}
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $sessionMessage = 'You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kaldis Coffee Case Management System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ========================================
           FULL SCREEN SPLIT-SCREEN LAYOUT
           ======================================== */
        html, body {
            width: 100vw;
            height: 100vh;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #FFFCF6;
        }

        .login-fullscreen-wrapper {
            display: flex;
            width: 100vw;
            min-height: 100vh;
            height: 100vh;
        }

        /* LEFT HERO BANNER SECTION */
        .login-hero-section {
            flex: 1.1;
            background: linear-gradient(135deg, #003F3F 0%, #006A6A 60%, #002B2B 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 60px 80px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Elements */
        .login-hero-section::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 160%;
            height: 160%;
            background: radial-gradient(circle, rgba(0, 170, 170, 0.15) 0%, transparent 60%);
            animation: pulseGlow 12s ease-in-out infinite alternate;
            pointer-events: none;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) rotate(0deg); }
            100% { transform: scale(1.15) rotate(5deg); }
        }

        .hero-top-brand {
            position: relative;
            z-index: 2;
        }

        .logo-icon-hero {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00A3A3, #006A6A);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.8rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .hero-brand-name {
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .hero-brand-name span {
            color: #FFD580;
        }

        .hero-center-content {
            position: relative;
            z-index: 2;
            max-width: 580px;
            margin: 40px 0;
        }

        .hero-heading {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 20px;
            letter-spacing: -0.8px;
            color: #ffffff;
        }

        .hero-subtext {
            font-size: 1.1rem;
            color: #B2DADA;
            line-height: 1.6;
            margin-bottom: 36px;
        }

        .hero-badges-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .hero-badge-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s ease;
        }

        .hero-badge-card:hover {
            background: rgba(255, 255, 255, 0.14);
            transform: translateX(8px);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .badge-icon {
            width: 46px;
            height: 46px;
            background: rgba(255, 213, 128, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #FFD580;
            flex-shrink: 0;
        }

        .badge-title {
            font-weight: 700;
            font-size: 1rem;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .badge-desc {
            font-size: 0.85rem;
            color: #B2DADA;
        }

        .hero-footer-text {
            position: relative;
            z-index: 2;
            color: #8BAAAA;
            font-size: 0.85rem;
        }

        /* RIGHT FORM SECTION */
        .login-form-section {
            flex: 0.9;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            position: relative;
            overflow-y: auto;
        }

        .login-form-container {
            width: 100%;
            max-width: 440px;
        }

        .login-form-header {
            margin-bottom: 32px;
        }

        .login-form-header h3 {
            font-size: 2rem;
            font-weight: 800;
            color: #003636;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control {
            padding: 14px 16px 14px 46px;
            border: 2px solid #E4EFEF;
            border-radius: 14px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #FAFCFC;
            height: 54px;
            color: #1A2E2E;
            font-weight: 500;
        }

        .input-group-custom .form-control:focus {
            border-color: #006A6A;
            box-shadow: 0 0 0 4px rgba(0, 106, 106, 0.08);
            background: #ffffff;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #90A4A4;
            font-size: 1.1rem;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .input-group-custom .form-control:focus + .input-icon {
            color: #006A6A;
        }

        .input-group-custom .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #90A4A4;
            cursor: pointer;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .input-group-custom .toggle-password:hover {
            color: #006A6A;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-check-input:checked {
            background-color: #006A6A;
            border-color: #006A6A;
        }

        .forgot-link {
            color: #006A6A;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #006A6A, #008888);
            border: none;
            border-radius: 14px;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.4s ease;
            height: 54px;
            position: relative;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 106, 106, 0.25);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 106, 106, 0.35);
            background: linear-gradient(135deg, #007A7A, #009999);
        }

        .btn-login .btn-loader {
            display: none;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-login.loading .btn-text { display: none; }
        .btn-login.loading .btn-loader { display: block; }

        .alert-custom {
            border: none;
            border-radius: 14px;
            padding: 14px 18px;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .alert-danger-custom {
            background: #FEF2F2;
            color: #991B1B;
            border-left: 4px solid #DC3545;
        }

        .alert-success-custom {
            background: #F0FDF4;
            color: #166534;
            border-left: 4px solid #2D8A4E;
        }

        .security-badge {
            text-align: center;
            font-size: 0.85rem;
            color: #708585;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 991px) {
            html, body {
                height: auto;
                overflow-y: auto;
            }

            .login-fullscreen-wrapper {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
            }

            .login-hero-section {
                padding: 40px 24px;
                flex: none;
            }

            .hero-heading {
                font-size: 1.8rem;
            }

            .login-form-section {
                padding: 40px 24px;
                flex: 1;
            }
        }
    </style>
</head>
<body>

    <div class="login-fullscreen-wrapper">
        <!-- LEFT HERO SECTION -->
        <div class="login-hero-section">
            <div class="hero-top-brand">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($logo): ?>
                        <img src="<?= htmlspecialchars($logo) ?>" 
                             alt="Company Logo" 
                             class="logo-image <?= htmlspecialchars($logoSize) ?>"
                             style="max-height: 56px;"
                             onerror="this.style.display='none'; document.getElementById('fallbackHeroLogo').style.display='inline-flex';">
                        <div id="fallbackHeroLogo" class="logo-icon-hero" style="display: none;">
                            <i class="fas fa-coffee"></i>
                        </div>
                    <?php else: ?>
                        <div class="logo-icon-hero">
                            <i class="fas fa-coffee"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="hero-brand-name">Kaldis <span>Coffee</span></div>
                        <small class="text-light opacity-75">Case Management System</small>
                    </div>
                </div>
            </div>

            <div class="hero-center-content">
                <h1 class="hero-heading">Branch Cash Sales Summery Voucher Difference </h1>
                <p class="hero-subtext">Centralized case , automated Cash Sales Summery  difference tracking for Kaldis Coffee all branches.</p>
                
                <div class="hero-badges-grid">
                    <div class="hero-badge-card">
                        <div class="badge-icon"><i class="fas fa-shield-halved"></i></div>
                        <div>
                            <div class="badge-title">Cash Sales Summery</div>
                            <div class="badge-desc">Difference from Z Report and cash sales summery </div>
                        </div>
                    </div>
                    <div class="hero-badge-card">
                        <div class="badge-icon"><i class="fas fa-paperclip"></i></div>
                        <div>
                            <div class="badge-title">Credit Sales Voucher</div>
                            <div class="badge-desc">Difference from Credit Sales</div>
                        </div>
                    </div>
                    <div class="hero-badge-card">
                        <div class="badge-icon"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="badge-title"> Cash Sales Adjustment Voucher</div>
                            <div class="badge-desc">Difference from Pade and Sales Adjustment</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-footer-text">
                &copy; <?= date('Y') ?> <strong>Kaldis Coffee</strong> &bull; All Rights Reserved
            </div>
        </div>

        <!-- RIGHT LOGIN FORM SECTION -->
        <div class="login-form-section">
            <div class="login-form-container">
                <div class="login-form-header">
                    <h3>Welcome Back</h3>
                    <p class="text-muted small">Enter your authorized credentials to access your account dashboard.</p>
                </div>

                <!-- Error/Success Messages -->
                <?php if ($error): ?>
                <div class="alert alert-custom alert-danger-custom mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($sessionMessage): ?>
                <div class="alert alert-custom alert-success-custom mb-4" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div><?= htmlspecialchars($sessionMessage) ?></div>
                </div>
                <?php endif; ?>

                <form method="POST" id="loginForm" autocomplete="off">
                    <div class="mb-4">
                        <label for="username" class="form-label fw-semibold text-dark">
                            <i class="fas fa-user me-1 text-primary"></i> Username
                        </label>
                        <div class="input-group-custom">
                            <input type="text" 
                                   class="form-control <?= $error ? 'is-invalid' : '' ?>" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Enter your username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   required 
                                   autofocus>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold text-dark">
                            <i class="fas fa-lock me-1 text-primary"></i> Password
                        </label>
                        <div class="input-group-custom">
                            <input type="password" 
                                   class="form-control <?= $error ? 'is-invalid' : '' ?>" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                            <label class="form-check-label text-muted small" for="rememberMe">
                                Remember me on this device
                            </label>
                        </div>
                        <a href="#" class="forgot-link small">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">
                            <i class="fas fa-right-to-bracket me-2"></i> Sign In to Portal
                        </span>
                        <span class="btn-loader"></span>
                    </button>
                </form>

                <div class="security-badge mt-4 pt-3 border-top">
                    <i class="fas fa-shield-halved text-success"></i>
                    <span>Secure Encrypted System Connection</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ============ TOGGLE PASSWORD VISIBILITY ============
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            }
            
            // ============ LOGIN FORM LOADING STATE ============
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            
            if (loginForm && loginBtn) {
                loginForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const password = document.getElementById('password').value;
                    
                    if (!username || !password) {
                        e.preventDefault();
                        return;
                    }
                    
                    loginBtn.classList.add('loading');
                    loginBtn.disabled = true;
                });
            }
            
            // ============ REMEMBER ME FUNCTIONALITY ============
            const rememberCheckbox = document.getElementById('rememberMe');
            const usernameInput = document.getElementById('username');
            
            if (rememberCheckbox && usernameInput) {
                const savedUsername = localStorage.getItem('savedUsername');
                if (savedUsername) {
                    usernameInput.value = savedUsername;
                    rememberCheckbox.checked = true;
                }
                
                if (loginForm) {
                    loginForm.addEventListener('submit', function() {
                        if (rememberCheckbox.checked) {
                            localStorage.setItem('savedUsername', usernameInput.value);
                        } else {
                            localStorage.removeItem('savedUsername');
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
