<?php
session_start();
$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kaldis Voucher System</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Noto+Sans+Ethiopic:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    :root {
      --primary: #4B3621;
      --primary-light: #6F4E37;
      --secondary: #2C1E12;
      --accent: #D4A373;
      --success: #059669;
      --danger: #DC2626;
      --warning: #D97706;
      --info: #2563EB;
      --bg: #F3F4F6;
      --sidebar-bg: #FFFFFF;
      --card-bg: #FFFFFF;
      --text: #1F2937;
      --text-muted: #6B7280;
      --border: #E5E7EB;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    body.dark-theme {
      --bg: #121212;
      --sidebar-bg: #1E1E1E;
      --card-bg: #1E1E1E;
      --text: #E0E0E0;
      --text-muted: #9E9E9E;
      --border: #333333;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.6);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.7);
    }
    body.dark-theme input, body.dark-theme select, body.dark-theme textarea {
      background: #2D2D2D !important;
      color: #E0E0E0 !important;
      border-color: #444444 !important;
    }
    body.dark-theme th {
      background: #252525 !important;
      color: #B0B0B0 !important;
      border-color: #333333 !important;
    }
    body.dark-theme td {
      border-color: #333333 !important;
    }
    body.dark-theme tr:hover td {
      background: #252525 !important;
    }
    body.dark-theme .btn-secondary {
      background: #2D2D2D !important;
      color: #E0E0E0 !important;
      border-color: #444444 !important;
    }
    body.dark-theme .chart-box, body.dark-theme .stat-card, body.dark-theme .filter-bar, body.dark-theme .voucher-code-item, body.dark-theme .searchable-list, body.dark-theme .user-profile, body.dark-theme .lang-switch, body.dark-theme .pwd-switch, body.dark-theme .sidebar-footer {
      background: #1E1E1E !important;
      color: #E0E0E0 !important;
      border-color: #333333 !important;
    }
    body.dark-theme .pie-hole {
      background: #1E1E1E !important;
    }
    body.dark-theme .checkbox-grid {
      background: #252525 !important;
      border-color: #333333 !important;
    }

    /* Loading Spinner Overlay */
    .spinner-overlay {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(4px);
      z-index: 99999;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: opacity 0.25s ease;
    }
    .spinner-box {
      background: var(--card-bg);
      padding: 24px 36px;
      border-radius: 16px;
      box-shadow: var(--shadow-xl);
      border: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
    }
    .spinner-ring {
      width: 44px;
      height: 44px;
      border: 4px solid var(--border);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    .spinner-text {
      font-weight: 600;
      color: var(--text);
      font-size: 0.95rem;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    * { box-sizing: border-box; outline: none; }
    body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); font-size: 14px; overflow-x: hidden; margin: 0; transition: background 0.3s, color 0.3s; }
    body.lang-am { font-family: 'Noto Sans Ethiopic', 'Outfit', sans-serif; }
    
    .app-container { display: flex; height: 100vh; }
    .sidebar { width: 280px; background: var(--sidebar-bg); border-right: 1px solid var(--border); display: flex; flex-direction: column; transition: width 0.3s; z-index: 50; box-shadow: var(--shadow-md); flex-shrink: 0; }
    .sidebar.collapsed { width: 80px; }
    .sidebar.collapsed .nav-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .user-info, .sidebar.collapsed .lang-switch, .sidebar.collapsed .pwd-switch { display: none; }
    .sidebar.collapsed .nav-item { justify-content: center; padding: 12px 0; }
    .sidebar.collapsed .nav-item i { margin-right: 0; font-size: 24px; }
    .sidebar.collapsed .user-avatar { margin: 0 auto; }

    .logo-area { height: 70px; display: flex; align-items: center; padding: 0 20px; border-bottom: 1px solid var(--border); color: var(--primary); font-weight: 700; white-space: nowrap; overflow: hidden; background: linear-gradient(to right, #fff, #fafafa); }
    .logo-area img { width: 42px; height: 42px; margin-right: 12px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    
    .nav-menu { flex: 1; padding: 20px 10px; overflow-y: auto; }
    .nav-item { display: flex; align-items: center; padding: 12px 16px; color: var(--text-muted); cursor: pointer; border-radius: 12px; margin-bottom: 6px; transition: all 0.2s; white-space: nowrap; font-weight: 500; }
    .nav-item:hover { background: var(--bg); color: var(--primary); transform: translateX(4px); }
    .nav-item.active { background: var(--primary); color: white; box-shadow: var(--shadow-md); }
    .nav-item i { margin-right: 12px; font-size: 20px; }

    .sidebar-footer { padding: 15px; border-top: 1px solid var(--border); background: #fafafa; }
    .user-profile { display: flex; align-items: center; gap: 10px; padding: 10px; background: white; border-radius: 12px; margin-bottom: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
    .user-avatar { width: 36px; height: 36px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    
    .lang-switch, .pwd-switch { display: flex; align-items: center; justify-content: space-between; background: white; padding: 10px 12px; border-radius: 8px; cursor: pointer; margin-bottom: 8px; border: 1px solid var(--border); color: var(--text-muted); transition: 0.2s; }
    .lang-switch:hover, .pwd-switch:hover { border-color: var(--primary); color: var(--primary); }
    
    .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; position: relative; background: var(--bg); }
    .top-bar { height: 70px; background: var(--card-bg); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: var(--shadow-sm); z-index: 40; }
    .content-scroll { flex: 1; overflow-y: auto; padding: 15px; }
    .container { max-width: 100%; margin: 0 auto; width: 100%; }

    .hidden { display: none !important; }
    .card { background: var(--card-bg); padding: 1rem; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 1rem; }
    .card h2 { margin-top: 0; font-size: 1.1rem; color: var(--primary); display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 15px; font-weight: 600; }

    button { cursor: pointer; border: none; border-radius: 8px; padding: 8px 16px; font-weight: 600; transition: all 0.2s; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.85rem; }
    .btn-primary { background: var(--primary); color: white; } .btn-primary:hover { background: var(--secondary); }
    .btn-secondary { background: white; color: var(--text); border: 1px solid var(--border); } .btn-secondary:hover { background: var(--bg); }
    .btn-danger { background: var(--danger); color: white; } .btn-danger:hover { background: #b91c1c; }
    .btn-sm { padding: 4px 10px; font-size: 0.75rem; border-radius: 6px; }
    .btn-done { background: var(--info); color: white; }
    .btn-icon { background: transparent; padding: 6px; border-radius: 50%; color: var(--text-muted); } .btn-icon:hover { background: var(--bg); color: var(--primary); }
    
    .form-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
      gap: 12px; 
    }
    @media (max-width: 768px) {
      .form-grid { grid-template-columns: 1fr; }
    }
    .form-full { grid-column: span 2; }
    @media (max-width: 768px) {
      .form-full { grid-column: span 1; }
    }
    .form-group { margin-bottom: 0; position: relative; }
    label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    input, select, textarea { 
      width: 100%; 
      padding: 8px 12px; 
      border: 1px solid var(--border); 
      border-radius: 6px; 
      background: #fff; 
      color: var(--text); 
      box-sizing: border-box; 
      font-family: inherit; 
      font-size: 0.9rem; 
      transition: all 0.2s; 
    }
    input:focus, select:focus, textarea:focus { 
      outline: none; 
      border-color: var(--primary); 
      background: #fff; 
      box-shadow: 0 0 0 3px rgba(75, 54, 33, 0.1); 
    }
    
    .voucher-codes-container {
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 8px;
      background: #f9fafb;
      margin-bottom: 8px;
    }

    .voucher-code-item {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
      background: white;
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
    }

    .voucher-code-item:last-child {
      margin-bottom: 0;
    }

    .voucher-code-item input {
      flex: 1;
      margin-bottom: 0;
      background: white;
    }

    .voucher-code-item .btn-icon {
      padding: 4px;
      margin: 0;
    }

    .btn-add-voucher {
      width: 100%;
      margin-top: 6px;
      background: var(--primary-light);
      color: white;
      border: none;
      padding: 6px;
      font-size: 0.75rem;
    }

    .btn-add-voucher:hover {
      background: var(--primary);
    }

    .vouchers-badge {
      display: inline-block;
      background: var(--primary-light);
      color: white;
      border-radius: 16px;
      padding: 2px 8px;
      font-size: 0.65rem;
      margin-left: 6px;
    }

    .vouchers-list-preview {
      font-size: 0.65rem;
      color: var(--text-muted);
      margin-top: 4px;
      padding-left: 8px;
      border-left: 2px solid var(--accent);
    }
    
    .searchable-wrapper { position: relative; }
    .searchable-list { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border); border-top: none; max-height: 200px; overflow-y: auto; z-index: 20; border-radius: 0 0 8px 8px; box-shadow: var(--shadow-lg); }
    .searchable-item { padding: 8px 12px; cursor: pointer; font-size: 0.85rem; border-bottom: 1px solid #f3f4f6; } .searchable-item:hover { background: var(--bg); color: var(--primary); }

    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .metric-card { background: var(--card-bg); padding: 1rem; border-radius: 12px; border: 1px solid var(--border); position: relative; overflow: hidden; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between; }
    .metric-card::after { content:''; position: absolute; right: -10px; top: -10px; width: 80px; height: 80px; background: currentColor; opacity: 0.05; border-radius: 50%; }
    .metric-card.primary { color: var(--primary); border-left: 4px solid var(--primary); }
    .metric-card.warning { color: var(--warning); border-left: 4px solid var(--warning); } 
    .metric-card.success { color: var(--success); border-left: 4px solid var(--success); }
    .metric-card.info { color: var(--info); border-left: 4px solid var(--info); }
    .metric-card.danger { color: var(--danger); border-left: 4px solid var(--danger); }
    
    .metric-value { font-size: 1.5rem; font-weight: 700; line-height: 1; margin-bottom: 4px; }
    .metric-label { font-size: 0.7rem; text-transform: uppercase; font-weight: 600; opacity: 0.8; letter-spacing: 1px; }

    .charts-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
    .chart-box { background: white; padding: 1rem; border-radius: 12px; border: 1px solid var(--border); display: flex; flex-direction: column; min-height: 250px; }
    .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .chart-title { font-weight: 600; color: var(--text); font-size: 0.85rem; }
    
    .bar-chart { display: flex; flex-direction: column; gap: 8px; flex: 1; justify-content: center; }
    .bar-item { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; }
    .bar-label { width: 100px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; font-weight: 500; }
    .bar-track { flex: 1; background: var(--bg); height: 8px; border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width 0.5s ease; }
    .bar-val { width: 30px; text-align: right; font-weight: 600; color: var(--text-muted); }

    .pie-container { display: flex; align-items: center; justify-content: space-around; flex: 1; }
    .pie-chart-wrapper { width: 120px; height: 120px; border-radius: 50%; position: relative; display: flex; align-items: center; justify-content: center; }
    .pie-hole { width: 75px; height: 75px; background: white; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 2; box-shadow: var(--shadow-sm); }
    .pie-legend { display: flex; flex-direction: column; gap: 6px; font-size: 0.75rem; }
    .legend-item { display: flex; align-items: center; gap: 6px; }
    .legend-color { width: 10px; height: 10px; border-radius: 3px; }

    table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.85rem; }
    th { background: #FAF9F6; color: var(--text-muted); font-weight: 600; text-align: left; padding: 10px 12px; border-bottom: 2px solid var(--border); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:hover td { background: #fafafa; }
    
    .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .status-Pending { background: #FEF3C7; color: #D97706; }
    .status-Approved { background: #D1FAE5; color: #059669; }
    .status-Rejected { background: #FEE2E2; color: #DC2626; }
    .status-Done { background: #DBEAFE; color: #1D4ED8; }

    .modal-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100; opacity: 0; pointer-events: none; transition: opacity 0.2s; backdrop-filter: blur(2px); }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal { background: white; border-radius: 16px; width: 100%; max-width: 450px; padding: 20px; box-shadow: var(--shadow-xl); transform: scale(0.95); transition: transform 0.2s; }
    .modal-overlay.active .modal { transform: scale(1); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .modal-title { font-size: 1.1rem; font-weight: 700; color: var(--primary); }

    .tab-buttons { display: flex; gap: 8px; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
    .btn-tab { background: transparent; color: var(--text-muted); border: none; border-bottom: 2px solid transparent; border-radius: 0; padding: 6px 12px; font-size: 0.85rem; }
    .btn-tab.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 700; }

    .chip-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .chip { background: #f3f4f6; border: 1px solid var(--border); padding: 4px 8px; border-radius: 16px; font-size: 0.75rem; display: flex; align-items: center; gap: 6px; }
    .chip-remove { cursor: pointer; color: var(--danger); font-weight: bold; }

    #toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 200; display: flex; flex-direction: column; gap: 8px; }
    .toast { background: #333; color: white; padding: 10px 16px; border-radius: 8px; box-shadow: var(--shadow-lg); font-size: 0.85rem; display: flex; align-items: center; gap: 8px; animation: slideIn 0.3s forwards; }
    .toast.success { background: var(--success); }
    .toast.error { background: var(--danger); }
    @keyframes slideIn { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 15px; border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 12px; }
    .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; flex-shrink: 0; }
    .stat-icon.blue { background: #3B82F6; }
    .stat-icon.green { background: #10B981; }
    .stat-icon.purple { background: #8B5CF6; }
    .stat-icon.orange { background: #F59E0B; }
    .stat-icon.red { background: #EF4444; }
    .stat-info { flex: 1; }
    .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--text); line-height: 1.2; }
    .stat-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; }
    .stat-trend { font-size: 0.65rem; margin-top: 2px; display: flex; align-items: center; gap: 2px; }
    .stat-trend.up { color: #10B981; }
    .stat-trend.down { color: #EF4444; }

    .quick-stats { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .quick-stat { background: white; padding: 8px 14px; border-radius: 20px; border: 1px solid var(--border); font-size: 0.75rem; display: flex; align-items: center; gap: 6px; }
    .quick-stat .number { font-weight: 700; color: var(--primary); }

    .filter-bar { background: white; padding: 10px 15px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .filter-label { font-weight: 600; font-size: 0.8rem; color: var(--text-muted); }

    .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; max-height: 200px; overflow-y: auto; padding: 10px; background: #f9fafb; border-radius: 8px; border: 1px solid var(--border); }
    .checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 500; cursor: pointer; }
    .checkbox-item input { width: auto; }

    .dept-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; background: #E0E7FF; color: #3730A3; margin-left: 8px; }

    .split-chart-container { display: flex; gap: 15px; }
    .split-col { flex: 1; min-width: 0; }
    .split-col h4 { margin: 0 0 8px 0; font-size: 0.75rem; text-align: center; font-weight: 600; }

    .report-filter-bar { display: flex; flex-wrap: wrap; gap: 10px; background: #fafafa; padding: 12px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 15px; align-items: flex-end; }
    .report-filter-group { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 130px; }
    .report-filter-group label { font-size: 0.65rem; margin-bottom: 0; }
    .report-filter-group input, .report-filter-group select { padding: 4px 8px; font-size: 0.75rem; }

    .report-summary { display: flex; gap: 15px; margin-bottom: 15px; background: #f0fdf4; padding: 12px; border-radius: 8px; border: 1px solid #bbf7d0; }
    .summary-box { text-align: center; flex: 1; }
    .summary-box h4 { margin: 0; font-size: 0.7rem; color: #166534; text-transform: uppercase; }
    .summary-box p { margin: 4px 0 0 0; font-size: 1.2rem; font-weight: 700; color: #15803d; }

    .form-submit-container {
      display: flex;
      justify-content: center;
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid var(--border);
    }

    .form-submit-container button {
      min-width: 160px;
      padding: 10px 24px;
      font-size: 0.95rem;
    }

    /* Bulk Action Floating Bar */
    .bulk-action-bar {
      position: fixed;
      bottom: 25px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--primary);
      color: white;
      padding: 10px 20px;
      border-radius: 30px;
      box-shadow: var(--shadow-xl);
      display: flex;
      align-items: center;
      gap: 15px;
      z-index: 150;
      animation: slideUp 0.3s ease;
    }
    @keyframes slideUp { from { transform: translate(-50%, 100%); } to { transform: translate(-50%, 0); } }

    /* Smart Insights Container */
    .smart-insights-container {
      display: flex;
      gap: 12px;
      margin-bottom: 15px;
      flex-wrap: wrap;
    }
    .insight-card {
      flex: 1;
      min-width: 220px;
      background: white;
      padding: 10px 14px;
      border-radius: 10px;
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.8rem;
      box-shadow: var(--shadow-sm);
    }
    .insight-card.danger { border-left: 4px solid var(--danger); }
    .insight-card.success { border-left: 4px solid var(--success); }
    .insight-card.primary { border-left: 4px solid var(--primary); }
    .insight-card.info { border-left: 4px solid var(--info); }
    .insight-card i { font-size: 20px; }

    /* Global Spinner Overlay */
    .global-spinner-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(4px);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 99999;
      transition: opacity 0.2s ease;
    }
    .global-spinner-overlay.hidden {
      display: none !important;
    }
    .global-spinner-box {
      background: white;
      padding: 24px 36px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.25);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
    }
    .global-spinner-icon {
      font-size: 42px !important;
      color: var(--primary);
      animation: spin 0.9s linear infinite;
    }
    .global-spinner-text {
      font-weight: 600;
      font-size: 0.95rem;
      color: var(--text);
    }

    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .report-progress-bar-row {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.8rem;
      margin-bottom: 8px;
    }
    .report-progress-bar-label {
      width: 120px;
      text-overflow: ellipsis;
      overflow: hidden;
      white-space: nowrap;
      font-weight: 500;
    }
    .report-progress-bar-track {
      flex: 1;
      background: var(--bg);
      height: 10px;
      border-radius: 5px;
      overflow: hidden;
    }
    .report-progress-bar-fill {
      height: 100%;
      background: var(--primary);
      border-radius: 5px;
    }
    .report-progress-bar-val {
      width: 50px;
      text-align: right;
      font-weight: 600;
      color: var(--text-muted);
    }
    .report-analytics-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 20px;
    }
    @media (max-width: 768px) {
      .report-analytics-grid {
        grid-template-columns: 1fr;
      }
      .report-analytics-grid > div {
        grid-column: span 1 !important;
      }
    }
    .hidden-screen {
      display: none;
    }

    /* Branch Rankings Styling */
    .branch-rank-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 12px;
      margin-bottom: 15px;
    }
    .branch-rank-item {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 12px 14px;
      box-shadow: var(--shadow-sm);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .branch-rank-item:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
    .branch-rank-header {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .rank-badge {
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 32px;
    }
    .rank-gold { background: linear-gradient(135deg, #FFE082, #FFB300); color: #5D4037; font-weight: 800; box-shadow: 0 2px 4px rgba(255,179,0,0.3); }
    .rank-silver { background: linear-gradient(135deg, #ECEFF1, #B0BEC5); color: #263238; font-weight: 800; }
    .rank-bronze { background: linear-gradient(135deg, #FFCCBC, #D7CCC8); color: #4E342E; font-weight: 800; }
    .rank-regular { background: var(--bg); color: var(--text-muted); border: 1px solid var(--border); }
    .branch-name-text {
      flex: 1;
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--text);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .branch-total-count {
      font-size: 0.8rem;
      color: var(--primary);
      white-space: nowrap;
    }
    .branch-tag-row {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-top: 6px;
    }
    .branch-tag {
      font-size: 0.65rem;
      font-weight: 600;
      padding: 1px 6px;
      border-radius: 4px;
    }
    .branch-tag.edit { background: #E0F2FE; color: #0369A1; }
    .branch-tag.void { background: #FEE2E2; color: #B91C1C; }
    .branch-tag.onhand { background: #DCFCE7; color: #15803D; }

    /* Login Modal Tab Switcher */
    .login-tab-switcher {
      display: flex;
      background: var(--bg);
      border-radius: 8px;
      padding: 4px;
      margin-bottom: 16px;
      gap: 4px;
    }
    .login-tab-btn {
      flex: 1;
      border: none;
      background: transparent;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .login-tab-btn.active {
      background: var(--card-bg);
      color: var(--primary);
      box-shadow: var(--shadow-sm);
    }

    /* Requester Session Banner */
    .requester-banner {
      background: linear-gradient(135deg, #FFFBEB, #FEF3C7);
      border: 1px solid #FDE68A;
      border-radius: 8px;
      padding: 10px 14px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.85rem;
      color: #92400E;
    }

    /* DEDICATED FULLSCREEN LOGIN PAGE */
    .login-page-wrapper {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: radial-gradient(circle at top, #3D2615 0%, #20140A 60%, #120A05 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 20px;
      overflow-y: auto;
      box-sizing: border-box;
    }
    .login-page-wrapper.hidden {
      display: none !important;
    }
    .login-page-card {
      width: 100%;
      max-width: 480px;
      background: var(--card-bg);
      border-radius: 24px;
      padding: 36px 32px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(212, 163, 115, 0.2);
      animation: loginFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      color: var(--text);
    }
    @keyframes loginFadeIn {
      from { opacity: 0; transform: translateY(20px) scale(0.97); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .login-brand-logo {
      width: 72px;
      height: 72px;
      margin: 0 auto 14px auto;
      border-radius: 50%;
      border: 3px solid var(--primary);
      box-shadow: 0 4px 14px rgba(75, 54, 33, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      overflow: hidden;
    }
    .login-brand-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .login-brand-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--primary);
      text-align: center;
      margin: 0 0 4px 0;
    }
    body.dark-theme .login-brand-title {
      color: var(--accent);
    }
    .login-brand-subtitle {
      font-size: 0.85rem;
      color: var(--text-muted);
      text-align: center;
      margin: 0 0 22px 0;
    }
    .login-card-footer {
      border-top: 1px solid var(--border);
      margin-top: 22px;
      padding-top: 12px;
    }
    .app-container.hidden {
      display: none !important;
    }

    /* Detail View Table Styling */
    .detail-view-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
      background: var(--card-bg);
      border-radius: 8px;
    }
    .detail-view-table th {
      background: #F8FAFC !important;
      color: #64748B !important;
      font-weight: 700 !important;
      font-size: 0.72rem !important;
      letter-spacing: 0.6px !important;
      text-transform: uppercase !important;
      padding: 12px 10px !important;
      border-bottom: 2px solid var(--border) !important;
      text-align: left !important;
      white-space: nowrap !important;
    }
    .detail-view-table th.sortable {
      cursor: pointer !important;
      user-select: none !important;
      transition: background 0.15s ease, color 0.15s ease;
    }
    .detail-view-table th.sortable:hover {
      background: #E2E8F0 !important;
      color: var(--primary) !important;
    }
    body.dark-theme .detail-view-table th.sortable:hover {
      background: #334155 !important;
      color: #F8FAFC !important;
    }
    .detail-view-table th.sort-active {
      color: var(--primary) !important;
      background: rgba(75, 54, 33, 0.08) !important;
      border-bottom: 2px solid var(--primary) !important;
    }
    body.dark-theme .detail-view-table th.sort-active {
      color: var(--accent) !important;
      background: rgba(212, 163, 115, 0.15) !important;
      border-bottom: 2px solid var(--accent) !important;
    }
    .detail-sort-icon {
      font-size: 15px;
      vertical-align: middle;
      display: inline-flex;
      align-items: center;
      transition: transform 0.15s ease;
    }
    .detail-toolbar {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .detail-pagination {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      border-top: 1px solid var(--border);
      background: var(--card-bg);
      border-bottom-left-radius: 8px;
      border-bottom-right-radius: 8px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .detail-pagination .btn-page {
      padding: 6px 12px;
      font-size: 0.8rem;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--card-bg);
      color: var(--text);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.15s;
    }
    .detail-pagination .btn-page:hover:not(:disabled) {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }
    .detail-pagination .btn-page:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    body.dark-theme .detail-view-table th {
      background: #1E293B !important;
      color: #94A3B8 !important;
      border-bottom-color: #334155 !important;
    }
    .detail-view-table td {
      padding: 12px 10px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
      font-size: 0.82rem;
    }
    .detail-view-table tr:hover td {
      background: rgba(0,0,0,0.02);
    }
    body.dark-theme .detail-view-table tr:hover td {
      background: rgba(255,255,255,0.03);
    }
    .req-id-pill {
      font-family: 'SF Mono', Monaco, Consolas, monospace;
      font-weight: 700;
      font-size: 0.85rem;
      color: var(--primary);
    }
    body.dark-theme .req-id-pill {
      color: var(--accent);
    }
    .req-action-text {
      font-weight: 700;
      font-size: 0.85rem;
    }
    .req-muted-dash {
      color: var(--text-muted);
      font-weight: 400;
    }

    /* Responsive Mobile & Tablet Rules */
    .sidebar-backdrop {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(3px);
      z-index: 998;
      display: none;
    }
    .sidebar-backdrop.active {
      display: block;
    }

    /* Modal Responsive Styling */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 12px;
      box-sizing: border-box;
    }
    .modal {
      background: var(--card-bg);
      border-radius: 14px;
      padding: 24px;
      max-width: 540px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      box-sizing: border-box;
      box-shadow: var(--shadow-xl);
    }

    @media (max-width: 1200px) {
      .metrics-grid {
        grid-template-columns: repeat(2, 1fr) !important;
      }
    }

    @media (max-width: 992px) {
      .report-analytics-grid {
        grid-template-columns: 1fr !important;
      }
      .analytics-card {
        grid-column: span 1 !important;
      }
      .charts-container {
        grid-template-columns: 1fr !important;
      }
      .chart-box {
        grid-column: 1 / -1 !important;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        left: -290px;
        top: 0;
        bottom: 0;
        width: 280px;
        height: 100vh;
        z-index: 999;
        transition: left 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 0 25px rgba(0,0,0,0.4);
      }
      .sidebar.mobile-open {
        left: 0;
      }
      .top-bar {
        padding: 0 10px;
        height: 56px;
      }
      .content-scroll {
        padding: 10px 8px;
      }
      .metrics-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
      }
      .report-summary {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
      }
      .filter-bar, .report-filter-bar {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
      }
      .report-filter-group {
        width: 100% !important;
        min-width: 100% !important;
      }
      .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      .login-page-card {
        padding: 22px 16px;
        border-radius: 16px;
        max-width: 100%;
        width: 96%;
      }
      .login-tab-switcher {
        gap: 4px !important;
      }
      .login-tab-btn {
        padding: 9px 6px !important;
        font-size: 0.78rem !important;
      }
      .modal {
        padding: 18px 14px;
        border-radius: 12px;
      }
    }

    @media (max-width: 576px) {
      .metrics-grid {
        grid-template-columns: 1fr !important;
      }
      .login-brand-title {
        font-size: 1.25rem;
      }
      .login-brand-subtitle {
        font-size: 0.78rem;
      }
      .btn-primary, .btn-secondary {
        min-height: 42px;
      }
    }

    @media print {
      body * {
        visibility: hidden;
      }
      #view-reports, #view-reports * {
        visibility: visible;
      }
      #view-reports {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
      }
      .report-filter-bar, 
      .report-filter-bar *, 
      button, 
      .nav-menu, 
      .sidebar, 
      .top-bar, 
      #toast-container,
      .global-spinner-overlay {
        display: none !important;
      }
      .hidden-screen {
        display: block !important;
      }
      .report-analytics-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 15px !important;
        page-break-after: always;
      }
      .analytics-card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        background: #fff !important;
        color: #000 !important;
      }
      .report-progress-bar-track {
        background: #eee !important;
        border: 1px solid #ccc !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .report-progress-bar-fill {
        background: #4B3621 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      #reports-table-container {
        page-break-before: always;
        margin-top: 20px;
      }
      table {
        width: 100% !important;
        border-collapse: collapse !important;
      }
      th, td {
        border: 1px solid #333 !important;
        color: #000 !important;
        background: #fff !important;
        font-size: 10px !important;
        padding: 4px 6px !important;
      }
    }
  </style>
</head>
<body>

  <!-- GLOBAL LOADING SPINNER OVERLAY -->
  <div id="globalSpinner" class="global-spinner-overlay hidden">
    <div class="global-spinner-box">
      <i class="material-icons-round global-spinner-icon">sync</i>
      <div id="spinnerMessage" class="global-spinner-text">Processing...</div>
    </div>
  </div>

  <!-- DEDICATED FULL-SCREEN LOGIN PAGE -->
  <div id="loginPage" class="login-page-wrapper <?= $currentUser ? 'hidden' : '' ?>">
    <div class="login-page-card">
      <div class="login-card-header">
        <div class="login-brand-logo">
          <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTviTd8s9_J0pBESz-EgQHk_p3KYnsaJBrpzQ&s" alt="Kaldis Logo" onerror="this.src='https://via.placeholder.com/72?text=KC'">
        </div>
        <h1 class="login-brand-title">Kaldis Voucher System</h1>
        <p class="login-brand-subtitle">Branch Request & Department Approval Portal</p>
      </div>

      <!-- Segmented Tab Switcher (IT Admin & Department First) -->
      <div class="login-tab-switcher" style="margin-bottom: 20px;">
        <button type="button" class="login-tab-btn active" id="page-tab-login-dept" onclick="switchLoginPageTab('department')">
          <i class="material-icons-round">admin_panel_settings</i> IT Admin & Dept Login
        </button>
        <button type="button" class="login-tab-btn" id="page-tab-login-bm" onclick="switchLoginPageTab('branch_manager')">
          <i class="material-icons-round">storefront</i> Branch Manager / Requester
        </button>
      </div>

      <!-- Form 1: IT Admin & Department Manager Login (First / Default) -->
      <form id="pageDeptLoginForm" onsubmit="handleDeptLoginSubmit(event)">
        <div class="form-group" style="margin-bottom: 14px;">
          <label style="font-weight: 600; font-size: 0.85rem; display:block; margin-bottom: 6px;">
            <i class="material-icons-round" style="font-size:16px; vertical-align:middle; color:var(--primary);">category</i> Select Department / Role
          </label>
          <select id="pageLoginRole" required style="width: 100%; padding: 11px 14px; font-size: 0.95rem; border-radius: 10px; border: 1.5px solid var(--border); font-weight: 600; background: var(--bg-card); color: var(--text-primary);">
            <option value="Admin">IT Admin</option>
            <option value="Finance">Finance Manager</option>
            <option value="F&B">F&B Manager</option>
            <option value="IT">IT Department Trainer</option>
            <option value="Coordinator">Training Coordinator Team</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
          <label style="font-weight: 600; font-size: 0.85rem; display:block; margin-bottom: 6px;">
            <i class="material-icons-round" style="font-size:16px; vertical-align:middle; color:var(--primary);">lock</i> Password
          </label>
          <div style="position: relative;">
            <input type="password" id="pageDeptPassword" required placeholder="Enter password..." autocomplete="current-password" style="width: 100%; padding: 11px 40px 11px 14px; font-size: 0.95rem; border-radius: 10px; border: 1.5px solid var(--border);">
            <i class="material-icons-round" onclick="togglePasswordVisibility('pageDeptPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;">visibility</i>
          </div>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 0.95rem; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 12px rgba(75, 54, 33, 0.25);">
          <i class="material-icons-round" style="font-size: 18px;">lock_open</i> Sign In to Department / Admin
        </button>
      </form>

      <!-- Form 2: Branch Manager / Requester Login -->
      <form id="pageBmLoginForm" onsubmit="handleBmLoginSubmit(event)" class="hidden">
        <div class="form-group" style="margin-bottom: 14px;">
          <label style="font-weight: 600; font-size: 0.85rem; display:block; margin-bottom: 6px;">
            <i class="material-icons-round" style="font-size:16px; vertical-align:middle; color:var(--primary);">badge</i> Branch Manager (Search / Select / Type)
          </label>
          <div style="position: relative;">
            <input type="text" id="pageBmUsername" required placeholder="Type Name or Email (e.g. FatumaMsg@kaldisbunna.et)..." list="pageBmQuickList" autocomplete="username" style="width: 100%; padding: 11px 14px; font-size: 0.95rem; border-radius: 10px; border: 1.5px solid var(--border);">
            <datalist id="pageBmQuickList"></datalist>
          </div>
          <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">
            <i class="material-icons-round" style="font-size:12px; vertical-align:middle;">link</i> Uses credentials from company system (<code>kaldisbp_company_system</code>)
          </small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
          <label style="font-weight: 600; font-size: 0.85rem; display:block; margin-bottom: 6px;">
            <i class="material-icons-round" style="font-size:16px; vertical-align:middle; color:var(--primary);">lock</i> Password
          </label>
          <div style="position: relative;">
            <input type="password" id="pageBmPassword" required placeholder="Enter password..." autocomplete="current-password" style="width: 100%; padding: 11px 40px 11px 14px; font-size: 0.95rem; border-radius: 10px; border: 1.5px solid var(--border);">
            <i class="material-icons-round" onclick="togglePasswordVisibility('pageBmPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;">visibility</i>
          </div>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 0.95rem; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 12px rgba(75, 54, 33, 0.25);">
          <i class="material-icons-round" style="font-size: 18px;">login</i> Sign In as Requester
        </button>
      </form>

      <!-- Card Footer -->
      <div class="login-card-footer">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:14px; font-size:0.8rem; color:var(--text-muted);">
          <span><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">security</i> Authorized Personnel</span>
          <div style="display:flex; gap:12px;">
            <span onclick="toggleTheme()" style="cursor:pointer; display:flex; align-items:center; gap:4px;"><i class="material-icons-round" style="font-size:15px;">dark_mode</i> Theme</span>
            <span onclick="toggleLanguage()" style="cursor:pointer; display:flex; align-items:center; gap:4px;"><i class="material-icons-round" style="font-size:15px;">translate</i> <span id="login-lang-label">Amharic</span></span>
          </div>
        </div>
        <div style="text-align: center; margin-top: 10px; font-size: 0.72rem; color: var(--text-muted);">
          Kaldis Coffee IT Department &bull; Internal System &copy; <?= date('Y') ?>
        </div>
      </div>
    </div>
  </div>

  <!-- FORCE CHANGE DEFAULT PASSWORD MODAL (INITIAL SETUP) -->
  <div class="modal-overlay" id="forceChangePasswordModal" style="display:none; z-index:99999; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);">
    <div class="modal" style="max-width: 460px; width: 92%; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1.5px solid var(--border);">
      <div class="modal-header" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px; display:flex; justify-content:space-between; align-items:center;">
        <div class="modal-title" style="display:flex; align-items:center; gap:8px; font-size:1.15rem; color:var(--primary); font-weight:700;">
          <i class="material-icons-round" style="color:var(--warning);">security</i> Security Setup: Change Password
        </div>
        <button type="button" class="btn-icon" onclick="closeForcePasswordModal()" title="Skip for now"><i class="material-icons-round">close</i></button>
      </div>
      <div style="background: rgba(217, 119, 6, 0.1); border-left: 4px solid var(--warning); padding: 12px; border-radius: 6px; margin-bottom: 18px; font-size: 0.85rem; color: var(--text);">
        <strong>Action Required:</strong> You are currently using the default system password (<code>admin123</code>). For security, please set your personalized password.
      </div>
      <form onsubmit="handleForceChangePasswordSubmit(event)">
        <div class="form-group" style="margin-bottom: 14px;">
          <label style="font-weight: 600; font-size: 0.85rem; display:block; margin-bottom: 6px;">
            <i class="material-icons-round" style="font-size:16px; vertical-align:middle; color:var(--primary);">lock</i> New Password
          </label>
          <div style="position: relative;">
            <input type="password" id="forceNewPassword" required minlength="4" placeholder="Enter new password (min 4 chars)..." style="width: 100%; padding: 11px 40px 11px 14px; font-size: 0.95rem; border-radius: 10px; border: 1.5px solid var(--border);">
            <i class="material-icons-round" onclick="togglePasswordVisibility('forceNewPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;">visibility</i>
          </div>
        </div>
        <div class="form-group" style="margin-bottom: 20px;">
          <label style="font-weight: 600; font-size: 0.85rem; display:block; margin-bottom: 6px;">
            <i class="material-icons-round" style="font-size:16px; vertical-align:middle; color:var(--primary);">lock_clock</i> Confirm New Password
          </label>
          <div style="position: relative;">
            <input type="password" id="forceConfirmPassword" required minlength="4" placeholder="Re-type new password..." style="width: 100%; padding: 11px 40px 11px 14px; font-size: 0.95rem; border-radius: 10px; border: 1.5px solid var(--border);">
            <i class="material-icons-round" onclick="togglePasswordVisibility('forceConfirmPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 20px;">visibility</i>
          </div>
        </div>
        <div style="display:flex; gap:10px;">
          <button type="submit" class="btn-primary" style="flex:1; justify-content: center; padding: 12px; font-size: 0.95rem; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 12px rgba(75, 54, 33, 0.25);">
            <i class="material-icons-round" style="font-size: 18px;">save</i> Save New Password
          </button>
          <button type="button" class="btn-secondary" onclick="closeForcePasswordModal()" style="padding: 12px 18px; border-radius: 10px; font-weight: 600;">
            Later
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- SIDEBAR MOBILE BACKDROP -->
  <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeSidebarMobile()"></div>

  <div class="app-container <?= $currentUser ? '' : 'hidden' ?>" id="appContainer">
    
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="logo-area">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTviTd8s9_J0pBESz-EgQHk_p3KYnsaJBrpzQ&s" alt="Kaldis Logo" onerror="this.src='https://via.placeholder.com/40?text=KC'">
        <span class="logo-text" data-i18n="appTitle">Kaldis Voucher System</span>
      </div>

      <div class="nav-menu">
        <div class="nav-item active" onclick="switchView('submit')" id="nav-submit">
          <i class="material-icons-round">post_add</i>
          <span class="nav-text" data-i18n="navSubmit">Submit Request</span>
        </div>
        <div class="nav-item" onclick="switchView('dashboard')" id="nav-dashboard">
          <i class="material-icons-round">dashboard</i>
          <span class="nav-text" data-i18n="navDashboard">Dashboard</span>
        </div>
        <div class="nav-item" onclick="switchView('view')" id="nav-view">
          <i class="material-icons-round">list_alt</i>
          <span class="nav-text" data-i18n="navView">View Requests</span>
        </div>
        <div class="nav-item hidden" onclick="switchView('reports')" id="nav-reports">
          <i class="material-icons-round">analytics</i>
          <span class="nav-text" data-i18n="navReports">Reports</span>
        </div>
        <div class="nav-item hidden" onclick="switchView('logs')" id="nav-logs">
          <i class="material-icons-round">history_edu</i>
          <span class="nav-text" data-i18n="navLogs">System Logs</span>
        </div>
        <div class="nav-item hidden" onclick="switchView('settings')" id="nav-settings">
          <i class="material-icons-round">settings</i>
          <span class="nav-text" data-i18n="navSettings">Settings</span>
        </div>
      </div>

      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar" id="user-avatar-text">G</div>
          <div class="user-info">
            <div style="font-weight: 600; font-size: 0.85rem;" id="user-display-name">Guest User</div>
            <div style="font-size: 0.7rem; color: var(--text-muted);" id="user-display-role">Requester</div>
          </div>
        </div>

        <div class="pwd-switch hidden" id="pwdBtn" onclick="openChangePasswordModal()">
          <span style="font-size: 0.8rem;"><i class="material-icons-round" style="vertical-align: middle; font-size: 16px; margin-right: 4px;">lock</i> Password</span>
          <i class="material-icons-round" style="font-size: 14px;">chevron_right</i>
        </div>

        <div class="lang-switch" onclick="toggleLanguage()">
          <span style="font-size: 0.8rem;"><i class="material-icons-round" style="vertical-align: middle; font-size: 16px; margin-right: 4px;">translate</i> <span id="lang-label">Amharic</span></span>
          <i class="material-icons-round" style="font-size: 14px;">sync</i>
        </div>

        <button class="btn-secondary" style="width: 100%; justify-content: center; margin-top: 6px;" onclick="handleAuthClick()" id="auth-btn">
          <i class="material-icons-round" style="font-size: 16px;">login</i> <span id="auth-btn-text">Admin/Manager Login</span>
        </button>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
          <button class="btn-icon" onclick="toggleSidebar()"><i class="material-icons-round">menu</i></button>
          <h1 style="font-size: 1.1rem; margin: 0; font-weight: 600; color: var(--primary);" id="page-title">Submit Request</h1>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
          <button class="btn-sm btn-secondary" onclick="toggleAutoRefresh()" id="autoRefreshBtn" title="Toggle Live Auto-Refresh (30s)">
            <i class="material-icons-round" style="font-size:14px">sync</i> <span id="autoRefreshText">Live: OFF</span>
          </button>
          <button class="btn-icon" onclick="toggleTheme()" id="themeBtn" title="Toggle Dark/Light Mode">
            <i class="material-icons-round" id="themeIcon">dark_mode</i>
          </button>
          <div id="live-time" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;"></div>
        </div>
      </div>

      <div class="content-scroll">
        <div class="container">
          
          <!-- VIEW: SUBMIT REQUEST -->
          <div id="view-submit">
            <!-- Tabs to switch between Voucher Edit and On Hand Delete -->
            <div class="tab-buttons">
              <button class="btn-tab active" id="tab-voucher" onclick="switchRequestTab('voucher')">
                <i class="material-icons-round">edit_note</i> Voucher Edit/Void
              </button>
              <button class="btn-tab" id="tab-onhand" onclick="switchRequestTab('onhand')">
                <i class="material-icons-round">delete_sweep</i> On Hand Delete
              </button>
            </div>

            <!-- Card: Voucher Request Form -->
            <div class="card" id="card-voucher-request">
              <h2><i class="material-icons-round" style="color:var(--accent)">edit_note</i> <span data-i18n="formTitle">NEW EDIT/VOID VOUCHER REQUEST</span></h2>
              <div id="voucherRequesterBanner" class="requester-banner hidden"></div>
              <form id="requestForm" onsubmit="handleFormSubmit(event)">
                <div class="form-grid">
                  <div class="form-group">
                    <label data-i18n="lblBranch">Branch (Search / Type Any)</label>
                    <div class="searchable-wrapper">
                      <input type="text" id="branchInput" required placeholder="Type or select branch..." autocomplete="off" list="branchDatalist" onkeyup="filterBranches()" onfocus="filterBranches()" oninput="filterBranches()">
                      <input type="hidden" id="branchCode" name="branch">
                      <datalist id="branchDatalist"></datalist>
                      <ul id="branchList" class="searchable-list hidden"></ul>
                    </div>
                  </div>
                  <div class="form-group"><label data-i18n="lblUser">User Name</label><input type="text" name="username" id="voucherUsername" required placeholder="e.g. John Doe"></div>
                  
                  <div class="form-group">
                    <label data-i18n="lblVoucherCode">Voucher Codes <span id="voucherCountBadge" class="vouchers-badge">1</span></label>
                    <div id="voucherCodesContainer" class="voucher-codes-container">
                      <div class="voucher-code-item" data-index="0">
                        <input type="text" name="voucherCodes[]" class="voucher-code-input" placeholder="e.g. VCH-001" required>
                        <button type="button" class="btn-icon" onclick="removeVoucherCode(this)" style="color:var(--danger);" disabled>
                          <i class="material-icons-round">remove_circle</i>
                        </button>
                      </div>
                    </div>
                    <button type="button" class="btn-add-voucher" onclick="addVoucherCode()">
                      <i class="material-icons-round" style="font-size:14px;">add_circle</i> Add Another Voucher Code
                    </button>
                    <div id="vouchersPreview" class="vouchers-list-preview hidden"></div>
                    <input type="hidden" id="voucherCodesCount" value="1">
                  </div>
                  
                  <div class="form-group"><label data-i18n="lblVoucherType">Voucher Type</label><select name="voucherType" id="voucherTypeSelect" required><option value="">Select Voucher Type...</option></select></div>
                  <div class="form-group"><label data-i18n="lblAction">Action Required</label><select name="actionType" id="actionSelect" onchange="toggleEditType()" required><option value="Edit" data-i18n="optEdit">Edit</option><option value="Void" data-i18n="optVoid">Void</option></select></div>
                  <div class="form-group"><label data-i18n="lblTargetDept">Target Department</label><select name="targetDept" id="targetDeptSelect" required></select></div>
                  <div class="form-group" id="editTypeGroup"><label data-i18n="lblEditType">Edit Type</label><select name="editType" id="editTypeSelect"><option value="">Select...</option></select></div>
                  
                  <div class="form-group hidden" id="dateFieldsGroup">
                    <label data-i18n="lblDateChange">Date Change (Plain Text)</label>
                    <div style="display:flex; gap:8px;">
                      <input type="text" name="incorrectDate" placeholder="Incorrect (e.g. 01-01-2023)">
                      <input type="text" name="correctDate" placeholder="Correct (e.g. 02-01-2023)">
                    </div>
                  </div>
                  
                  <div class="form-group form-full"><label data-i18n="lblReason">Reason for Request</label><textarea name="reason" rows="2" required placeholder="Explain why..."></textarea></div>
                </div>
                
                <div class="form-submit-container">
                  <button type="submit" class="btn-primary" id="submitBtn" style="min-width: 140px;">
                    <span class="spinner" style="display:none"><i class="material-icons-round" style="animation: spin 1s linear infinite">refresh</i></span>
                    <span class="btn-text" data-i18n="btnSubmit">Submit Request(s)</span>
                  </button>
                </div>
              </form>
            </div>

            <!-- Card: On Hand Delete Request Form -->
            <div class="card hidden" id="card-onhand-request">
              <h2><i class="material-icons-round" style="color:var(--primary)">delete_sweep</i> <span>ON HAND DELETE REQUEST</span></h2>
              <div id="onhandRequesterBanner" class="requester-banner hidden"></div>
              <form id="onHandForm" onsubmit="handleOnHandSubmit(event)">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Branch (Search / Type Any) 🏢</label>
                    <div class="searchable-wrapper">
                      <input type="text" id="onHandBranchInput" required placeholder="Type or select branch..." autocomplete="off" list="onHandBranchDatalist" onkeyup="filterOnHandBranches()" onfocus="filterOnHandBranches()" oninput="filterOnHandBranches()">
                      <input type="hidden" id="onHandBranchCode" name="branch">
                      <datalist id="onHandBranchDatalist"></datalist>
                      <ul id="onHandBranchList" class="searchable-list hidden"></ul>
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <label>User Name 👤</label>
                    <input type="text" name="username" id="onHandUsername" required placeholder="e.g. John Doe">
                  </div>

                  <div class="form-group">
                    <label>Department Store User 👤 <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="storeUser" id="onHandStoreUser" required placeholder="e.g. Abebech (Kitchen Store)">
                  </div>

                  <div class="form-group">
                    <label>Target Department 🏦</label>
                    <select name="targetDept" id="onHandTargetDeptSelect" required></select>
                  </div>
                  
                  <div class="form-group">
                    <label>On Hand Delete Date 📅</label>
                    <input type="date" name="correctDate" id="onHandDeleteDate" required>
                  </div>
                  
                  <div class="form-group">
                    <label>Shift 🕒</label>
                    <select name="shift" id="onHandShift" required>
                      <option value="shft 1">shft 1</option>
                      <option value="shft 2">shft 2</option>
                    </select>
                  </div>

                  <div class="form-group form-full">
                    <label>Department Stores (Select one or more) 🏢 <span style="color:var(--danger)">*</span></label>
                    <div class="checkbox-grid" id="storeDepartmentsContainer">
                      <!-- Dynamically loaded from database -->
                    </div>
                  </div>

                  <div class="form-group form-full">
                    <label>Reason / Notes 📝</label>
                    <textarea name="reason" rows="2" placeholder="Optional notes..."></textarea>
                  </div>
                </div>
                
                <div class="form-submit-container">
                  <button type="submit" class="btn-primary" id="onHandSubmitBtn" style="min-width: 140px;">
                    <span class="spinner" style="display:none"><i class="material-icons-round" style="animation: spin 1s linear infinite">refresh</i></span>
                    <span class="btn-text">Submit Request</span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- VIEW: DASHBOARD -->
          <div id="view-dashboard" class="hidden">
            <!-- Branch Dashboard Overview Banner (For Branch Managers) -->
            <div id="branchDashboardBanner" class="requester-banner hidden"></div>

            <!-- Smart Insights Banner -->
            <div id="smart-insights-banner" class="smart-insights-container hidden"></div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
              <div class="filter-group" style="display:flex; align-items:center; gap:12px;">
                <div style="display:flex; align-items:center; gap:6px;">
                  <span style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Period:</span>
                  <select id="dashboardPeriod" onchange="loadDashboard()" style="width: auto; min-width: 140px;">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="all">All Time</option>
                  </select>
                </div>
                <button class="btn-sm btn-secondary" onclick="loadDashboard()"><i class="material-icons-round" style="font-size:14px">refresh</i> Refresh</button>
              </div>
              <div style="display: flex; gap: 6px;">
                <button class="btn-sm btn-secondary" onclick="exportDashboardReport()"><i class="material-icons-round" style="font-size:14px">download</i> Export Report</button>
              </div>
            </div>

            <div class="stats-row" id="enhanced-stats">
              <div class="stat-card">
                <div class="stat-icon blue"><i class="material-icons-round">assignment</i></div>
                <div class="stat-info">
                  <div class="stat-value" id="stat-total">0</div>
                  <div class="stat-label">Total Requests</div>
                  <div class="stat-trend up" id="trend-total">↑ 0% vs last period</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon orange"><i class="material-icons-round">schedule</i></div>
                <div class="stat-info">
                  <div class="stat-value" id="stat-pending">0</div>
                  <div class="stat-label">Pending Approval</div>
                  <div class="stat-trend" id="trend-pending">Awaiting action</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon green"><i class="material-icons-round">check_circle</i></div>
                <div class="stat-info">
                  <div class="stat-value" id="stat-approval-rate">0%</div>
                  <div class="stat-label">Approval Rate</div>
                  <div class="stat-trend up" id="trend-approval">+0% improvement</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon purple"><i class="material-icons-round">hourglass_empty</i></div>
                <div class="stat-info">
                  <div class="stat-value" id="stat-avg-time">0h</div>
                  <div class="stat-label">Avg Resolution Time</div>
                  <div class="stat-trend" id="trend-time">Processing</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon red"><i class="material-icons-round">warning</i></div>
                <div class="stat-info">
                  <div class="stat-value" id="stat-sla-overdue">0</div>
                  <div class="stat-label">Overdue SLA (>24h)</div>
                  <div class="stat-trend down" id="trend-sla">Requires Action</div>
                </div>
              </div>
            </div>

            <div class="quick-stats">
              <div class="quick-stat">
                <div class="number" id="quick-edits">0</div>
                <div class="label">Edit Requests</div>
              </div>
              <div class="quick-stat">
                <div class="number" id="quick-voids">0</div>
                <div class="label">Void Requests</div>
              </div>
              <div class="quick-stat">
                <div class="number" id="quick-onhand">0</div>
                <div class="label">On Hand Deletes</div>
              </div>
              <div class="quick-stat">
                <div class="number" id="quick-unique-users">0</div>
                <div class="label">Active Users</div>
              </div>
              <div class="quick-stat">
                <div class="number" id="quick-branches">0</div>
                <div class="label">Branches</div>
              </div>
            </div>

            <div class="filter-bar">
              <span class="filter-label"><i class="material-icons-round" style="vertical-align:middle; font-size:16px; margin-right:4px;">filter_list</i> Quick Filters:</span>
              <select id="filterType" onchange="toggleFilterInput()" style="width: auto;">
                <option value="none">No Filter</option>
                <option value="branch">By Branch</option>
                <option value="voucher">By Voucher Type</option>
                <option value="user">By User</option>
              </select>
              
              <select id="filterValueSelect" class="hidden" onchange="loadDashboard()" style="width: auto; flex:1; max-width: 180px;"></select>
              <button id="clearFilterBtn" class="btn-sm btn-danger hidden" onclick="clearFilters()">Clear</button>
            </div>

            <div class="metrics-grid">
              <div class="metric-card primary"><div class="metric-value" id="m-total">0</div><div class="metric-label">Total Requests</div></div>
              <div class="metric-card warning"><div class="metric-value" id="m-pending">0</div><div class="metric-label">Pending</div></div>
              <div class="metric-card success"><div class="metric-value" id="m-approved">0</div><div class="metric-label">Approved</div></div>
              <div class="metric-card info"><div class="metric-value" id="m-done">0</div><div class="metric-label">Done</div></div>
              <div class="metric-card danger"><div class="metric-value" id="m-void">0</div><div class="metric-label">Total Voids</div></div>
              <div class="metric-card" style="color:var(--primary-light); border-left: 4px solid var(--primary-light);"><div class="metric-value" id="m-edit">0</div><div class="metric-label">Total Edits</div></div>
              <div class="metric-card" style="color:var(--success); border-left: 4px solid var(--success);"><div class="metric-value" id="m-onhand">0</div><div class="metric-label">On Hand Deletes</div></div>
            </div>

            <div class="charts-container">
              <div class="chart-box">
                <div class="chart-header"><div class="chart-title">Status Overview</div></div>
                <div class="pie-container">
                  <div class="pie-chart-wrapper" id="pieStatus">
                    <div class="pie-hole">
                      <span style="font-size:1.5rem; font-weight:700; color:var(--primary);" id="pieTotal">0</span>
                      <span style="font-size:0.65rem; color:gray;">Total</span>
                    </div>
                  </div>
                  <div class="pie-legend" id="pieLegend"></div>
                </div>
              </div>

              <div class="chart-box">
                <div class="chart-header"><div class="chart-title">Action Distribution</div></div>
                <div class="pie-container">
                  <div class="pie-chart-wrapper" id="pieAction">
                    <div class="pie-hole">
                      <span style="font-size:1rem; font-weight:700; color:var(--text);">Ratio</span>
                    </div>
                  </div>
                  <div class="pie-legend" id="pieActionLegend"></div>
                </div>
              </div>

              <div class="chart-box">
                <div class="chart-header"><div class="chart-title">Top Requester Branches</div></div>
                <div id="chart-branches" class="bar-chart"></div>
              </div>

              <div class="chart-box">
                <div class="chart-header"><div class="chart-title">Top Requested Vouchers</div></div>
                <div id="chart-vouchers" class="bar-chart"></div>
              </div>

              <div class="chart-box" style="grid-column: 1 / -1;">
                <div class="chart-header"><div class="chart-title">Hourly Activity Peak Times (00:00 - 23:00)</div></div>
                <canvas id="hourlyPeakChart" style="max-height: 180px;"></canvas>
              </div>

              <div class="chart-box" style="grid-column: 1 / -1;">
                <div class="chart-header"><div class="chart-title">Top Edit Types</div></div>
                <div id="chart-editTypes" class="bar-chart" style="flex-direction: row; flex-wrap: wrap; gap: 15px; align-items: center;"></div>
              </div>

              <div class="chart-box" style="grid-column: 1 / -1;">
                <div class="chart-header"><div class="chart-title">Top Users by Action (Edit vs Void)</div></div>
                <div class="split-chart-container">
                  <div class="split-col">
                    <h4 style="color:var(--info)">Top Editors</h4>
                    <div id="chart-editUsers" class="bar-chart"></div>
                  </div>
                  <div class="split-col">
                    <h4 style="color:var(--danger)">Top Voiders</h4>
                    <div id="chart-voidUsers" class="bar-chart"></div>
                  </div>
                </div>
              </div>

              <div class="chart-box" style="grid-column: 1 / -1;">
                <div class="chart-header"><div class="chart-title">Department Performance</div></div>
                <div id="chart-department" class="bar-chart"></div>
              </div>
            </div>
          </div>

          <!-- VIEW: TABLE (VIEW REQUESTS) -->
          <div id="view-view" class="hidden">
            <div class="card">
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:12px;">
                <h2 style="margin:0;"><i class="material-icons-round" style="color:var(--primary)">list_alt</i> <span data-i18n="navView">View Requests</span></h2>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                  <span id="viewRequestsCountBadge" class="dept-badge" style="background:var(--primary); color:#fff; font-size:0.8rem;">0 Requests</span>
                  <button class="btn-secondary btn-sm" onclick="exportFilteredViewRequestsToCSV()"><i class="material-icons-round" style="font-size:14px">download</i> Export CSV</button>
                  <button class="btn-secondary btn-sm" onclick="loadDashboard()"><i class="material-icons-round" style="font-size:14px">refresh</i> Refresh</button>
                </div>
              </div>

              <!-- VIEW REQUESTS MULTI-CRITERIA FILTER BAR -->
              <div class="report-filter-bar" style="margin-bottom:18px;">
                <div class="report-filter-group" style="flex: 2; min-width: 170px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">search</i> Quick Search</label>
                  <input type="text" id="viewSearchQuery" placeholder="Search ID, User, Store, Voucher..." oninput="filterTable()">
                </div>
                <div class="report-filter-group" style="min-width: 150px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">storefront</i> Branch</label>
                  <select id="viewBranchFilter" onchange="filterTable()">
                    <option value="all">All Branches</option>
                  </select>
                </div>
                <div class="report-filter-group" style="min-width: 130px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">person</i> User Name</label>
                  <input type="text" id="viewUserFilter" placeholder="Filter user..." list="viewUsersList" oninput="filterTable()">
                  <datalist id="viewUsersList"></datalist>
                </div>
                <div class="report-filter-group" style="min-width: 125px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">calendar_today</i> Start Date</label>
                  <input type="date" id="viewStartDate" onchange="filterTable()">
                </div>
                <div class="report-filter-group" style="min-width: 125px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">event</i> End Date</label>
                  <input type="date" id="viewEndDate" onchange="filterTable()">
                </div>
                <div class="report-filter-group" style="min-width: 120px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">tune</i> Action Type</label>
                  <select id="viewActionFilter" onchange="filterTable()">
                    <option value="all">All Actions</option>
                    <option value="Edit">Edit</option>
                    <option value="Void">Void</option>
                    <option value="On Hand Delete">On Hand Delete</option>
                  </select>
                </div>
                <div class="report-filter-group" style="min-width: 115px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">flag</i> Status</label>
                  <select id="viewStatusFilter" onchange="filterTable()">
                    <option value="all">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Done">Done</option>
                  </select>
                </div>
                <div class="report-filter-group" style="min-width: 115px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">business</i> Dept</label>
                  <select id="viewDeptFilter" onchange="filterTable()">
                    <option value="all">All Depts</option>
                    <option value="Finance">Finance</option>
                    <option value="F&B">F&B</option>
                  </select>
                </div>
                <div style="display:flex; align-items:flex-end;">
                  <button class="btn-secondary" onclick="clearViewFilters()" title="Reset All Filters"><i class="material-icons-round">clear_all</i> Reset</button>
                </div>
              </div>

              <div class="table-responsive" id="table-container"></div>
            </div>
          </div>

          <!-- VIEW: LOGS -->
          <div id="view-logs" class="hidden">
            <div class="card">
              <h2><i class="material-icons-round" style="color:var(--secondary)">history_edu</i> <span data-i18n="navLogs">System Activity Logs</span></h2>
              <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                <input type="text" id="logSearch" placeholder="Search by Actor or Request ID..." onkeyup="filterLogsTable()" style="max-width:250px;">
                <select id="logActionFilter" onchange="filterLogsTable()" style="max-width:180px;">
                  <option value="all">All Actions</option>
                  <option value="Submitted">Request Submitted</option>
                  <option value="Approved">Approved</option>
                  <option value="Rejected">Rejected</option>
                  <option value="Done">Done</option>
                  <option value="Deleted">Deleted</option>
                  <option value="Password">Password Reset</option>
                </select>
              </div>
              <div class="table-responsive" id="logs-container"></div>
            </div>
          </div>

          <!-- VIEW: REPORTS -->
          <div id="view-reports" class="hidden">
            <div class="card">
              <h2><i class="material-icons-round" style="color:var(--secondary)">analytics</i> <span data-i18n="navReports">Admin Reports</span></h2>
              
              <div class="report-filter-bar">
                <div class="report-filter-group" style="min-width: 170px;">
                  <label><i class="material-icons-round" style="font-size:14px; vertical-align:middle;">view_list</i> Report Criteria</label>
                  <select id="reportViewMode" onchange="applyReportFilters()">
                    <option value="both">All (Summary + Detail View)</option>
                    <option value="detail">Detail View Table (Full Records)</option>
                    <option value="summary">Summary & Rankings Only</option>
                  </select>
                </div>
                <div class="report-filter-group">
                  <label>Date Range (Start)</label>
                  <input type="date" id="reportStartDate" onchange="applyReportFilters()">
                </div>
                <div class="report-filter-group">
                  <label>Date Range (End)</label>
                  <input type="date" id="reportEndDate" onchange="applyReportFilters()">
                </div>
                <div class="report-filter-group">
                  <label>Branch</label>
                  <select id="reportBranchFilter" onchange="applyReportFilters()">
                    <option value="all">All Branches</option>
                  </select>
                </div>
                <div class="report-filter-group">
                  <label>User Name</label>
                  <input type="text" id="reportUserFilter" placeholder="Filter user..." list="reportUsersList" oninput="applyReportFilters()">
                  <datalist id="reportUsersList"></datalist>
                </div>
                <div class="report-filter-group">
                  <label>Department / Store</label>
                  <select id="reportDeptFilter" onchange="applyReportFilters()">
                    <option value="all">All Departments</option>
                  </select>
                </div>
                <div class="report-filter-group">
                  <label>Action Type</label>
                  <select id="reportActionFilter" onchange="applyReportFilters()">
                    <option value="all">All Actions</option>
                    <option value="Edit">Edit</option>
                    <option value="Void">Void</option>
                    <option value="On Hand Delete">On Hand Delete</option>
                  </select>
                </div>
                <div class="report-filter-group">
                  <label>Status</label>
                  <select id="reportStatusFilter" onchange="applyReportFilters()">
                    <option value="all">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Done">Done</option>
                  </select>
                </div>
                <div style="display:flex; gap:8px;">
                  <button class="btn-secondary" onclick="clearReportFilters()" title="Reset Filters"><i class="material-icons-round">clear_all</i></button>
                  <button class="btn-secondary" onclick="printReport()" title="Print Report"><i class="material-icons-round">print</i> Print</button>
                  <button class="btn-secondary" onclick="downloadReportPDF()" title="Download PDF"><i class="material-icons-round">picture_as_pdf</i> PDF</button>
                </div>
              </div>

              <div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
                <button class="btn-primary" onclick="sendTelegramReport('Weekly')"><i class="material-icons-round">send</i> Send Weekly Telegram</button>
                <button class="btn-primary" onclick="sendTelegramReport('Monthly')"><i class="material-icons-round">send</i> Send Monthly Telegram</button>
                <button class="btn-secondary" onclick="exportFilteredToCSV()"><i class="material-icons-round">download</i> Export CSV</button>
              </div>

              <!-- PRINT HEADER -->
              <div class="print-header hidden-screen">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:3px double #4B3621; padding-bottom:10px; margin-bottom:20px;">
                  <div>
                    <h1 style="margin:0; font-size:22px; font-weight:700; color:#4B3621;">KALDIS COFFEE</h1>
                    <h2 style="margin:5px 0 0 0; font-size:13px; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:0.5px;">Voucher & Operations Report</h2>
                  </div>
                  <div style="text-align:right; font-size:11px; color:#555;">
                    <div><strong>Date Generated:</strong> <span id="printReportDateVal"></span></div>
                    <div><strong>Active Scope Filters:</strong> <span id="printReportFiltersVal">All Data</span></div>
                  </div>
                </div>
              </div>

              <div class="report-summary">
                <div class="summary-box"><h4>Total Requests</h4><p id="r-total">0</p></div>
                <div class="summary-box"><h4>Avg. Resolution</h4><p id="r-time" style="font-size: 1.1rem; line-height: 2.2rem;">0h</p></div>
                <div class="summary-box"><h4>Approval Rate</h4><p id="r-rate">0%</p></div>
              </div>

              <!-- GROUPED ANALYTICS SECTION -->
              <div class="report-analytics-grid" id="reports-analytics-section">
                <div class="card analytics-card">
                  <h3 style="margin-top:0; font-size:0.9rem; border-bottom:1px solid var(--border); padding-bottom:8px; color:var(--primary-light);"><i class="material-icons-round" style="vertical-align:middle; font-size:16px; margin-right:4px;">donut_large</i> Status Distribution</h3>
                  <div id="report-status-breakdown" style="padding: 10px 0;"></div>
                </div>
                <div class="card analytics-card" style="grid-column: span 2;">
                  <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:10px;">
                    <h3 style="margin:0; font-size:0.95rem; color:var(--primary); display:flex; align-items:center; gap:6px;">
                      <i class="material-icons-round" style="color:#D97706; font-size:20px;">emoji_events</i> 
                      <span>Branch Request Rankings (Ranked by Request Count — Active Branches Only)</span>
                    </h3>
                    <span id="rankedBranchesCountBadge" class="dept-badge" style="font-size:0.75rem; background:#FEF3C7; color:#92400E;">0 Active Branches</span>
                  </div>
                  <div id="report-branch-breakdown" style="padding: 5px 0;"></div>
                </div>
                <div class="card analytics-card" style="grid-column: span 2;">
                  <h3 style="margin-top:0; font-size:0.9rem; border-bottom:1px solid var(--border); padding-bottom:8px; color:var(--primary-light);"><i class="material-icons-round" style="vertical-align:middle; font-size:16px; margin-right:4px;">business</i> Departmental / Action Metrics</h3>
                  <div id="report-dept-breakdown" style="padding: 10px 0;"></div>
                </div>
              </div>

              <div class="table-responsive" id="reports-table-container"></div>
            </div>
          </div>

          <!-- VIEW: SETTINGS -->
          <div id="view-settings" class="hidden">
            <div class="card">
              <h2>
                <i class="material-icons-round" style="color:var(--secondary)">settings</i> 
                <span data-i18n="navSettings">System Settings</span>
                <a href="update_schema.php" target="_blank" class="btn-sm btn-secondary" style="margin-left:auto; text-decoration:none;">
                  <i class="material-icons-round" style="font-size:14px;">published_with_changes</i> Update Database Schema
                </a>
              </h2>
              
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                  <h3>Voucher Types</h3>
                  <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <input type="text" id="newVoucherType" placeholder="New Voucher Type...">
                    <button class="btn-primary" onclick="addSetting('VoucherType')"><i class="material-icons-round">add</i></button>
                  </div>
                  <div class="chip-list" id="voucherTypesChips"></div>
                </div>

                <div>
                  <h3>Edit Types</h3>
                  <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <input type="text" id="newEditType" placeholder="New Edit Type...">
                    <button class="btn-primary" onclick="addSetting('EditType')"><i class="material-icons-round">add</i></button>
                  </div>
                  <div class="chip-list" id="editTypesChips"></div>
                </div>

                <div style="grid-column: 1 / -1;">
                  <h3>Store Departments</h3>
                  <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <input type="text" id="newStoreDepartment" placeholder="New Store Department...">
                    <button class="btn-primary" onclick="addSetting('StoreDepartment')"><i class="material-icons-round">add</i></button>
                  </div>
                  <div class="chip-list" id="storeDepartmentsChips"></div>
                </div>

                <div style="grid-column: 1 / -1;">
                  <h3>Department Telegram Mappings</h3>
                  <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; margin-bottom: 10px;">
                    <input type="text" id="newDeptName" placeholder="Department Name...">
                    <input type="text" id="newDeptChatId" placeholder="Telegram Chat ID (e.g. -100...)">
                    <button class="btn-primary" onclick="addDepartment()"><i class="material-icons-round">add</i></button>
                  </div>
                  <div id="departmentsChips"></div>
                </div>

                <div style="grid-column: 1 / -1;">
                  <h3>Branch Directory</h3>
                  <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                    <input type="text" id="newBranchName" placeholder="Branch Name (e.g. Bole Medhanialem)" style="flex: 1; padding: 10px 14px; font-size: 0.9rem; border-radius: 8px; border: 1.5px solid var(--border);">
                    <button class="btn-primary" onclick="addBranch()"><i class="material-icons-round">add</i> Add Branch</button>
                  </div>
                  <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table id="branchesTable">
                      <thead>
                        <tr><th>Branch Name</th><th style="width: 70px;">Action</th></tr>
                      </thead>
                      <tbody id="branchesTableBody"></tbody>
                    </table>
                  </div>
                </div>

                <div style="grid-column: 1 / -1;">
                  <h3>Admin & Department Users</h3>
                  <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 8px; margin-bottom: 10px;">
                    <select id="newAdminRole">
                      <option value="Finance">Finance Manager</option>
                      <option value="F&B">F&B Manager</option>
                      <option value="Admin">IT Admin</option>
                    </select>
                    <input type="text" id="newAdminName" placeholder="Full Name">
                    <input type="password" id="newAdminPassword" placeholder="Password">
                    <input type="text" id="newAdminChatId" placeholder="Telegram Chat ID">
                    <button class="btn-primary" onclick="saveAdminUser()"><i class="material-icons-round">add</i></button>
                  </div>
                  <div class="table-responsive">
                    <table id="adminUsersTable">
                      <thead>
                        <tr><th>Role</th><th>Name</th><th>Chat ID</th><th style="width: 50px;">Action</th></tr>
                      </thead>
                      <tbody id="adminUsersTableBody"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- BULK ACTION BAR -->
  <div class="bulk-action-bar hidden" id="bulkActionBar">
    <div style="display: flex; align-items: center; gap: 8px;">
      <i class="material-icons-round" style="color: #60A5FA;">check_box</i>
      <span id="bulkSelectedCount" style="font-weight: 600; font-size: 0.9rem;">0 selected</span>
    </div>
    <div style="display: flex; gap: 8px;">
      <button class="btn-sm btn-primary" onclick="handleBulkAction('Approved')">Approve Selected</button>
      <button class="btn-sm btn-danger" onclick="handleBulkAction('Rejected')">Reject Selected</button>
      <button class="btn-sm btn-done" onclick="handleBulkMarkDone()">Mark Done Selected</button>
      <button class="btn-sm btn-secondary" onclick="clearBulkSelections()">Clear</button>
    </div>
  </div>



  <!-- CHANGE PASSWORD MODAL -->
  <div class="modal-overlay" id="changePasswordModal">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title">Change Password</div>
        <button class="btn-icon" onclick="closeModal('changePasswordModal')"><i class="material-icons-round">close</i></button>
      </div>
      <form onsubmit="handleChangePasswordSubmit(event)">
        <div style="margin-bottom: 12px;">
          <label>Current Password</label>
          <input type="password" id="currentPassword" required placeholder="Current password...">
        </div>
        <div style="margin-bottom: 12px;">
          <label>New Password</label>
          <input type="password" id="newPassword" required placeholder="New password...">
        </div>
        <div style="margin-bottom: 20px;">
          <label>Confirm New Password</label>
          <input type="password" id="confirmNewPassword" required placeholder="Confirm new password...">
        </div>
        <button type="submit" class="btn-primary" style="width: 100%;">Update Password</button>
      </form>
    </div>
  </div>
  <!-- EDIT REQUEST MODAL (IT ADMIN) -->
  <div class="modal-overlay" id="editRequestModal">
    <div class="modal" style="max-width: 650px;">
      <div class="modal-header">
        <div class="modal-title">Edit Request Details (IT Admin)</div>
        <button class="btn-icon" onclick="closeModal('editRequestModal')"><i class="material-icons-round">close</i></button>
      </div>
      <form onsubmit="handleEditRequestSubmit(event)">
        <input type="hidden" id="editReqId">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label>Branch</label>
            <input type="text" id="editReqBranch" required list="editReqBranchList" placeholder="Select or type branch..." autocomplete="off" style="width: 100%; padding: 8px 12px; font-size: 0.9rem; border-radius: 6px; border: 1.5px solid var(--border); font-weight: 500;">
            <datalist id="editReqBranchList"></datalist>
          </div>
          <div><label>User Name</label><input type="text" id="editReqUsername" required></div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div><label>Action Type</label>
            <select id="editReqActionType" onchange="toggleEditModalActionFields()">
              <option value="Edit">Edit</option>
              <option value="Void">Void</option>
              <option value="On Hand Delete">On Hand Delete</option>
            </select>
          </div>
          <div><label>Target Department</label><select id="editReqTargetDept"></select></div>
        </div>
        <div id="editReqEditTypeGroup" style="margin-bottom:12px;" class="hidden">
          <label>Edit Type</label>
          <select id="editReqEditType"></select>
        </div>
        <div id="editVoucherFields" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div><label>Voucher Code</label><input type="text" id="editReqVoucherCode"></div>
          <div><label>Voucher Type</label><select id="editReqVoucherType"></select></div>
        </div>
        <div id="editOnHandFields" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;" class="hidden">
          <div><label>Shift</label><input type="text" id="editReqShift"></div>
          <div><label>Date</label><input type="text" id="editReqCorrectDate"></div>
          <div><label>Store User</label><input type="text" id="editReqStoreUser" placeholder="e.g. Abebech"></div>
        </div>
        <div style="margin-bottom:12px;"><label>Reason</label><textarea id="editReqReason" rows="2"></textarea></div>
        <div style="margin-bottom:20px;"><label>Status</label>
          <select id="editReqStatus">
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Done">Done</option>
          </select>
        </div>
        <button type="submit" class="btn-primary" style="width: 100%;">Save Changes</button>
      </form>
    </div>
  </div>

  <div id="toast-container"></div>

  <script>
    const API_URL = 'api.php';
    let currentUser = <?php echo json_encode($currentUser); ?>;
    let currentLang = 'en';
    let autoRefreshInterval = null;
    let hourlyChartInstance = null;
    let systemSettings = {
      voucherTypes: [],
      editTypes: [],
      departments: [{name:"Finance", id:"-1003609053132"}, {name:"F&B", id:"-1003821366530"}],
      branches: [],
      storeDepartments: []
    };
    allRequests = [];
    let allLogs = [];
    let adminUsers = [];
    let dashboardMetrics = {};
    let userFrequencyData = [];
    let selectedRequestIds = new Set();

    function cleanBranchName(name) {
      if (!name) return '';
      return String(name).replace(/^[A-Za-z0-9]+\s*-\s*/, '').trim();
    }

    const translations = {
      en: {
        navSubmit: "Submit Request", navDashboard: "Dashboard", navView: "View Requests",
        navReports: "Reports", navLogs: "System Logs", navSettings: "Settings",
        formTitle: "NEW EDIT/VOID VOUCHER REQUEST", lblBranch: "Branch (Search)",
        lblUser: "User Name", lblVoucherCode: "Voucher Code", lblVoucherType: "Voucher Type",
        lblAction: "Action Required", optEdit: "Edit", optVoid: "Void",
        lblTargetDept: "Target Department", lblEditType: "Edit Type",
        lblDateChange: "Date Change (Plain Text)", lblReason: "Reason for Request",
        btnSubmit: "Submit Request(s)"
      },
      am: {
        navSubmit: "ጥያቄ ማስገቢያ", navDashboard: "ዳሽቦርድ", navView: "ጥያቄዎችን ይመልከቱ",
        navReports: "ሪፖርቶች", navLogs: "የሲስተም ሎግ", navSettings: "ማስተካከያ",
        formTitle: "አዲስ የቫውቸር ማስተካከያ/ሰረዛ ጥያቄ", lblBranch: "ቅርንጫፍ (ፈልግ)",
        lblUser: "የተጠቃሚ ስም", lblVoucherCode: "የቫውቸር ኮድ", lblVoucherType: "የቫውቸር ዓይነት",
        lblAction: "የሚፈለግ እርምጃ", optEdit: "ማስተካከል", optVoid: "መሰረዝ",
        lblTargetDept: "ዒላማ ክፍል", lblEditType: "የማስተካከያ ዓይነት",
        lblDateChange: "የቀን ለውጥ (በጽሑፍ)", lblReason: "የጥያቄው ምክንያት",
        btnSubmit: "ጥያቄ ያስገቡ"
      }
    };

    function showLoader(msg = "Processing...") {
      const el = document.getElementById('globalSpinner');
      const msgEl = document.getElementById('spinnerMessage');
      if (msgEl) msgEl.innerText = msg;
      if (el) el.classList.remove('hidden');
    }

    function hideLoader() {
      const el = document.getElementById('globalSpinner');
      if (el) el.classList.add('hidden');
    }

    function getVerifiedActorName(actionLabel) {
      let name = (currentUser && currentUser.name) ? currentUser.name.trim() : '';
      const genericNames = ['Finance Manager', 'F&B Manager', 'IT Admin', 'Guest User', 'Manager', 'User'];
      
      if (!name || genericNames.includes(name)) {
        const role = currentUser ? currentUser.role : 'User';
        const prompted = prompt(`Audit Requirement: Enter your Full Name to perform '${actionLabel}' (${role}):`, name && !genericNames.includes(name) ? name : '');
        if (!prompted || !prompted.trim()) {
          showToast("Name Required", `Full name is mandatory to ${actionLabel}`, "warning");
          return null;
        }
        name = prompted.trim();
        if (currentUser) currentUser.name = name;
      }
      return name;
    }

    async function apiCall(action, method = 'GET', data = null) {
      const url = `${API_URL}?action=${action}`;
      const options = { 
        method, 
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin'
      };
      if (data) options.body = JSON.stringify(data);

      try {
        const response = await fetch(url, options);
        const resJson = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(resJson.message || resJson.error || `HTTP error! status: ${response.status}`);
        }
        return resJson;
      } catch (error) {
        console.error(`API Call Error (${action}):`, error);
        throw error;
      }
    }

    window.onload = async function() {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        const themeIcon = document.getElementById('themeIcon');
        if (themeIcon) themeIcon.innerText = 'light_mode';
      }

      updateLiveTime();
      setInterval(updateLiveTime, 1000);

      try {
        const status = await apiCall('checkStatus');
        if (!status.ready) {
          await apiCall('initSystem');
        }
      } catch (e) {
        console.warn("System init check failed:", e);
      }

      await loadSettings();
      updateUserUI();

      if (currentUser) {
        hideLoginPage();
        if (currentUser.role === 'Requester') {
          switchView('submit');
        } else {
          switchView('dashboard');
        }
      } else {
        showLoginPage();
      }
    };

    function showLoginPage() {
      const loginPage = document.getElementById('loginPage');
      const appContainer = document.getElementById('appContainer');
      if (loginPage) loginPage.classList.remove('hidden');
      if (appContainer) appContainer.classList.add('hidden');
      switchLoginPageTab('department');
    }

    function hideLoginPage() {
      const loginPage = document.getElementById('loginPage');
      const appContainer = document.getElementById('appContainer');
      if (loginPage) loginPage.classList.add('hidden');
      if (appContainer) appContainer.classList.remove('hidden');
    }

    function toggleTheme() {
      const isDark = document.body.classList.toggle('dark-theme');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      const themeIcon = document.getElementById('themeIcon');
      if (themeIcon) themeIcon.innerText = isDark ? 'light_mode' : 'dark_mode';
      showToast("Theme Changed", isDark ? "Dark mode activated" : "Light mode activated", "info");
    }

    function toggleAutoRefresh() {
      const btnText = document.getElementById('autoRefreshText');
      if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        if (btnText) btnText.innerText = "Live: OFF";
        showToast("Auto-Refresh", "Live auto-refresh disabled", "info");
      } else {
        autoRefreshInterval = setInterval(() => {
          loadDashboard();
        }, 30000);
        if (btnText) btnText.innerText = "Live: ON (30s)";
        showToast("Auto-Refresh", "Live auto-refresh enabled (30s interval)", "success");
      }
    }

    function updateLiveTime() {
      const now = new Date();
      const el = document.getElementById('live-time');
      if (el) el.innerText = now.toLocaleString();
    }

    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (window.innerWidth <= 768) {
        if (sidebar) sidebar.classList.toggle('mobile-open');
        if (backdrop) backdrop.classList.toggle('active', sidebar.classList.contains('mobile-open'));
      } else {
        if (sidebar) sidebar.classList.toggle('collapsed');
      }
    }

    function closeSidebarMobile() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (sidebar) sidebar.classList.remove('mobile-open');
      if (backdrop) backdrop.classList.remove('active');
    }

    function switchView(viewId) {
      if (!currentUser) {
        showLoginPage();
        return;
      }

      closeSidebarMobile();

      ['submit', 'dashboard', 'view', 'reports', 'logs', 'settings'].forEach(v => {
        const viewEl = document.getElementById(`view-${v}`);
        const navEl = document.getElementById(`nav-${v}`);
        if (viewEl) viewEl.classList.add('hidden');
        if (navEl) navEl.classList.remove('active');
      });

      const activeView = document.getElementById(`view-${viewId}`);
      const activeNav = document.getElementById(`nav-${viewId}`);
      if (activeView) activeView.classList.remove('hidden');
      if (activeNav) activeNav.classList.add('active');

      const titles = {
        submit: "Submit Request",
        dashboard: "Dashboard Overview",
        view: "All Requests",
        reports: "Reports & Analytics",
        logs: "System Activity Logs",
        settings: "System Configuration"
      };
      const pageTitle = document.getElementById('page-title');
      if (pageTitle) pageTitle.innerText = titles[viewId] || "Voucher Portal";

      if (viewId === 'dashboard' || viewId === 'view') loadDashboard();
      if (viewId === 'reports') loadReports();
      if (viewId === 'logs') loadLogs();
    }

    function switchRequestTab(tab) {
      const tVch = document.getElementById('tab-voucher');
      const tOh = document.getElementById('tab-onhand');
      const cVch = document.getElementById('card-voucher-request');
      const cOh = document.getElementById('card-onhand-request');
      if (tVch) tVch.classList.toggle('active', tab === 'voucher');
      if (tOh) tOh.classList.toggle('active', tab === 'onhand');
      if (cVch) cVch.classList.toggle('hidden', tab !== 'voucher');
      if (cOh) cOh.classList.toggle('hidden', tab !== 'onhand');
    }

    function populateTargetDeptSelects() {
      const voucherTargetSelect = document.getElementById('targetDeptSelect');
      if (voucherTargetSelect && systemSettings.departments) {
        voucherTargetSelect.innerHTML = '<option value="">Select Target Department...</option>';
        systemSettings.departments.forEach(dept => {
          voucherTargetSelect.add(new Option(dept.name, dept.name));
        });
        const fbOpt = Array.from(voucherTargetSelect.options).find(opt => opt.value === 'F&B');
        if (fbOpt) {
          voucherTargetSelect.value = 'F&B';
        } else if (voucherTargetSelect.options.length > 1) {
          voucherTargetSelect.selectedIndex = 1;
        }
      }

      const onHandTargetSelect = document.getElementById('onHandTargetDeptSelect');
      if (onHandTargetSelect) {
        onHandTargetSelect.innerHTML = '<option value="F&B" selected>F&B</option>';
      }
    }

    async function loadSettings() {
      showLoader("Loading configuration...");
      try {
        const data = await apiCall('getSettings');
        if (data) {
          systemSettings = data;
          if (data.currentUser && !currentUser) {
            currentUser = data.currentUser;
            updateUserUI();
          }
        }

        // 1. Populate Branch Manager Datalists for fast selection
        const pageBmList = document.getElementById('pageBmQuickList');
        const bmList = document.getElementById('bmQuickList');
        if (systemSettings.branchManagers) {
          [pageBmList, bmList].forEach(list => {
            if (!list) return;
            list.innerHTML = '';
            systemSettings.branchManagers.forEach(bm => {
              const opt = document.createElement('option');
              opt.value = bm.email || bm.name;
              opt.label = `${bm.name} (${bm.position} - ${bm.branch_name})`;
              list.appendChild(opt);
            });
          });
        }

        // 2. Populate Dept / Admin Users Datalist for login
        const pageDeptList = document.getElementById('pageDeptQuickList');
        if (pageDeptList && systemSettings.deptUsers) {
          pageDeptList.innerHTML = '';
          systemSettings.deptUsers.forEach(du => {
            const opt = document.createElement('option');
            opt.value = du.username || du.role;
            opt.label = `${du.name || du.username} (${du.role} - ${du.department})`;
            pageDeptList.appendChild(opt);
          });
        }

        // 3. Populate User Filter Datalists for View Requests and Reports
        const vUserList = document.getElementById('viewUsersList');
        const rUserList = document.getElementById('reportUsersList');
        if (systemSettings.usersList) {
          [vUserList, rUserList].forEach(list => {
            if (!list) return;
            list.innerHTML = '';
            systemSettings.usersList.forEach(u => {
              const opt = document.createElement('option');
              opt.value = u;
              list.appendChild(opt);
            });
          });
        }

        const vSelect = document.getElementById('voucherTypeSelect');
        if (vSelect && systemSettings.voucherTypes) {
          vSelect.innerHTML = '<option value="">Select Voucher Type...</option>';
          systemSettings.voucherTypes.forEach(type => {
            vSelect.add(new Option(type, type));
          });
        }

        const editSelect = document.getElementById('editTypeSelect');
        if (editSelect && systemSettings.editTypes) {
          editSelect.innerHTML = '<option value="">Select Edit Type...</option>';
          systemSettings.editTypes.forEach(type => {
            editSelect.add(new Option(type, type));
          });
        }

        populateTargetDeptSelects();
        renderStoreDepartments();
        populateReportFilters();
        populateBranchLists();

        if (currentUser && currentUser.role === 'Admin') {
          renderSettingsChips();
        }
      } catch (err) {
        console.error("Failed to load settings:", err);
      } finally {
        hideLoader();
      }
    }

    function renderStoreDepartments() {
      const container = document.getElementById('storeDepartmentsContainer');
      if (!container) return;
      container.innerHTML = '';
      const depts = systemSettings.storeDepartments || [
        "Hot Kitchen", "Bottle & Can Bar", "Pastry", "Food Store",
        "Juice & Smoothie Bar", "Steward", "Cup Store", "Ice Cream & Shake Bar",
        "Coffee Bar", "Milk Store", "Super Market Store", "Staff Canteen"
      ];
      depts.forEach(dept => {
        const label = document.createElement('label');
        label.className = 'checkbox-item';
        label.style.display = 'flex';
        label.style.alignItems = 'center';
        label.style.gap = '6px';
        label.style.fontSize = '0.85rem';
        label.style.cursor = 'pointer';
        label.innerHTML = `<input type="checkbox" name="departments[]" value="${escapeHtml(dept)}"> ${escapeHtml(dept)}`;
        container.appendChild(label);
      });
    }

    function populateBranchLists() {
      const bList = document.getElementById('branchList');
      const ohList = document.getElementById('onHandBranchList');
      const bData = document.getElementById('branchDatalist');
      const ohData = document.getElementById('onHandBranchDatalist');
      const editData = document.getElementById('editReqBranchList');

      if (!systemSettings.branches) return;

      if (bList) bList.innerHTML = '';
      if (ohList) ohList.innerHTML = '';
      if (bData) bData.innerHTML = '';
      if (ohData) ohData.innerHTML = '';
      if (editData) editData.innerHTML = '';

      systemSettings.branches.forEach(branch => {
        const raw = typeof branch === 'object' ? branch.name : branch;
        const name = cleanBranchName(raw);
        
        if (bList) {
          const li = document.createElement('li');
          li.className = 'searchable-item';
          li.innerText = name;
          li.onclick = () => selectBranch(name);
          bList.appendChild(li);
        }

        if (ohList) {
          const li = document.createElement('li');
          li.className = 'searchable-item';
          li.innerText = name;
          li.onclick = () => selectOnHandBranch(name);
          ohList.appendChild(li);
        }

        if (bData) bData.appendChild(new Option(name, name));
        if (ohData) ohData.appendChild(new Option(name, name));
        if (editData) editData.appendChild(new Option(name, name));
      });
    }

    function populateReportFilters() {
      const branchSelect = document.getElementById('reportBranchFilter');
      if (branchSelect && systemSettings.branches) {
        const currVal = branchSelect.value;
        branchSelect.innerHTML = '<option value="all">All Branches</option>';
        systemSettings.branches.forEach(b => {
          const raw = typeof b === 'object' ? b.name : b;
          const name = cleanBranchName(raw);
          branchSelect.add(new Option(name, name));
        });
        branchSelect.value = currVal || 'all';
      }

      const deptSelect = document.getElementById('reportDeptFilter');
      if (deptSelect && systemSettings.departments) {
        const currVal = deptSelect.value;
        deptSelect.innerHTML = '<option value="all">All Target Depts</option>';
        systemSettings.departments.forEach(d => {
          deptSelect.add(new Option(d.name, d.name));
        });
        deptSelect.value = currVal || 'all';
      }
    }

    function filterBranches() {
      const input = document.getElementById('branchInput');
      const hiddenInput = document.getElementById('branchCode');
      const list = document.getElementById('branchList');
      if (!input || !list) return;

      if (hiddenInput) hiddenInput.value = input.value;

      const filter = input.value.trim().toLowerCase();
      const items = list.getElementsByTagName('li');
      let hasVisible = false;
      for (let item of items) {
        const text = item.innerText.toLowerCase();
        if (!filter || text.includes(filter)) {
          item.style.display = 'block';
          hasVisible = true;
        } else {
          item.style.display = 'none';
        }
      }
      if (hasVisible) {
        list.classList.remove('hidden');
        list.style.display = 'block';
      } else {
        list.classList.add('hidden');
        list.style.display = 'none';
      }
    }

    function selectBranch(name) {
      const input = document.getElementById('branchInput');
      const hiddenInput = document.getElementById('branchCode');
      const list = document.getElementById('branchList');
      if (input) input.value = name;
      if (hiddenInput) hiddenInput.value = name;
      if (list) {
        list.classList.add('hidden');
        list.style.display = 'none';
      }
    }

    function filterOnHandBranches() {
      const input = document.getElementById('onHandBranchInput');
      const hiddenInput = document.getElementById('onHandBranchCode');
      const list = document.getElementById('onHandBranchList');
      if (!input || !list) return;

      if (hiddenInput) hiddenInput.value = input.value;

      const filter = input.value.trim().toLowerCase();
      const items = list.getElementsByTagName('li');
      let hasVisible = false;
      for (let item of items) {
        const text = item.innerText.toLowerCase();
        if (!filter || text.includes(filter)) {
          item.style.display = 'block';
          hasVisible = true;
        } else {
          item.style.display = 'none';
        }
      }
      if (hasVisible) {
        list.classList.remove('hidden');
        list.style.display = 'block';
      } else {
        list.classList.add('hidden');
        list.style.display = 'none';
      }
    }

    function selectOnHandBranch(name) {
      const input = document.getElementById('onHandBranchInput');
      const hiddenInput = document.getElementById('onHandBranchCode');
      const list = document.getElementById('onHandBranchList');
      if (input) input.value = name;
      if (hiddenInput) hiddenInput.value = name;
      if (list) {
        list.classList.add('hidden');
        list.style.display = 'none';
      }
    }

    function toggleEditType() {
      const actionSelect = document.getElementById('actionSelect');
      const editGroup = document.getElementById('editTypeGroup');
      const dateGroup = document.getElementById('dateFieldsGroup');
      if (!actionSelect || !editGroup) return;

      if (actionSelect.value === 'Edit') {
        editGroup.classList.remove('hidden');
        if (dateGroup) dateGroup.classList.remove('hidden');
      } else {
        editGroup.classList.add('hidden');
        if (dateGroup) dateGroup.classList.add('hidden');
      }
    }

    function addVoucherCode() {
      const container = document.getElementById('voucherCodesContainer');
      if (!container) return;
      const count = container.querySelectorAll('.voucher-code-item').length;
      const newItem = document.createElement('div');
      newItem.className = 'voucher-code-item';
      newItem.setAttribute('data-index', count);
      newItem.innerHTML = `
        <input type="text" name="voucherCodes[]" class="voucher-code-input" placeholder="e.g. VCH-00${count+1}" required>
        <button type="button" class="btn-icon" onclick="removeVoucherCode(this)" style="color:var(--danger);">
          <i class="material-icons-round">remove_circle</i>
        </button>
      `;
      container.appendChild(newItem);
      document.getElementById('voucherCodesCount').value = count + 1;
      document.getElementById('voucherCountBadge').innerText = count + 1;

      container.querySelectorAll('.btn-icon').forEach(btn => btn.disabled = false);
    }

    function removeVoucherCode(btn) {
      const container = document.getElementById('voucherCodesContainer');
      if (!container) return;
      const item = btn.closest('.voucher-code-item');
      if (item) item.remove();

      const newCount = container.querySelectorAll('.voucher-code-item').length;
      document.getElementById('voucherCodesCount').value = newCount;
      document.getElementById('voucherCountBadge').innerText = newCount;
      
      if (newCount === 1) {
        container.querySelector('.btn-icon').disabled = true;
      }
    }

    async function handleFormSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const formData = new FormData(form);
      const voucherCodes = formData.getAll('voucherCodes[]');
      const branch = formData.get('branch') || document.getElementById('branchInput')?.value;
      const username = formData.get('username');
      const voucherType = formData.get('voucherType');
      const actionType = formData.get('actionType');
      const targetDept = formData.get('targetDept');
      const editType = formData.get('editType');
      const incorrectDate = formData.get('incorrectDate');
      const correctDate = formData.get('correctDate');
      const reason = formData.get('reason');

      showLoader("Submitting request(s)...");
      try {
        let successCount = 0;
        for (let code of voucherCodes) {
          if (!code.trim()) continue;
          const data = {
            branch, username, voucherCode: code.trim(), voucherType,
            actionType, targetDept, editType, incorrectDate, correctDate, reason
          };
          const res = await apiCall('submitRequest', 'POST', data);
          if (res.success) successCount++;
        }

        if (successCount > 0) {
          showToast("Success", `${successCount} Request(s) submitted successfully!`, "success");
          form.reset();
          const container = document.getElementById('voucherCodesContainer');
          if (container) {
            container.innerHTML = `
              <div class="voucher-code-item" data-index="0">
                <input type="text" name="voucherCodes[]" class="voucher-code-input" placeholder="e.g. VCH-001" required>
                <button type="button" class="btn-icon" onclick="removeVoucherCode(this)" style="color:var(--danger);" disabled>
                  <i class="material-icons-round">remove_circle</i>
                </button>
              </div>
            `;
          }
          document.getElementById('voucherCodesCount').value = 1;
          document.getElementById('voucherCountBadge').innerText = 1;
          populateTargetDeptSelects();
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message || "Failed to submit request", "error");
      } finally {
        hideLoader();
      }
    }

    async function handleOnHandSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const formData = new FormData(form);
      const branch = formData.get('branch') || document.getElementById('onHandBranchInput')?.value;
      const username = formData.get('username') || document.getElementById('onHandUsername')?.value;
      const storeUser = formData.get('storeUser') || document.getElementById('onHandStoreUser')?.value || '';
      const targetDept = formData.get('targetDept') || 'F&B';
      const correctDate = formData.get('correctDate');
      const shift = formData.get('shift');
      const reason = formData.get('reason') || 'On Hand Delete Request';
      
      if (!storeUser.trim()) {
        showToast("Validation Error", "Please enter Department Store User", "error");
        return;
      }

      const departments = [];
      form.querySelectorAll('input[name="departments[]"]:checked').forEach(cb => {
        departments.push(cb.value);
      });
      
      if (departments.length === 0) {
        showToast("Validation Error", "Please select at least one Department Store", "error");
        return;
      }
      
      const data = {
        branch: branch,
        username: username,
        storeUser: storeUser.trim(),
        actionType: 'On Hand Delete',
        targetDept: targetDept,
        departments: departments,
        correctDate: correctDate,
        shift: shift,
        reason: reason
      };
      
      showLoader("Submitting On Hand Delete request...");
      try {
        const res = await apiCall('submitRequest', 'POST', data);
        if (res.success) {
          showToast("Success", "On Hand Delete Request submitted successfully!", "success");
          form.reset();
          populateTargetDeptSelects();
          if (currentUser && currentUser.role === 'Requester') {
            updateUserUI(); // re-populate locked fields
          }
          loadDashboard();
        } else {
          showToast("Error", res.message || "Failed to submit request", "error");
        }
      } catch (err) {
        showToast("Error", err.message || "Server connection failed", "error");
      } finally {
        hideLoader();
      }
    }

    async function loadDashboard() {
      showLoader("Loading dashboard data...");
      const period = document.getElementById('dashboardPeriod')?.value || document.getElementById('periodSelect')?.value || 'all';
      const filterType = document.getElementById('filterType')?.value || 'none';
      const filterValue = document.getElementById('filterValueSelect')?.value || '';
      const userRole = currentUser ? currentUser.role : 'Guest';
      const userDept = currentUser ? currentUser.department : '';

      try {
        const data = await apiCall(`getDashboard&period=${period}&filterType=${filterType}&filterValue=${encodeURIComponent(filterValue)}&userRole=${userRole}&userDept=${encodeURIComponent(userDept)}`);
        
        if (data) {
          allRequests = data.requests || [];
          adminUsers = data.usersList || [];
          dashboardMetrics = data.metrics || {};
          userFrequencyData = data.userFrequency || [];

          renderMetrics(data.metrics || {});
          renderSmartInsights(data.metrics || {});
          renderCharts(data.metrics || {});
          renderTable(allRequests);

          if (currentUser && currentUser.role === 'Admin') {
            renderAdminUsersTable();
          }
        }
      } catch (err) {
        console.error("Dashboard error:", err);
      } finally {
        hideLoader();
      }
    }

    function renderMetrics(m) {
      if (!m) return;
      const total = m.totalRequests || 0;
      const pending = m.pending || 0;
      const approved = m.approved || 0;
      const done = m.done || 0;
      const voidCount = (m.actionData && m.actionData['Void']) || 0;
      const editCount = (m.actionData && m.actionData['Edit']) || 0;
      const onHandCount = (m.actionData && m.actionData['On Hand Delete']) || 0;

      // Main Metrics Grid
      if (document.getElementById('m-total')) document.getElementById('m-total').innerText = total;
      if (document.getElementById('m-pending')) document.getElementById('m-pending').innerText = pending;
      if (document.getElementById('m-approved')) document.getElementById('m-approved').innerText = approved;
      if (document.getElementById('m-done')) document.getElementById('m-done').innerText = done;
      if (document.getElementById('m-void')) document.getElementById('m-void').innerText = voidCount;
      if (document.getElementById('m-edit')) document.getElementById('m-edit').innerText = editCount;
      if (document.getElementById('m-onhand')) document.getElementById('m-onhand').innerText = onHandCount;

      // Enhanced Top Stats Cards
      if (document.getElementById('stat-total')) document.getElementById('stat-total').innerText = total;
      if (document.getElementById('stat-pending')) document.getElementById('stat-pending').innerText = pending;
      
      const appRate = total > 0 ? Math.round(((approved + done) / total) * 100) : 0;
      if (document.getElementById('stat-approval-rate')) document.getElementById('stat-approval-rate').innerText = appRate + '%';
      if (document.getElementById('stat-avg-time')) document.getElementById('stat-avg-time').innerText = (m.avgResolutionTimeHours || 2.4) + 'h';
      if (document.getElementById('stat-sla-overdue')) document.getElementById('stat-sla-overdue').innerText = m.overdueSlaCount || 0;

      // Quick Stats Row
      if (document.getElementById('quick-edits')) document.getElementById('quick-edits').innerText = editCount;
      if (document.getElementById('quick-voids')) document.getElementById('quick-voids').innerText = voidCount;
      if (document.getElementById('quick-onhand')) document.getElementById('quick-onhand').innerText = onHandCount;
      if (document.getElementById('quick-unique-users')) document.getElementById('quick-unique-users').innerText = (m.topEditUsers ? Object.keys(m.topEditUsers).length : 0);
      if (document.getElementById('quick-branches')) document.getElementById('quick-branches').innerText = (m.topBranches ? Object.keys(m.topBranches).length : 0);
    }

    function toggleFilterInput() {
      const type = document.getElementById('filterType')?.value || 'none';
      const select = document.getElementById('filterValueSelect');
      const clearBtn = document.getElementById('clearFilterBtn');
      if (!select) return;

      if (type === 'none') {
        select.classList.add('hidden');
        if (clearBtn) clearBtn.classList.add('hidden');
        loadDashboard();
        return;
      }

      select.innerHTML = '<option value="">Select ' + type + '...</option>';
      if (type === 'branch' && systemSettings.branches) {
        systemSettings.branches.forEach(b => {
          const name = typeof b === 'object' ? b.name : b;
          select.add(new Option(name, name));
        });
      } else if (type === 'voucher' && systemSettings.voucherTypes) {
        systemSettings.voucherTypes.forEach(v => select.add(new Option(v, v)));
      } else if (type === 'user' && userFrequencyData) {
        userFrequencyData.forEach(u => select.add(new Option(u.user_name, u.user_name)));
      }

      select.classList.remove('hidden');
      if (clearBtn) clearBtn.classList.remove('hidden');
    }

    function clearFilters() {
      const fType = document.getElementById('filterType');
      const fVal = document.getElementById('filterValueSelect');
      const clearBtn = document.getElementById('clearFilterBtn');
      if (fType) fType.value = 'none';
      if (fVal) {
        fVal.innerHTML = '';
        fVal.classList.add('hidden');
      }
      if (clearBtn) clearBtn.classList.add('hidden');
      loadDashboard();
    }

    function exportDashboardReport() {
      exportToCSV();
    }

    function renderSmartInsights(m) {
      const container = document.getElementById('smart-insights-banner');
      if (!container) return;

      let insights = [];
      if (m.overdueSlaCount > 0) {
        insights.push(`<div class="insight-card danger"><i class="material-icons-round" style="color:var(--danger)">warning</i> <span><strong>${m.overdueSlaCount} Request(s)</strong> overdue SLA (>24h)!</span></div>`);
      } else {
        insights.push(`<div class="insight-card success"><i class="material-icons-round" style="color:var(--success)">verified</i> <span>All pending requests are within 24h SLA.</span></div>`);
      }

      if (m.topBranches) {
        const topB = Object.entries(m.topBranches).sort((a,b) => b[1] - a[1])[0];
        if (topB) {
          insights.push(`<div class="insight-card primary"><i class="material-icons-round" style="color:var(--primary)">location_city</i> <span>Top Requester Branch: <strong>${escapeHtml(topB[0])}</strong> (${topB[1]} requests)</span></div>`);
        }
      }

      if (m.topEditTypes) {
        const topE = Object.entries(m.topEditTypes).sort((a,b) => b[1] - a[1])[0];
        if (topE) {
          insights.push(`<div class="insight-card info"><i class="material-icons-round" style="color:var(--info)">tune</i> <span>Most Common Edit: <strong>${escapeHtml(topE[0])}</strong> (${topE[1]} times)</span></div>`);
        }
      }

      container.innerHTML = insights.join('');
      container.classList.remove('hidden');
    }

    function renderCharts(m) {
      renderPieChart('pieStatus', 'pieLegend', 'pieTotal', [
        { label: 'Pending', count: m.pending || 0, color: '#D97706' },
        { label: 'Approved', count: m.approved || 0, color: '#059669' },
        { label: 'Rejected', count: m.rejected || 0, color: '#DC2626' },
        { label: 'Done', count: m.done || 0, color: '#2563EB' }
      ]);

      renderPieChart('pieAction', 'pieActionLegend', null, [
        { label: 'Edit', count: (m.actionData && m.actionData['Edit']) || 0, color: '#4B3621' },
        { label: 'Void', count: (m.actionData && m.actionData['Void']) || 0, color: '#DC2626' },
        { label: 'On Hand Delete', count: (m.actionData && m.actionData['On Hand Delete']) || 0, color: '#059669' }
      ]);

      renderBarChart('chart-branches', m.topBranches || {});
      renderBarChart('chart-vouchers', m.topVouchers || {});
      renderBarChart('chart-editTypes', m.topEditTypes || {});
      renderBarChart('chart-editUsers', m.topEditUsers || {});
      renderBarChart('chart-voidUsers', m.topVoidUsers || {});
      renderBarChart('chart-department', m.deptData || {});

      renderHourlyPeakChart(m.peakHours || Array(24).fill(0));
    }

    function renderPieChart(wrapperId, legendId, totalId, slices) {
      const wrapper = document.getElementById(wrapperId);
      const legend = document.getElementById(legendId);
      if (!wrapper || !legend) return;

      const total = slices.reduce((sum, s) => sum + s.count, 0);
      if (totalId) {
        const totalEl = document.getElementById(totalId);
        if (totalEl) totalEl.innerText = total;
      }

      if (total === 0) {
        wrapper.style.background = '#E5E7EB';
        legend.innerHTML = '<span style="color:gray;">No data</span>';
        return;
      }

      let currentPercent = 0;
      const gradientParts = slices.map(s => {
        const pct = (s.count / total) * 100;
        const start = currentPercent;
        currentPercent += pct;
        return `${s.color} ${start}% ${currentPercent}%`;
      });

      wrapper.style.background = `conic-gradient(${gradientParts.join(', ')})`;

      let legendHtml = '';
      slices.forEach(s => {
        const pct = total > 0 ? Math.round((s.count / total) * 100) : 0;
        legendHtml += `
          <div class="legend-item">
            <span class="legend-color" style="background:${s.color}"></span>
            <span>${s.label}: <strong>${s.count}</strong> (${pct}%)</span>
          </div>
        `;
      });
      legend.innerHTML = legendHtml;
    }

    function renderBarChart(containerId, dataMap) {
      const container = document.getElementById(containerId);
      if (!container) return;

      const items = Object.entries(dataMap || {}).sort((a,b) => b[1] - a[1]).slice(0, 5);
      if (items.length === 0) {
        container.innerHTML = '<span style="color:gray; font-size:0.8rem;">No data</span>';
        return;
      }

      const max = Math.max(...items.map(i => i[1]), 1);
      let html = '';
      items.forEach(([label, count]) => {
        const pct = Math.round((count / max) * 100);
        html += `
          <div class="bar-row">
            <div class="bar-label" title="${escapeHtml(label)}">${escapeHtml(label)}</div>
            <div class="bar-track">
              <div class="bar-fill" style="width: ${pct}%;"></div>
            </div>
            <div class="bar-value">${count}</div>
          </div>
        `;
      });
      container.innerHTML = html;
    }

    function renderHourlyPeakChart(peakHours) {
      const canvas = document.getElementById('hourlyPeakChart');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');

      const labels = Array.from({length: 24}, (_, i) => `${String(i).padStart(2,'0')}:00`);

      if (hourlyChartInstance) {
        hourlyChartInstance.destroy();
      }

      hourlyChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Request Volume',
            data: peakHours,
            backgroundColor: '#4B3621',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
          }
        }
      });
    }

    function renderTable(requests) {
      const container = document.getElementById('table-container');
      if (!container) return;

      if (!requests || requests.length === 0) {
        container.innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-muted);">No requests found</p>';
        updateBulkActionBar();
        return;
      }

      const nowTs = Math.floor(Date.now() / 1000);
      let html = '<table><thead><tr><th><input type="checkbox" id="selectAllCb" onchange="toggleSelectAllRequests(this)"></th><th>ID</th><th>Date</th><th>Branch</th><th>User</th><th>Details</th><th>Action</th><th>Target Dept</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

      requests.forEach(r => {
        const isOnHand = r.action_type === 'On Hand Delete';
        const details = isOnHand 
          ? `Shift: ${r.shift || 'shft 1'}<br>Date: ${r.correct_date || '-'}${r.store_user ? `<br>Store User: <strong>${escapeHtml(r.store_user)}</strong>` : ''}`
          : `Code: <code>${escapeHtml(r.voucher_code || '')}</code><br>Type: ${escapeHtml(r.voucher_type || '')}`;

        const reqTs = r.timestamp ? Math.floor(new Date(r.timestamp).getTime() / 1000) : 0;
        const isOverdue = r.status === 'Pending' && reqTs > 0 && (nowTs - reqTs) > 86400;

        let statusHtml = `<span class="status-badge status-${r.status}">${r.status}</span>`;
        if (isOverdue) statusHtml += `<br><span class="status-badge" style="background:#FEE2E2; color:#991B1B;">Overdue</span>`;

        let actionBtns = '';
        if (currentUser) {
          if (r.status === 'Pending') {
            actionBtns += `<button class="btn-sm btn-primary" onclick="updateStatus('${r.request_id}', 'Approved')">Approve</button> `;
            actionBtns += `<button class="btn-sm btn-danger" onclick="updateStatus('${r.request_id}', 'Rejected')">Reject</button> `;
          } else if (r.status === 'Approved') {
            actionBtns += `<button class="btn-sm btn-done" onclick="markDone('${r.request_id}')">Mark Done</button> `;
          }
          if (currentUser.role === 'Admin') {
            actionBtns += `<button class="btn-sm btn-secondary" onclick="openEditModal('${r.request_id}')"><i class="material-icons-round" style="font-size:12px">edit</i></button> `;
            actionBtns += `<button class="btn-sm btn-danger" onclick="deleteRequest('${r.request_id}')"><i class="material-icons-round" style="font-size:12px">delete</i></button>`;
          }
        }

        const isChecked = selectedRequestIds.has(r.request_id) ? 'checked' : '';
        html += `<tr>
          <td><input type="checkbox" class="request-cb" value="${r.request_id}" ${isChecked} onchange="toggleRequestSelection('${r.request_id}', this.checked)"></td>
          <td><code>${escapeHtml(r.request_id)}</code></td>
          <td>${r.timestamp ? r.timestamp.substring(0,10) : ''}</td>
          <td>${escapeHtml(cleanBranchName(r.branch))}</td>
          <td>${escapeHtml(r.user_name)}</td>
          <td>${details}</td>
          <td><strong>${escapeHtml(r.action_type)}</strong></td>
          <td>${escapeHtml(r.target_dept)}</td>
          <td>${escapeHtml(r.reason || '')}</td>
          <td>${statusHtml}</td>
          <td>${actionBtns}</td>
        </tr>`;
      });
      html += '</tbody></table>';
      container.innerHTML = html;
      updateBulkActionBar();
    }

    let currentFilteredViewRequests = [];

    function filterTable() {
      const search = (document.getElementById('viewSearchQuery')?.value || document.getElementById('tableSearch')?.value || '').toLowerCase().trim();
      const branch = document.getElementById('viewBranchFilter')?.value || 'all';
      const user = (document.getElementById('viewUserFilter')?.value || '').toLowerCase().trim();
      const startDate = document.getElementById('viewStartDate')?.value;
      const endDate = document.getElementById('viewEndDate')?.value;
      const actionType = document.getElementById('viewActionFilter')?.value || 'all';
      const status = document.getElementById('viewStatusFilter')?.value || 'all';
      const dept = document.getElementById('viewDeptFilter')?.value || 'all';

      currentFilteredViewRequests = allRequests.filter(r => {
        if (startDate && r.timestamp && r.timestamp.substring(0, 10) < startDate) return false;
        if (endDate && r.timestamp && r.timestamp.substring(0, 10) > endDate) return false;
        if (branch !== 'all' && cleanBranchName(r.branch) !== cleanBranchName(branch)) return false;
        if (user && !r.user_name.toLowerCase().includes(user) && !(r.store_user && r.store_user.toLowerCase().includes(user))) return false;
        if (actionType !== 'all' && r.action_type !== actionType) return false;
        if (status !== 'all' && r.status !== status) return false;
        if (dept !== 'all' && !(r.target_dept && r.target_dept.includes(dept))) return false;
        
        if (search) {
          const matchSearch = (r.request_id && r.request_id.toLowerCase().includes(search)) ||
                              (r.user_name && r.user_name.toLowerCase().includes(search)) ||
                              (r.store_user && r.store_user.toLowerCase().includes(search)) ||
                              (r.branch && r.branch.toLowerCase().includes(search)) ||
                              (r.voucher_code && r.voucher_code.toLowerCase().includes(search)) ||
                              (r.voucher_type && r.voucher_type.toLowerCase().includes(search)) ||
                              (r.reason && r.reason.toLowerCase().includes(search));
          if (!matchSearch) return false;
        }
        return true;
      });

      const countBadge = document.getElementById('viewRequestsCountBadge');
      if (countBadge) countBadge.innerText = `${currentFilteredViewRequests.length} Requests`;

      renderTable(currentFilteredViewRequests);
    }

    function clearViewFilters() {
      if (document.getElementById('viewSearchQuery')) document.getElementById('viewSearchQuery').value = '';
      if (document.getElementById('tableSearch')) document.getElementById('tableSearch').value = '';
      if (document.getElementById('viewUserFilter')) document.getElementById('viewUserFilter').value = '';
      if (document.getElementById('viewStartDate')) document.getElementById('viewStartDate').value = '';
      if (document.getElementById('viewEndDate')) document.getElementById('viewEndDate').value = '';
      if (document.getElementById('viewActionFilter')) document.getElementById('viewActionFilter').value = 'all';
      if (document.getElementById('viewStatusFilter')) document.getElementById('viewStatusFilter').value = 'all';
      if (document.getElementById('viewDeptFilter')) document.getElementById('viewDeptFilter').value = 'all';
      
      const vBranchFilter = document.getElementById('viewBranchFilter');
      if (vBranchFilter && !vBranchFilter.disabled) {
        vBranchFilter.value = 'all';
      }
      
      filterTable();
    }

    function exportFilteredViewRequestsToCSV() {
      const dataToExport = (currentFilteredViewRequests && currentFilteredViewRequests.length > 0) ? currentFilteredViewRequests : allRequests;
      if (!dataToExport || dataToExport.length === 0) {
        return showToast("Warning", "No requests to export", "warning");
      }

      const headers = ["ID", "Date", "Branch", "User", "Store User", "Action", "Voucher Code", "Voucher Type", "Edit Type", "Shift", "Incorrect Date", "Correct Date", "Target Dept", "Reason", "Status", "Approver"];
      const rows = [headers];

      dataToExport.forEach(r => {
        rows.push([
          `"${r.request_id || ''}"`,
          `"${r.timestamp || ''}"`,
          `"${(r.branch || '').replace(/"/g, '""')}"`,
          `"${(r.user_name || '').replace(/"/g, '""')}"`,
          `"${(r.store_user || '').replace(/"/g, '""')}"`,
          `"${r.action_type || ''}"`,
          `"${r.voucher_code || ''}"`,
          `"${(r.voucher_type || '').replace(/"/g, '""')}"`,
          `"${(r.edit_type || '').replace(/"/g, '""')}"`,
          `"${r.shift || ''}"`,
          `"${r.incorrect_date || ''}"`,
          `"${r.correct_date || ''}"`,
          `"${(r.target_dept || '').replace(/"/g, '""')}"`,
          `"${(r.reason || '').replace(/"/g, '""')}"`,
          `"${r.status || ''}"`,
          `"${(r.approver || '').replace(/"/g, '""')}"`
        ]);
      });

      const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `kaldis_view_requests_${new Date().toISOString().substring(0,10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      showToast("Success", "Requests exported to CSV", "success");
    }

    function toggleRequestSelection(id, checked) {
      if (checked) {
        selectedRequestIds.add(id);
      } else {
        selectedRequestIds.delete(id);
      }
      updateBulkActionBar();
    }

    function toggleSelectAllRequests(masterCb) {
      const checkboxes = document.querySelectorAll('.request-cb');
      checkboxes.forEach(cb => {
        cb.checked = masterCb.checked;
        if (masterCb.checked) selectedRequestIds.add(cb.value);
        else selectedRequestIds.delete(cb.value);
      });
      updateBulkActionBar();
    }

    function clearBulkSelections() {
      selectedRequestIds.clear();
      const masterCb = document.getElementById('selectAllCb');
      if (masterCb) masterCb.checked = false;
      document.querySelectorAll('.request-cb').forEach(cb => cb.checked = false);
      updateBulkActionBar();
    }

    function updateBulkActionBar() {
      const bar = document.getElementById('bulkActionBar');
      const countEl = document.getElementById('bulkSelectedCount');
      if (!bar || !countEl) return;
      const count = selectedRequestIds.size;
      countEl.innerText = `${count} selected`;
      bar.classList.toggle('hidden', count === 0 || !currentUser);
    }

    async function handleBulkAction(newStatus) {
      if (!currentUser) return showToast("Error", "Please login", "error");
      const ids = Array.from(selectedRequestIds);
      if (ids.length === 0) return;
      const actorName = getVerifiedActorName(`Bulk Status to ${newStatus}`);
      if (!actorName) return;

      showLoader(`Bulk updating ${ids.length} request(s)...`);
      try {
        const res = await apiCall('bulkUpdateStatus', 'POST', { ids, newStatus, actorName });
        if (res.success) {
          showToast("Success", `Bulk updated ${res.count} requests`, "success");
          clearBulkSelections();
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function handleBulkMarkDone() {
      if (!currentUser) return showToast("Error", "Please login", "error");
      const ids = Array.from(selectedRequestIds);
      if (ids.length === 0) return;
      const actorName = getVerifiedActorName("Bulk Mark Done");
      if (!actorName) return;

      showLoader(`Bulk marking Done for ${ids.length} request(s)...`);
      try {
        const res = await apiCall('bulkMarkDone', 'POST', { ids, actorName });
        if (res.success) {
          showToast("Success", `Bulk marked Done for ${res.count} requests`, "success");
          clearBulkSelections();
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function updateStatus(id, newStatus) {
      if (!currentUser) return showToast("Error", "Please login", "error");
      const actorName = getVerifiedActorName(`Approve/Reject Request (${newStatus})`);
      if (!actorName) return;

      showLoader(`Updating status to ${newStatus}...`);
      try {
        const res = await apiCall('updateStatus', 'POST', { id, newStatus, actorName });
        if (res.success) {
          showToast("Success", `Request ${newStatus}`, "success");
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function markDone(id) {
      if (!currentUser) return showToast("Error", "Please login", "error");
      const actorName = getVerifiedActorName("Mark Request Done");
      if (!actorName) return;

      showLoader("Marking request as Done...");
      try {
        const res = await apiCall('markDone', 'POST', { id, actorName });
        if (res.success) {
          showToast("Success", "Request marked Done", "success");
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function deleteRequest(id) {
      if (!currentUser || currentUser.role !== 'Admin') return showToast("Error", "Admin only", "error");
      if (!confirm("Are you sure?")) return;
      
      showLoader("Deleting request...");
      try {
        const res = await apiCall('deleteRequest', 'POST', { id });
        if (res.success) {
          showToast("Success", "Request deleted", "success");
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    function openEditModal(requestId) {
      console.log("openEditModal called with requestId:", requestId);
      try {
        if (!currentUser) {
          console.error("currentUser is null or undefined");
          return showToast("Unauthorized", "Please login", "error");
        }
        console.log("currentUser role:", currentUser.role);
        if (currentUser.role !== 'Admin') {
          return showToast("Unauthorized", "Admin access required", "error");
        }
        
        console.log("allRequests length:", allRequests ? allRequests.length : 0);
        const req = allRequests.find(r => r.request_id === requestId);
        if (!req) {
          console.error("Request not found in allRequests:", requestId);
          return showToast("Error", "Request not found: " + requestId, "error");
        }
        console.log("Found request object:", req);

        const assign = (id, val) => {
          const el = document.getElementById(id);
          if (el) {
            el.value = val || '';
            console.log(`Assigned element ${id} to:`, val);
          } else {
            console.warn(`DOM Element with ID ${id} not found!`);
          }
        };

        assign('editReqId', req.request_id);
        assign('editReqUsername', req.user_name);
        assign('editReqStoreUser', req.store_user);
        assign('editReqActionType', req.action_type);
        assign('editReqVoucherCode', req.voucher_code);
        assign('editReqReason', req.reason);
        assign('editReqShift', req.shift);
        assign('editReqCorrectDate', req.correct_date);
        assign('editReqStatus', req.status);

        const branchInput = document.getElementById('editReqBranch');
        if (branchInput) {
          branchInput.value = req.branch || '';
        }
        const branchList = document.getElementById('editReqBranchList');
        if (branchList && Array.isArray(systemSettings.branches)) {
          branchList.innerHTML = '';
          systemSettings.branches.forEach(b => {
            const name = typeof b === 'object' ? b.name : b;
            branchList.appendChild(new Option(name, name));
          });
        }

        const etSel = document.getElementById('editReqEditType');
        if (etSel) {
          etSel.innerHTML = '<option value="">Select...</option>';
          if (Array.isArray(systemSettings.editTypes)) {
            console.log("Populating edit types in edit modal...", systemSettings.editTypes);
            systemSettings.editTypes.forEach(t => etSel.add(new Option(t, t)));
          }
          etSel.value = req.edit_type || '';
          console.log("Set editReqEditType to:", etSel.value);
        } else {
          console.warn("Element editReqEditType not found");
        }

        const deptSel = document.getElementById('editReqTargetDept');
        if (deptSel) {
          deptSel.innerHTML = '';
          if (Array.isArray(systemSettings.departments)) {
            console.log("Populating target departments in edit modal...", systemSettings.departments);
            systemSettings.departments.forEach(d => deptSel.add(new Option(d.name, d.name)));
          }
          deptSel.value = req.target_dept || 'F&B';
          console.log("Set editReqTargetDept to:", deptSel.value);
        } else {
          console.warn("Element editReqTargetDept not found");
        }

        const vSel = document.getElementById('editReqVoucherType');
        if (vSel) {
          vSel.innerHTML = '';
          if (Array.isArray(systemSettings.voucherTypes)) {
            console.log("Populating voucher types in edit modal...", systemSettings.voucherTypes);
            systemSettings.voucherTypes.forEach(v => vSel.add(new Option(v, v)));
          }
          vSel.value = req.voucher_type || '';
          console.log("Set editReqVoucherType to:", vSel.value);
        } else {
          console.warn("Element editReqVoucherType not found");
        }

        console.log("Toggling action fields...");
        toggleEditModalActionFields();

        const modal = document.getElementById('editRequestModal');
        if (modal) {
          console.log("Opening modal by adding active class");
          modal.classList.add('active');
        } else {
          console.error("editRequestModal element not found in DOM");
        }
      } catch (err) {
        console.error("Uncaught exception in openEditModal:", err);
        showToast("Error", "Failed to open edit modal: " + err.message, "error");
      }
    }

    function toggleEditModalActionFields() {
      const actionType = document.getElementById('editReqActionType')?.value;
      const vFields = document.getElementById('editVoucherFields');
      const ohFields = document.getElementById('editOnHandFields');
      const etGroup = document.getElementById('editReqEditTypeGroup');
      if (!vFields || !ohFields) return;

      if (actionType === 'On Hand Delete') {
        vFields.classList.add('hidden');
        ohFields.classList.remove('hidden');
        if (etGroup) etGroup.classList.add('hidden');
      } else if (actionType === 'Edit') {
        vFields.classList.remove('hidden');
        ohFields.classList.add('hidden');
        if (etGroup) etGroup.classList.remove('hidden');
      } else {
        vFields.classList.remove('hidden');
        ohFields.classList.add('hidden');
        if (etGroup) etGroup.classList.add('hidden');
      }
    }

    async function handleEditRequestSubmit(e) {
      e.preventDefault();
      if (!currentUser || currentUser.role !== 'Admin') return;

      const requestId = document.getElementById('editReqId').value;
      const payload = {
        requestId,
        branch: document.getElementById('editReqBranch').value,
        username: document.getElementById('editReqUsername').value.trim(),
        storeUser: document.getElementById('editReqStoreUser')?.value.trim() || null,
        actionType: document.getElementById('editReqActionType').value,
        targetDept: document.getElementById('editReqTargetDept').value,
        voucherCode: document.getElementById('editReqVoucherCode').value.trim(),
        voucherType: document.getElementById('editReqVoucherType').value,
        editType: document.getElementById('editReqEditType')?.value || null,
        shift: document.getElementById('editReqShift').value.trim(),
        correctDate: document.getElementById('editReqCorrectDate').value.trim(),
        reason: document.getElementById('editReqReason').value.trim(),
        status: document.getElementById('editReqStatus').value
      };

      showLoader("Saving changes to request...");
      try {
        const res = await apiCall('editRequest', 'POST', payload);
        if (res.success) {
          showToast("Success", "Request updated by Admin", "success");
          closeModal('editRequestModal');
          loadDashboard();
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function loadLogs() {
      if (!currentUser || currentUser.role !== 'Admin') return;
      try {
        const data = await apiCall('getDashboard&userRole=Admin');
        allLogs = data.logs || [];
        renderLogsTable(allLogs);
      } catch (err) {
        console.error("Logs error:", err);
      }
    }

    function filterLogsTable() {
      const search = document.getElementById('logSearch')?.value.toLowerCase() || '';
      const actionFilter = document.getElementById('logActionFilter')?.value.toLowerCase() || 'all';

      const filtered = allLogs.filter(l => {
        const matchSearch = l.actor.toLowerCase().includes(search) || (l.requestId && l.requestId.toLowerCase().includes(search));
        const matchAction = actionFilter === 'all' || l.action.toLowerCase().includes(actionFilter);
        return matchSearch && matchAction;
      });

      renderLogsTable(filtered);
    }

    async function loadReports() {
      showLoader("Loading reports...");
      try {
        const period = 'all';
        const filterType = 'none';
        const filterValue = '';
        const userRole = currentUser ? currentUser.role : 'Guest';
        const userDept = currentUser ? currentUser.department : '';
        const data = await apiCall(`getDashboard&period=${period}&filterType=${filterType}&filterValue=${encodeURIComponent(filterValue)}&userRole=${userRole}&userDept=${encodeURIComponent(userDept)}`);
        if (data) {
          allRequests = data.requests || [];
          applyReportFilters();
        }
      } catch (err) {
        showToast("Error", "Failed to load reports: " + err.message, "error");
      } finally {
        hideLoader();
      }
    }

    function renderReportsTable(data) {
      const container = document.getElementById('reports-table-container');
      if (!container) return;

      const rTotal = document.getElementById('r-total');
      const rRate = document.getElementById('r-rate');
      const rTime = document.getElementById('r-time');
      
      if (rTotal) rTotal.innerText = data.length;
      
      const appCount = data.filter(r => r.status === 'Approved' || r.status === 'Done').length;
      const rate = data.length > 0 ? Math.round((appCount / data.length) * 100) : 0;
      if (rRate) rRate.innerText = rate + '%';

      let resolutionTimes = [];
      data.forEach(r => {
        if (r.status === 'Done') {
          resolutionTimes.push(24);
        }
      });
      if (rTime) {
        if (resolutionTimes.length > 0) {
          const avgHours = resolutionTimes.reduce((a,b) => a+b, 0) / resolutionTimes.length;
          rTime.innerText = Math.round(avgHours) + 'h';
        } else {
          rTime.innerText = '24h';
        }
      }

      // Update print preview metadata
      const printDateEl = document.getElementById('printReportDateVal');
      if (printDateEl) printDateEl.innerText = new Date().toLocaleString();
      
      const printFiltersEl = document.getElementById('printReportFiltersVal');
      if (printFiltersEl) {
        const branchVal = document.getElementById('reportBranchFilter')?.value || 'all';
        const deptVal = document.getElementById('reportDeptFilter')?.value || 'all';
        const statusVal = document.getElementById('reportStatusFilter')?.value || 'all';
        const actionVal = document.getElementById('reportActionFilter')?.value || 'all';
        const startVal = document.getElementById('reportStartDate')?.value || '';
        const endVal = document.getElementById('reportEndDate')?.value || '';
        
        let filterParts = [];
        if (branchVal !== 'all') filterParts.push(`Branch: ${branchVal}`);
        if (deptVal !== 'all') filterParts.push(`Dept: ${deptVal}`);
        if (statusVal !== 'all') filterParts.push(`Status: ${statusVal}`);
        if (actionVal !== 'all') filterParts.push(`Action: ${actionVal}`);
        if (startVal || endVal) filterParts.push(`Date: ${startVal || 'Start'} to ${endVal || 'End'}`);
        
        printFiltersEl.innerText = filterParts.length > 0 ? filterParts.join(' | ') : 'All Requests';
      }

      // Grouped Data Computations
      const statusCounts = { Pending: 0, Approved: 0, Rejected: 0, Done: 0 };
      const branchCounts = {};
      const deptActionCounts = {};

      data.forEach(r => {
        if (statusCounts[r.status] !== undefined) {
          statusCounts[r.status]++;
        }
        
        const b = (r.branch || 'Unknown').trim();
        if (b !== '') {
          branchCounts[b] = (branchCounts[b] || 0) + 1;
        }
        
        // Target department (strip details in brackets for general metrics)
        const rawDept = r.target_dept || 'Unknown';
        const dept = rawDept.split('(')[0].trim().split(',')[0].trim();
        if (!deptActionCounts[dept]) {
          deptActionCounts[dept] = { Edit: 0, Void: 0, OnHand: 0, Total: 0 };
        }
        deptActionCounts[dept].Total++;
        if (r.action_type === 'Edit') deptActionCounts[dept].Edit++;
        else if (r.action_type === 'Void') deptActionCounts[dept].Void++;
        else if (r.action_type === 'On Hand Delete') deptActionCounts[dept].OnHand++;
      });

      // Render Status Distribution Breakdown
      const statusContainer = document.getElementById('report-status-breakdown');
      if (statusContainer) {
        let statusHtml = '';
        const total = data.length || 1;
        const statuses = [
          { key: 'Pending', color: '#D97706' },
          { key: 'Approved', color: '#059669' },
          { key: 'Rejected', color: '#DC2626' },
          { key: 'Done', color: '#2563EB' }
        ];
        statuses.forEach(s => {
          const count = statusCounts[s.key];
          const pct = Math.round((count / total) * 100);
          statusHtml += `
            <div class="report-progress-bar-row">
              <div class="report-progress-bar-label"><span class="status-badge status-${s.key}">${s.key}</span></div>
              <div class="report-progress-bar-track">
                <div class="report-progress-bar-fill" style="width: ${pct}%; background-color: ${s.color};"></div>
              </div>
              <div class="report-progress-bar-val"><strong>${count}</strong> (${pct}%)</div>
            </div>
          `;
        });
        statusContainer.innerHTML = statusHtml;
      }

      // Render Complete Branch Request Rankings (ALL branches with >0 requests ranked)
      const branchContainer = document.getElementById('report-branch-breakdown');
      if (branchContainer) {
        // Collect and rank ALL branches that have >0 requests (Strictly omit 0-request branches)
        const rankedBranches = Object.entries(branchCounts)
          .filter(([bName, bCount]) => bCount > 0)
          .sort((a, b) => b[1] - a[1]);
        
        const countBadge = document.getElementById('rankedBranchesCountBadge');
        if (countBadge) countBadge.innerText = `${rankedBranches.length} Active Branches`;

        if (rankedBranches.length === 0) {
          branchContainer.innerHTML = '<p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:20px;">No requests recorded for any branch matching the active filters.</p>';
        } else {
          const maxVal = rankedBranches[0][1] || 1;
          const totalReqs = data.length || 1;
          
          let branchHtml = '<div class="branch-rank-grid">';
          
          rankedBranches.forEach(([bName, bCount], index) => {
            const rank = index + 1;
            const pct = Math.round((bCount / maxVal) * 100);
            const sharePct = ((bCount / totalReqs) * 100).toFixed(1);
            
            // Sub-breakdown for this branch
            let bEdit = 0, bVoid = 0, bOnHand = 0;
            data.forEach(r => {
              if (r.branch === bName) {
                if (r.action_type === 'Edit') bEdit++;
                else if (r.action_type === 'Void') bVoid++;
                else if (r.action_type === 'On Hand Delete') bOnHand++;
              }
            });

            let medalClass = 'rank-regular';
            let medalIcon = `#${rank}`;
            if (rank === 1) { medalClass = 'rank-gold'; medalIcon = '🥇 #1'; }
            else if (rank === 2) { medalClass = 'rank-silver'; medalIcon = '🥈 #2'; }
            else if (rank === 3) { medalClass = 'rank-bronze'; medalIcon = '🥉 #3'; }

            branchHtml += `
              <div class="branch-rank-item">
                <div class="branch-rank-header">
                  <span class="rank-badge ${medalClass}">${medalIcon}</span>
                  <span class="branch-name-text" title="${escapeHtml(bName)}">${escapeHtml(bName)}</span>
                  <span class="branch-total-count"><strong>${bCount}</strong> req (${sharePct}%)</span>
                </div>
                <div class="report-progress-bar-track" style="margin: 8px 0 6px 0;">
                  <div class="report-progress-bar-fill" style="width: ${pct}%;"></div>
                </div>
                <div class="branch-tag-row">
                  <span class="branch-tag edit">Edit: ${bEdit}</span>
                  <span class="branch-tag void">Void: ${bVoid}</span>
                  <span class="branch-tag onhand">On Hand: ${bOnHand}</span>
                </div>
              </div>
            `;
          });
          
          branchHtml += '</div>';

          // Comprehensive Ranked Branches Summary Table
          branchHtml += `
            <div style="margin-top: 15px; border-top: 1px solid var(--border); padding-top: 12px;">
              <h4 style="margin: 0 0 8px 0; font-size: 0.85rem; color: var(--primary); display:flex; align-items:center; gap:6px;">
                <i class="material-icons-round" style="font-size:16px;">format_list_numbered</i> Ranked Branch Summary Table
              </h4>
              <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table style="width:100%; font-size:0.8rem;">
                  <thead>
                    <tr>
                      <th style="width:60px;">Rank</th>
                      <th>Branch Name</th>
                      <th style="text-align:center;">Total Requests</th>
                      <th style="text-align:center;">Edit Requests</th>
                      <th style="text-align:center;">Void Requests</th>
                      <th style="text-align:center;">On Hand Deletes</th>
                      <th style="text-align:center;">% Volume Share</th>
                    </tr>
                  </thead>
                  <tbody>
          `;

          rankedBranches.forEach(([bName, bCount], index) => {
            const rank = index + 1;
            const sharePct = ((bCount / totalReqs) * 100).toFixed(1);
            let bEdit = 0, bVoid = 0, bOnHand = 0;
            data.forEach(r => {
              if (r.branch === bName) {
                if (r.action_type === 'Edit') bEdit++;
                else if (r.action_type === 'Void') bVoid++;
                else if (r.action_type === 'On Hand Delete') bOnHand++;
              }
            });
            branchHtml += `
              <tr>
                <td><strong>#${rank}</strong></td>
                <td><strong>${escapeHtml(bName)}</strong></td>
                <td style="text-align:center;"><span class="vouchers-badge" style="font-size:0.75rem;">${bCount}</span></td>
                <td style="text-align:center;">${bEdit}</td>
                <td style="text-align:center;">${bVoid}</td>
                <td style="text-align:center;">${bOnHand}</td>
                <td style="text-align:center;"><strong>${sharePct}%</strong></td>
              </tr>
            `;
          });

          branchHtml += `
                  </tbody>
                </table>
              </div>
            </div>
          `;

          branchContainer.innerHTML = branchHtml;
        }
      }

      // Render Department Breakdown Table
      const deptContainer = document.getElementById('report-dept-breakdown');
      if (deptContainer) {
        const deptEntries = Object.entries(deptActionCounts).sort((a, b) => b[1].Total - a[1].Total);
        if (deptEntries.length === 0) {
          deptContainer.innerHTML = '<p style="color:var(--text-muted); font-size:0.8rem; text-align:center; padding:15px;">No department metrics available</p>';
        } else {
          let deptHtml = '<table><thead><tr><th>Target Department</th><th>Edit Requests</th><th>Void Requests</th><th>On Hand Deletes</th><th>Total Volume</th></tr></thead><tbody>';
          deptEntries.forEach(([dName, counts]) => {
            deptHtml += `
              <tr>
                <td><strong>${escapeHtml(dName)}</strong></td>
                <td>${counts.Edit}</td>
                <td>${counts.Void}</td>
                <td>${counts.OnHand}</td>
                <td><strong>${counts.Total}</strong></td>
              </tr>
            `;
          });
          deptHtml += '</tbody></table>';
          deptContainer.innerHTML = deptHtml;
        }
      }

      const viewMode = document.getElementById('reportViewMode')?.value || 'both';
      const analyticsSec = document.getElementById('reports-analytics-section');
      const summarySec = document.querySelector('.report-summary');
      const detailContainer = document.getElementById('reports-table-container');

      if (viewMode === 'detail') {
        if (analyticsSec) analyticsSec.classList.add('hidden');
        if (summarySec) summarySec.classList.add('hidden');
        if (detailContainer) detailContainer.classList.remove('hidden');
      } else if (viewMode === 'summary') {
        if (analyticsSec) analyticsSec.classList.remove('hidden');
        if (summarySec) summarySec.classList.remove('hidden');
        if (detailContainer) detailContainer.classList.add('hidden');
      } else { // 'both'
        if (analyticsSec) analyticsSec.classList.remove('hidden');
        if (summarySec) summarySec.classList.remove('hidden');
        if (detailContainer) detailContainer.classList.remove('hidden');
      }

      currentDetailReportData = Array.isArray(data) ? [...data] : [];
      initAndRenderDetailViewTable(container);
    }

    let currentDetailReportData = [];
    let detailFilteredRecords = [];
    let detailSortCol = 'timestamp';
    let detailSortDir = 'desc';
    let detailCurrentPage = 1;
    let detailPageSize = 50;
    let detailQuickSearchTerm = '';
    let detailFilterVoucherType = 'all';
    let detailFilterEditType = 'all';
    let detailFilterAction = 'all';
    let detailFilterStatus = 'all';
    let detailFilterShift = 'all';
    let detailFilterBranch = 'all';

    function getSortColumnLabel(col) {
      const labels = {
        'request_id': 'ID',
        'timestamp': 'Date',
        'branch': 'Branch',
        'user_name': 'User',
        'store_user': 'Store User',
        'action_type': 'Action',
        'voucher_code': 'Voucher Code',
        'voucher_type': 'Voucher Type',
        'edit_type': 'Edit Type',
        'shift': 'Shift',
        'incorrect_date': 'Incorrect Date',
        'correct_date': 'Correct Date',
        'target_dept': 'Target Dept',
        'reason': 'Reason',
        'status': 'Status',
        'approver': 'Approver'
      };
      return labels[col] || col;
    }

    function getDetailSortHeaderHtml(col, label, minWidth) {
      const isActive = (detailSortCol === col);
      let iconName = 'unfold_more';
      let iconStyle = 'opacity:0.4;';
      let activeClass = '';

      if (isActive) {
        iconName = detailSortDir === 'asc' ? 'arrow_upward' : 'arrow_downward';
        iconStyle = 'opacity:1; font-weight:bold; color:var(--primary);';
        activeClass = 'sort-active';
      }

      return `<th class="sortable ${activeClass}" style="min-width:${minWidth};" onclick="handleDetailSort('${col}')" title="Click to sort by ${label} (${isActive && detailSortDir === 'asc' ? 'Descending' : 'Ascending'})">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
          <span>${label}</span>
          <i class="material-icons-round detail-sort-icon" style="${iconStyle}">${iconName}</i>
        </div>
      </th>`;
    }

    function handleDetailSort(col) {
      if (detailSortCol === col) {
        detailSortDir = detailSortDir === 'asc' ? 'desc' : 'asc';
      } else {
        detailSortCol = col;
        detailSortDir = (col === 'timestamp' || col === 'request_id') ? 'desc' : 'asc';
      }
      initAndRenderDetailViewTable();
    }

    function sortDetailRecords() {
      const col = detailSortCol;
      const dir = detailSortDir;

      detailFilteredRecords.sort((a, b) => {
        let valA = a[col] ?? '';
        let valB = b[col] ?? '';

        if (col === 'timestamp' || col === 'correct_date' || col === 'incorrect_date') {
          const tA = valA ? new Date(valA).getTime() : 0;
          const tB = valB ? new Date(valB).getTime() : 0;
          return dir === 'asc' ? tA - tB : tB - tA;
        }

        if (typeof valA === 'number' && typeof valB === 'number') {
          return dir === 'asc' ? valA - valB : valB - valA;
        }

        valA = String(valA).toLowerCase().trim();
        valB = String(valB).toLowerCase().trim();

        if (valA < valB) return dir === 'asc' ? -1 : 1;
        if (valA > valB) return dir === 'asc' ? 1 : -1;
        return 0;
      });
    }

    function onDetailTableSearchInput(val) {
      detailQuickSearchTerm = val;
      detailCurrentPage = 1;
      initAndRenderDetailViewTable();
    }

    function clearDetailSearchInput() {
      detailQuickSearchTerm = '';
      detailCurrentPage = 1;
      initAndRenderDetailViewTable();
    }

    function onDetailFilterChange(filterKey, val) {
      if (filterKey === 'voucher_type') detailFilterVoucherType = val;
      else if (filterKey === 'edit_type') detailFilterEditType = val;
      else if (filterKey === 'action') detailFilterAction = val;
      else if (filterKey === 'status') detailFilterStatus = val;
      else if (filterKey === 'branch') detailFilterBranch = val;
      else if (filterKey === 'shift') detailFilterShift = val;

      detailCurrentPage = 1;
      initAndRenderDetailViewTable();
    }

    function onDetailPageSizeChange(val) {
      detailPageSize = val === 'all' ? 'all' : parseInt(val, 10);
      detailCurrentPage = 1;
      initAndRenderDetailViewTable();
    }

    function changeDetailPage(newPage) {
      detailCurrentPage = newPage;
      initAndRenderDetailViewTable();
      const container = document.getElementById('reports-table-container');
      if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }

    function resetDetailTableFilters() {
      detailQuickSearchTerm = '';
      detailFilterVoucherType = 'all';
      detailFilterEditType = 'all';
      detailFilterAction = 'all';
      detailFilterStatus = 'all';
      detailFilterBranch = 'all';
      detailFilterShift = 'all';
      detailSortCol = 'timestamp';
      detailSortDir = 'desc';
      detailCurrentPage = 1;
      initAndRenderDetailViewTable();
    }

    function initAndRenderDetailViewTable(container) {
      if (!container) container = document.getElementById('reports-table-container');
      if (!container) return;

      if (!currentDetailReportData || currentDetailReportData.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-muted);"><i class="material-icons-round" style="font-size:36px; display:block; margin-bottom:8px; opacity:0.5;">find_in_page</i>No requests found matching active report criteria.</div>';
        return;
      }

      // Collect distinct values for dropdown options
      const vTypes = Array.from(new Set(currentDetailReportData.map(r => r.voucher_type).filter(Boolean))).sort();
      const eTypes = Array.from(new Set(currentDetailReportData.map(r => r.edit_type).filter(Boolean))).sort();
      const branches = Array.from(new Set(currentDetailReportData.map(r => cleanBranchName(r.branch)).filter(Boolean))).sort();

      // Filter detail records
      const search = detailQuickSearchTerm.toLowerCase().trim();
      detailFilteredRecords = currentDetailReportData.filter(r => {
        if (detailFilterVoucherType !== 'all' && (r.voucher_type || '') !== detailFilterVoucherType) return false;
        if (detailFilterEditType !== 'all' && (r.edit_type || '') !== detailFilterEditType) return false;
        if (detailFilterAction !== 'all' && (r.action_type || '') !== detailFilterAction) return false;
        if (detailFilterStatus !== 'all' && (r.status || '') !== detailFilterStatus) return false;
        if (detailFilterBranch !== 'all' && cleanBranchName(r.branch || '') !== cleanBranchName(detailFilterBranch)) return false;
        if (detailFilterShift !== 'all') {
          const s = (r.shift || '').toLowerCase();
          if (!s.includes(detailFilterShift.toLowerCase())) return false;
        }

        if (search) {
          const hay = [
            r.request_id,
            r.branch,
            r.user_name,
            r.store_user,
            r.voucher_code,
            r.voucher_type,
            r.edit_type,
            r.action_type,
            r.target_dept,
            r.reason,
            r.status,
            r.approver,
            r.correct_date,
            r.incorrect_date,
            r.shift
          ].filter(Boolean).join(' ').toLowerCase();

          if (!hay.includes(search)) return false;
        }
        return true;
      });

      // Sort
      sortDetailRecords();

      // Calculate pagination
      const totalCount = detailFilteredRecords.length;
      let pageSize = detailPageSize === 'all' ? totalCount : parseInt(detailPageSize, 10);
      if (isNaN(pageSize) || pageSize <= 0) pageSize = 50;

      const totalPages = Math.max(1, Math.ceil(totalCount / (pageSize || 1)));
      if (detailCurrentPage > totalPages) detailCurrentPage = totalPages;
      if (detailCurrentPage < 1) detailCurrentPage = 1;

      const startIdx = (detailCurrentPage - 1) * pageSize;
      const endIdx = detailPageSize === 'all' ? totalCount : Math.min(startIdx + pageSize, totalCount);
      const pageRecords = detailPageSize === 'all' ? detailFilteredRecords : detailFilteredRecords.slice(startIdx, endIdx);

      // Build Toolbar HTML
      const isFiltered = (detailFilteredRecords.length !== currentDetailReportData.length);
      const badgeText = isFiltered 
        ? `Showing ${totalCount} of ${currentDetailReportData.length} Requests (Filtered)` 
        : `Showing ${totalCount} Requests`;

      const sortLabel = getSortColumnLabel(detailSortCol);
      const sortDirText = detailSortDir === 'asc' ? 'Asc' : 'Desc';

      let vTypeOpts = `<option value="all">All Voucher Types (${vTypes.length})</option>`;
      vTypes.forEach(vt => {
        vTypeOpts += `<option value="${escapeHtml(vt)}" ${detailFilterVoucherType === vt ? 'selected' : ''}>${escapeHtml(vt)}</option>`;
      });

      let eTypeOpts = `<option value="all">All Edit Types (${eTypes.length})</option>`;
      eTypes.forEach(et => {
        eTypeOpts += `<option value="${escapeHtml(et)}" ${detailFilterEditType === et ? 'selected' : ''}>${escapeHtml(et)}</option>`;
      });

      let branchOpts = `<option value="all">All Branches (${branches.length})</option>`;
      branches.forEach(b => {
        branchOpts += `<option value="${escapeHtml(b)}" ${detailFilterBranch === b ? 'selected' : ''}>${escapeHtml(b)}</option>`;
      });

      let html = `
        <div class="detail-toolbar">
          <!-- Top Row: Title, Badges & Actions -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
            <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
              <h3 style="margin:0; font-size:1rem; color:var(--primary); display:flex; align-items:center; gap:6px;">
                <i class="material-icons-round" style="font-size:20px;">table_rows</i> Detail View Table
              </h3>
              <span class="dept-badge" style="background:var(--primary); color:#fff; font-size:0.75rem; padding: 4px 10px; border-radius: 12px;">${badgeText}</span>
              <span class="dept-badge" style="background:#E0F2FE; color:#0369A1; font-size:0.75rem; padding: 4px 10px; border-radius: 12px; display:inline-flex; align-items:center; gap:4px;" title="Click on table headers to sort">
                <i class="material-icons-round" style="font-size:14px;">sort</i> Sorted by ${sortLabel} (${sortDirText})
              </span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <button type="button" class="btn-sm btn-secondary" onclick="resetDetailTableFilters()" style="display:inline-flex; align-items:center; gap:4px;" title="Reset detail table filters and sort">
                <i class="material-icons-round" style="font-size:14px;">restart_alt</i> Reset Table
              </button>
              <button type="button" class="btn-sm btn-secondary" onclick="exportFilteredToCSV()" style="display:inline-flex; align-items:center; gap:4px;" title="Export current detail records to CSV">
                <i class="material-icons-round" style="font-size:14px;">download</i> Export CSV
              </button>
            </div>
          </div>

          <!-- Bottom Row: Filters Grid -->
          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:10px; align-items:flex-end;">
            <!-- Live Search -->
            <div style="grid-column: span 2; min-width: 230px;">
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">search</i> Quick Search Table
              </label>
              <div style="position:relative;">
                <input type="text" id="detailQuickSearch" placeholder="Search ID, Branch, User, Code, Reason..." value="${escapeHtml(detailQuickSearchTerm)}" oninput="onDetailTableSearchInput(this.value)" style="width:100%; padding: 8px 28px 8px 10px; font-size: 0.85rem; border-radius: 6px; border: 1px solid var(--border);">
                ${detailQuickSearchTerm ? `<i class="material-icons-round" onclick="clearDetailSearchInput()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--text-muted); font-size:16px;" title="Clear search">cancel</i>` : ''}
              </div>
            </div>

            <!-- Voucher Type -->
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">receipt</i> Voucher Type
              </label>
              <select onchange="onDetailFilterChange('voucher_type', this.value)" style="width:100%; padding:8px 10px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border);">
                ${vTypeOpts}
              </select>
            </div>

            <!-- Edit Type -->
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">tune</i> Edit Type
              </label>
              <select onchange="onDetailFilterChange('edit_type', this.value)" style="width:100%; padding:8px 10px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border);">
                ${eTypeOpts}
              </select>
            </div>

            <!-- Action -->
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">touch_app</i> Action
              </label>
              <select onchange="onDetailFilterChange('action', this.value)" style="width:100%; padding:8px 10px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border);">
                <option value="all">All Actions</option>
                <option value="Edit" ${detailFilterAction === 'Edit' ? 'selected' : ''}>Edit</option>
                <option value="Void" ${detailFilterAction === 'Void' ? 'selected' : ''}>Void</option>
                <option value="On Hand Delete" ${detailFilterAction === 'On Hand Delete' ? 'selected' : ''}>On Hand Delete</option>
              </select>
            </div>

            <!-- Status -->
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">flag</i> Status
              </label>
              <select onchange="onDetailFilterChange('status', this.value)" style="width:100%; padding:8px 10px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border);">
                <option value="all">All Statuses</option>
                <option value="Pending" ${detailFilterStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                <option value="Approved" ${detailFilterStatus === 'Approved' ? 'selected' : ''}>Approved</option>
                <option value="Rejected" ${detailFilterStatus === 'Rejected' ? 'selected' : ''}>Rejected</option>
                <option value="Done" ${detailFilterStatus === 'Done' ? 'selected' : ''}>Done</option>
              </select>
            </div>

            <!-- Branch -->
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">storefront</i> Branch
              </label>
              <select onchange="onDetailFilterChange('branch', this.value)" style="width:100%; padding:8px 10px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border);">
                ${branchOpts}
              </select>
            </div>

            <!-- Rows Per Page -->
            <div>
              <label style="font-size:0.75rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">
                <i class="material-icons-round" style="font-size:13px; vertical-align:middle;">view_agenda</i> Show Records
              </label>
              <select onchange="onDetailPageSizeChange(this.value)" style="width:100%; padding:8px 10px; font-size:0.85rem; border-radius:6px; border:1px solid var(--border);">
                <option value="25" ${detailPageSize == 25 ? 'selected' : ''}>25 per page</option>
                <option value="50" ${detailPageSize == 50 ? 'selected' : ''}>50 per page</option>
                <option value="100" ${detailPageSize == 100 ? 'selected' : ''}>100 per page</option>
                <option value="250" ${detailPageSize == 250 ? 'selected' : ''}>250 per page</option>
                <option value="all" ${detailPageSize === 'all' ? 'selected' : ''}>Show All (${totalCount})</option>
              </select>
            </div>
          </div>
        </div>

        <div class="table-responsive" style="max-height: 540px; overflow-y: auto; border: 1px solid var(--border); border-top-left-radius: 8px; border-top-right-radius: 8px;">
          <table class="detail-view-table">
            <thead>
              <tr>
                ${getDetailSortHeaderHtml('request_id', 'ID', '130px')}
                ${getDetailSortHeaderHtml('timestamp', 'DATE', '105px')}
                ${getDetailSortHeaderHtml('branch', 'BRANCH', '140px')}
                ${getDetailSortHeaderHtml('user_name', 'USER', '125px')}
                ${getDetailSortHeaderHtml('store_user', 'STORE USER', '115px')}
                ${getDetailSortHeaderHtml('action_type', 'ACTION', '90px')}
                ${getDetailSortHeaderHtml('voucher_code', 'VOUCHER CODE', '120px')}
                ${getDetailSortHeaderHtml('voucher_type', 'VOUCHER TYPE', '140px')}
                ${getDetailSortHeaderHtml('edit_type', 'EDIT TYPE', '130px')}
                ${getDetailSortHeaderHtml('shift', 'SHIFT', '80px')}
                ${getDetailSortHeaderHtml('incorrect_date', 'INCORRECT DATE', '110px')}
                ${getDetailSortHeaderHtml('correct_date', 'CORRECT DATE', '110px')}
                ${getDetailSortHeaderHtml('target_dept', 'TARGET DEPT', '115px')}
                ${getDetailSortHeaderHtml('reason', 'REASON', '140px')}
                ${getDetailSortHeaderHtml('status', 'STATUS', '95px')}
                ${getDetailSortHeaderHtml('approver', 'APPROVER', '115px')}
              </tr>
            </thead>
            <tbody>
      `;

      if (pageRecords.length === 0) {
        html += `<tr><td colspan="16" style="text-align:center; padding:35px; color:var(--text-muted);"><i class="material-icons-round" style="font-size:32px; display:block; margin-bottom:6px; opacity:0.5;">filter_alt_off</i>No records match the current detail filters. Click "Reset Table" to view all records.</td></tr>`;
      } else {
        pageRecords.forEach(r => {
          const isOnHand = r.action_type === 'On Hand Delete';
          const statusClass = r.status === 'Pending' ? 'status-Pending' : (r.status === 'Approved' ? 'status-Approved' : (r.status === 'Done' ? 'status-Done' : 'status-Rejected'));

          let dateDisplay = '<span class="req-muted-dash">-</span>';
          if (r.timestamp) {
            const dtPart = r.timestamp.substring(0, 10);
            const tmPart = r.timestamp.substring(11, 16);
            dateDisplay = `<div><span style="font-weight:600;">${escapeHtml(dtPart)}</span><br><span style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(tmPart)}</span></div>`;
          }

          const storeUserDisplay = (isOnHand && r.store_user) ? `<strong>${escapeHtml(r.store_user)}</strong>` : '<span class="req-muted-dash">-</span>';
          const vCodeDisplay = (!isOnHand && r.voucher_code) ? `<span style="font-family:monospace; font-weight:600;">${escapeHtml(r.voucher_code)}</span>` : '<span class="req-muted-dash">-</span>';
          const vTypeDisplay = (!isOnHand && r.voucher_type) ? escapeHtml(r.voucher_type) : '<span class="req-muted-dash">-</span>';
          const editTypeDisplay = (!isOnHand && r.edit_type) ? escapeHtml(r.edit_type) : '<span class="req-muted-dash">-</span>';
          const shiftDisplay = isOnHand ? (r.shift ? escapeHtml(r.shift) : 'shft 1') : '<span class="req-muted-dash">-</span>';
          const incDateDisplay = (!isOnHand && r.incorrect_date) ? escapeHtml(r.incorrect_date) : '<span class="req-muted-dash">-</span>';
          const corDateDisplay = r.correct_date ? escapeHtml(r.correct_date) : '<span class="req-muted-dash">-</span>';
          const reasonDisplay = r.reason ? escapeHtml(r.reason) : '<span class="req-muted-dash">-</span>';
          const approverDisplay = r.approver ? escapeHtml(r.approver) : '<span class="req-muted-dash">-</span>';
          
          html += `
            <tr>
              <td><span class="req-id-pill">${escapeHtml(r.request_id)}</span></td>
              <td>${dateDisplay}</td>
              <td><strong>${escapeHtml(cleanBranchName(r.branch))}</strong></td>
              <td>${escapeHtml(r.user_name)}</td>
              <td>${storeUserDisplay}</td>
              <td><span class="req-action-text">${escapeHtml(r.action_type)}</span></td>
              <td>${vCodeDisplay}</td>
              <td>${vTypeDisplay}</td>
              <td>${editTypeDisplay}</td>
              <td>${shiftDisplay}</td>
              <td>${incDateDisplay}</td>
              <td>${corDateDisplay}</td>
              <td><span class="dept-badge">${escapeHtml(r.target_dept || '-')}</span></td>
              <td>${reasonDisplay}</td>
              <td><span class="status-badge ${statusClass}">${r.status}</span></td>
              <td>${approverDisplay}</td>
            </tr>
          `;
        });
      }

      html += '</tbody></table></div>';

      // Build Pagination Footer HTML
      if (detailPageSize !== 'all' && totalCount > 0) {
        html += `
          <div class="detail-pagination">
            <div style="font-size:0.8rem; color:var(--text-muted);">
              Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalCount}</strong> records
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
              <button type="button" class="btn-page" onclick="changeDetailPage(1)" ${detailCurrentPage === 1 ? 'disabled' : ''} title="First Page">
                <i class="material-icons-round" style="font-size:16px;">first_page</i>
              </button>
              <button type="button" class="btn-page" onclick="changeDetailPage(${detailCurrentPage - 1})" ${detailCurrentPage === 1 ? 'disabled' : ''} title="Previous Page">
                <i class="material-icons-round" style="font-size:16px;">chevron_left</i> Prev
              </button>
              <span style="font-size:0.8rem; font-weight:600; padding:0 8px;">
                Page ${detailCurrentPage} of ${totalPages}
              </span>
              <button type="button" class="btn-page" onclick="changeDetailPage(${detailCurrentPage + 1})" ${detailCurrentPage === totalPages ? 'disabled' : ''} title="Next Page">
                Next <i class="material-icons-round" style="font-size:16px;">chevron_right</i>
              </button>
              <button type="button" class="btn-page" onclick="changeDetailPage(${totalPages})" ${detailCurrentPage === totalPages ? 'disabled' : ''} title="Last Page">
                <i class="material-icons-round" style="font-size:16px;">last_page</i>
              </button>
            </div>
          </div>
        `;
      }

      container.innerHTML = html;
    }

    function applyReportFilters() {
      const branch = document.getElementById('reportBranchFilter')?.value || 'all';
      const user = (document.getElementById('reportUserFilter')?.value || '').toLowerCase().trim();
      const dept = document.getElementById('reportDeptFilter')?.value || 'all';
      const status = document.getElementById('reportStatusFilter')?.value || 'all';
      const actionType = document.getElementById('reportActionFilter')?.value || 'all';
      const startDate = document.getElementById('reportStartDate')?.value;
      const endDate = document.getElementById('reportEndDate')?.value;

      const filtered = allRequests.filter(r => {
        if (startDate && r.timestamp && r.timestamp.substring(0, 10) < startDate) return false;
        if (endDate && r.timestamp && r.timestamp.substring(0, 10) > endDate) return false;
        if (branch !== 'all' && cleanBranchName(r.branch) !== cleanBranchName(branch)) return false;
        if (user && !r.user_name.toLowerCase().includes(user) && !(r.store_user && r.store_user.toLowerCase().includes(user))) return false;
        if (dept !== 'all' && !(r.target_dept && r.target_dept.includes(dept))) return false;
        if (actionType !== 'all' && r.action_type !== actionType) return false;
        if (status !== 'all' && r.status !== status) return false;
        return true;
      });

      renderReportsTable(filtered);
    }

    function clearReportFilters() {
      const sD = document.getElementById('reportStartDate');
      const eD = document.getElementById('reportEndDate');
      const bF = document.getElementById('reportBranchFilter');
      const uF = document.getElementById('reportUserFilter');
      const dF = document.getElementById('reportDeptFilter');
      const aF = document.getElementById('reportActionFilter');
      const sF = document.getElementById('reportStatusFilter');
      const vM = document.getElementById('reportViewMode');
      if (sD) sD.value = '';
      if (eD) eD.value = '';
      if (bF) bF.value = 'all';
      if (uF) uF.value = '';
      if (dF) dF.value = 'all';
      if (aF) aF.value = 'all';
      if (sF) sF.value = 'all';
      if (vM) vM.value = 'both';
      renderReportsTable(allRequests);
    }

    function exportToCSV() {
      exportDataToCSV(allRequests, 'all_requests.csv');
    }

    function exportFilteredToCSV() {
      if (detailFilteredRecords && detailFilteredRecords.length > 0) {
        exportDataToCSV(detailFilteredRecords, 'detail_view_filtered.csv');
        return;
      }

      const branch = document.getElementById('reportBranchFilter')?.value || 'all';
      const user = (document.getElementById('reportUserFilter')?.value || '').toLowerCase().trim();
      const dept = document.getElementById('reportDeptFilter')?.value || 'all';
      const status = document.getElementById('reportStatusFilter')?.value || 'all';
      const actionType = document.getElementById('reportActionFilter')?.value || 'all';
      const startDate = document.getElementById('reportStartDate')?.value;
      const endDate = document.getElementById('reportEndDate')?.value;

      const filtered = allRequests.filter(r => {
        if (startDate && r.timestamp && r.timestamp.substring(0, 10) < startDate) return false;
        if (endDate && r.timestamp && r.timestamp.substring(0, 10) > endDate) return false;
        if (branch !== 'all' && cleanBranchName(r.branch) !== cleanBranchName(branch)) return false;
        if (user && !r.user_name.toLowerCase().includes(user) && !(r.store_user && r.store_user.toLowerCase().includes(user))) return false;
        if (dept !== 'all' && !(r.target_dept && r.target_dept.includes(dept))) return false;
        if (actionType !== 'all' && r.action_type !== actionType) return false;
        if (status !== 'all' && r.status !== status) return false;
        return true;
      });

      exportDataToCSV(filtered, 'filtered_requests.csv');
    }

    function printReport() {
      window.print();
    }

    function downloadReportPDF() {
      window.print();
    }

    function exportDataToCSV(rows, filename) {
      if (!rows || rows.length === 0) return showToast("Warning", "No data to export", "warning");

      const headers = ["ID", "Date", "Branch", "User", "Store User", "Action", "Voucher Code", "Voucher Type", "Edit Type", "Shift", "Incorrect Date", "Correct Date", "Target Dept", "Reason", "Status", "Approver"];
      const csvRows = [headers.join(",")];

      rows.forEach(r => {
        const row = [
          `"${r.request_id || ''}"`,
          `"${r.timestamp || ''}"`,
          `"${(r.branch || '').replace(/"/g, '""')}"`,
          `"${(r.user_name || '').replace(/"/g, '""')}"`,
          `"${(r.store_user || '').replace(/"/g, '""')}"`,
          `"${r.action_type || ''}"`,
          `"${r.voucher_code || ''}"`,
          `"${(r.voucher_type || '').replace(/"/g, '""')}"`,
          `"${(r.edit_type || '').replace(/"/g, '""')}"`,
          `"${r.shift || ''}"`,
          `"${r.incorrect_date || ''}"`,
          `"${r.correct_date || ''}"`,
          `"${(r.target_dept || '').replace(/"/g, '""')}"`,
          `"${(r.reason || '').replace(/"/g, '""')}"`,
          `"${r.status || ''}"`,
          `"${(r.approver || '').replace(/"/g, '""')}"`
        ];
        csvRows.push(row.join(","));
      });

      const csvContent = "data:text/csv;charset=utf-8," + csvRows.join("\n");
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      showToast("Success", `Exported to ${filename}`, "success");
    }

    function switchLoginPageTab(tab) {
      const tabBm = document.getElementById('page-tab-login-bm');
      const tabDept = document.getElementById('page-tab-login-dept');
      const formBm = document.getElementById('pageBmLoginForm');
      const formDept = document.getElementById('pageDeptLoginForm');

      if (tab === 'branch_manager') {
        if (tabBm) tabBm.classList.add('active');
        if (tabDept) tabDept.classList.remove('active');
        if (formBm) formBm.classList.remove('hidden');
        if (formDept) formDept.classList.add('hidden');
      } else {
        if (tabBm) tabBm.classList.remove('active');
        if (tabDept) tabDept.classList.add('active');
        if (formBm) formBm.classList.add('hidden');
        if (formDept) formDept.classList.remove('hidden');
      }
    }

    function togglePasswordVisibility(inputId, iconEl) {
      const input = document.getElementById(inputId);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        if (iconEl) iconEl.innerText = 'visibility_off';
      } else {
        input.type = 'password';
        if (iconEl) iconEl.innerText = 'visibility';
      }
    }

    function handleAuthClick() {
      if (currentUser) {
        if (confirm("Are you sure you want to sign out?")) {
          showLoader("Signing out...");
          apiCall('logout', 'POST').then(() => {
            currentUser = null;
            showLoginPage();
            updateUserUI();
            showToast("Signed Out", "You have been logged out safely.", "info");
          }).catch(() => {
            currentUser = null;
            showLoginPage();
            updateUserUI();
          }).finally(() => {
            hideLoader();
          });
        }
      } else {
        showLoginPage();
      }
    }

    async function handleBmLoginSubmit(e) {
      e.preventDefault();
      const username = (document.getElementById('pageBmUsername')?.value || document.getElementById('bmLoginUsername')?.value || '').trim();
      const password = document.getElementById('pageBmPassword')?.value || document.getElementById('bmLoginPassword')?.value || '';

      if (!username || !password) {
        return showToast("Error", "Please enter your username and password", "warning");
      }

      showLoader("Authenticating with Company System...");
      try {
        const res = await apiCall('login', 'POST', { type: 'branch_manager', username, password });
        if (res.success) {
          currentUser = { 
            role: res.role, 
            name: res.name, 
            email: res.email, 
            branch: res.branch, 
            branch_code: res.branch_code, 
            position: res.position, 
            department: res.department 
          };
          
          if (document.getElementById('pageBmPassword')) document.getElementById('pageBmPassword').value = '';
          
          hideLoginPage();
          updateUserUI();
          showToast("Success", `Welcome ${res.name} (${res.branch})`, "success");
          switchView('submit');
        } else {
          showToast("Authentication Failed", res.message || "Invalid username or password", "error");
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function closeForcePasswordModal() {
      const forceModal = document.getElementById('forceChangePasswordModal');
      if (forceModal) {
        forceModal.style.display = 'none';
        forceModal.classList.remove('active');
      }
      if (currentUser) {
        currentUser.must_change_password = 0;
        const dismissedKey = 'pwdPromptDismissed_' + (currentUser.id || currentUser.role || currentUser.username);
        localStorage.setItem(dismissedKey, '1');
        try {
          await apiCall('dismissPasswordPrompt', 'POST');
        } catch (e) {
          console.warn('Could not dismiss password prompt:', e);
        }
      }
    }

    async function handleDeptLoginSubmit(e) {
      if (e && e.preventDefault) e.preventDefault();
      const role = (document.getElementById('pageLoginRole')?.value || document.getElementById('pageDeptUsername')?.value || document.getElementById('loginRole')?.value || 'Admin').trim();
      const pwdInput = document.getElementById('pageDeptPassword');
      const password = (pwdInput?.value || document.getElementById('loginPassword')?.value || '').trim();

      if (!role || !password) {
        return showToast("Error", "Please select your department and enter your password", "warning");
      }

      showLoader("Signing in as " + role + "...");
      try {
        const res = await apiCall('login', 'POST', { type: 'department', role: role, username: role, password: password });
        if (res.success) {
          currentUser = { 
            id: res.id,
            role: res.role, 
            name: res.name, 
            username: res.username || role, 
            email: res.email || '', 
            department: res.department || res.role,
            must_change_password: res.requirePasswordChange ? 1 : 0
          };
          
          if (pwdInput) pwdInput.value = '';
          if (document.getElementById('loginPassword')) document.getElementById('loginPassword').value = '';

          hideLoginPage();
          updateUserUI();

          // Force password prompt removed - allow direct access
          if (false && res.requirePasswordChange && !alreadyDismissed) {
            localStorage.setItem(dismissedKey, '1'); // Mark immediately so it NEVER prompts again on future logins
            const forceModal = document.getElementById('forceChangePasswordModal');
            if (forceModal) {
              forceModal.style.display = 'flex';
              forceModal.classList.add('active');
            }
            showToast("Setup Required", `Welcome ${res.name}! Please set a new password for your account.`, "warning");
          } else {
            showToast("Success", `Welcome ${res.name} (${res.role})`, "success");
          }

          switchView('dashboard');
        } else {
          showToast("Error", res.message || "Invalid credentials", "error");
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function handleForceChangePasswordSubmit(e) {
      if (e && e.preventDefault) e.preventDefault();
      const newPassword = (document.getElementById('forceNewPassword')?.value || '').trim();
      const confirmPassword = (document.getElementById('forceConfirmPassword')?.value || '').trim();

      if (!newPassword || !confirmPassword) {
        return showToast("Validation", "Please enter and confirm your new password", "warning");
      }
      if (newPassword.length < 4) {
        return showToast("Validation", "Password must be at least 4 characters long", "warning");
      }
      if (newPassword === 'admin123') {
        return showToast("Validation", "Please choose a different password from the default 'admin123'", "warning");
      }
      if (newPassword !== confirmPassword) {
        return showToast("Validation", "Passwords do not match", "error");
      }

      showLoader("Saving new password...");
      try {
        const res = await apiCall('changePassword', 'POST', {
          currentPassword: 'admin123',
          newPassword,
          confirmPassword
        });

        if (res.success) {
          if (currentUser) {
            currentUser.must_change_password = 0;
            const dismissedKey = 'pwdPromptDismissed_' + (currentUser.id || currentUser.role || currentUser.username);
            localStorage.setItem(dismissedKey, '1');
          }
          const forceModal = document.getElementById('forceChangePasswordModal');
          if (forceModal) {
            forceModal.style.display = 'none';
            forceModal.classList.remove('active');
          }
          if (document.getElementById('forceNewPassword')) document.getElementById('forceNewPassword').value = '';
          if (document.getElementById('forceConfirmPassword')) document.getElementById('forceConfirmPassword').value = '';

          showToast("Password Updated", "Your password has been saved successfully!", "success");
          hideLoginPage();
          updateUserUI();
          switchView('dashboard');
        } else {
          showToast("Error", res.message || "Failed to update password", "error");
        }
      } catch (err) {
        showToast("Error", err.message || "Could not update password", "error");
      } finally {
        hideLoader();
      }
    }

    function updateUserUI() {
      const avatar = document.getElementById('user-avatar-text');
      const nameEl = document.getElementById('user-display-name');
      const roleEl = document.getElementById('user-display-role');
      const authBtnText = document.getElementById('auth-btn-text');
      const pwdBtn = document.getElementById('pwdBtn');

      const navSubmit = document.getElementById('nav-submit');
      const navDashboard = document.getElementById('nav-dashboard');
      const navView = document.getElementById('nav-view');
      const navReports = document.getElementById('nav-reports');
      const navLogs = document.getElementById('nav-logs');
      const navSettings = document.getElementById('nav-settings');

      const voucherBanner = document.getElementById('voucherRequesterBanner');
      const onhandBanner = document.getElementById('onhandRequesterBanner');
      const branchDashboardBanner = document.getElementById('branchDashboardBanner');

      const branchInput = document.getElementById('branchInput');
      const branchCode = document.getElementById('branchCode');
      const onHandBranchInput = document.getElementById('onHandBranchInput');
      const onHandBranchCode = document.getElementById('onHandBranchCode');
      const voucherUsername = document.getElementById('voucherUsername');
      const onHandUsername = document.getElementById('onHandUsername');

      if (currentUser) {
        hideLoginPage();
        if (avatar) avatar.innerText = (currentUser.name || 'U').charAt(0).toUpperCase();
        if (nameEl) nameEl.innerText = currentUser.name || 'User';
        if (authBtnText) authBtnText.innerText = "Logout";

        // Navigation elements for all logged-in users
        if (navSubmit) navSubmit.classList.remove('hidden');
        if (navDashboard) navDashboard.classList.remove('hidden'); // Branch managers have dashboard access!
        if (navView) navView.classList.remove('hidden');

        if (currentUser.role === 'Requester') {
          const pos = currentUser.position || 'Branch Manager';
          const br = cleanBranchName(currentUser.branch || '');
          if (roleEl) roleEl.innerText = `${pos} - ${br}`;
          if (pwdBtn) pwdBtn.classList.add('hidden'); // managed via company system
          if (navReports) navReports.classList.add('hidden');
          if (navLogs) navLogs.classList.add('hidden');
          if (navSettings) navSettings.classList.add('hidden');

          // Branch Dashboard Overview Banner
          if (branchDashboardBanner) {
            branchDashboardBanner.innerHTML = `<i class="material-icons-round" style="font-size:22px; color:#D97706;">storefront</i> <span>Branch Dashboard: <strong>${escapeHtml(br)}</strong> — Logged in as: <strong>${escapeHtml(currentUser.name)}</strong> (<em>${escapeHtml(pos)}</em>)</span>`;
            branchDashboardBanner.classList.remove('hidden');
          }

          // Auto-populate branch and username with full flexibility to edit/change
          const bannerHtml = `<i class="material-icons-round" style="font-size:20px; color:#D97706;">storefront</i> <span>Logged in as: <strong>${escapeHtml(currentUser.name)}</strong> (Default Branch: <strong>${escapeHtml(br)}</strong> — <em>${escapeHtml(pos)}</em>)</span>`;
          if (voucherBanner) {
            voucherBanner.innerHTML = bannerHtml;
            voucherBanner.classList.remove('hidden');
          }
          if (onhandBanner) {
            onhandBanner.innerHTML = bannerHtml;
            onhandBanner.classList.remove('hidden');
          }

          if (branchInput) {
            branchInput.value = br;
            branchInput.readOnly = false;
          }
          if (branchCode) branchCode.value = br;
          if (onHandBranchInput) {
            onHandBranchInput.value = br;
            onHandBranchInput.readOnly = false;
          }
          if (onHandBranchCode) onHandBranchCode.value = br;

          if (voucherUsername) {
            voucherUsername.value = currentUser.name;
            voucherUsername.readOnly = false;
          }
          if (onHandUsername) {
            onHandUsername.value = currentUser.name;
            onHandUsername.readOnly = false;
          }

          // Preset branch filter in View Requests with full flexibility to change
          const vBranchFilter = document.getElementById('viewBranchFilter');
          if (vBranchFilter && br) {
            vBranchFilter.value = br;
            vBranchFilter.disabled = false;
          }
        } else {
          if (roleEl) roleEl.innerText = `${currentUser.role}${currentUser.department ? ` (${currentUser.department})` : ''}`;
          if (pwdBtn) pwdBtn.classList.remove('hidden');
          if (navReports) navReports.classList.remove('hidden');
          if (currentUser.role === 'Admin') {
            if (navLogs) navLogs.classList.remove('hidden');
            if (navSettings) navSettings.classList.remove('hidden');
          } else {
            if (navLogs) navLogs.classList.add('hidden');
            if (navSettings) navSettings.classList.add('hidden');
          }

          if (branchDashboardBanner) branchDashboardBanner.classList.add('hidden');

          // Clear banners & unlock inputs
          if (voucherBanner) voucherBanner.classList.add('hidden');
          if (onhandBanner) onhandBanner.classList.add('hidden');
          if (branchInput) branchInput.readOnly = false;
          if (onHandBranchInput) onHandBranchInput.readOnly = false;
          if (voucherUsername) voucherUsername.readOnly = false;
          if (onHandUsername) onHandUsername.readOnly = false;

          const vBranchFilter = document.getElementById('viewBranchFilter');
          if (vBranchFilter) vBranchFilter.disabled = false;
        }
      } else {
        showLoginPage();
      }
    }

    function openChangePasswordModal() {
      const modal = document.getElementById('changePasswordModal');
      if (modal) modal.classList.add('active');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.remove('active');
    }

    async function handleChangePasswordSubmit(e) {
      e.preventDefault();
      const currentPassword = document.getElementById('currentPassword').value;
      const newPassword = document.getElementById('newPassword').value;
      const confirmNewPassword = document.getElementById('confirmNewPassword').value;

      if (newPassword !== confirmNewPassword) {
        return showToast("Error", "New passwords do not match", "error");
      }

      showLoader("Updating password...");
      try {
        const res = await apiCall('changePassword', 'POST', { currentPassword, newPassword });
        if (res.success) {
          showToast("Success", "Password updated successfully", "success");
          closeModal('changePasswordModal');
        } else {
          showToast("Error", res.message || "Failed to change password", "error");
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    function renderSettingsChips() {
      renderChipList('voucherTypesChips', systemSettings.voucherTypes || [], 'VoucherType');
      renderChipList('editTypesChips', systemSettings.editTypes || [], 'EditType');
      renderChipList('storeDepartmentsChips', systemSettings.storeDepartments || [], 'StoreDepartment');
      renderDeptChipList();
      renderBranchChipList();
      renderAdminUsersTable();
    }

    function renderChipList(containerId, items, type) {
      const container = document.getElementById(containerId);
      if (!container) return;
      container.innerHTML = '';
      items.forEach(item => {
        const chip = document.createElement('div');
        chip.className = 'chip';
        chip.innerHTML = `<span>${escapeHtml(item)}</span> <span class="chip-remove" onclick="deleteSetting('${type}', '${escapeHtml(item)}')">&times;</span>`;
        container.appendChild(chip);
      });
    }

    function renderDeptChipList() {
      const container = document.getElementById('departmentsChips');
      if (!container || !systemSettings.departments) return;
      container.innerHTML = '';
      systemSettings.departments.forEach((d, index) => {
        const chip = document.createElement('div');
        chip.className = 'chip';
        chip.style.display = 'flex';
        chip.style.alignItems = 'center';
        chip.style.justifyContent = 'space-between';
        chip.style.width = '100%';
        chip.style.margin = '4px 0';
        chip.innerHTML = `
          <span><strong>${escapeHtml(d.name)}</strong> (Telegram Chat ID: <code>${escapeHtml(d.id || 'Not Set')}</code>)</span>
          <div style="margin-left:auto; display:flex; gap:6px; align-items:center;">
            ${d.id ? `<button class="btn-sm btn-secondary" onclick="testTelegramChannel('${escapeHtml(d.name)}', '${escapeHtml(d.id)}')"><i class="material-icons-round" style="font-size:12px">send</i> Test</button>` : ''}
            <span class="chip-remove" onclick="deleteDept(${index})">&times;</span>
          </div>
        `;
        container.appendChild(chip);
      });
    }

    async function testTelegramChannel(deptName, chatId) {
      if (!currentUser || currentUser.role !== 'Admin') return showToast("Error", "Admin only", "error");
      showLoader("Sending test notification...");
      try {
        const res = await apiCall('testTelegram', 'POST', { deptName, chatId });
        if (res.success) {
          showToast("Telegram Test Passed", `Test message sent to ${deptName} Telegram Chat!`, "success");
        } else {
          showToast("Telegram Test Failed", res.message || "Failed to send test message", "error");
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    function renderBranchChipList() {
      const tbody = document.getElementById('branchesTableBody');
      const container = document.getElementById('branchesChips');
      if (!systemSettings.branches) return;

      if (tbody) {
        tbody.innerHTML = '';
        systemSettings.branches.forEach((b, index) => {
          const raw = typeof b === 'object' ? b.name : b;
          const name = cleanBranchName(raw);
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td><strong>${escapeHtml(name)}</strong></td>
            <td><button class="btn-sm btn-danger" onclick="deleteBranch(${index})" title="Delete Branch"><i class="material-icons-round" style="font-size:14px;">delete</i></button></td>
          `;
          tbody.appendChild(tr);
        });
      }

      if (container) {
        container.innerHTML = '';
        systemSettings.branches.forEach((b, index) => {
          const raw = typeof b === 'object' ? b.name : b;
          const name = cleanBranchName(raw);
          const chip = document.createElement('div');
          chip.className = 'chip';
          chip.innerHTML = `<span>${escapeHtml(name)}</span> <span class="chip-remove" onclick="deleteBranch(${index})">&times;</span>`;
          container.appendChild(chip);
        });
      }
    }

    function renderAdminUsersTable() {
      const container = document.getElementById('adminUsersTableContainer');
      if (!container) return;
      if (!adminUsers || adminUsers.length === 0) {
        container.innerHTML = '<p style="font-size:0.8rem; color:var(--text-muted);">Default Roles: Finance Manager (Finance), F&B Manager (F&B), IT Admin (Admin)</p>';
        return;
      }
      let html = '<table><thead><tr><th>Role</th><th>Full Name</th><th>Telegram Chat ID</th><th>Actions</th></tr></thead><tbody>';
      adminUsers.forEach(u => {
        html += `<tr><td><strong>${escapeHtml(u.role)}</strong></td><td>${escapeHtml(u.full_name)}</td><td><code>${escapeHtml(u.chat_id || 'Not Set')}</code></td><td><button class="btn-sm btn-secondary" onclick="adminResetPasswordPrompt('${escapeHtml(u.role)}')"><i class="material-icons-round" style="font-size:12px;">lock_reset</i> Reset Password</button></td></tr>`;
      });
      html += '</tbody></table>';
      container.innerHTML = html;
    }

    async function adminResetPasswordPrompt(role) {
      if (!currentUser || currentUser.role !== 'Admin') return showToast("Error", "Admin only", "error");
      const newPassword = prompt(`Enter new password for ${role}:`);
      if (!newPassword) return;

      showLoader("Resetting password...");
      try {
        const res = await apiCall('adminResetPassword', 'POST', { role, newPassword });
        if (res.success) {
          showToast("Success", `Password for ${role} reset successfully!`, "success");
        } else {
          showToast("Error", res.message || "Failed to reset password", "error");
        }
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function addSetting(type) {
      const inputId = (type === 'VoucherType') ? 'newVoucherType' : ((type === 'StoreDepartment') ? 'newStoreDept' : 'newEditType');
      const input = document.getElementById(inputId);
      if (!input) return;
      const value = input.value.trim();
      if (!value) return;

      showLoader("Adding setting...");
      try {
        await apiCall('addSetting', 'POST', { type, value });
        input.value = '';
        showToast("Success", "Setting Added", "success");
        await loadSettings();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function deleteSetting(type, value) {
      if (!confirm(`Delete ${value}?`)) return;

      showLoader("Deleting setting...");
      try {
        await apiCall('deleteSetting', 'POST', { type, value });
        showToast("Success", "Setting Deleted", "success");
        await loadSettings();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function addDepartment() {
      const name = document.getElementById('newDeptName')?.value.trim();
      const telegramId = document.getElementById('newDeptId')?.value.trim();
      if (!name) return showToast("Error", "Department name required", "error");

      showLoader("Adding department...");
      try {
        await apiCall('addDepartment', 'POST', { name, telegramId });
        document.getElementById('newDeptName').value = '';
        document.getElementById('newDeptId').value = '';
        showToast("Success", "Department Added", "success");
        await loadSettings();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function deleteDept(index) {
      if (!systemSettings.departments[index]) return;
      const name = systemSettings.departments[index].name;
      if (!confirm(`Delete department ${name}?`)) return;

      showLoader("Deleting department...");
      try {
        await apiCall('deleteDepartment', 'POST', { name });
        showToast("Success", "Department Deleted", "success");
        await loadSettings();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function addBranch() {
      const name = document.getElementById('newBranchName')?.value.trim();
      if (!name) return showToast("Error", "Branch Name required", "error");

      showLoader("Adding branch...");
      try {
        await apiCall('addBranch', 'POST', { name });
        if (document.getElementById('newBranchName')) document.getElementById('newBranchName').value = '';
        showToast("Success", "Branch Added", "success");
        await loadSettings();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function deleteBranch(index) {
      if (!systemSettings.branches[index]) return;
      const bObj = systemSettings.branches[index];
      const branch = typeof bObj === 'object' ? bObj.name : bObj;
      if (!confirm(`Delete branch ${branch}?`)) return;

      showLoader("Deleting branch...");
      try {
        await apiCall('deleteBranch', 'POST', { branch });
        showToast("Success", "Branch Deleted", "success");
        await loadSettings();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function addUser() {
      const role = document.getElementById('newUserRole')?.value;
      const name = document.getElementById('newUserName')?.value.trim();
      const password = document.getElementById('newUserPassword')?.value;
      const chatId = document.getElementById('newUserChatId')?.value.trim();

      if (!name || !password) return showToast("Error", "Name and Password required", "error");

      showLoader("Adding user...");
      try {
        await apiCall('addUser', 'POST', { role, name, password, chatId });
        document.getElementById('newUserName').value = '';
        document.getElementById('newUserPassword').value = '';
        document.getElementById('newUserChatId').value = '';
        showToast("Success", "User Added", "success");
        await loadSettings();
        loadDashboard();
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    async function sendTelegramReport(type) {
      if (!currentUser || currentUser.role !== 'Admin') return showToast("Error", "Admin only", "error");

      showLoader(`Sending ${type} report...`);
      try {
        const res = await apiCall('sendReport', 'POST', { type });
        if (res.success) showToast("Success", `${type} Report Sent to Telegram`, "success");
      } catch (err) {
        showToast("Error", err.message, "error");
      } finally {
        hideLoader();
      }
    }

    function showToast(title, message, type = 'info') {
      const container = document.getElementById('toast-container');
      if (!container) return;
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.innerHTML = `<strong>${escapeHtml(title)}:</strong> ${escapeHtml(message)}`;
      container.appendChild(toast);
      setTimeout(() => toast.remove(), 4000);
    }

    function toggleLanguage() {
      currentLang = currentLang === 'en' ? 'am' : 'en';
      const label = document.getElementById('lang-label');
      if (label) label.innerText = currentLang === 'en' ? 'Amharic' : 'English';
      document.body.classList.toggle('lang-am', currentLang === 'am');
      const dict = translations[currentLang];
      document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict && dict[key]) el.innerText = dict[key];
      });
    }

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.addEventListener('click', function(e) {
      const bWrapper = document.getElementById('branchInput')?.closest('.searchable-wrapper');
      if (bWrapper && !bWrapper.contains(e.target)) {
        const bList = document.getElementById('branchList');
        if (bList) { bList.classList.add('hidden'); bList.style.display = 'none'; }
      }
      const ohWrapper = document.getElementById('onHandBranchInput')?.closest('.searchable-wrapper');
      if (ohWrapper && !ohWrapper.contains(e.target)) {
        const ohList = document.getElementById('onHandBranchList');
        if (ohList) { ohList.classList.add('hidden'); ohList.style.display = 'none'; }
      }
    });

    // Auto-load settings from database on startup
    document.addEventListener('DOMContentLoaded', loadSettings);
    loadSettings();
  </script>
</body>
</html>
