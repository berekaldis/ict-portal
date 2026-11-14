<?php
require_once 'config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

 $error = '';
 $lockout = false;
 $remaining = 0;

// Simple rate limiting via session
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['lockout_until'])) $_SESSION['lockout_until'] = 0;

if ($_SESSION['lockout_until'] > time()) {
    $lockout = true;
    $remaining = ceil(($_SESSION['lockout_until'] - time()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockout) {
    $password = $_POST['admin_password'] ?? '';

    if ($password) {
        $db = getDB();
        // Hardcoded admin username since we removed the input field
        $username = 'admin'; 
        $stmt = $db->prepare("SELECT * FROM " . TABLE_PREFIX . "admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'] ?? 1;
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_until'] = 0;
            logActivity('login', 'admin', $admin['id'] ?? null, "Admin logged in from " . getClientIP());
            
            // Redirect to the originally requested tab if exists
            $redirectUrl = 'admin.php';
            if (isset($_GET['tab'])) {
                $redirectUrl .= '?tab=' . clean($_GET['tab']);
            }
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_until'] = time() + 300; // 5 min lockout
                $lockout = true;
                $remaining = 300;
                $error = 'Too many failed attempts. Locked for 5 minutes.';
            } else {
                $error = 'Incorrect password. Please try again.';
            }
            logActivity('login_failed', 'admin', null, "Failed login attempt from " . getClientIP());
        }
    } else {
        $error = 'Please enter the admin password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaldis Coffee | Admin Access</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-tertiary: #e2e8f0;
            
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            
            --border-color: #e2e8f0;
            --border-focus: #cbd5e1;
            
            --card-bg: #ffffff;
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --card-hover-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
            
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 24px;
            
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: var(--text-primary);
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        /* Animated background */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 0;
        }

        .bg-gradient {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(79, 70, 229, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(14, 165, 233, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 50%);
            z-index: 0;
        }

        .floating-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            z-index: 0;
            animation: float 20s infinite ease-in-out;
        }

        .orb-1 {
            width: 400px; height: 400px;
            background: var(--primary);
            top: -100px; left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 300px; height: 300px;
            background: var(--secondary);
            bottom: -50px; right: -50px;
            animation-delay: -7s;
            animation-duration: 25s;
        }

        .orb-3 {
            width: 200px; height: 200px;
            background: #3b82f6;
            top: 50%; left: 50%;
            animation-delay: -14s;
            animation-duration: 30s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 40px) scale(0.95); }
            75% { transform: translate(30px, 20px) scale(1.05); }
        }

        /* Login container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 48px 40px;
            box-shadow: 
                0 0 0 1px rgba(255,255,255,0.1),
                var(--card-hover-shadow),
                0 0 100px -20px rgba(79, 70, 229, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--secondary));
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 24px;
        }

        .logo-ring {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-ring 3s infinite;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
        }

        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            50% { box-shadow: 0 0 0 12px rgba(79, 70, 229, 0); }
        }

        .logo-ring img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .lock-icon-wrapper {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 10px 15px -3px rgba(79,70,229,.3);
        }

        .lock-icon {
            font-size: 1.75rem;
            color: white;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .login-header .subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Error */
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }

        .error-box .error-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
            color: #ef4444;
        }

        /* Lockout */
        .lockout-box {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #92400e;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lockout-timer {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--danger);
            font-variant-numeric: tabular-nums;
        }

        /* Form */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            color: var(--text-tertiary);
            pointer-events: none;
            transition: var(--transition-fast);
        }

        .form-group input {
            width: 100%;
            padding: 14px 48px 14px 48px;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            font-family: inherit;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
            background: var(--bg-secondary);
            transition: var(--transition-fast);
        }

        .form-group input:hover {
            border-color: var(--border-focus);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            background: #fff;
        }

        .form-group input:focus ~ .input-icon {
            color: var(--primary);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: var(--text-tertiary);
            padding: 4px;
            transition: var(--transition-fast);
        }

        .toggle-password:hover { color: var(--text-secondary); }

        /* Button */
        .login-btn {
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.15) 50%, transparent 60%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .login-btn:hover::before { transform: translateX(100%); }

        .login-btn:active { transform: translateY(0); }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .login-footer a:hover { color: var(--primary-light); }

        .login-footer .copyright {
            color: var(--text-tertiary);
            font-size: 0.75rem;
        }

        /* Attempts warning */
        .attempts-warning {
            text-align: center;
            margin-top: 16px;
            font-size: 0.8rem;
            color: #92400e;
            background: rgba(245, 158, 11, 0.1);
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        @media (max-width: 480px) {
            .login-card { padding: 36px 24px; }
            .login-header h1 { font-size: 1.5rem; }
            .login-footer { flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
    <!-- Background effects -->
    <div class="bg-grid"></div>
    <div class="bg-gradient"></div>
    <div class="floating-orb orb-1"></div>
    <div class="floating-orb orb-2"></div>
    <div class="floating-orb orb-3"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-wrapper">
                    <div class="logo-ring">
                        <img src="https://media.licdn.com/dms/image/v2/C4E0BAQHCgkEymt1qLw/company-logo_200_200/company-logo_200_200/0/1630622377221?e=2147483647&v=beta&t=l--VwhnuVDejHwCjvqauI-msqoBWbkivoMkZf_EHHlw" alt="Kaldis Logo">
                    </div>
                </div>
                <h1>Admin Access</h1>
                <p class="subtitle">Enter the administrator password to continue</p>
            </div>

            <?php if ($lockout): ?>
                <div class="lockout-box">
                    <span style="font-size: 1.5rem;">🔐</span>
                    <div>
                        <strong>Account Temporarily Locked</strong><br>
                        Please wait <span class="lockout-timer" id="lockoutTimer"><?= $remaining ?></span> seconds before trying again.
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error && !$lockout): ?>
                <div class="error-box">
                    <span class="error-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm" <?= $lockout ? 'style="opacity:0.5;pointer-events:none;"' : '' ?>>
                <div class="form-group">
                    <label for="password">Admin Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="admin_password" placeholder="••••••••" required autofocus autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-lock-open"></i>
                    <span id="btnText">Unlock Dashboard</span>
                </button>
            </form>

            <?php if (!$lockout && $_SESSION['login_attempts'] > 0 && $_SESSION['login_attempts'] < 5): ?>
                <div class="attempts-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?= 5 - $_SESSION['login_attempts'] ?> attempts remaining before lockout
                </div>
            <?php endif; ?>
        </div>

        <div class="login-footer">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Survey
            </a>
            <span class="copyright">© <?= date('Y') ?> Kaldis Coffee — ICT</span>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Show loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('btnText');
            btn.disabled = true;
            text.innerHTML = '<span class="btn-spinner"></span> Unlocking...';
        });

        // Lockout countdown
        <?php if ($lockout): ?>
        let seconds = <?= $remaining ?>;
        const timerEl = document.getElementById('lockoutTimer');
        const interval = setInterval(() => {
            seconds--;
            timerEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                location.reload();
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>