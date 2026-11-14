<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Kaldis Coffee | IT Maintenance Survey</title>
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
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(79, 70, 229, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(79, 70, 229, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 0;
        }

        .bg-gradient {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(79, 70, 229, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
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

        .container {
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 48px 32px;
            box-shadow: 
                0 0 0 1px rgba(255,255,255,0.1),
                var(--card-hover-shadow),
                0 0 100px -20px rgba(79, 70, 229, 0.2);
            text-align: center;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--secondary));
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 
                0 0 0 1px rgba(255,255,255,0.1),
                0 20px 40px -10px rgba(79, 70, 229, 0.3),
                0 0 100px -20px rgba(79, 70, 229, 0.3);
        }

        .logo-ring {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
            animation: pulse-ring 3s infinite;
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
            border: 4px solid white;
        }

        h1 {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .sub {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 40px;
        }

        .btn {
            display: block;
            padding: 18px 24px;
            border-radius: var(--radius-xl);
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            margin: 16px 0;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.15) 50%, transparent 60%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-hardware {
            background: linear-gradient(135deg, #a0522d, #c46a3a);
            color: white;
        }

        .btn-software {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
        }

        .btn-admin {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
        }

        .btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }

        .btn:hover::before { transform: translateX(100%); }

        .btn:active { transform: translateY(-2px); }

        .footer {
            margin-top: 40px;
            font-size: 0.875rem;
            color: var(--text-tertiary);
            text-align: center;
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-fast);
        }

        .footer a:hover {
            color: var(--primary-light);
        }

        @media (max-width: 640px) {
            .card {
                padding: 36px 24px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .sub {
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 16px 20px;
                font-size: 0.95rem;
            }
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

    <div class="container">
        <div class="card">
            <div class="logo-ring">
                <img src="https://media.licdn.com/dms/image/v2/C4E0BAQHCgkEymt1qLw/company-logo_200_200/company-logo_200_200/0/1630622377221?e=2147483647&v=beta&t=l--VwhnuVDejHwCjvqauI-msqoBWbkivoMkZf_EHHlw" alt="Kaldis Coffee">
            </div>
            <h1>☕ Kaldis Coffee</h1>
            <div class="sub">IT Preventive Maintenance Survey</div>
            <a href="hardware.php" class="btn btn-hardware">
                <i class="fas fa-tools"></i> Hardware Inspection →
            </a>
            <a href="software.php" class="btn btn-software">
                <i class="fas fa-laptop-code"></i> Software Inspection →
            </a>
            <a href="admin.php" class="btn btn-admin">
                <i class="fas fa-shield-alt"></i> Admin Panel →
            </a>
        </div>
        <div class="footer">
            © <?= date('Y') ?> Kaldis Coffee IT Department | 
            <a href="https://kaldisbunnaet.com" target="_blank">Version 3.0</a>
        </div>
    </div>
</body>
</html>