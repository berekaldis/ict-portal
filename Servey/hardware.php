<?php
require_once 'config.php';
 $type = 'hardware';
 $technician = getDefaultTechnician($type);
 $technicians = getTechnicians($type);
 $branches = getBranches();
 $checklist = getChecklist($type);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Kaldis Coffee | Hardware Survey</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
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
            --skip-color: #8b5cf6;
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-tertiary: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            --border-color: #e2e8f0;
            --border-focus: #cbd5e1;
            --card-bg: #ffffff;
            --card-shadow: 0 1px 3px rgba(0,0,0,.1), 0 1px 2px rgba(0,0,0,.06);
            --card-hover-shadow: 0 4px 6px rgba(0,0,0,.1), 0 2px 4px rgba(0,0,0,.06);
            --radius-sm: 6px; --radius-md: 8px; --radius-lg: 12px; --radius-xl: 16px; --radius-2xl: 24px;
            --transition-fast: 150ms cubic-bezier(.4,0,.2,1);
            --transition-normal: 300ms cubic-bezier(.4,0,.2,1);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-primary); line-height: 1.6;
            padding: 20px 16px 40px; min-height: 100vh;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: var(--card-bg); border-radius: var(--radius-2xl);
            padding: 24px; text-align: center; margin-bottom: 24px;
            box-shadow: var(--card-shadow); position: relative; overflow: hidden;
        }
        .header::before {
            content:''; position:absolute; top:0; left:0; right:0; height:4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--secondary));
        }
        .logo-img { width:64px; height:64px; border-radius:50%; box-shadow:0 4px 12px rgba(79,70,229,.2); border:3px solid var(--card-bg); }
        h1 { color:var(--text-primary); font-size:1.75rem; font-weight:800; margin-top:12px; letter-spacing:-.5px; }
        .admin-link { margin-top:12px; font-size:.875rem; color:var(--text-secondary); }
        .admin-link a { color:var(--primary); text-decoration:none; font-weight:600; transition:var(--transition-fast); }
        .admin-link a:hover { color:var(--primary-light); }
        .card {
            background:var(--card-bg); border-radius:var(--radius-2xl);
            margin-bottom:24px; overflow:hidden; box-shadow:var(--card-shadow); transition:var(--transition-normal);
        }
        .card:hover { box-shadow:var(--card-hover-shadow); }
        .category-header {
            background:linear-gradient(135deg,rgba(79,70,229,.1),rgba(129,140,248,.1));
            padding:20px; border-bottom:1px solid var(--border-color);
            display:flex; align-items:center; gap:16px;
        }
        .category-icon {
            width:48px; height:48px; background:rgba(79,70,229,.1);
            border-radius:var(--radius-lg); display:flex; align-items:center;
            justify-content:center; font-size:1.5rem; color:var(--primary);
        }
        .category-title { font-weight:700; color:var(--text-primary); font-size:1.25rem; }
        .checklist-area { padding:20px; }
        .check-item {
            background:var(--bg-secondary); border-radius:var(--radius-xl);
            margin:16px 0; padding:16px 20px; border:1px solid var(--border-color);
            transition:var(--transition-normal); position:relative; overflow:hidden;
        }
        .check-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; }
        .check-item.pending::before { background:var(--warning); }
        .check-item.ok::before { background:var(--success); }
        .check-item.issue::before { background:var(--danger); }
        .check-item.skipped::before { background:var(--skip-color); }
        .check-item.skipped { opacity:.45; background:var(--bg-tertiary); display:none; }
        body.show-skipped .check-item.skipped { display:block; }
        body.show-skipped .check-item.skipped .question-text { text-decoration:line-through; color:var(--text-tertiary); }
        .check-item:hover { transform:translateX(4px); box-shadow:var(--card-shadow); }
        .check-item.skipped:hover { opacity:.7; }
        .skip-reason { margin-top:16px; padding-top:16px; border-top:1px dashed var(--border-color); }
        .skip-reason textarea {
            width:100%; padding:12px 16px; border-radius:var(--radius-md);
            border:1px solid var(--border-color); font-family:inherit; font-size:.875rem;
            resize:vertical; min-height:60px; transition:var(--transition-fast);
        }
        .skip-reason textarea:focus { outline:none; border-color:var(--skip-color); box-shadow:0 0 0 3px rgba(139,92,246,.1); }
        .item-row { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
        .question-text { font-weight:600; color:var(--text-primary); flex:1; font-size:.95rem; line-height:1.5; }
        .status-group {
            display:flex; gap:6px; background:var(--card-bg); padding:4px;
            border-radius:9999px; box-shadow:0 1px 3px rgba(0,0,0,.1); flex-wrap:wrap;
        }
        .status-btn {
            padding:8px 14px; border-radius:9999px; border:none; font-weight:600;
            cursor:pointer; transition:var(--transition-fast); font-size:.8rem;
            color:var(--text-secondary); white-space:nowrap;
        }
        .status-btn.ok { color:var(--success); }
        .status-btn.ok.active { background:var(--success); color:#fff; box-shadow:0 2px 4px rgba(16,185,129,.2); }
        .status-btn.issue { color:var(--danger); }
        .status-btn.issue.active { background:var(--danger); color:#fff; box-shadow:0 2px 4px rgba(239,68,68,.2); }
        .status-btn.skip { color:var(--skip-color); }
        .status-btn.skip.active { background:var(--skip-color); color:#fff; box-shadow:0 2px 4px rgba(139,92,246,.2); }
        .issue-detail { margin-top:16px; padding-top:16px; border-top:1px dashed var(--border-color); }
        .issue-detail textarea {
            width:100%; padding:12px 16px; border-radius:var(--radius-md);
            border:1px solid var(--border-color); font-family:inherit; font-size:.875rem;
            resize:vertical; min-height:80px; transition:var(--transition-fast);
        }
        .issue-detail textarea:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(79,70,229,.1); }
        .photo-section {
            background:var(--bg-secondary); border-radius:var(--radius-xl);
            padding:20px; margin-top:16px; border:2px dashed var(--border-color); transition:var(--transition-normal);
        }
        .photo-section:hover { border-color:var(--primary); }
        .photo-preview { margin:16px 0; display:flex; flex-wrap:wrap; gap:12px; }
        .photo-img { width:100px; height:100px; object-fit:cover; border-radius:var(--radius-md); box-shadow:var(--card-shadow); }
        .camera-btn {
            background:var(--primary); color:#fff; border:none; padding:10px 20px;
            border-radius:9999px; cursor:pointer; font-weight:600;
            transition:var(--transition-normal); display:inline-flex; align-items:center; gap:8px;
        }
        .camera-btn:hover { background:var(--primary-dark); transform:translateY(-2px); box-shadow:0 4px 12px rgba(79,70,229,.3); }
        .final-section { padding:24px; }
        .form-group { margin-bottom:20px; }
        label {
            font-weight:600; display:block; margin-bottom:8px; color:var(--text-secondary);
            font-size:.875rem; text-transform:uppercase; letter-spacing:.05em;
        }
        input, select, textarea {
            width:100%; padding:12px 16px; border-radius:var(--radius-md);
            border:1px solid var(--border-color); font-family:inherit; font-size:.95rem;
            transition:var(--transition-fast); background:var(--card-bg);
        }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(79,70,229,.1); }
        .sign-row { display:flex; gap:16px; flex-wrap:wrap; }
        .sign-row .form-group { flex:1; min-width:200px; }
        .submit-btn {
            background:linear-gradient(135deg,var(--primary),var(--primary-dark));
            width:100%; padding:16px; border:none; border-radius:var(--radius-xl);
            color:#fff; font-weight:700; font-size:1rem; cursor:pointer;
            transition:var(--transition-normal); position:relative; overflow:hidden; margin-top:24px;
        }
        .submit-btn::before {
            content:''; position:absolute; inset:0;
            background:linear-gradient(135deg,transparent 40%,rgba(255,255,255,.15) 50%,transparent 60%);
            transform:translateX(-100%); transition:transform .6s;
        }
        .submit-btn:hover { transform:translateY(-2px); box-shadow:0 8px 25px -5px rgba(79,70,229,.4); }
        .submit-btn:hover::before { transform:translateX(100%); }
        .submit-btn:active { transform:translateY(0); }
        .submit-btn:disabled { opacity:.6; cursor:not-allowed; transform:none!important; box-shadow:none!important; }
        .score-summary {
            background:linear-gradient(135deg,rgba(79,70,229,.1),rgba(129,140,248,.1));
            border-radius:var(--radius-xl); padding:20px; margin:20px 0;
            text-align:center; font-weight:700; color:var(--text-primary); box-shadow:var(--card-shadow);
        }
        .warning-banner {
            background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.2);
            padding:16px; border-radius:var(--radius-lg); margin-bottom:20px;
            font-size:.875rem; color:#92400e; text-align:center;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .skip-banner {
            background:rgba(139,92,246,.08); border:1px solid rgba(139,92,246,.15);
            padding:12px 16px; border-radius:var(--radius-lg); margin-bottom:16px;
            font-size:.85rem; color:var(--skip-color); text-align:center;
            display:flex; align-items:center; justify-content:center; gap:8px;
            cursor:pointer; transition:var(--transition-fast); font-weight:600;
        }
        .skip-banner:hover { background:rgba(139,92,246,.15); }
        .photo-label { font-weight:600; color:var(--text-secondary); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .skip-badge {
            display:inline-flex; align-items:center; gap:4px;
            background:rgba(139,92,246,.1); color:var(--skip-color);
            font-size:.7rem; font-weight:700; padding:2px 8px;
            border-radius:9999px; margin-left:8px; text-transform:uppercase; letter-spacing:.05em;
        }

        /* ── Success Toast Styles ── */
        .toast-overlay {
            position:fixed; inset:0; background:rgba(15,23,42,.5);
            backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
            z-index:9998; display:flex; align-items:center; justify-content:center;
            animation:fadeInBg .3s ease;
        }
        .toast-card {
            background:#fff; border-radius:24px; padding:40px 32px 32px;
            box-shadow:0 25px 50px -12px rgba(0,0,0,.25); text-align:center;
            max-width:380px; width:90%; position:relative;
            animation:scaleIn .4s cubic-bezier(.175,.885,.32,1.275);
        }
        .toast-icon {
            width:72px; height:72px; border-radius:50%; margin:0 auto 20px;
            display:flex; align-items:center; justify-content:center;
            background:linear-gradient(135deg, var(--success), #059669);
            box-shadow:0 10px 25px -5px rgba(16,185,129,.4);
        }
        .toast-card h2 { color:var(--text-primary); font-size:1.4rem; font-weight:800; margin-bottom:8px; }
        .toast-card p { color:var(--text-secondary); font-size:.9rem; margin-bottom:28px; line-height:1.6; }
        .toast-close-btn {
            background:var(--success); color:#fff; border:none; padding:14px 32px;
            border-radius:9999px; font-weight:700; cursor:pointer; font-size:.95rem;
            transition:all .2s; box-shadow:0 4px 14px rgba(16,185,129,.3);
        }
        .toast-close-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(16,185,129,.4); }
        .checkmark-svg { width:40px; height:40px; }
        .checkmark-circle { stroke:#fff; stroke-width:2; fill:none; stroke-dasharray:166; stroke-dashoffset:166; animation:checkStroke .6s cubic-bezier(.65,0,.45,1) forwards; }
        .checkmark-check { stroke:#fff; stroke-width:3; fill:none; stroke-linecap:round; stroke-linejoin:round; stroke-dasharray:48; stroke-dashoffset:48; animation:checkStroke .4s cubic-bezier(.65,0,.45,1) .4s forwards; }
        @keyframes fadeInBg { from{opacity:0} to{opacity:1} }
        @keyframes scaleIn { from{opacity:0;transform:scale(.8)} to{opacity:1;transform:scale(1)} }
        @keyframes checkStroke { to{stroke-dashoffset:0} }

        @media (max-width:600px) {
            .item-row { flex-direction:column; align-items:flex-start; }
            .status-group { align-self:flex-end; }
            .sign-row { flex-direction:column; }
            .sign-row .form-group { min-width:100%; }
            .status-btn { padding:7px 10px; font-size:.75rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="https://media.licdn.com/dms/image/v2/C4E0BAQHCgkEymt1qLw/company-logo_200_200/company-logo_200_200/0/1630622377221?e=2147483647&v=beta&t=l--VwhnuVDejHwCjvqauI-msqoBWbkivoMkZf_EHHlw" class="logo-img">
        <h1>🛠️ Hardware Maintenance Survey</h1>
        <div class="admin-link">
            <a href="admin.php">🔐 Admin Panel</a> | 
            <a href="index.php">🏠 Home</a>
        </div>
    </div>
    <form id="surveyForm" method="POST" action="submit.php" enctype="multipart/form-data">
        <input type="hidden" name="type" value="hardware">
        <div id="dynamicContent"></div>
    </form>
</div>

<script>
    const checklist = <?= json_encode($checklist) ?>;
    const branches = <?= json_encode($branches) ?>;
    const technicians = <?= json_encode($technicians) ?>;
    const defaultTech = <?= json_encode($technician) ?>;
    let state = {};

    const isTelegram = window.Telegram && Telegram.WebApp && Telegram.WebApp.initData;
    if (isTelegram) { Telegram.WebApp.expand(); Telegram.WebApp.ready(); }

    function initState() {
        checklist.forEach(cat => {
            cat.items.forEach(item => {
                const key = `${cat.id}_${item.id}`;
                if (!state[key]) state[key] = { status: null, detail: "", skipReason: "", question: item.question, category: cat.name };
            });
        });
    }
    initState();

    function getStats() {
        let total = 0, selected = 0, ok = 0, skipped = 0, issues = 0;
        for (let k in state) {
            total++;
            if (state[k].status) {
                selected++;
                if (state[k].status === 'OK') ok++;
                if (state[k].status === 'Issue') issues++;
                if (state[k].status === 'Skip') skipped++;
            }
        }
        return { total, selected, ok, skipped, issues };
    }

    function toggleSkipped() {
        document.body.classList.toggle('show-skipped');
        render();
    }

    function render() {
        const stats = getStats();
        const allSelected = stats.selected === stats.total;
        const showSkipped = document.body.classList.contains('show-skipped');
        let html = '';

        if (!allSelected) {
            html += `<div class="warning-banner"><i class="fas fa-exclamation-triangle"></i>⚠️ ${stats.selected} of ${stats.total} items completed.</div>`;
        }
        if (stats.skipped > 0) {
            html += `<div class="skip-banner" onclick="toggleSkipped()"><i class="fas fa-eye${showSkipped ? '-slash' : ''}"></i>⏭️ ${stats.skipped} skipped — Click to ${showSkipped ? 'hide' : 'show'}</div>`;
        }

        checklist.forEach(cat => {
            html += `<div class="card"><div class="category-header"><div class="category-icon">${cat.icon}</div><div class="category-title">${escape(cat.name)}</div></div><div class="checklist-area">`;
            cat.items.forEach(item => {
                const key = `${cat.id}_${item.id}`;
                const st = state[key] || { status: null, detail: "", skipReason: "" };
                const isOk = st.status === 'OK', isIssue = st.status === 'Issue', isSkip = st.status === 'Skip', pending = !st.status;
                let itemClass = pending ? 'pending' : (isOk ? 'ok' : (isIssue ? 'issue' : 'skipped'));
                html += `<div class="check-item ${itemClass}"><div class="item-row"><div class="question-text">${isSkip ? '⏭️' : '🔍'} ${escape(item.question)}${isSkip ? '<span class="skip-badge">Skipped</span>' : ''}</div><div class="status-group"><button type="button" class="status-btn ok ${isOk ? 'active' : ''}" data-cat="${cat.id}" data-item="${item.id}" data-status="OK">✅ OK</button><button type="button" class="status-btn issue ${isIssue ? 'active' : ''}" data-cat="${cat.id}" data-item="${item.id}" data-status="Issue">⚠️ Issue</button><button type="button" class="status-btn skip ${isSkip ? 'active' : ''}" data-cat="${cat.id}" data-item="${item.id}" data-status="Skip">⏭️ Skip</button></div></div>${isIssue ? `<div class="issue-detail"><textarea placeholder="Describe the issue..." rows="2" data-key="${key}" data-type="detail">${escape(st.detail)}</textarea></div>` : ''}${isSkip ? `<div class="skip-reason"><textarea placeholder="Reason for skipping (optional)..." rows="2" data-key="${key}" data-type="skipReason">${escape(st.skipReason)}</textarea></div>` : ''}</div>`;
            });
            if (cat.has_photo) {
                html += `<div class="photo-section"><div class="photo-label"><i class="fas fa-camera"></i> Attach photo evidence</div><div class="photo-preview" id="preview_${cat.id}"></div><button type="button" class="camera-btn" onclick="capturePhoto(${cat.id})"><i class="fas fa-camera"></i> Upload Photo</button><input type="file" id="photo_${cat.id}" accept="image/*" style="display:none" data-cat="${cat.id}"></div>`;
            }
            html += `</div></div>`;
        });

        const evaluatedNonSkipped = stats.ok + stats.issues;
        const score = evaluatedNonSkipped > 0 ? Math.round((stats.ok / evaluatedNonSkipped) * 100) : 0;
        const statusMsg = score >= 85 ? "🟢 Good" : (score >= 60 ? "🟡 Warning" : "🔴 Critical");

        html += `<div class="card"><div class="final-section"><div class="score-summary">📊 Health Score: ${score}% · ${statusMsg}<br><small style="font-weight:400;opacity:.8;">${stats.ok} OK · ${stats.issues} Issue · ${stats.skipped} Skipped</small></div><div class="form-group"><label>🏢 Select Branch *</label><select name="branch_id" required>${branches.map(b => `<option value="${b.id}">${escape(b.name)}</option>`).join('')}</select></div><div class="sign-row"><div class="form-group"><label>👨‍🔧 Hardware Technician *</label><select name="technician_id" required>${technicians.map(t => `<option value="${t.id}" ${t.id == defaultTech.id ? 'selected' : ''}>${escape(t.name)}</option>`).join('')}</select></div><div class="form-group"><label>📋 Branch Manager *</label><input type="text" name="branch_manager" placeholder="Manager name" required></div></div><div class="form-group"><label>⚠️ Summary of Issues</label><textarea name="issues_summary" rows="2" placeholder="List all major issues..."></textarea></div><div class="form-group"><label>🔧 Actions Taken</label><textarea name="actions_taken" rows="2" placeholder="What was repaired?"></textarea></div><div class="form-group"><label>📌 Recommendations</label><textarea name="recommendations" rows="2" placeholder="Preventive next steps..."></textarea></div><button type="button" class="submit-btn" id="submitBtn" ${!allSelected ? 'disabled' : ''} onclick="submitForm()">✅ Submit Hardware Survey</button>${!allSelected ? '<p style="color:var(--danger);font-size:.75rem;text-align:center;margin-top:8px;">Complete all items first</p>' : ''}</div></div>`;

        document.getElementById('dynamicContent').innerHTML = html;
        attachEvents();
    }

    function attachEvents() {
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.onclick = () => {
                const key = `${btn.dataset.cat}_${btn.dataset.item}`;
                const status = btn.dataset.status;
                if (!state[key]) state[key] = { status: null, detail: "", skipReason: "", question: "", category: "" };
                if (state[key].status === status) { state[key].status = null; state[key].detail = ""; state[key].skipReason = ""; }
                else { state[key].status = status; if (status === 'OK') { state[key].detail = ""; state[key].skipReason = ""; } if (status === 'Skip') state[key].detail = ""; if (status === 'Issue') state[key].skipReason = ""; }
                render();
            };
        });
    }

    document.addEventListener('input', (e) => {
        if (e.target.tagName === 'TEXTAREA' && e.target.dataset.key) {
            const key = e.target.dataset.key, type = e.target.dataset.type;
            if (state[key]) { if (type === 'skipReason') state[key].skipReason = e.target.value; else state[key].detail = e.target.value; }
        }
    });

    window.capturePhoto = function(catId) {
        const input = document.getElementById(`photo_${catId}`);
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    document.getElementById(`preview_${catId}`).innerHTML = `<img src="${ev.target.result}" class="photo-img" />`;
                    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = `photo_${catId}`; hidden.value = ev.target.result;
                    document.getElementById('surveyForm').appendChild(hidden);
                }; reader.readAsDataURL(file);
            }
        }; input.click();
    };

    function showSuccess(message) {
        const overlay = document.createElement('div');
        overlay.className = 'toast-overlay';
        overlay.innerHTML = `
            <div class="toast-card">
                <div class="toast-icon">
                    <svg class="checkmark-svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25"/>
                        <path class="checkmark-check" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                <h2>Successfully Submitted!</h2>
                <p>${escape(message)}</p>
                <button class="toast-close-btn" onclick="closeToast()">Done</button>
            </div>`;
        document.body.appendChild(overlay);
        setTimeout(closeToast, 3500);
    }

    window.closeToast = function() {
        if (isTelegram) Telegram.WebApp.close();
        else location.reload();
    };

    window.submitForm = function() {
        const stats = getStats();
        if (stats.selected !== stats.total) { alert("Please select OK, Issue, or Skip for all items."); return; }
        for (let k in state) { if (state[k].status === 'Issue' && (!state[k].detail || !state[k].detail.trim())) { alert("Please describe all issues."); return; } }
        const form = document.getElementById('surveyForm'), fd = new FormData(form);
        fd.append('checklist_state', JSON.stringify(state));
        const btn = document.getElementById('submitBtn'); btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        fetch('submit.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => { if (data.success) showSuccess(data.message); else { alert(data.message); btn.disabled = false; btn.innerHTML = '✅ Submit Hardware Survey'; } })
            .catch(err => { alert("Error: " + err.message); btn.disabled = false; btn.innerHTML = '✅ Submit Hardware Survey'; });
    };

    function escape(str) { return String(str).replace(/[&<>"']/g, m => m === '&' ? '&amp;' : m === '<' ? '&lt;' : m === '>' ? '&gt;' : m === '"' ? '&quot;' : '&#39;'); }
    render();
</script>
</body>
</html>