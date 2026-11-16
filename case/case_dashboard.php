<?php
session_start();
if (!isset($_SESSION['case_user']) || empty($_SESSION['case_user'])) {
    header('Location: case_login.php');
    exit;
}
$currentUser = $_SESSION['case_user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Kaldis CNET Case Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #D97706;
            --primary-hover: #B45309;
            --primary-light: #FDE68A;
            --primary-bg: rgba(217, 119, 6, 0.08);
            --primary-glow: rgba(217, 119, 6, 0.25);
            --bg-body: #F1F5F9;
            --bg-surface: #FFFFFF;
            --bg-sidebar: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            --text-main: #0F172A;
            --text-muted: #64748B;
            --text-light: #F1F5F9;
            --border: #E2E8F0;
            --border-light: #F1F5F9;
            --danger: #EF4444;
            --danger-bg: rgba(239, 68, 68, 0.08);
            --danger-glow: rgba(239, 68, 68, 0.2);
            --success: #10B981;
            --success-bg: rgba(16, 185, 129, 0.08);
            --success-glow: rgba(16, 185, 129, 0.2);
            --warning: #F59E0B;
            --warning-bg: rgba(245, 158, 11, 0.08);
            --warning-glow: rgba(245, 158, 11, 0.2);
            --info: #3B82F6;
            --info-bg: rgba(59, 130, 246, 0.08);
            --info-glow: rgba(59, 130, 246, 0.2);
            --return: #D97706;
            --return-bg: rgba(217, 119, 6, 0.08);
            --reject: #DC2626;
            --reject-bg: rgba(220, 38, 38, 0.08);
            --sidebar-width: 270px;
            --header-height: 64px;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.03);
            --card-shadow-hover: 0 20px 40px -12px rgba(0,0,0,0.08), 0 8px 20px -8px rgba(0,0,0,0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius: 20px;
            --radius-sm: 14px;
            --radius-xs: 10px;
        }
        .dark-mode {
            --bg-body: #0B1120;
            --bg-surface: #151D2E;
            --bg-sidebar: linear-gradient(180deg, #070C16 0%, #0D1525 100%);
            --text-main: #E8EDF5;
            --text-muted: #7B8BA5;
            --text-light: #D8E0EC;
            --border: #1E2D45;
            --border-light: #151D2E;
            --primary-bg: rgba(217, 119, 6, 0.12);
            --danger-bg: rgba(239, 68, 68, 0.12);
            --success-bg: rgba(16, 185, 129, 0.12);
            --warning-bg: rgba(245, 158, 11, 0.12);
            --info-bg: rgba(59, 130, 246, 0.12);
            --return-bg: rgba(217, 119, 6, 0.12);
            --reject-bg: rgba(220, 38, 38, 0.12);
            --card-shadow: 0 1px 3px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 20px 40px -12px rgba(0,0,0,0.3);
        }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 15px; -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            transition: background 0.4s ease, color 0.4s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 100px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-hover); }

        /* ========== APP LAYOUT ========== */
        .app-container { display: flex; height: 100vh; height: 100dvh; width: 100%; }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 50;
            position: relative;
            flex-shrink: 0;
        }
        .sidebar.collapsed { width: 76px; }
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .menu-text,
        .sidebar.collapsed .logo-sub,
        .sidebar.collapsed .sidebar-toggle i { display: none; }
        .sidebar.collapsed .sidebar-header { justify-content: center; padding: 0 12px; }
        .sidebar.collapsed .menu-item { justify-content: center; padding: 13px 0; }
        .sidebar.collapsed .menu-item i { margin-right: 0; font-size: 22px; }
        .sidebar.collapsed .sidebar-toggle::after { content: '›'; font-size: 18px; font-weight: 700; }
        .sidebar-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 15px; width: 100%; }
        .logo-circle {
            width: 42px; height: 42px;
            border-radius: 14px;
            border: 2px solid var(--primary);
            background: rgba(255,255,255,0.05);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; flex-shrink: 0;
        }
        .logo-circle img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }
        .logo-text { line-height: 1.25; white-space: nowrap; overflow: hidden; }
        .logo-sub { font-size: 10px; opacity: 0.5; font-weight: 400; letter-spacing: 0.5px; }
        .sidebar-toggle {
            position: absolute;
            right: -13px; top: 78px;
            width: 26px; height: 26px;
            background: var(--primary);
            color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 60;
            box-shadow: 0 2px 8px rgba(217,119,6,0.4);
            border: 2.5px solid var(--bg-body);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .sidebar-toggle:hover { transform: scale(1.15); box-shadow: 0 4px 12px rgba(217,119,6,0.5); }
        .sidebar-toggle i { font-size: 16px; }
        .sidebar-menu { flex: 1; padding: 16px 10px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
        .menu-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: rgba(255,255,255,0.25); padding: 16px 16px 8px; font-weight: 700; }
        .sidebar.collapsed .menu-label { display: none; }
        .menu-item {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 16px; border-radius: var(--radius-sm);
            color: #8896B0; cursor: pointer;
            transition: var(--transition); font-size: 13.5px; font-weight: 500;
            position: relative; overflow: hidden;
        }
        .menu-item::before {
            content: ''; position: absolute; left: 0; top: 50%;
            transform: translateY(-50%); width: 3px; height: 0;
            background: var(--primary); border-radius: 0 4px 4px 0;
            transition: height 0.25s ease;
        }
        .menu-item:hover { background: rgba(255,255,255,0.05); color: #CBD5E1; }
        .menu-item.active { background: rgba(217,119,6,0.12); color: var(--primary-light); }
        .menu-item.active::before { height: 60%; }
        .menu-item i { font-size: 20px; width: 24px; flex-shrink: 0; }

        /* ========== MAIN CONTENT ========== */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }
        .header {
            height: var(--header-height);
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; flex-shrink: 0;
            backdrop-filter: blur(12px);
            transition: background 0.3s;
        }
        .header-left { display: flex; align-items: center; gap: 16px; min-width: 0; }
        .header h2 {
            font-size: 20px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            background-clip: text; -webkit-background-clip: text; color: transparent;
        }
        .user-info { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .user-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 800; font-size: 15px;
            box-shadow: 0 2px 8px rgba(217,119,6,0.3);
        }
        .user-name-text { font-size: 13px; font-weight: 600; display: none; }
        .content-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 24px; scroll-behavior: smooth; }
        .container { max-width: 1600px; margin: 0 auto; }
        .content-section { display: none; animation: fadeSlideIn 0.35s ease-out; }
        .content-section.active { display: block; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        /* ========== ENHANCED STAT CARDS ========== */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        .stat-card {
            background: var(--bg-surface);
            padding: 22px 14px 18px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            position: relative; overflow: hidden;
        }
        .stat-card::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, var(--primary-bg), transparent);
            opacity: 0; transition: opacity 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--card-shadow-hover); border-color: rgba(217,119,6,0.3); }
        .stat-card:hover::after { opacity: 1; }
        .stat-value {
            font-size: 30px; font-weight: 900; margin-bottom: 2px; position: relative; z-index: 1;
            background: linear-gradient(135deg, var(--text-main) 30%, var(--primary));
            background-clip: text; -webkit-background-clip: text; color: transparent;
        }
        .stat-label { font-size: 10.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; position: relative; z-index: 1; }
        .stat-icon {
            font-size: 20px; margin-bottom: 8px; opacity: 0.4; position: relative; z-index: 1;
        }

        /* Enhanced Dashboard Widgets */
        .performance-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .performance-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            border-radius: var(--radius);
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .performance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
        }
        .performance-value {
            font-size: 36px;
            font-weight: 900;
            margin-top: 10px;
        }
        .performance-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .trend-up { color: #4ade80; }
        .trend-down { color: #f87171; }

        /* ========== CARDS ========== */
        .card {
            background: var(--bg-surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 24px;
            transition: var(--transition);
            box-shadow: var(--card-shadow);
        }
        .card:hover { box-shadow: var(--card-shadow-hover); }
        .card-title {
            font-size: 17px; font-weight: 700; margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
        }
        .card-title-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--primary-bg); color: var(--primary);
            display: inline-flex; align-items: center; justify-content: center;
            margin-right: 10px; font-size: 20px;
        }

        /* ========== BUTTONS ========== */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 10px 20px; border-radius: var(--radius-xs);
            font-weight: 600; cursor: pointer; transition: var(--transition);
            border: none; font-size: 13px; font-family: inherit;
            white-space: nowrap; text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: white; box-shadow: 0 2px 10px rgba(217,119,6,0.25);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(217,119,6,0.35); }
        .btn-primary:active { transform: translateY(0); }
        .btn-secondary { background: var(--bg-surface); border: 1.5px solid var(--border); color: var(--text-main); }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-bg); }
        .btn-danger { background: linear-gradient(135deg, #EF4444, #DC2626); color: white; box-shadow: 0 2px 10px rgba(239,68,68,0.2); }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239,68,68,0.3); }
        .btn-success { background: linear-gradient(135deg, #10B981, #059669); color: white; }
        .btn-warning { background: linear-gradient(135deg, #F59E0B, #D97706); color: white; }
        .btn-sm { padding: 7px 14px; font-size: 12px; border-radius: 8px; }
        .btn-xs { padding: 5px 10px; font-size: 11px; border-radius: 7px; }
        .btn-icon {
            width: 36px; height: 36px; padding: 0; border-radius: 10px;
            background: transparent; border: none; color: var(--text-muted);
            cursor: pointer; transition: var(--transition);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-icon:hover { background: var(--primary-bg); color: var(--primary); transform: scale(1.08); }

        /* ========== FORMS ========== */
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block; font-size: 12px; font-weight: 700; margin-bottom: 7px;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-label .required { color: var(--danger); margin-left: 2px; }
        .form-control {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--border); border-radius: var(--radius-xs);
            background: var(--bg-surface); color: var(--text-main);
            font-family: inherit; font-size: 13.5px;
            transition: var(--transition);
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-bg); }
        .form-control:read-only { background: var(--border-light); color: var(--text-muted); cursor: not-allowed; }
        .form-control::placeholder { color: var(--text-muted); opacity: 0.6; }
        textarea.form-control { resize: vertical; min-height: 90px; line-height: 1.6; }
        select.form-control { cursor: pointer; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center;
            padding-right: 36px;
        }

        /* ========== TABLES ========== */
        .table-container { overflow-x: auto; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; min-width: 750px; }
        th {
            background: var(--bg-body); padding: 13px 14px; text-align: left;
            font-size: 10.5px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.7px;
            border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        td { padding: 13px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.15s; }
        tbody tr:hover td { background: var(--primary-bg); }

        /* ========== BADGES ========== */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 11px; border-radius: 30px;
            font-size: 11px; font-weight: 700; white-space: nowrap;
            letter-spacing: 0.2px;
        }
        .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
        .badge.status-not-started { background: var(--border); color: var(--text-muted); }
        .badge.status-in-progress { background: var(--info-bg); color: var(--info); }
        .badge.status-fixed { background: var(--success-bg); color: var(--success); }
        .badge.status-return { background: var(--return-bg); color: var(--return); }
        .badge.status-reject { background: var(--reject-bg); color: var(--reject); }
        .badge.status-completed { background: var(--success-bg); color: var(--success); }
        .badge.priority-critical { background: var(--danger-bg); color: var(--danger); }
        .badge.priority-medium { background: var(--warning-bg); color: var(--warning); }
        .badge.priority-low { background: var(--success-bg); color: var(--success); }
        .badge.role-admin { background: var(--primary-bg); color: var(--primary); }
        .badge.role-kaldis { background: var(--info-bg); color: var(--info); }
        .badge.role-cnet { background: var(--success-bg); color: var(--success); }

        /* ========== CHART ========== */
        .chart-container { position: relative; height: 280px; width: 100%; }

        /* ========== SLA ========== */
        .sla-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .sla-card {
            background: var(--bg-surface); padding: 22px 20px;
            border-radius: var(--radius); border: 1px solid var(--border);
            text-align: center; transition: var(--transition);
            box-shadow: var(--card-shadow);
        }
        .sla-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); }
        .sla-value { font-size: 32px; font-weight: 900; margin-top: 8px; }
        .sla-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px; border-bottom: 1px solid var(--border); gap: 16px;
            transition: background 0.15s;
        }
        .sla-item:hover { background: var(--primary-bg); }
        .sla-item:last-child { border-bottom: none; }
        .sla-content { flex: 1; min-width: 0; }
        .sla-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; align-items: center; }
        .sla-description { font-size: 13px; color: var(--text-muted); margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .text-danger { color: var(--danger); font-weight: 700; }
        .text-warning { color: var(--warning); font-weight: 700; }

        /* ========== BULK ACTIONS ========== */
        .bulk-actions-bar {
            position: sticky; bottom: 0; left: 0; right: 0;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            padding: 14px 24px; display: none;
            justify-content: space-between; align-items: center;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            z-index: 40; flex-wrap: wrap; gap: 12px; color: white;
            box-shadow: 0 -4px 20px rgba(217,119,6,0.3);
            font-weight: 600;
        }
        .bulk-actions-bar.active { display: flex; }
        .bulk-actions-bar select {
            background: white; color: var(--text-main); border: none;
            border-radius: 8px; padding: 6px 10px; font-size: 13px; font-weight: 600;
        }

        /* ========== MODALS ========== */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.55); backdrop-filter: blur(10px);
            z-index: 200; justify-content: center; align-items: center;
            padding: 16px;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--bg-surface); width: 100%; max-width: 920px;
            max-height: 92vh; max-height: 92dvh; border-radius: 24px;
            overflow: hidden; animation: modalSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
            display: flex; flex-direction: column;
        }
        @keyframes modalSlideIn { from { opacity: 0; transform: scale(0.92) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-header {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg-body); flex-shrink: 0;
        }
        .modal-header h3 { font-size: 18px; font-weight: 800; }
        .modal-body { padding: 24px; overflow-y: auto; flex: 1; }
        .modal-footer {
            padding: 14px 24px; border-top: 1px solid var(--border);
            display: flex; justify-content: flex-end; gap: 10px;
            background: var(--bg-body); flex-shrink: 0;
        }

        /* ========== DETAIL VIEW ========== */
        .detail-grid { display: grid; grid-template-columns: 1fr 270px; gap: 24px; }
        .detail-tabs { display: flex; gap: 24px; border-bottom: 2px solid var(--border); margin-bottom: 24px; }
        .detail-tab {
            padding: 10px 0; cursor: pointer; color: var(--text-muted);
            font-weight: 600; font-size: 13px;
            border-bottom: 2px solid transparent; transition: var(--transition);
        }
        .detail-tab:hover { color: var(--primary); }
        .detail-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .detail-panel { display: none; }
        .detail-panel.active { display: block; }
        .info-card { background: var(--bg-body); border-radius: var(--radius); padding: 18px; margin-bottom: 16px; }
        .info-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border); }
        .info-row:last-child { border-bottom: none; }
        .info-icon { width: 32px; color: var(--primary); font-size: 20px; flex-shrink: 0; }
        .info-row strong { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 2px; }
        .comment-list { display: flex; flex-direction: column; gap: 12px; max-height: 400px; overflow-y: auto; }
        .comment-item { background: var(--bg-body); padding: 14px 16px; border-radius: 14px; transition: var(--transition); }
        .comment-item.mine { background: var(--primary-bg); border-left: 3px solid var(--primary); }
        .comment-meta { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; }
        .comment-text { font-size: 13px; line-height: 1.6; }
        .history-item { display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--border); gap: 16px; }
        .history-content { flex: 1; }
        .history-content strong { font-size: 13px; display: block; margin-bottom: 4px; }
        .history-content p { font-size: 12px; color: var(--text-muted); }
        .history-time { text-align: right; font-size: 11px; color: var(--text-muted); white-space: nowrap; flex-shrink: 0; }

        /* ========== ENHANCED SETTINGS ========== */
        .settings-tabs { display: flex; gap: 20px; border-bottom: 2px solid var(--border); margin-bottom: 24px; flex-wrap: wrap; }
        .settings-tab {
            padding: 10px 0; cursor: pointer; color: var(--text-muted);
            font-weight: 600; font-size: 13px;
            border-bottom: 2px solid transparent; transition: var(--transition);
        }
        .settings-tab:hover { color: var(--primary); }
        .settings-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .settings-panel { display: none; }
        .settings-panel.active { display: block; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .settings-section {
            background: var(--bg-body);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 20px;
        }
        .settings-section h4 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ========== ENHANCED TOAST ========== */
        .toast-container {
            position: fixed; bottom: 24px; right: 24px;
            z-index: 1000; display: flex; flex-direction: column-reverse; gap: 10px;
            pointer-events: none;
        }
        .toast {
            pointer-events: auto;
            background: var(--bg-surface);
            padding: 0; border-radius: 16px;
            box-shadow: 0 15px 40px -10px rgba(0,0,0,0.15), 0 0 0 1px var(--border);
            display: flex; align-items: stretch;
            font-size: 13.5px; font-weight: 500;
            transform: translateX(130%); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden; max-width: 400px; min-width: 300px;
            position: relative;
        }
        .toast.show { transform: translateX(0); }
        .toast-icon {
            width: 48px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .toast.success .toast-icon { background: linear-gradient(135deg, #10B981, #059669); color: white; }
        .toast.error .toast-icon { background: linear-gradient(135deg, #EF4444, #DC2626); color: white; }
        .toast.info .toast-icon { background: linear-gradient(135deg, #3B82F6, #2563EB); color: white; }
        .toast.warning .toast-icon { background: linear-gradient(135deg, #F59E0B, #D97706); color: white; }
        .toast-body { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
        .toast-title { font-weight: 700; font-size: 13px; }
        .toast-message { font-size: 12.5px; color: var(--text-muted); line-height: 1.4; }
        .toast-close {
            position: absolute; top: 8px; right: 8px;
            width: 22px; height: 22px; border-radius: 6px;
            background: transparent; border: none;
            color: var(--text-muted); cursor: pointer; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            transition: var(--transition); opacity: 0;
        }
        .toast.show .toast-close { opacity: 1; }
        .toast-close:hover { background: var(--border); color: var(--text-main); }
        .toast-progress {
            position: absolute; bottom: 0; left: 0; height: 3px;
            border-radius: 0 0 0 16px;
            animation: toastProgress 4s linear forwards;
        }
        .toast.success .toast-progress { background: var(--success); }
        .toast.error .toast-progress { background: var(--danger); }
        .toast.info .toast-progress { background: var(--info); }
        .toast.warning .toast-progress { background: var(--warning); }
        @keyframes toastProgress { from { width: 100%; } to { width: 0%; } }

        /* ========== LOADER ========== */
        .loader-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(6px);
            z-index: 300; display: none; justify-content: center; align-items: center;
        }
        .loader-overlay.active { display: flex; }
        .loader-box {
            background: var(--bg-surface); border-radius: 20px; padding: 32px 40px;
            display: flex; flex-direction: column; align-items: center; gap: 16px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
            animation: modalSlideIn 0.25s ease-out;
        }
        .loader-box span { font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .spinner {
            width: 40px; height: 40px;
            border: 3px solid var(--border); border-top-color: var(--primary);
            border-radius: 50%; animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ========== REMARK FIELD ========== */
        .remark-field {
            background: linear-gradient(135deg, rgba(245,158,11,0.06), rgba(217,119,6,0.04));
            border: 1.5px dashed rgba(217,119,6,0.3);
            border-radius: var(--radius-sm);
            padding: 16px; margin-bottom: 18px;
        }
        .remark-field .form-label { color: var(--primary); }
        .remark-field .form-control { border-color: rgba(217,119,6,0.2); background: var(--bg-surface); }
        .remark-field .form-control:focus { border-color: var(--primary); }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1400px) {
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .detail-grid { grid-template-columns: 1fr; }
            .performance-stats { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            .sidebar {
                position: fixed; transform: translateX(-100%);
                z-index: 100; height: 100%; height: 100dvh; width: 270px;
                box-shadow: 4px 0 30px rgba(0,0,0,0.2);
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar.collapsed { width: 270px; }
            .sidebar.collapsed .logo-text,
            .sidebar.collapsed .menu-text,
            .sidebar.collapsed .logo-sub,
            .sidebar.collapsed .sidebar-toggle i { display: block; }
            .sidebar.collapsed .sidebar-header { justify-content: flex-start; padding: 0 20px; }
            .sidebar.collapsed .menu-item { justify-content: flex-start; padding: 11px 16px; }
            .sidebar.collapsed .menu-item i { margin-right: 12px; }
            .sidebar.collapsed .menu-label { display: block; }
            .sidebar.collapsed .sidebar-toggle::after { display: none; }
            .sidebar-toggle { display: none; }
            .mobile-menu-btn { display: inline-flex !important; }
            .mobile-overlay {
                position: fixed; inset: 0; background: rgba(0,0,0,0.4);
                z-index: 90; display: none;
                backdrop-filter: blur(4px);
            }
            .mobile-overlay.active { display: block; }
            .user-name-text { display: block; }
            .user-avatar { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 16px 10px 14px; }
            .stat-value { font-size: 26px; }
            .sla-grid { grid-template-columns: 1fr; }
            .content-scroll { padding: 16px; }
            .card { padding: 18px; }
            .modal-content { max-width: 100%; max-height: 100vh; max-height: 100dvh; border-radius: 20px; }
            .toast { max-width: calc(100vw - 48px); min-width: 0; }
            .toast-container { left: 24px; right: 24px; bottom: 16px; }
            .header { padding: 0 16px; }
            .header h2 { font-size: 17px; }
        }
        @media (max-width: 600px) {
            html { font-size: 14px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .stat-value { font-size: 24px; }
            .stat-label { font-size: 9.5px; }
            .stat-icon { font-size: 18px; margin-bottom: 6px; }
            .header { height: 56px; }
            .content-scroll { padding: 12px; }
            .card { padding: 14px; border-radius: 16px; margin-bottom: 16px; }
            .card-title { font-size: 15px; margin-bottom: 14px; }
            .btn { padding: 9px 14px; font-size: 12.5px; border-radius: 9px; }
            .btn-sm { padding: 6px 10px; font-size: 11.5px; }
            .form-control { padding: 10px 12px; font-size: 13px; }
            .table-container { border-radius: 12px; }
            th, td { padding: 10px 10px; font-size: 12px; }
            .modal { padding: 8px; }
            .modal-content { border-radius: 18px; }
            .modal-header { padding: 14px 16px; }
            .modal-header h3 { font-size: 16px; }
            .modal-body { padding: 16px; }
            .modal-footer { padding: 12px 16px; }
            .detail-tabs { gap: 16px; }
            .detail-tab { font-size: 12px; }
            .bulk-actions-bar { padding: 10px 14px; }
            .sla-card { padding: 16px; }
            .sla-value { font-size: 26px; }
            .user-info .btn-secondary.btn-sm span { display: none; }
            .toast { min-width: 0; }
            .toast-icon { width: 40px; font-size: 18px; }
            .toast-body { padding: 12px 14px; }
            .toast-title { font-size: 12px; }
            .toast-message { font-size: 11.5px; }
        }
        @media (max-width: 380px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
            .stat-card { padding: 12px 8px 10px; }
            .stat-value { font-size: 22px; }
        }
        @media (min-width: 901px) {
            .mobile-menu-btn { display: none !important; }
            .mobile-overlay { display: none !important; }
        }

        /* ========== ROLE VISIBILITY ========== */
        body.is-kaldis .admin-only, body.is-cnet .admin-only { display: none !important; }
        body.is-kaldis .cnet-only { display: none !important; }
        body.is-cnet .kaldis-only { display: none !important; }
    </style>
</head>
<body>

<div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>

<div id="mainApp" class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <div class="logo-circle" id="logoCircle">
                    <img src="https://media.licdn.com/dms/image/v2/C4E0BAQHCgkEymt1qLw/company-logo_200_200/company-logo_200_200/0/1630622377221?e=2147483647&v=beta&t=l--VwhnuVDejHwCjvqauI-msqoBWbkivoMkZf_EHHlw" alt="Kaldis">
                </div>
                <div class="logo-text">Kaldis CNET<br><span class="logo-sub" id="companyNameDisplay">Case Management</span></div>
            </div>
        </div>
        <div class="sidebar-toggle" onclick="toggleSidebar()"><i class="material-icons-round">chevron_left</i></div>
        <nav class="sidebar-menu">
            <div class="menu-label">Main</div>
            <div class="menu-item active" onclick="navigateTo('dashboard')"><i class="material-icons-round">dashboard</i><span class="menu-text">Dashboard</span></div>
            <div class="menu-item" onclick="navigateTo('cases')"><i class="material-icons-round">assignment</i><span class="menu-text">Case List</span></div>
            <div class="menu-item" onclick="navigateTo('sla')"><i class="material-icons-round">speed</i><span class="menu-text">SLA Dashboard</span></div>
            <div class="menu-item kaldis-only" onclick="navigateTo('newCase')"><i class="material-icons-round">add_circle</i><span class="menu-text">New Case</span></div>
            <div class="menu-item" onclick="navigateTo('assignees')"><i class="material-icons-round">badge</i><span class="menu-text">Assignees</span></div>
            <div style="flex: 1;"></div>
            <div class="menu-label">Preferences</div>
            <div class="menu-item" onclick="toggleDarkMode()"><i class="material-icons-round" id="darkModeIcon">dark_mode</i><span class="menu-text">Dark Mode</span></div>
            <div class="menu-item admin-only" onclick="navigateTo('users')"><i class="material-icons-round">people</i><span class="menu-text">Users</span></div>
            <div class="menu-item admin-only" onclick="navigateTo('settings')"><i class="material-icons-round">settings</i><span class="menu-text">Settings</span></div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="btn-icon mobile-menu-btn" onclick="toggleMobileSidebar()"><i class="material-icons-round">menu</i></button>
                <h2 id="pageTitle">Dashboard</h2>
            </div>
            <div class="user-info">
                <button class="btn btn-secondary btn-sm" onclick="loadMyTasks()"><i class="material-icons-round">person_search</i> <span>My Tasks</span></button>
                <span class="user-name-text"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                <div class="user-avatar" id="userAvatar"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                <button class="btn-icon" onclick="logout()" title="Logout"><i class="material-icons-round">logout</i></button>
            </div>
        </header>

        <div class="content-scroll">
            <!-- ENHANCED Dashboard Panel -->
            <div id="dashboardPanel" class="content-section active">
                <div class="container">
                    <!-- Performance Stats -->
                    <div class="performance-stats">
                        <div class="performance-card">
                            <div class="performance-label">Resolution Rate</div>
                            <div class="performance-value" id="resolutionRate">0%</div>
                            <small class="trend-up" id="resolutionTrend">↑ 0%</small>
                        </div>
                        <div class="performance-card">
                            <div class="performance-label">Average Response Time</div>
                            <div class="performance-value" id="avgResponseTime">0</div>
                            <small>hours</small>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card" onclick="filterByStatus('Completed')"><div class="stat-icon"><i class="material-icons-round">check_circle</i></div><div class="stat-value" id="statCompleted">0</div><div class="stat-label">Completed</div></div>
                        <div class="stat-card" onclick="filterByStatus('Not Started')"><div class="stat-icon"><i class="material-icons-round">radio_button_unchecked</i></div><div class="stat-value" id="statNotStarted">0</div><div class="stat-label">Not Started</div></div>
                        <div class="stat-card" onclick="filterByStatus('In Progress')"><div class="stat-icon"><i class="material-icons-round">pending</i></div><div class="stat-value" id="statInProgress">0</div><div class="stat-label">In Progress</div></div>
                        <div class="stat-card" onclick="filterByStatus('Fixed')"><div class="stat-icon"><i class="material-icons-round">build</i></div><div class="stat-value" id="statFixed">0</div><div class="stat-label">Fixed</div></div>
                        <div class="stat-card" onclick="filterByStatus('Return')"><div class="stat-icon"><i class="material-icons-round">replay</i></div><div class="stat-value" id="statReturn">0</div><div class="stat-label">Returned</div></div>
                        <div class="stat-card" onclick="filterByStatus('Reject')"><div class="stat-icon"><i class="material-icons-round">block</i></div><div class="stat-value" id="statReject">0</div><div class="stat-label">Rejected</div></div>
                        <div class="stat-card" onclick="navigateTo('sla')"><div class="stat-icon"><i class="material-icons-round">warning</i></div><div class="stat-value" id="statOverdue">0</div><div class="stat-label">Overdue</div></div>
                        <div class="stat-card" onclick="filterByStatus('All')"><div class="stat-icon"><i class="material-icons-round">all_inbox</i></div><div class="stat-value" id="statTotal">0</div><div class="stat-label">Total Cases</div></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="card"><div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">donut_large</i></span>Priority Distribution</span></div><div class="chart-container"><canvas id="priorityChart"></canvas></div></div>
                        <div class="card"><div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">timeline</i></span>Weekly Trend</span></div><div class="chart-container"><canvas id="trendChart"></canvas></div></div>
                    </div>
                    <div class="card"><div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">history</i></span>Recent Activity</span><button class="btn btn-secondary btn-sm" onclick="refreshDashboard()"><i class="material-icons-round">refresh</i> Refresh</button></div><div id="recentActivityList"></div></div>
                </div>
            </div>

            <!-- Cases Panel -->
            <div id="casesPanel" class="content-section">
                <div class="container">
                    <div class="card">
                        <div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">inventory_2</i></span>Case Management</span><div><button class="btn btn-primary btn-sm" onclick="exportCases()"><i class="material-icons-round">download</i> Export</button></div></div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search cases..." style="flex: 1; min-width: 140px; max-width: 220px;">
                            <select id="statusFilter" class="form-control" style="min-width: 130px;">
                                <option value="">All Status</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Fixed">Fixed</option>
                                <option value="Return">Return</option>
                                <option value="Reject">Reject</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <input type="date" id="dateStart" class="form-control" style="min-width: 130px;">
                            <input type="date" id="dateEnd" class="form-control" style="min-width: 130px;">
                            <button class="btn btn-primary btn-sm" onclick="loadCases(true)"><i class="material-icons-round">search</i></button>
                            <button class="btn btn-secondary btn-sm" onclick="clearFilters()"><i class="material-icons-round">clear_all</i></button>
                        </div>
                        <div class="table-container">
                            <div id="casesLoader" style="display: flex; justify-content: center; align-items: center; padding: 60px;"><div class="spinner"></div></div>
                            <table id="casesTable" style="display: none;">
                                <thead><tr><th width="40"><input type="checkbox" id="selectAllCheckbox"></th><th>Case ID</th><th>Description</th><th>Status</th><th>Priority</th><th>Assigned To</th><th>Due Date</th><th>Actions</th></tr></thead>
                                <tbody id="casesTableBody"></tbody>
                            </table>
                        </div>
                        <div id="bulkActionsBar" class="bulk-actions-bar">
                            <span><strong id="selectedCount">0</strong> cases selected</span>
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <select id="bulkStatusSelect" class="form-control" style="width: 170px;">
                                    <option value="In Progress">In Progress</option>
                                    <option value="Fixed">Fixed</option>
                                    <option value="Return">Return (Need Revision)</option>
                                    <option value="Reject">Reject (Cannot Fix)</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                <button class="btn btn-danger btn-sm" onclick="applyBulkUpdate()"><i class="material-icons-round">update</i> Update</button>
                                <button class="btn btn-secondary btn-sm" onclick="clearSelection()" style="border-color: rgba(255,255,255,0.3); color: white;">Cancel</button>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 20px;"><button class="btn btn-secondary" id="loadMoreBtn" onclick="loadMoreCases()" style="display: none;">Load More Cases</button></div>
                    </div>
                </div>
            </div>

            <!-- SLA Panel -->
            <div id="slaPanel" class="content-section">
                <div class="container">
                    <div class="card">
                        <div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">filter_alt</i></span>SLA Filters</span></div>
                        <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
                            <div style="min-width: 130px;"><label class="form-label">Priority</label><select id="slaPriority" class="form-control"><option value="All">All</option><option value="Critical">Critical</option><option value="Medium">Medium</option><option value="Low">Low</option></select></div>
                            <div style="min-width: 130px;"><label class="form-label">Status</label><select id="slaStatus" class="form-control"><option value="All">All Active</option><option value="Not Started">Not Started</option><option value="In Progress">In Progress</option><option value="Fixed">Fixed</option><option value="Return">Return</option></select></div>
                            <div><label class="form-label">Overdue</label><label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px;"><input type="checkbox" id="slaOverdueOnly" style="width:18px;height:18px;accent-color:var(--primary);"> <span style="font-size:13px;font-weight:600;">Only</span></label></div>
                            <button class="btn btn-primary btn-sm" onclick="loadSLAData()"><i class="material-icons-round">filter_alt</i> Apply</button>
                            <div class="admin-only"><button class="btn btn-danger btn-sm" onclick="sendSLAToTelegram()"><i class="material-icons-round">send</i> Send Report</button></div>
                        </div>
                    </div>
                    <div class="sla-grid">
                        <div class="sla-card"><div class="stat-label">Overdue Cases</div><div class="sla-value text-danger" id="slaOverdueValue">0</div></div>
                        <div class="sla-card"><div class="stat-label">Critical Active</div><div class="sla-value text-warning" id="slaCriticalValue">0</div></div>
                        <div class="sla-card"><div class="stat-label">Total Active</div><div class="sla-value" style="color: var(--success);" id="slaActiveValue">0</div></div>
                    </div>
                    <div class="card"><div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">list_alt</i></span>Filtered Cases</span></div><div id="slaCasesList"></div></div>
                </div>
            </div>

            <!-- New Case Panel -->
            <div id="newCasePanel" class="content-section">
                <div class="container">
                    <div class="card">
                        <div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">note_add</i></span>Create New Case</span></div>
                        <form id="newCaseForm">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
                                <div><label class="form-label">Case Type <span class="required">*</span></label><select id="newCaseType" class="form-control" required></select></div>
                                <div><label class="form-label">Priority <span class="required">*</span></label><select id="newPriority" class="form-control"><option value="Low">Low</option><option value="Medium" selected>Medium</option><option value="Critical">Critical</option></select></div>
                                <div><label class="form-label">Start Date <span class="required">*</span></label><input type="date" id="newStartDate" class="form-control" required></div>
                                <div><label class="form-label">Assign To</label><select id="newAssignee" class="form-control"><option value="">-- Unassigned --</option></select></div>
                                <div><label class="form-label">Initial Status</label><select id="newStatus" class="form-control"><option value="Not Started">Not Started</option><option value="In Progress">In Progress</option></select></div>
                            </div>
                            <div class="form-group"><label class="form-label">Description <span class="required">*</span></label><textarea id="newDescription" class="form-control" rows="4" required placeholder="Describe the issue in detail..."></textarea></div>
                            <div class="remark-field">
                                <label class="form-label"><i class="material-icons-round" style="font-size:14px;vertical-align:middle;margin-right:4px;">edit_note</i> Remarks / Notes</label>
                                <textarea id="newRemark" class="form-control" rows="3" placeholder="Add any additional notes, context, or instructions for the assignee..."></textarea>
                            </div>
                            <div class="form-group"><label class="form-label">Attachment</label><input type="file" id="newAttachment" class="form-control"></div>
                            <div style="display: flex; gap: 12px; justify-content: flex-end;"><button type="button" class="btn btn-secondary" onclick="navigateTo('cases')">Cancel</button><button type="submit" class="btn btn-primary"><i class="material-icons-round">send</i> Create Case</button></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Assignees Panel -->
            <div id="assigneesPanel" class="content-section">
                <div class="container">
                    <div class="card">
                        <div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">group</i></span>Assignees</span><button class="btn btn-primary btn-sm admin-only" onclick="openAddAssigneeModal()"><i class="material-icons-round">person_add</i> Add</button></div>
                        <div class="table-container"><table><thead><tr><th>Name</th><th>Telegram ID</th><th>Status</th><th class="admin-only">Actions</th></tr></thead><tbody id="assigneesTableBody"><tr><td colspan="4" style="text-align:center;padding:40px;">Loading...</td></tr></tbody></table></div>
                    </div>
                </div>
            </div>

            <!-- Users Panel -->
            <div id="usersPanel" class="content-section">
                <div class="container">
                    <div class="card">
                        <div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">admin_panel_settings</i></span>Users</span><button class="btn btn-primary btn-sm admin-only" onclick="openAddUserModal()"><i class="material-icons-round">person_add</i> Add</button></div>
                        <div class="table-container"><table><thead><tr><th>Username</th><th>Role</th><th>Status</th><th class="admin-only">Actions</th></tr></thead><tbody id="usersTableBody"><tr><td colspan="4" style="text-align:center;padding:40px;">Loading...</td></tr></tbody></table></div>
                    </div>
                </div>
            </div>

            <!-- ENHANCED Settings Panel -->
            <div id="settingsPanel" class="content-section">
                <div class="container">
                    <div class="card">
                        <div class="card-title"><span><span class="card-title-icon"><i class="material-icons-round">tune</i></span>System Settings</span></div>
                        <div class="settings-tabs">
                            <div class="settings-tab active" onclick="switchSettingsTab('general')">General</div>
                            <div class="settings-tab" onclick="switchSettingsTab('appearance')">Appearance</div>
                            <div class="settings-tab" onclick="switchSettingsTab('caseTypes')">Case Types</div>
                            <div class="settings-tab" onclick="switchSettingsTab('telegram')">Telegram</div>
                        </div>
                        
                        <div id="generalSettings" class="settings-panel active">
                            <div class="settings-grid">
                                <div class="settings-section">
                                    <h4><i class="material-icons-round">business</i> Company Information</h4>
                                    <div class="form-group"><label class="form-label">Company Name</label><input type="text" id="companyName" class="form-control" placeholder="Kaldis Coffee"></div>
                                    <div class="form-group"><label class="form-label">Company Email</label><input type="email" id="companyEmail" class="form-control" placeholder="support.ict@kaldisbunna.et"></div>
                                    <div class="form-group"><label class="form-label">Company Phone</label><input type="text" id="companyPhone" class="form-control" placeholder="+251 XXX XXX XXX"></div>
                                    <div class="form-group"><label class="form-label">Company Address</label><textarea id="companyAddress" class="form-control" rows="2" placeholder="Company address..."></textarea></div>
                                </div>
                                <div class="settings-section">
                                    <h4><i class="material-icons-round">notifications</i> Notification Settings</h4>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="notifyOnCreate" style="margin-right: 8px;"> Notify on case creation</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="notifyOnStatusChange" style="margin-right: 8px;"> Notify on status change</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="notifyOnAssignment" style="margin-right: 8px;"> Notify on assignment</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="emailDailyDigest" style="margin-right: 8px;"> Send daily digest email</label></div>
                                </div>
                            </div>
                            <div style="margin-top: 20px; text-align: right;"><button class="btn btn-primary" onclick="saveGeneralSettings()"><i class="material-icons-round">save</i> Save All</button></div>
                        </div>

                        <div id="appearanceSettings" class="settings-panel">
                            <div class="settings-grid">
                                <div class="settings-section">
                                    <h4><i class="material-icons-round">palette</i> Branding</h4>
                                    <div class="form-group"><label class="form-label">Logo URL</label><input type="text" id="logoUrl" class="form-control" placeholder="https://..."></div>
                                    <div class="form-group"><label class="form-label">Favicon URL</label><input type="text" id="faviconUrl" class="form-control" placeholder="https://..."></div>
                                    <div class="form-group"><label class="form-label">Primary Color</label><input type="color" id="primaryColor" class="form-control" style="height: 45px; padding: 5px;"></div>
                                    <div class="form-group"><label class="form-label">Secondary Color</label><input type="color" id="secondaryColor" class="form-control" style="height: 45px; padding: 5px;"></div>
                                </div>
                                <div class="settings-section">
                                    <h4><i class="material-icons-round">dashboard_customize</i> Dashboard Preferences</h4>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="showPerformanceStats" style="margin-right: 8px;"> Show performance statistics</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="showWeeklyTrend" style="margin-right: 8px;"> Show weekly trend chart</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="autoRefreshDashboard" style="margin-right: 8px;"> Auto-refresh dashboard (30s)</label></div>
                                    <div class="form-group"><label class="form-label">Default items per page</label><select id="itemsPerPage" class="form-control"><option value="25">25</option><option value="50" selected>50</option><option value="100">100</option></select></div>
                                </div>
                            </div>
                            <div style="margin-top: 20px; text-align: right;"><button class="btn btn-primary" onclick="saveAppearanceSettings()"><i class="material-icons-round">save</i> Save Appearance</button></div>
                        </div>

                        <div id="caseTypesSettings" class="settings-panel">
                            <div style="display: flex; gap: 10px; margin-bottom: 20px;"><input type="text" id="newCaseTypeInput" class="form-control" placeholder="New Case Type Name"><button class="btn btn-primary" onclick="addNewCaseType()"><i class="material-icons-round">add</i> Add</button></div>
                            <div class="table-container"><table><thead><tr><th>Type Name</th><th>Total Cases</th><th class="admin-only">Action</th></tr></thead><tbody id="caseTypesTableBody"></tbody></table></div>
                        </div>

                        <div id="telegramSettings" class="settings-panel">
                            <div class="settings-grid">
                                <div class="settings-section">
                                    <h4><i class="material-icons-round">telegram</i> Telegram Bot Configuration</h4>
                                    <div class="form-group"><label class="form-label">Bot Token</label><input type="password" id="telegramBotToken" class="form-control" placeholder="From @BotFather"></div>
                                    <div class="form-group"><label class="form-label">Group Chat ID</label><input type="text" id="telegramGroupChatId" class="form-control" placeholder="-100..."></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="telegramActive" style="margin-right: 8px;"> Enable Telegram Notifications</label></div>
                                </div>
                                <div class="settings-section">
                                    <h4><i class="material-icons-round">notifications_active</i> Notification Types</h4>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="tgNotifyNewCase" style="margin-right: 8px;"> New case created</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="tgNotifyStatusChange" style="margin-right: 8px;"> Status changes</label></div>
                                    <div class="form-group"><label class="form-label"><input type="checkbox" id="tgNotifyOverdue" style="margin-right: 8px;"> Overdue cases</label></div>
                                </div>
                            </div>
                            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;"><button class="btn btn-secondary btn-sm" onclick="testTelegramConnection()"><i class="material-icons-round">science</i> Test Connection</button><button class="btn btn-primary btn-sm" onclick="saveTelegramSettings()"><i class="material-icons-round">save</i> Save Settings</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Case Detail Modal -->
<div id="caseDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <h3 id="detailCaseId">Case Details</h3>
                <span id="detailStatusBadge" class="badge"></span>
            </div>
            <button class="btn-icon" onclick="closeModal('caseDetailModal')"><i class="material-icons-round">close</i></button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div>
                    <div class="detail-tabs">
                        <div class="detail-tab active" onclick="switchDetailTab('info')">Details</div>
                        <div class="detail-tab" onclick="switchDetailTab('comments')">Comments (<span id="commentCount">0</span>)</div>
                        <div class="detail-tab" onclick="switchDetailTab('history')">History</div>
                    </div>
                    <div id="infoTab" class="detail-panel active">
                        <p id="detailDescription" style="white-space: pre-wrap; line-height: 1.7; font-size: 13.5px;"></p>
                        <div id="detailRemark" style="margin-top: 12px;"></div>
                        <div id="detailAttachment" style="margin-top: 12px;"></div>
                    </div>
                    <div id="commentsTab" class="detail-panel">
                        <div id="commentsList" style="max-height: 380px; overflow-y: auto;"></div>
                        <div style="display: flex; gap: 10px; margin-top: 16px;">
                            <input type="text" id="newCommentInput" class="form-control" placeholder="Type a comment..." onkeydown="if(event.key==='Enter')addComment()">
                            <button class="btn btn-primary btn-sm" onclick="addComment()" style="flex-shrink:0;"><i class="material-icons-round">send</i></button>
                        </div>
                    </div>
                    <div id="historyTab" class="detail-panel"><div id="historyList"></div></div>
                </div>
                <div>
                    <div class="info-card">
                        <div class="info-row"><div class="info-icon"><i class="material-icons-round">person</i></div><div><strong>Assigned To</strong><span id="detailAssignee"></span></div></div>
                        <div class="info-row"><div class="info-icon"><i class="material-icons-round">flag</i></div><div><strong>Priority</strong><span id="detailPriority"></span></div></div>
                        <div class="info-row"><div class="info-icon"><i class="material-icons-round">event</i></div><div><strong>Due Date</strong><span id="detailDueDate"></span></div></div>
                        <div class="info-row"><div class="info-icon"><i class="material-icons-round">folder</i></div><div><strong>Case Type</strong><span id="detailCaseType"></span></div></div>
                    </div>
                    <div class="cnet-only" style="margin-top: 12px;"><button class="btn btn-primary" style="width: 100%;" onclick="editCurrentCase()"><i class="material-icons-round">edit</i> Edit Case</button></div>
                    <div class="admin-only" style="margin-top: 12px;"><button class="btn btn-danger" style="width: 100%;" onclick="deleteCurrentCase()"><i class="material-icons-round">delete</i> Delete Case</button></div>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('caseDetailModal')">Close</button></div>
    </div>
</div>

<!-- Delete Case Confirmation Modal -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header"><h3><i class="material-icons-round" style="color:var(--danger);">warning</i> Delete Case</h3><button class="btn-icon" onclick="closeModal('deleteConfirmModal')"><i class="material-icons-round">close</i></button></div>
        <div class="modal-body">
            <p>Are you sure you want to delete this case?</p>
            <p style="color: var(--danger); font-size: 12px; margin-top: 10px;">This action cannot be undone. All comments and history will be permanently removed.</p>
            <input type="hidden" id="deleteCaseId">
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button><button class="btn btn-danger" onclick="confirmDeleteCase()"><i class="material-icons-round">delete_forever</i> Delete Permanently</button></div>
    </div>
</div>

<!-- Edit Case Modal -->
<div id="editCaseModal" class="modal">
    <div class="modal-content" style="max-width: 680px;">
        <div class="modal-header"><h3><i class="material-icons-round" style="vertical-align:middle;margin-right:8px;color:var(--primary);">edit_note</i>Update Case</h3><button class="btn-icon" onclick="closeModal('editCaseModal')"><i class="material-icons-round">close</i></button></div>
        <div class="modal-body">
            <form id="editCaseForm">
                <input type="hidden" id="editCaseId">
                <div id="kaldisEditSection" style="display: none;">
                    <div class="form-group"><label class="form-label">Status</label><select id="editStatus" class="form-control"><option value="Fixed">Fixed - Ready for Review</option><option value="Return">Return - Need Revision</option><option value="Reject">Reject - Cannot Fix</option></select></div>
                    <div class="remark-field">
                        <label class="form-label"><i class="material-icons-round" style="font-size:14px;vertical-align:middle;margin-right:4px;">edit_note</i> Remarks / Reason <span class="required">*</span></label>
                        <textarea id="editRemarks" class="form-control" rows="4" placeholder="Explain your reason for this status change in detail..."></textarea>
                    </div>
                </div>
                <div id="cnetEditSection">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div><label class="form-label">Start Date</label><input type="date" id="editStartDate" class="form-control" readonly></div>
                        <div><label class="form-label">Due Date</label><input type="date" id="editDueDate" class="form-control"></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div><label class="form-label">Priority</label><select id="editPriority" class="form-control"><option value="Low">Low</option><option value="Medium">Medium</option><option value="Critical">Critical</option></select></div>
                        <div><label class="form-label">Assignee</label><select id="editAssignee" class="form-control"></select></div>
                    </div>
                    <div class="form-group"><label class="form-label">Description</label><textarea id="editDescription" class="form-control" rows="3"></textarea></div>
                    <div class="remark-field">
                        <label class="form-label"><i class="material-icons-round" style="font-size:14px;vertical-align:middle;margin-right:4px;">edit_note</i> Remarks / Notes</label>
                        <textarea id="editCnetRemarks" class="form-control" rows="3" placeholder="Add any remarks or notes for this update..."></textarea>
                    </div>
                </div>
                <div id="adminEditSection" style="display:none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div><label class="form-label">Status</label><select id="editAdminStatus" class="form-control"><option value="Not Started">Not Started</option><option value="In Progress">In Progress</option><option value="Fixed">Fixed</option><option value="Return">Return</option><option value="Reject">Reject</option><option value="Completed">Completed</option></select></div>
                        <div><label class="form-label">Priority</label><select id="editAdminPriority" class="form-control"><option value="Low">Low</option><option value="Medium">Medium</option><option value="Critical">Critical</option></select></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div><label class="form-label">Due Date</label><input type="date" id="editAdminDueDate" class="form-control"></div>
                        <div><label class="form-label">Assignee</label><select id="editAdminAssignee" class="form-control"></select></div>
                    </div>
                    <div class="form-group"><label class="form-label">Description</label><textarea id="editAdminDescription" class="form-control" rows="3"></textarea></div>
                    <div class="remark-field">
                        <label class="form-label"><i class="material-icons-round" style="font-size:14px;vertical-align:middle;margin-right:4px;">edit_note</i> Admin Remarks</label>
                        <textarea id="editAdminRemarks" class="form-control" rows="3" placeholder="Admin notes..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('editCaseModal')">Cancel</button><button class="btn btn-primary" onclick="submitEditCase()"><i class="material-icons-round">check</i> Update</button></div>
    </div>
</div>

<!-- Assignee Modal -->
<div id="assigneeModal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header"><h3 id="assigneeModalTitle">Add Assignee</h3><button class="btn-icon" onclick="closeModal('assigneeModal')"><i class="material-icons-round">close</i></button></div>
        <div class="modal-body">
            <form id="assigneeForm"><input type="hidden" id="editAssigneeId">
                <div class="form-group"><label class="form-label">Name <span class="required">*</span></label><input type="text" id="assigneeName" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Telegram ID</label><input type="text" id="assigneeTelegramId" class="form-control" placeholder="Numeric ID or @username"></div>
                <div class="form-group"><label class="form-label">Status</label><select id="assigneeStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('assigneeModal')">Cancel</button><button class="btn btn-primary" onclick="saveAssignee()"><i class="material-icons-round">save</i> Save</button></div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header"><h3 id="userModalTitle">Add User</h3><button class="btn-icon" onclick="closeModal('userModal')"><i class="material-icons-round">close</i></button></div>
        <div class="modal-body">
            <form id="userForm"><input type="hidden" id="editUsername">
                <div class="form-group"><label class="form-label">Username <span class="required">*</span></label><input type="text" id="userUsername" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Password</label><input type="password" id="userPassword" class="form-control" placeholder="Leave blank to keep current"></div>
                <div class="form-group"><label class="form-label">Role</label><select id="userRole" class="form-control"><option value="admin">Admin</option><option value="kaldis">Kaldis</option><option value="cnet">CNET</option></select></div>
                <div class="form-group"><label class="form-label">Status</label><select id="userStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            </form>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button><button class="btn btn-primary" onclick="saveUser()"><i class="material-icons-round">save</i> Save</button></div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Loader -->
<div class="loader-overlay" id="loader"><div class="loader-box"><div class="spinner"></div><span>Loading...</span></div></div>

<script>
const API_URL = 'case_api.php';
let currentUser = <?php echo json_encode($currentUser); ?>;
let assigneesList = [], caseTypesList = [], usersList = [];
let currentOffset = 0, limit = 50, hasMore = true;
let selectedCaseIds = new Set();
let currentDetailCaseId = null;
let priorityChart = null, trendChart = null;
let autoRefreshInterval = null;

// ==================== UTILITIES ====================
function showLoader(show) { 
    const loader = document.getElementById('loader');
    if (loader) loader.classList.toggle('active', show);
}
function closeModal(id) { 
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}
function openModal(id) { 
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    const icons = { success: 'check_circle', error: 'error', info: 'info', warning: 'warning' };
    const titles = { success: 'Success', error: 'Error', info: 'Information', warning: 'Warning' };
    const bgColors = { success: '#10B981', error: '#EF4444', info: '#3B82F6', warning: '#F59E0B' };
    
    if (message.toLowerCase().includes('failed') || 
        message.toLowerCase().includes('denied') || 
        message.toLowerCase().includes('required') ||
        message.toLowerCase().includes('invalid') ||
        message.toLowerCase().includes('not found')) {
        type = 'error';
    }
    
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon" style="background: ${bgColors[type]}20; color: ${bgColors[type]};">
            <i class="material-icons-round">${icons[type]}</i>
        </div>
        <div class="toast-body">
            <div class="toast-title">${titles[type]}</div>
            <div class="toast-message">${escapeHtml(message)}</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this.parentElement)">
            <i class="material-icons-round" style="font-size:14px;">close</i>
        </button>
        <div class="toast-progress" style="background: ${bgColors[type]};"></div>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => dismissToast(toast), 4500);
}

function dismissToast(toast) {
    if (!toast || !toast.parentElement) return;
    toast.classList.remove('show');
    setTimeout(() => {
        if (toast.parentElement) toast.remove();
    }, 300);
}

function escapeHtml(text) { 
    if (!text) return ''; 
    const div = document.createElement('div'); 
    div.textContent = text; 
    return div.innerHTML; 
}

async function apiCall(action, method = 'GET', data = null) {
    let url = `${API_URL}?action=${encodeURIComponent(action)}`;
    
    if (method === 'GET' && data) {
        const params = new URLSearchParams();
        for (const [key, value] of Object.entries(data)) {
            if (value !== undefined && value !== null && value !== '') {
                params.append(key, value);
            }
        }
        const queryString = params.toString();
        if (queryString) url += '&' + queryString;
    }
    
    const options = { 
        method: method,
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };
    
    if (method === 'POST' && data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        return result;
    } catch (error) {
        console.error(`API Error [${action}]:`, error);
        throw error;
    }
}

async function logout() { 
    try { 
        await apiCall('logout', 'POST'); 
        showToast('Logged out successfully', 'success');
        setTimeout(() => window.location.reload(), 500);
    } catch(e){ 
        window.location.reload(); 
    } 
}

function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    if (!sb) return;
    sb.classList.toggle('collapsed');
    const icon = sb.querySelector('.sidebar-toggle i');
    if (icon) icon.textContent = sb.classList.contains('collapsed') ? 'chevron_right' : 'chevron_left';
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('active');
}

function closeMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
    showToast(`Dark mode ${isDark ? 'enabled' : 'disabled'}`, 'info');
}

// ==================== NAVIGATION ====================
function navigateTo(section) {
    if (['users', 'settings'].includes(section) && currentUser.role !== 'admin') { 
        showToast('Access Denied: Admin privileges required', 'error'); 
        return; 
    }
    if (section === 'newCase' && currentUser.role !== 'kaldis' && currentUser.role !== 'admin') { 
        showToast('Access Denied: Kaldis users only', 'error'); 
        return; 
    }
    
    document.querySelectorAll('.content-section').forEach(el => el.classList.remove('active'));
    const panel = document.getElementById(`${section}Panel`);
    if (panel) panel.classList.add('active');
    
    document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
    const activeMenu = document.querySelector(`.menu-item[onclick*="'${section}'"]`);
    if (activeMenu) activeMenu.classList.add('active');
    
    const titles = { 
        dashboard: 'Dashboard', 
        cases: 'Case List', 
        sla: 'SLA Dashboard', 
        newCase: 'New Case', 
        assignees: 'Assignees', 
        users: 'Users', 
        settings: 'Settings' 
    };
    const titleElement = document.getElementById('pageTitle');
    if (titleElement) titleElement.innerText = titles[section] || section;
    
    closeMobileSidebar();
    
    if (section === 'dashboard') { loadDashboard(); }
    if (section === 'cases') { loadCases(true); }
    if (section === 'sla') { loadSLAData(); }
    if (section === 'assignees') { loadAssignees(); }
    if (section === 'users') { loadUsers(); }
    if (section === 'settings') { loadSettings(); }
}

function loadMyTasks() { 
    navigateTo('cases'); 
    setTimeout(() => {
        const filterSelect = document.getElementById('statusFilter');
        if (filterSelect) filterSelect.value = '';
        loadCases(true);
        showToast('Loading your assigned cases...', 'info');
    }, 100);
}

function filterByStatus(status) { 
    if (status === 'All') {
        const filterSelect = document.getElementById('statusFilter');
        if (filterSelect) filterSelect.value = '';
    } else {
        const filterSelect = document.getElementById('statusFilter');
        if (filterSelect) filterSelect.value = status;
    }
    navigateTo('cases'); 
}

function switchSettingsTab(tab) {
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    const activeTab = document.querySelector(`.settings-tab[onclick="switchSettingsTab('${tab}')"]`);
    if (activeTab) activeTab.classList.add('active');
    
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
    let panelId = '';
    if (tab === 'general') panelId = 'generalSettings';
    else if (tab === 'appearance') panelId = 'appearanceSettings';
    else if (tab === 'caseTypes') panelId = 'caseTypesSettings';
    else if (tab === 'telegram') panelId = 'telegramSettings';
    
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');
}

function switchDetailTab(tab) {
    document.querySelectorAll('.detail-tab').forEach(t => t.classList.remove('active'));
    const activeTab = document.querySelector(`.detail-tab[onclick="switchDetailTab('${tab}')"]`);
    if (activeTab) activeTab.classList.add('active');
    
    document.querySelectorAll('.detail-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById(`${tab}Tab`);
    if (panel) panel.classList.add('active');
}

// ==================== ENHANCED DASHBOARD ====================
async function loadDashboard() {
    showLoader(true);
    try {
        const data = await apiCall('getDashboardData');
        document.getElementById('statCompleted').innerText = data.details.completed || 0;
        document.getElementById('statNotStarted').innerText = data.details.notStarted || 0;
        document.getElementById('statInProgress').innerText = data.details.inProgress || 0;
        document.getElementById('statFixed').innerText = data.details.fixed || 0;
        document.getElementById('statReturn').innerText = data.details.return || 0;
        document.getElementById('statReject').innerText = data.details.reject || 0;
        document.getElementById('statOverdue').innerText = data.details.overdue || 0;
        document.getElementById('statTotal').innerText = data.details.total || 0;
        
        // Calculate resolution rate
        const total = data.details.total || 1;
        const completed = data.details.completed || 0;
        const resolutionRate = Math.round((completed / total) * 100);
        document.getElementById('resolutionRate').innerText = resolutionRate + '%';
        
        if (priorityChart) priorityChart.destroy();
        const ctx = document.getElementById('priorityChart').getContext('2d');
        priorityChart = new Chart(ctx, {
            type: 'doughnut',
            data: { 
                labels: ['Critical', 'Medium', 'Low'], 
                datasets: [{ 
                    data: [data.matrix.Critical || 0, data.matrix.Medium || 0, data.matrix.Low || 0], 
                    backgroundColor: ['#EF4444', '#F59E0B', '#10B981'], 
                    borderWidth: 0, 
                    spacing: 3, 
                    borderRadius: 6 
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '68%', 
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            padding: 20, 
                            usePointStyle: true, 
                            pointStyleWidth: 10, 
                            font: { family: 'Inter', weight: '600', size: 12 } 
                        } 
                    } 
                } 
            }
        });
        
        // Weekly trend chart
        if (trendChart) trendChart.destroy();
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Cases Created',
                    data: [12, 19, 15, 17, 14, 10, 8],
                    borderColor: '#D97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
        
        const activities = await apiCall('getRecentActivity');
        const activityList = document.getElementById('recentActivityList');
        if (activityList) {
            activityList.innerHTML = activities.length ? 
                activities.slice(0, 8).map(a => `
                    <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i class="material-icons-round" style="font-size:16px;color:var(--primary);">circle</i>
                            <strong style="font-size:13px;">${escapeHtml(a.action)}</strong>
                            <span style="font-size:11px;color:var(--text-muted);margin-left:auto;white-space:nowrap;">
                                ${new Date(a.timestamp).toLocaleString()}
                            </span>
                        </div>
                        <div style="font-size:12.5px;color:var(--text-muted);margin-top:4px;padding-left:24px;">
                            ${escapeHtml(a.details)}
                        </div>
                    </div>
                `).join('') : 
                '<div style="text-align:center;padding:40px;color:var(--text-muted);">' +
                '<i class="material-icons-round" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;">inbox</i>' +
                'No recent activity</div>';
        }
    } catch (err) { 
        showToast(err.message || 'Failed to load dashboard', 'error'); 
    } finally { 
        showLoader(false); 
    }
}

function refreshDashboard() {
    loadDashboard();
    showToast('Dashboard refreshed', 'info');
}

// ==================== DELETE CASE (Admin Only) ====================
function deleteCurrentCase() {
    if (!currentDetailCaseId) {
        showToast('No case selected', 'error');
        return;
    }
    document.getElementById('deleteCaseId').value = currentDetailCaseId;
    closeModal('caseDetailModal');
    openModal('deleteConfirmModal');
}

async function confirmDeleteCase() {
    const caseId = document.getElementById('deleteCaseId').value;
    if (!caseId) return;
    
    if (!confirm('WARNING: This will permanently delete the case and all associated data. This action cannot be undone!\n\nAre you absolutely sure?')) {
        closeModal('deleteConfirmModal');
        return;
    }
    
    showLoader(true);
    try {
        await apiCall('deleteCase', 'POST', { caseId: caseId });
        showToast(`Case ${caseId} has been permanently deleted`, 'success');
        closeModal('deleteConfirmModal');
        loadCases(true);
        if (document.getElementById('dashboardPanel').classList.contains('active')) {
            loadDashboard();
        }
    } catch (err) {
        showToast(err.message || 'Failed to delete case', 'error');
    } finally {
        showLoader(false);
    }
}

// ==================== CASES ====================
async function loadCases(reset = false) {
    if (reset) { 
        currentOffset = 0; 
        hasMore = true; 
        const tableBody = document.getElementById('casesTableBody');
        if (tableBody) tableBody.innerHTML = ''; 
        clearSelection(); 
    }
    if (!hasMore && currentOffset > 0) return;
    
    const loader = document.getElementById('casesLoader');
    const table = document.getElementById('casesTable');
    if (loader) loader.style.display = 'flex';
    if (table) table.style.display = 'none';
    
    try {
        const filters = { 
            term: document.getElementById('searchInput')?.value || '', 
            status: document.getElementById('statusFilter')?.value || '', 
            startDate: document.getElementById('dateStart')?.value || '', 
            endDate: document.getElementById('dateEnd')?.value || '' 
        };
        const result = await apiCall('getCaseList', 'GET', { offset: currentOffset, limit: limit, filters: JSON.stringify(filters) });
        
        if (loader) loader.style.display = 'none';
        if (table) table.style.display = 'table';
        
        if (!result.cases || result.cases.length === 0) { 
            hasMore = false; 
            if (currentOffset === 0 && table) {
                const tableBody = document.getElementById('casesTableBody');
                if (tableBody) {
                    tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:50px;color:var(--text-muted);">' +
                        '<i class="material-icons-round" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;">search_off</i>' +
                        'No cases found</td></tr>';
                }
            }
            return; 
        }
        
        currentOffset += result.cases.length;
        hasMore = result.cases.length >= limit;
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) loadMoreBtn.style.display = hasMore ? 'inline-block' : 'none';
        
        const today = new Date(); 
        today.setHours(0,0,0,0);
        
        const html = result.cases.map(c => {
            let editBtn = '';
            if (currentUser.role === 'cnet' && (c.status === 'In Progress' || c.status === 'Not Started')) {
                editBtn = `<button class="btn-icon" onclick='openEditCaseModal("${c.case_id}")' title="Edit Case"><i class="material-icons-round">edit</i></button>`;
            } else if (currentUser.role === 'kaldis' && (c.status === 'Fixed' || c.status === 'In Progress')) {
                editBtn = `<button class="btn-icon" onclick='openEditCaseModal("${c.case_id}")' title="Update Status"><i class="material-icons-round">edit_note</i></button>`;
            } else if (currentUser.role === 'admin') {
                editBtn = `<button class="btn-icon" onclick='openEditCaseModal("${c.case_id}")' title="Edit Case"><i class="material-icons-round">edit</i></button>`;
            }
            
            // Delete button for admin
            let deleteBtn = '';
            if (currentUser.role === 'admin') {
                deleteBtn = `<button class="btn-icon" onclick='deleteCaseFromList("${c.case_id}")' title="Delete Case" style="color:var(--danger);"><i class="material-icons-round">delete</i></button>`;
            }
            
            const due = new Date(c.due_date);
            let dueClass = '';
            if (c.due_date && c.status !== 'Completed') { 
                if (due < today) dueClass = 'text-danger'; 
                else if (due <= new Date(today.getTime() + 86400000)) dueClass = 'text-warning'; 
            }
            
            const desc = c.description && c.description.length > 60 ? 
                escapeHtml(c.description.substring(0, 60)) + '...' : 
                escapeHtml(c.description || '');
            
            return `<tr>
                <td><input type="checkbox" class="case-checkbox" value="${c.case_id}" onchange="updateBulkSelection()"></td>
                <td><strong style="font-size:12px;">${escapeHtml(c.case_id)}</strong>${c.attachments ? ' <i class="material-icons-round" style="font-size:14px;color:var(--primary);vertical-align:middle;">attach_file</i>' : ''}</td>
                <td style="max-width:200px;">${desc}</td>
                <td><span class="badge status-${c.status.toLowerCase().replace(/\s/g, '-')}">${escapeHtml(c.status)}</span></td>
                <td><span class="badge priority-${c.priority.toLowerCase()}">${escapeHtml(c.priority)}</span></td>
                <td style="white-space:nowrap;">${escapeHtml(c.assigned_to || '-')}</td>
                <td class="${dueClass}" style="white-space:nowrap;font-size:12px;">${c.due_date || '-'}</td>
                <td style="white-space:nowrap;">${editBtn}${deleteBtn}<button class="btn-icon" onclick='viewCaseDetail("${c.case_id}")' title="View Details"><i class="material-icons-round">visibility</i></button></td>
            </tr>`;
        }).join('');
        
        const tableBody = document.getElementById('casesTableBody');
        if (tableBody) tableBody.innerHTML += html;
        
    } catch (err) { 
        showToast(err.message || 'Failed to load cases', 'error'); 
        if (loader) loader.style.display = 'none';
    } finally { 
        if (loader) loader.style.display = 'none';
    }
}

function deleteCaseFromList(caseId) {
    if (!confirm(`Permanently delete case ${caseId}? This action cannot be undone.`)) return;
    showLoader(true);
    apiCall('deleteCase', 'POST', { caseId: caseId })
        .then(() => {
            showToast(`Case ${caseId} deleted`, 'success');
            loadCases(true);
        })
        .catch(err => showToast(err.message, 'error'))
        .finally(() => showLoader(false));
}

function loadMoreCases() { loadCases(false); }

function clearFilters() { 
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const dateStart = document.getElementById('dateStart');
    const dateEnd = document.getElementById('dateEnd');
    
    if (searchInput) searchInput.value = ''; 
    if (statusFilter) statusFilter.value = ''; 
    if (dateStart) dateStart.value = ''; 
    if (dateEnd) dateEnd.value = ''; 
    loadCases(true);
    showToast('Filters cleared', 'info');
}

function updateBulkSelection() { 
    selectedCaseIds.clear(); 
    document.querySelectorAll('.case-checkbox:checked').forEach(cb => selectedCaseIds.add(cb.value)); 
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkBar = document.getElementById('bulkActionsBar');
    if (selectedCountSpan) selectedCountSpan.innerText = selectedCaseIds.size; 
    if (bulkBar) bulkBar.classList.toggle('active', selectedCaseIds.size > 0);
}

function clearSelection() { 
    document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = false); 
    const selectAll = document.getElementById('selectAllCheckbox');
    if (selectAll) selectAll.checked = false;
    updateBulkSelection(); 
    showToast('Selection cleared', 'info');
}

async function applyBulkUpdate() { 
    if (selectedCaseIds.size === 0) {
        showToast('Please select at least one case to update', 'warning');
        return;
    }
    
    const statusSelect = document.getElementById('bulkStatusSelect');
    const status = statusSelect ? statusSelect.value : '';
    
    if (!status) {
        showToast('Please select a status to apply', 'warning');
        return;
    }
    
    if (!confirm(`Update ${selectedCaseIds.size} case(s) to "${status}"?`)) return;
    
    showLoader(true); 
    try { 
        const caseIdsArray = Array.from(selectedCaseIds);
        const result = await apiCall('bulkUpdateCases', 'POST', { caseIds: caseIdsArray, newStatus: status }); 
        showToast(result.message || `${selectedCaseIds.size} case(s) updated to ${status}`, 'success'); 
        clearSelection(); 
        loadCases(true); 
    } catch (err) { 
        showToast(err.message || 'Bulk update failed', 'error'); 
    } finally { 
        showLoader(false); 
    } 
}

async function exportCases() { 
    showLoader(true); 
    try { 
        const filters = { 
            term: document.getElementById('searchInput')?.value || '', 
            status: document.getElementById('statusFilter')?.value || '', 
            startDate: document.getElementById('dateStart')?.value || '', 
            endDate: document.getElementById('dateEnd')?.value || '' 
        };
        const result = await apiCall('exportFilteredCases', 'GET', { filters: JSON.stringify(filters) });
        if (result.url) {
            window.open(result.url, '_blank');
            showToast(`Exporting ${result.count || 'cases'}...`, 'success');
        }
    } catch (err) { 
        showToast(err.message || 'Export failed', 'error'); 
    } finally { 
        showLoader(false); 
    } 
}

// ==================== CASE DETAIL ====================
async function viewCaseDetail(caseId) {
    currentDetailCaseId = caseId; 
    showLoader(true);
    try {
        const result = await apiCall('getCaseList', 'GET', { offset: 0, limit: 1, filters: JSON.stringify({ term: caseId }) });
        if (!result.cases || result.cases.length === 0) throw new Error('Case not found');
        
        const c = result.cases[0];
        
        document.getElementById('detailCaseId').innerHTML = `<span style="font-size:12px;font-weight:600;color:var(--text-muted);">CASE</span> ${escapeHtml(c.case_id)}`;
        document.getElementById('detailStatusBadge').innerHTML = escapeHtml(c.status);
        document.getElementById('detailStatusBadge').className = `badge status-${c.status.toLowerCase().replace(/\s/g, '-')}`;
        document.getElementById('detailDescription').innerHTML = escapeHtml(c.description);
        document.getElementById('detailRemark').innerHTML = c.remarks ? `<div class="remark-field" style="margin-top:12px;"><div style="font-size:11px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="material-icons-round" style="font-size:13px;vertical-align:middle;">edit_note</i> Remarks</div><div style="font-size:13px;line-height:1.6;white-space:pre-wrap;">${escapeHtml(c.remarks)}</div></div>` : '';
        document.getElementById('detailAssignee').innerHTML = escapeHtml(c.assigned_to || 'Unassigned');
        document.getElementById('detailPriority').innerHTML = `<span class="badge priority-${c.priority.toLowerCase()}">${escapeHtml(c.priority)}</span>`;
        document.getElementById('detailDueDate').innerHTML = c.due_date || 'Not set';
        document.getElementById('detailCaseType').innerHTML = escapeHtml(c.case_type);
        document.getElementById('detailAttachment').innerHTML = c.attachments ? `<a href="${c.attachments}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;color:var(--primary);font-weight:600;font-size:13px;text-decoration:none;padding:8px 14px;background:var(--primary-bg);border-radius:10px;"><i class="material-icons-round" style="font-size:18px;">attach_file</i>Download Attachment</a>` : '';
        
        switchDetailTab('info');
        
        const comments = await apiCall('getComments', 'GET', { caseId: caseId });
        document.getElementById('commentCount').innerText = comments.length;
        document.getElementById('commentsList').innerHTML = comments.length ? comments.map(com => `<div class="comment-item ${com.author === currentUser.username ? 'mine' : ''}"><div class="comment-meta"><strong>${escapeHtml(com.author)}</strong><span>${new Date(com.created_at).toLocaleString()}</span></div><div class="comment-text">${escapeHtml(com.comment)}</div></div>`).join('') : '<div style="text-align:center;padding:30px;color:var(--text-muted);"><i class="material-icons-round" style="font-size:32px;display:block;margin-bottom:6px;opacity:0.3;">chat_bubble_outline</i>No comments yet</div>';
        
        const history = await apiCall('getCaseHistory', 'GET', { caseId: caseId });
        document.getElementById('historyList').innerHTML = history.length ? history.map(h => `<div class="history-item"><div class="history-content"><strong>${escapeHtml(h.action)}</strong><p>${escapeHtml(h.details)}</p></div><div class="history-time">${new Date(h.timestamp).toLocaleString()}<br><span style="color:var(--primary);font-weight:600;">${escapeHtml(h.user)}</span></div></div>`).join('') : '<div style="text-align:center;padding:30px;color:var(--text-muted);"><i class="material-icons-round" style="font-size:32px;display:block;margin-bottom:6px;opacity:0.3;">update</i>No history recorded</div>';
        
        openModal('caseDetailModal');
    } catch (err) { 
        showToast(err.message || 'Failed to load case details', 'error'); 
    } finally { 
        showLoader(false); 
    }
}

async function addComment() { 
    const commentInput = document.getElementById('newCommentInput');
    const comment = commentInput ? commentInput.value.trim() : '';
    
    if (!comment) { showToast('Please enter a comment', 'warning'); return; }
    if (!currentDetailCaseId) { showToast('No case selected', 'error'); return; }
    
    showLoader(true); 
    try { 
        await apiCall('addComment', 'POST', { caseId: currentDetailCaseId, comment: comment }); 
        if (commentInput) commentInput.value = ''; 
        const comments = await apiCall('getComments', 'GET', { caseId: currentDetailCaseId });
        document.getElementById('commentCount').innerText = comments.length;
        document.getElementById('commentsList').innerHTML = comments.map(com => `<div class="comment-item ${com.author === currentUser.username ? 'mine' : ''}"><div class="comment-meta"><strong>${escapeHtml(com.author)}</strong><span>${new Date(com.created_at).toLocaleString()}</span></div><div class="comment-text">${escapeHtml(com.comment)}</div></div>`).join('');
        showToast('Comment added', 'success'); 
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } 
}

// ==================== EDIT CASE ====================
async function openEditCaseModal(caseId) {
    if (!caseId) { showToast('Invalid case ID', 'error'); return; }
    
    closeModal('caseDetailModal'); 
    showLoader(true);
    try {
        await loadAssigneesList();
        const result = await apiCall('getCaseList', 'GET', { offset: 0, limit: 1, filters: JSON.stringify({ term: caseId }) });
        if (!result.cases || result.cases.length === 0) throw new Error('Case not found');
        const c = result.cases[0];
        document.getElementById('editCaseId').value = c.case_id;
        
        document.getElementById('kaldisEditSection').style.display = 'none';
        document.getElementById('cnetEditSection').style.display = 'none';
        document.getElementById('adminEditSection').style.display = 'none';
        
        if (currentUser.role === 'kaldis') {
            document.getElementById('kaldisEditSection').style.display = 'block';
            document.getElementById('editStatus').value = 'Fixed';
            document.getElementById('editRemarks').value = '';
        } else if (currentUser.role === 'cnet') {
            document.getElementById('cnetEditSection').style.display = 'block';
            document.getElementById('editStartDate').value = c.created_date;
            document.getElementById('editDueDate').value = c.due_date || '';
            document.getElementById('editPriority').value = c.priority;
            document.getElementById('editAssignee').innerHTML = '<option value="">-- Unassigned --</option>' + assigneesList.filter(a => a.status === 'active').map(a => `<option ${a.name === c.assigned_to ? 'selected' : ''}>${escapeHtml(a.name)}</option>`).join('');
            document.getElementById('editDescription').value = c.description;
            document.getElementById('editCnetRemarks').value = '';
        } else if (currentUser.role === 'admin') {
            document.getElementById('adminEditSection').style.display = 'block';
            document.getElementById('editAdminStatus').value = c.status;
            document.getElementById('editAdminPriority').value = c.priority;
            document.getElementById('editAdminDueDate').value = c.due_date || '';
            document.getElementById('editAdminAssignee').innerHTML = '<option value="">-- Unassigned --</option>' + assigneesList.filter(a => a.status === 'active').map(a => `<option ${a.name === c.assigned_to ? 'selected' : ''}>${escapeHtml(a.name)}</option>`).join('');
            document.getElementById('editAdminDescription').value = c.description;
            document.getElementById('editAdminRemarks').value = '';
        }
        openModal('editCaseModal');
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
}

function editCurrentCase() { if (currentDetailCaseId) openEditCaseModal(currentDetailCaseId); }

async function submitEditCase() {
    showLoader(true);
    try {
        const editCaseId = document.getElementById('editCaseId');
        if (!editCaseId || !editCaseId.value) throw new Error('Case ID not found');
        const data = { caseId: editCaseId.value };
        
        if (currentUser.role === 'kaldis') {
            const status = document.getElementById('editStatus')?.value || '';
            const remarks = document.getElementById('editRemarks')?.value.trim() || '';
            if (!remarks) { showToast('Remarks are required', 'warning'); showLoader(false); return; }
            if (!status) { showToast('Please select a status', 'warning'); showLoader(false); return; }
            data.status = status; data.remarks = remarks;
        } else if (currentUser.role === 'cnet') {
            const description = document.getElementById('editDescription')?.value.trim() || '';
            if (!description) { showToast('Description is required', 'warning'); showLoader(false); return; }
            data.status = 'In Progress';
            data.priority = document.getElementById('editPriority')?.value || 'Medium';
            data.assignedTo = document.getElementById('editAssignee')?.value || '';
            data.description = description;
            data.dueDate = document.getElementById('editDueDate')?.value || '';
            const remarks = document.getElementById('editCnetRemarks')?.value.trim() || '';
            if (remarks) data.remarks = remarks;
        } else if (currentUser.role === 'admin') {
            data.status = document.getElementById('editAdminStatus')?.value || '';
            data.priority = document.getElementById('editAdminPriority')?.value || 'Medium';
            data.assignedTo = document.getElementById('editAdminAssignee')?.value || '';
            data.description = document.getElementById('editAdminDescription')?.value || '';
            data.dueDate = document.getElementById('editAdminDueDate')?.value || '';
            const remarks = document.getElementById('editAdminRemarks')?.value.trim() || '';
            if (remarks) data.remarks = remarks;
        }
        
        await apiCall('updateCase', 'POST', data);
        showToast('Case updated successfully', 'success');
        closeModal('editCaseModal');
        loadCases(true);
        if (currentDetailCaseId) viewCaseDetail(currentDetailCaseId);
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
}

// ==================== NEW CASE ====================
async function initNewCaseForm() {
    await loadCaseTypesList(); 
    await loadAssigneesList();
    
    document.getElementById('newCaseType').innerHTML = caseTypesList.map(t => `<option value="${escapeHtml(t)}">${escapeHtml(t)}</option>`).join('');
    document.getElementById('newAssignee').innerHTML = '<option value="">-- Unassigned --</option>' + assigneesList.filter(a => a.status === 'active').map(a => `<option value="${escapeHtml(a.name)}">${escapeHtml(a.name)}</option>`).join('');
    document.getElementById('newStartDate').valueAsDate = new Date();
    
    document.getElementById('newCaseForm').onsubmit = async (e) => {
        e.preventDefault(); showLoader(true);
        try {
            let attachmentUrl = '';
            const fileInput = document.getElementById('newAttachment');
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const reader = new FileReader();
                const base64 = await new Promise((resolve, reject) => { reader.onload = ev => resolve(ev.target.result); reader.onerror = reject; reader.readAsDataURL(file); });
                const upload = await apiCall('uploadFileToDrive', 'POST', { base64: base64, fileName: file.name, mimeType: file.type });
                attachmentUrl = upload.url;
            }
            
            const data = {
                caseType: document.getElementById('newCaseType')?.value || '',
                priority: document.getElementById('newPriority')?.value || 'Medium',
                description: document.getElementById('newDescription')?.value || '',
                createdDate: document.getElementById('newStartDate')?.value || '',
                assignedTo: document.getElementById('newAssignee')?.value || '',
                status: document.getElementById('newStatus')?.value || 'Not Started',
                attachments: attachmentUrl,
                remarks: document.getElementById('newRemark')?.value.trim() || ''
            };
            
            if (!data.caseType) throw new Error('Case type is required');
            if (!data.description) throw new Error('Description is required');
            if (!data.createdDate) throw new Error('Start date is required');
            
            const result = await apiCall('createCase', 'POST', data);
            showToast(`Case ${result.caseId} created!`, 'success');
            document.getElementById('newCaseForm').reset();
            document.getElementById('newStartDate').valueAsDate = new Date();
            navigateTo('cases');
        } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
    };
}

// ==================== SLA ====================
async function loadSLAData() {
    showLoader(true);
    try {
        const filters = { priority: document.getElementById('slaPriority')?.value || 'All', status: document.getElementById('slaStatus')?.value || 'All', overdueOnly: document.getElementById('slaOverdueOnly')?.checked || false };
        const data = await apiCall('getSLAData', 'GET', { filters: JSON.stringify(filters) });
        document.getElementById('slaOverdueValue').innerText = data.overdueCount || 0;
        document.getElementById('slaCriticalValue').innerText = data.criticalCount || 0;
        document.getElementById('slaActiveValue').innerText = data.activeCases.length || 0;
        document.getElementById('slaCasesList').innerHTML = data.activeCases.length ? data.activeCases.map(c => {
            let daysText = c.daysLeft !== null ? (c.daysLeft < 0 ? `${Math.abs(c.daysLeft)}d overdue` : `${c.daysLeft}d left`) : 'No due date';
            let daysClass = c.daysLeft < 0 ? 'text-danger' : (c.daysLeft <= 2 ? 'text-warning' : '');
            const desc = c.description.length > 80 ? escapeHtml(c.description.substring(0, 80)) + '...' : escapeHtml(c.description);
            return `<div class="sla-item"><div class="sla-content"><div class="sla-meta"><strong>${escapeHtml(c.caseId)}</strong><span class="badge priority-${c.priority.toLowerCase()}">${escapeHtml(c.priority)}</span><span class="badge status-${c.status.toLowerCase().replace(/\s/g, '-')}">${escapeHtml(c.status)}</span></div><div class="sla-description">${desc}</div></div><div style="text-align:right;"><div class="${daysClass}">${daysText}</div><small>${c.dueDate || 'N/A'}</small><div><button class="btn-icon" onclick="viewCaseDetail('${c.caseId}')"><i class="material-icons-round">visibility</i></button></div></div></div>`;
        }).join('') : '<div style="text-align:center;padding:50px;">No cases match filters</div>';
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
}

async function sendSLAToTelegram() {
    const filters = { priority: document.getElementById('slaPriority')?.value || 'All', status: document.getElementById('slaStatus')?.value || 'All', overdueOnly: document.getElementById('slaOverdueOnly')?.checked || false };
    showLoader(true);
    try { await apiCall('sendFilteredSLAReport', 'POST', filters); showToast('SLA report sent to Telegram', 'success'); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
}

// ==================== ASSIGNEES ====================
async function loadAssigneesList() { try { assigneesList = await apiCall('getAssignees'); } catch(e){ assigneesList = []; } return assigneesList; }
async function loadAssignees() {
    showLoader(true);
    try {
        const list = await apiCall('getAssignees');
        document.getElementById('assigneesTableBody').innerHTML = list.length ? list.map(a => `</table><td style="font-weight:600;">${escapeHtml(a.name)}</td><td>${escapeHtml(a.telegramId || '-')}</td><td><span class="badge ${a.status === 'active' ? 'status-completed' : 'status-not-started'}">${escapeHtml(a.status)}</span></td><td class="admin-only"><button class="btn-icon" onclick='editAssignee("${escapeHtml(a.name)}")'><i class="material-icons-round">edit</i></button><button class="btn-icon" onclick='deleteAssignee("${escapeHtml(a.name)}")' style="color:var(--danger);"><i class="material-icons-round">delete</i></button></td></tr>`).join('') : '<tr><td colspan="4" style="text-align:center;padding:40px;">No assignees found</td></tr>';
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
}
function openAddAssigneeModal() { document.getElementById('assigneeModalTitle').innerText = 'Add Assignee'; document.getElementById('editAssigneeId').value = ''; document.getElementById('assigneeName').value = ''; document.getElementById('assigneeName').disabled = false; document.getElementById('assigneeTelegramId').value = ''; document.getElementById('assigneeStatus').value = 'active'; openModal('assigneeModal'); }
function editAssignee(name) { const a = assigneesList.find(x => x.name === name); if (!a) return; document.getElementById('assigneeModalTitle').innerText = 'Edit Assignee'; document.getElementById('editAssigneeId').value = a.name; document.getElementById('assigneeName').value = a.name; document.getElementById('assigneeName').disabled = true; document.getElementById('assigneeTelegramId').value = a.telegramId || ''; document.getElementById('assigneeStatus').value = a.status; openModal('assigneeModal'); }
async function saveAssignee() { showLoader(true); try { await apiCall('saveAssignee', 'POST', { name: document.getElementById('assigneeName').value, telegramId: document.getElementById('assigneeTelegramId').value, status: document.getElementById('assigneeStatus').value }); showToast('Assignee saved'); closeModal('assigneeModal'); loadAssignees(); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } }
async function deleteAssignee(name) { if (!confirm(`Delete "${name}"?`)) return; showLoader(true); try { await apiCall('deleteAssignee', 'POST', { name: name }); showToast('Assignee deleted'); loadAssignees(); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } }

// ==================== USERS ====================
async function loadUsers() {
    showLoader(true);
    try {
        usersList = await apiCall('getUsers');
        document.getElementById('usersTableBody').innerHTML = usersList.length ? usersList.map(u => `<tr><td style="font-weight:600;">${escapeHtml(u.username)}</td><td><span class="badge role-${escapeHtml(u.role)}">${escapeHtml(u.role)}</span></td><td><span class="badge ${u.status === 'active' ? 'status-completed' : 'status-not-started'}">${escapeHtml(u.status)}</span></td><td class="admin-only"><button class="btn-icon" onclick='editUser("${escapeHtml(u.username)}")'><i class="material-icons-round">edit</i></button><button class="btn-icon" onclick='deleteUser("${escapeHtml(u.username)}")' style="color:var(--danger);"><i class="material-icons-round">delete</i></button></td></tr>`).join('') : '<tr><td colspan="4" style="text-align:center;padding:40px;">No users found</td></tr>';
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); }
}
function openAddUserModal() { document.getElementById('userModalTitle').innerText = 'Add User'; document.getElementById('editUsername').value = ''; document.getElementById('userUsername').value = ''; document.getElementById('userUsername').disabled = false; document.getElementById('userPassword').value = ''; document.getElementById('userRole').value = 'kaldis'; document.getElementById('userStatus').value = 'active'; openModal('userModal'); }
function editUser(username) { const u = usersList.find(x => x.username === username); if (!u) return; document.getElementById('userModalTitle').innerText = 'Edit User'; document.getElementById('editUsername').value = u.username; document.getElementById('userUsername').value = u.username; document.getElementById('userUsername').disabled = true; document.getElementById('userPassword').value = ''; document.getElementById('userRole').value = u.role; document.getElementById('userStatus').value = u.status; openModal('userModal'); }
async function saveUser() { showLoader(true); try { await apiCall('saveUser', 'POST', { username: document.getElementById('userUsername').value, password: document.getElementById('userPassword').value, role: document.getElementById('userRole').value, status: document.getElementById('userStatus').value }); showToast('User saved'); closeModal('userModal'); loadUsers(); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } }
async function deleteUser(username) { if (!confirm(`Delete "${username}"?`)) return; showLoader(true); try { await apiCall('deleteUser', 'POST', { username: username }); showToast('User deleted'); loadUsers(); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } }

// ==================== CASE TYPES ====================
async function loadCaseTypesList() { 
    try { 
        caseTypesList = await apiCall('getCaseTypes');
        document.getElementById('caseTypesTableBody').innerHTML = caseTypesList.length ? caseTypesList.map(t => `<tr><td style="font-weight:600;">${escapeHtml(t)}</td><td>0</td><td class="admin-only"><button class="btn-icon" onclick='deleteCaseType("${escapeHtml(t)}")' style="color:var(--danger);"><i class="material-icons-round">delete</i></button></td></tr>`).join('') : '<tr><td colspan="3" style="text-align:center;padding:30px;">No case types</td></tr>';
    } catch(e){ caseTypesList = ['Support', 'Project', 'Maintenance']; } 
    return caseTypesList; 
}
async function addNewCaseType() { const name = document.getElementById('newCaseTypeInput').value.trim(); if (!name) { showToast('Enter a name', 'warning'); return; } showLoader(true); try { await apiCall('addCaseType', 'POST', { name: name }); document.getElementById('newCaseTypeInput').value = ''; await loadCaseTypesList(); showToast('Case type added', 'success'); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } }
async function deleteCaseType(name) { if (!confirm(`Delete "${name}"?`)) return; showLoader(true); try { await apiCall('deleteCaseType', 'POST', { name: name }); await loadCaseTypesList(); showToast('Case type deleted', 'success'); } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } }

// ==================== ENHANCED SETTINGS ====================
async function loadSettings() { 
    try { 
        const s = await apiCall('getSettings');
        document.getElementById('companyName').value = s.companyName || '';
        document.getElementById('companyEmail').value = s.companyEmail || '';
        document.getElementById('companyPhone').value = s.companyPhone || '';
        document.getElementById('companyAddress').value = s.companyAddress || '';
        document.getElementById('logoUrl').value = s.logoUrl || '';
        document.getElementById('faviconUrl').value = s.faviconUrl || '';
        document.getElementById('primaryColor').value = s.primaryColor || '#D97706';
        document.getElementById('secondaryColor').value = s.secondaryColor || '#B45309';
        document.getElementById('telegramBotToken').value = s.telegramBotToken || '';
        document.getElementById('telegramGroupChatId').value = s.telegramGroupChatId || '';
        document.getElementById('companyNameDisplay').innerText = s.companyName || 'Case Management';
        if (s.logoUrl) document.getElementById('logoCircle').innerHTML = `<img src="${s.logoUrl}" alt="Logo">`;
    } catch(e){ console.error(e); } 
}

async function saveGeneralSettings() { 
    showLoader(true); 
    try { 
        await apiCall('saveSettings', 'POST', { 
            companyName: document.getElementById('companyName')?.value || '', 
            companyEmail: document.getElementById('companyEmail')?.value || '',
            companyPhone: document.getElementById('companyPhone')?.value || '', 
            companyAddress: document.getElementById('companyAddress')?.value || ''
        }); 
        showToast('General settings saved', 'success'); 
        document.getElementById('companyNameDisplay').innerText = document.getElementById('companyName')?.value || 'Case Management';
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } 
}

async function saveAppearanceSettings() { 
    showLoader(true); 
    try { 
        await apiCall('saveSettings', 'POST', { 
            logoUrl: document.getElementById('logoUrl')?.value || '', 
            faviconUrl: document.getElementById('faviconUrl')?.value || '',
            primaryColor: document.getElementById('primaryColor')?.value || '#D97706',
            secondaryColor: document.getElementById('secondaryColor')?.value || '#B45309'
        }); 
        if (document.getElementById('logoUrl')?.value) document.getElementById('logoCircle').innerHTML = `<img src="${document.getElementById('logoUrl').value}" alt="Logo">`;
        showToast('Appearance settings saved', 'success'); 
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } 
}

async function saveTelegramSettings() { 
    showLoader(true); 
    try { 
        await apiCall('saveSettings', 'POST', { 
            telegramBotToken: document.getElementById('telegramBotToken')?.value || '', 
            telegramGroupChatId: document.getElementById('telegramGroupChatId')?.value || '' 
        }); 
        showToast('Telegram settings saved', 'success'); 
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } 
}

async function testTelegramConnection() { 
    showLoader(true); 
    try { 
        await apiCall('testTelegram', 'POST'); 
        showToast('Test message sent!', 'success'); 
    } catch (err) { showToast(err.message, 'error'); } finally { showLoader(false); } 
}

// ==================== INIT ====================
document.body.classList.add(`is-${currentUser.role}`);
if (localStorage.getItem('darkMode') === 'true') { document.body.classList.add('dark-mode'); document.getElementById('darkModeIcon').textContent = 'light_mode'; }
document.getElementById('userAvatar').innerText = currentUser.username.charAt(0).toUpperCase();
document.getElementById('selectAllCheckbox').addEventListener('change', function() { document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = this.checked); updateBulkSelection(); });

initNewCaseForm();
loadDashboard();
loadAssignees();
loadCaseTypesList();
if (currentUser.role === 'admin') { loadUsers(); loadSettings(); }
loadCases(true);

console.log('Case Management System v2.0 - Enhanced with Delete Case Feature');
</script>
</body>
</html>