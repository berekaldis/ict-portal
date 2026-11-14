<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

ensureDefaultUsers($pdo);

$message = '';

$settings = getSettings($pdo);
$logoUrl = isset($settings['logo_url']) && $settings['logo_url'] ? $settings['logo_url'] : '';

if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

function ensureDefaultUsers($pdo) {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS hr_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT \'User\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM hr_users');
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $defaults = [
            ['username' => 'user', 'password' => 'user123', 'role' => 'User'],
            ['username' => 'admin', 'password' => 'admin123', 'role' => 'Admin'],
            ['username' => 'superadmin', 'password' => 'super123', 'role' => 'Superadmin'],
        ];
        $ins = $pdo->prepare('INSERT INTO hr_users (username, password_hash, role) VALUES (?, ?, ?)');
        foreach ($defaults as $u) {
            $ins->execute([$u['username'], password_hash($u['password'], PASSWORD_DEFAULT), $u['role']]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $message = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM hr_users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit;
            } else {
                $message = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $message = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KALDIS COFFEE HR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #4a3028 0%, #2d1e18 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.05"><path fill="none" d="M20,30 Q40,10 60,30 T100,30" stroke="%23ffffff" stroke-width="2"/><circle cx="30" cy="50" r="3" fill="%23ffffff"/><circle cx="70" cy="70" r="4" fill="%23ffffff"/></svg>');
            background-repeat: repeat;
            pointer-events: none;
        }

        /* Main Container */
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* LEFT PANEL - Coffee Theme */
        .left-panel {
            flex: 1;
            background: linear-gradient(125deg, #faf3e8 0%, #f2e3d5 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '☕';
            position: absolute;
            font-size: 300px;
            bottom: -60px;
            right: -80px;
            opacity: 0.08;
            pointer-events: none;
            transform: rotate(-15deg);
        }

        .coffee-icon-main {
            text-align: center;
            margin-bottom: 30px;
        }

        .coffee-icon-main span {
            font-size: 90px;
            display: inline-block;
            animation: floatBean 3s ease-in-out infinite;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));
        }

        @keyframes floatBean {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .brand-name {
            font-size: 48px;
            font-weight: 800;
            text-align: center;
            background: linear-gradient(135deg, #6b3a2a, #9b5e3c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        .brand-subtitle {
            text-align: center;
            font-size: 18px;
            font-weight: 500;
            color: #b87a5a;
            letter-spacing: 2px;
            margin-bottom: 40px;
        }

        .tagline-box {
            background: rgba(255, 245, 235, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(200, 140, 100, 0.3);
        }

        .tagline-box p {
            color: #5a3628;
            font-size: 14px;
            line-height: 1.6;
            font-weight: 500;
        }

        .tagline-box i {
            color: #c27e55;
            margin-right: 8px;
        }

        .coffee-beans-deco {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            font-size: 28px;
            opacity: 0.5;
            animation: pulseOpacity 3s infinite;
        }

        @keyframes pulseOpacity {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.7; }
        }

        /* RIGHT PANEL - Login Form */
        .right-panel {
            flex: 1;
            background: white;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .logo-img {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
        }

        .logo-area h3 {
            font-size: 18px;
            font-weight: 600;
            color: #8b5a42;
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d1e18;
            margin-bottom: 8px;
        }

        .welcome-sub {
            font-size: 14px;
            color: #a07a64;
            margin-bottom: 30px;
            border-left: 3px solid #e8b48c;
            padding-left: 12px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #8b6b58;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #eedbcb;
            border-radius: 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fefcf9;
            font-weight: 500;
            color: #3a241e;
        }

        .form-group input:focus {
            outline: none;
            border-color: #c27e55;
            box-shadow: 0 0 0 3px rgba(194, 126, 85, 0.1);
            background: white;
        }

        .form-group input::placeholder {
            color: #cfbba9;
            font-weight: 400;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7a5a48;
            cursor: pointer;
        }

        .forgot-link {
            color: #c27e55;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #9b5e3c;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #b66f48, #9b5a3a);
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(155, 90, 58, 0.25);
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #a35d3a, #8a4e30);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(155, 90, 58, 0.35);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .register-prompt {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #8b6b58;
        }

        .register-prompt a {
            color: #c27e55;
            text-decoration: none;
            font-weight: 700;
            margin-left: 5px;
        }

        .register-prompt a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #ffe8e6;
            border-left: 4px solid #e07c5c;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #c2492a;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsive */
        @media (max-width: 850px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            .left-panel {
                padding: 40px 30px;
            }
            .right-panel {
                padding: 40px 30px;
            }
            .brand-name {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <!-- LEFT SIDE - Coffee & Happiness -->
    <div class="left-panel">
        <div class="coffee-icon-main">
            <span>☕✨</span>
        </div>
        <div class="brand-name">KALDIS COFFEE</div>
        <div class="brand-subtitle">Internship Management System</div>
        
        <div class="tagline-box">
            <p><i class="fa fa-heart" style="color: #e8a87c;"></i> Record all new employees</p>
            <p style="margin-top: 10px;"><i class="fa fa-users"></i> HR Internship Management System</p>
            <p style="margin-top: 10px; font-size: 12px; color: #a07a64;">Kaldis Coffee · Since 2026</p>
        </div>
        
        <div class="coffee-beans-deco">
            <span>🫘</span>
            <span>☕</span>
            <span>🫘</span>
            <span>🍃</span>
        </div>
    </div>

    <!-- RIGHT SIDE - LOGIN FORM -->
    <div class="right-panel">
        <div class="logo-area">
            <?php if ($logoUrl): ?>
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="logo-img">
            <?php else: ?>
                <div style="font-size: 40px;">☕</div>
            <?php endif; ?>
            <h3>KALDIS COFFEE HR</h3>
        </div>
        
        <div class="welcome-title">Welcome Back</div>
        <div class="welcome-sub">Please login to access your dashboard</div>
        
        <?php if ($message): ?>
            <div class="error-message">
                <i class="fa fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label><i class="fa fa-user"></i> Username</label>
                <input type="text" name="username" id="username" placeholder="Enter the username" autocomplete="username" required>
            </div>
            
            <div class="form-group">
                <label><i class="fa fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" required>
            </div>
            
            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember"> Remember me
                </label>
              
            </div>
            
            <button type="submit" class="login-btn" id="loginButton">
                <i class="fa fa-sign-in"></i> SUBMIT
            </button>
        </form>
    </div>
</div>

<script>
    // Simple form enhancement - loading state and demo helpers
    const form = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginButton');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    // Add loading effect on form submit
    if (form) {
        form.addEventListener('submit', function(e) {
            const btn = loginBtn;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;
            btn.style.opacity = '0.8';
            
            // Don't prevent default - let PHP handle normally
            // But store original content in case of error (page will reload anyway)
            setTimeout(function() {
                if (btn.disabled) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            }, 5000);
        });
    }
    
    // Double-click username to fill demo credentials (helpful for testing)
    if (usernameInput) {
        usernameInput.addEventListener('dblclick', function() {
            if (usernameInput.value === 'user' || usernameInput.value === '') {
                usernameInput.value = 'user';
                passwordInput.value = 'user123';
            } else if (usernameInput.value === 'admin') {
                passwordInput.value = 'admin123';
            } else if (usernameInput.value === 'superadmin') {
                passwordInput.value = 'super123';
            }
        });
    }
    
    // Forgot password handler
    const forgotLink = document.getElementById('forgotPasswordLink');
    if (forgotLink) {
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('🔐 Forgot password? Please contact HR Administrator.\n\nDemo credentials:\n• user / user123\n• admin / admin123\n• superadmin / super123');
        });
    }
    
    // Register link handler
    const registerLink = document.getElementById('registerLink');
    if (registerLink) {
        registerLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('📝 New account registration is managed by HR Admin.\n\nPlease contact your HR representative to create an account.');
        });
    }
    
    // Add floating effect to coffee icon
    const coffeeIcon = document.querySelector('.coffee-icon-main span');
    if (coffeeIcon) {
        setInterval(() => {
            coffeeIcon.style.transform = 'scale(1.05)';
            setTimeout(() => {
                coffeeIcon.style.transform = 'scale(1)';
            }, 300);
        }, 3000);
    }
    
    // Auto-hide error message after 5 seconds
    const errorMsg = document.querySelector('.error-message');
    if (errorMsg) {
        setTimeout(() => {
            errorMsg.style.opacity = '0';
            setTimeout(() => {
                if (errorMsg.parentNode) errorMsg.style.display = 'none';
            }, 500);
        }, 5000);
    }
</script>
</body>
</html>