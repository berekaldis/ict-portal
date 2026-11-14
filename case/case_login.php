<?php
session_start();
if (isset($_SESSION['case_user']) && !empty($_SESSION['case_user'])) {
    header('Location: case_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Kaldis CNET - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #F8F9FB;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        /* Network canvas background */
        #netCanvas {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .card {
            width: 100%;
            max-width: 380px;
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(229,231,235,.7);
            border-radius: 16px;
            padding: 40px 32px 32px;
            animation: cardIn .45s cubic-bezier(.22,1,.36,1);
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 24px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.03);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .brand { text-align: center; margin-bottom: 32px; }
        .brand img {
            width: 56px; height: 56px;
            border-radius: 14px; margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .brand h1 {
            font-size: 17px; font-weight: 700;
            color: #111827; letter-spacing: -.3px;
        }
        .brand p { font-size: 13px; color: #9CA3AF; margin-top: 4px; }

        .form-panel {
            display: none;
            opacity: 0;
            transform: translateX(12px);
        }
        .form-panel.active {
            display: block;
            animation: panelIn .35s cubic-bezier(.22,1,.36,1) forwards;
        }
        .form-panel.exit { animation: panelOut .2s ease forwards; }

        @keyframes panelIn {
            from { opacity: 0; transform: translateX(12px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes panelOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-12px); }
        }

        .field { margin-bottom: 20px; position: relative; }
        .field label {
            display: block; font-size: 12px; font-weight: 600;
            color: #6B7280; margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: .4px;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrap .ico-left {
            position: absolute; left: 0; top: 0; bottom: 0;
            display: flex; align-items: center; justify-content: center;
            width: 40px; pointer-events: none;
        }
        .input-wrap .ico-left .material-icons-round {
            font-size: 18px; color: #D1D5DB;
            transition: color .2s;
        }
        .input-wrap input {
            width: 100%; height: 42px;
            padding: 0 12px 0 40px;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px; font-family: inherit;
            color: #111827; background: rgba(255,255,255,.7);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .input-wrap input:focus {
            border-color: #D97706;
            box-shadow: 0 0 0 3px rgba(217,119,6,.08);
            background: #fff;
        }
        .input-wrap input:focus ~ .ico-left .material-icons-round { color: #D97706; }
        .input-wrap input.has-toggle { padding-right: 42px; }

        .toggle-pw {
            position: absolute; right: 2px; top: 2px; bottom: 2px;
            width: 38px;
            display: flex; align-items: center; justify-content: center;
            background: none; border: none; cursor: pointer;
            border-radius: 8px; color: #D1D5DB;
            transition: color .2s, background .2s;
        }
        .toggle-pw:hover { color: #9CA3AF; background: rgba(0,0,0,.03); }
        .toggle-pw:active { transform: scale(.9); }
        .toggle-pw .material-icons-round {
            font-size: 19px;
            transition: transform .25s cubic-bezier(.22,1,.36,1), opacity .2s;
        }
        .toggle-pw .ico-show {
            position: absolute;
            transform: rotateY(90deg);
            opacity: 0;
        }
        .toggle-pw .ico-hide {
            position: absolute;
            transform: rotateY(0deg);
            opacity: 1;
        }
        .toggle-pw.showing .ico-show { transform: rotateY(0deg); opacity: 1; }
        .toggle-pw.showing .ico-hide { transform: rotateY(-90deg); opacity: 0; }

        .suggestions {
            position: absolute; top: calc(100% + 4px);
            left: 0; right: 0;
            background: #fff; border: 1px solid #E5E7EB;
            border-radius: 10px; max-height: 180px;
            overflow-y: auto; z-index: 50; display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,.06);
        }
        .suggestions.open { display: block; animation: sugIn .2s ease; }
        @keyframes sugIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .suggestions div {
            padding: 9px 14px; font-size: 13px; color: #374151;
            cursor: pointer; display: flex; align-items: center;
            gap: 8px; transition: background .12s;
        }
        .suggestions div:hover, .suggestions div.active { background: #FEF3C7; }
        .suggestions div .material-icons-round { font-size: 15px; color: #D97706; }

        .btn {
            width: 100%; padding: 11px; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn .material-icons-round { font-size: 18px; transition: transform .2s; }
        .btn-primary { background: #D97706; color: #fff; }
        .btn-primary:hover {
            background: #B45309; transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(217,119,6,.25);
        }
        .btn-primary:hover .material-icons-round { transform: translateX(2px); }
        .btn-primary:active { transform: translateY(0); }

        .btn-ghost { background: none; color: #6B7280; padding: 8px; font-size: 13px; }
        .btn-ghost:hover { color: #D97706; }
        .btn-ghost .material-icons-round { transition: transform .2s; }
        .btn-ghost:hover .material-icons-round { transform: translateX(-3px); }

        .link-btn {
            background: none; border: none; font-family: inherit;
            font-size: 12px; font-weight: 600; color: #D97706;
            cursor: pointer; padding: 0; transition: color .15s;
        }
        .link-btn:hover { color: #B45309; }

        .row-between {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 24px;
        }

        .msg {
            padding: 10px 14px; border-radius: 10px;
            font-size: 13px; font-weight: 500; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
            max-height: 0; overflow: hidden; opacity: 0;
            transition: max-height .3s cubic-bezier(.22,1,.36,1),
                        opacity .25s ease, margin .3s ease, padding .3s ease;
            padding-top: 0; padding-bottom: 0; margin-bottom: 0;
        }
        .msg.show {
            max-height: 60px; opacity: 1;
            padding: 10px 14px; margin-bottom: 20px;
        }
        .msg .material-icons-round { font-size: 16px; flex-shrink: 0; }
        .msg-err { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .msg-ok { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }

        .spinner {
            display: flex; justify-content: center;
            margin-top: 16px; height: 0; overflow: hidden;
            transition: height .25s ease, opacity .2s ease; opacity: 0;
        }
        .spinner.show { height: 24px; opacity: 1; }
        .spinner::after {
            content: ''; width: 20px; height: 20px;
            border: 2px solid #E5E7EB; border-top-color: #D97706;
            border-radius: 50%; animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .divider { text-align: center; margin: 20px 0; position: relative; }
        .divider::before {
            content: ''; position: absolute;
            left: 0; right: 0; top: 50%; height: 1px; background: #E5E7EB;
        }
        .divider span {
            background: rgba(255,255,255,.85); padding: 0 12px;
            font-size: 11px; color: #9CA3AF;
            position: relative; text-transform: uppercase; letter-spacing: .5px;
        }

        .pw-strength {
            height: 3px; background: #F3F4F6;
            border-radius: 2px; margin-top: 8px; overflow: hidden;
        }
        .pw-strength div {
            height: 100%; width: 0;
            transition: width .3s ease, background .3s ease; border-radius: 2px;
        }
        .pw-strength div.w { width: 33%; background: #EF4444; }
        .pw-strength div.m { width: 66%; background: #F59E0B; }
        .pw-strength div.s { width: 100%; background: #22C55E; }
        .pw-hint { font-size: 11px; color: #9CA3AF; margin-top: 4px; min-height: 16px; }

        .foot {
            text-align: center; margin-top: 24px;
            padding-top: 16px; border-top: 1px solid rgba(243,244,246,.7);
            font-size: 11px; color: #D1D5DB;
        }

        @media (max-width: 420px) {
            .card { padding: 32px 24px 24px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>

<canvas id="netCanvas"></canvas>

<div class="card">
    <div class="brand">
        <img src="https://media.licdn.com/dms/image/v2/C4E0BAQHCgkEymt1qLw/company-logo_200_200/company-logo_200_200/0/1630622377221?e=2147483647&v=beta&t=l--VwhnuVDejHwCjvqauI-msqoBWbkivoMkZf_EHHlw" alt="Logo">
        <h1 id="pageTitle">CNET Case Management</h1>
        <p id="pageSub">Sign in to your account</p>
    </div>

    <div id="errMsg" class="msg msg-err">
        <span class="material-icons-round">error</span>
        <span id="errText"></span>
    </div>
    <div id="okMsg" class="msg msg-ok">
        <span class="material-icons-round">check_circle</span>
        <span id="okText"></span>
    </div>

    <!-- LOGIN -->
    <div id="panelLogin" class="form-panel active">
        <form id="loginForm" autocomplete="off">
            <div class="field">
                <label>Username</label>
                <div class="input-wrap">
                    <input type="text" id="loginUser" placeholder="Type to search..." required autofocus>
                    <span class="ico-left"><span class="material-icons-round">search</span></span>
                    <div id="sugBox" class="suggestions"></div>
                </div>
            </div>
            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <input type="password" id="loginPw" class="has-toggle" placeholder="Enter password" required>
                    <span class="ico-left"><span class="material-icons-round">lock</span></span>
                    <button type="button" class="toggle-pw" data-target="loginPw" aria-label="Toggle password">
                        <span class="material-icons-round ico-hide">visibility_off</span>
                        <span class="material-icons-round ico-show">visibility</span>
                    </button>
                </div>
            </div>
            <div class="row-between">
                <span></span>
                <button type="button" class="link-btn" onclick="go('forgot')">Forgot password?</button>
            </div>
            <button type="submit" class="btn btn-primary">
                <span class="material-icons-round">arrow_forward</span>
                Sign In
            </button>
        </form>
        <div id="spinLogin" class="spinner"></div>
    </div>

    <!-- FORGOT -->
    <div id="panelForgot" class="form-panel">
        <form id="forgotForm" autocomplete="off">
            <div class="field">
                <label>Username</label>
                <div class="input-wrap">
                    <input type="text" id="fUser" placeholder="Your username" required>
                    <span class="ico-left"><span class="material-icons-round">person</span></span>
                </div>
            </div>
            <div class="field">
                <label>Email</label>
                <div class="input-wrap">
                    <input type="email" id="fEmail" placeholder="Registered email" required>
                    <span class="ico-left"><span class="material-icons-round">mail</span></span>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="sendOTP()">
                <span class="material-icons-round">send</span>
                Send Code
            </button>
        </form>
        <div id="spinForgot" class="spinner"></div>
        <div class="divider"><span>or</span></div>
        <button class="btn btn-ghost" onclick="go('login')">
            <span class="material-icons-round">arrow_back</span>
            Back to sign in
        </button>
    </div>

    <!-- OTP -->
    <div id="panelOTP" class="form-panel">
        <form id="otpForm" autocomplete="off">
            <div class="field">
                <label>Verification Code</label>
                <div class="input-wrap">
                    <input type="text" id="otpInput" maxlength="6" placeholder="6-digit code" required pattern="[0-9]{6}">
                    <span class="ico-left"><span class="material-icons-round">pin</span></span>
                </div>
                <div class="pw-hint">Check your email for the code</div>
            </div>
            <button type="button" class="btn btn-primary" onclick="verifyOTP()">
                <span class="material-icons-round">check</span>
                Verify
            </button>
        </form>
        <div id="spinOTP" class="spinner"></div>
        <div class="divider"><span>or</span></div>
        <button class="btn btn-ghost" onclick="go('forgot')">
            <span class="material-icons-round">arrow_back</span>
            Back
        </button>
    </div>

    <!-- RESET PW -->
    <div id="panelReset" class="form-panel">
        <form id="resetForm" autocomplete="off">
            <div class="field">
                <label>New Password</label>
                <div class="input-wrap">
                    <input type="password" id="newPw" class="has-toggle" placeholder="Min 6 characters" required oninput="pwStr()">
                    <span class="ico-left"><span class="material-icons-round">lock</span></span>
                    <button type="button" class="toggle-pw" data-target="newPw" aria-label="Toggle password">
                        <span class="material-icons-round ico-hide">visibility_off</span>
                        <span class="material-icons-round ico-show">visibility</span>
                    </button>
                </div>
                <div class="pw-strength"><div id="pwBar"></div></div>
                <div class="pw-hint" id="pwHint"></div>
            </div>
            <div class="field">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" id="cfmPw" class="has-toggle" placeholder="Re-enter password" required>
                    <span class="ico-left"><span class="material-icons-round">lock</span></span>
                    <button type="button" class="toggle-pw" data-target="cfmPw" aria-label="Toggle password">
                        <span class="material-icons-round ico-hide">visibility_off</span>
                        <span class="material-icons-round ico-show">visibility</span>
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="doReset()">
                <span class="material-icons-round">lock_reset</span>
                Reset Password
            </button>
        </form>
        <div id="spinReset" class="spinner"></div>
        <div class="divider"><span>or</span></div>
        <button class="btn btn-ghost" onclick="go('login')">
            <span class="material-icons-round">arrow_back</span>
            Back to sign in
        </button>
    </div>

    <div class="foot">Authorized access only</div>
</div>

<script>
/* =============================================
   NETWORK ANIMATION
   ============================================= */
(function() {
    const canvas = document.getElementById('netCanvas');
    const ctx = canvas.getContext('2d');
    let W, H, nodes = [], mouse = { x: -9999, y: -9999 };
    const CONNECT_DIST = 150;
    const MOUSE_DIST = 200;

    /* Colors matching the light theme */
    const C = {
        node:       'rgba(209,113,6,0.25)',
        nodeCore:   'rgba(217,119,6,0.6)',
        nodeAccent: 'rgba(217,119,6,0.8)',
        line:       'rgba(180,83,9,0.06)',
        lineMouse:  'rgba(217,119,6,0.12)',
        pulse:      'rgba(217,119,6,0.04)',
        dataDot:    'rgba(217,119,6,0.7)',
    };

    function resize() {
        W = canvas.width = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    function createNodes() {
        nodes = [];
        /* Density based on screen area */
        const area = W * H;
        const count = Math.min(Math.floor(area / 18000), 80);
        for (let i = 0; i < count; i++) {
            nodes.push({
                x: Math.random() * W,
                y: Math.random() * H,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                r: Math.random() * 1.5 + 1,
                accent: Math.random() < 0.15,   /* 15% are accent nodes */
                pulsePhase: Math.random() * Math.PI * 2,
            });
        }
    }

    /* Data packets traveling along connections */
    let packets = [];
    function spawnPacket(a, b) {
        if (packets.length > 12) return;
        packets.push({
            ax: a.x, ay: a.y,
            bx: b.x, by: b.y,
            t: 0,
            speed: 0.008 + Math.random() * 0.012,
        });
    }

    let frameCount = 0;

    function draw() {
        ctx.clearRect(0, 0, W, H);
        frameCount++;

        /* Update positions */
        for (const n of nodes) {
            n.x += n.vx;
            n.y += n.vy;
            if (n.x < -20) n.x = W + 20;
            if (n.x > W + 20) n.x = -20;
            if (n.y < -20) n.y = H + 20;
            if (n.y > H + 20) n.y = -20;
        }

        /* Draw connections */
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const dx = nodes[i].x - nodes[j].x;
                const dy = nodes[i].y - nodes[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < CONNECT_DIST) {
                    const alpha = 1 - dist / CONNECT_DIST;

                    /* Check if near mouse */
                    const mx = (nodes[i].x + nodes[j].x) / 2;
                    const my = (nodes[i].y + nodes[j].y) / 2;
                    const md = Math.hypot(mx - mouse.x, my - mouse.y);
                    const nearMouse = md < MOUSE_DIST;
                    const mouseAlpha = nearMouse ? (1 - md / MOUSE_DIST) * 0.15 : 0;

                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.strokeStyle = nearMouse
                        ? `rgba(217,119,6,${alpha * 0.18 + mouseAlpha})`
                        : `rgba(180,83,9,${alpha * 0.06})`;
                    ctx.lineWidth = nearMouse ? 1 : 0.5;
                    ctx.stroke();

                    /* Randomly spawn data packets */
                    if (nearMouse && Math.random() < 0.003) {
                        spawnPacket(nodes[i], nodes[j]);
                    }
                }
            }
        }

        /* Draw & update packets */
        for (let i = packets.length - 1; i >= 0; i--) {
            const p = packets[i];
            p.t += p.speed;
            if (p.t > 1) { packets.splice(i, 1); continue; }
            const px = p.ax + (p.bx - p.ax) * p.t;
            const py = p.ay + (p.by - p.ay) * p.t;
            const fade = p.t < 0.2 ? p.t / 0.2 : p.t > 0.8 ? (1 - p.t) / 0.2 : 1;
            ctx.beginPath();
            ctx.arc(px, py, 1.5, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(217,119,6,${0.7 * fade})`;
            ctx.fill();
        }

        /* Draw nodes */
        for (const n of nodes) {
            const md = Math.hypot(n.x - mouse.x, n.y - mouse.y);
            const nearMouse = md < MOUSE_DIST;
            const mouseFactor = nearMouse ? (1 - md / MOUSE_DIST) : 0;

            /* Pulse for accent nodes */
            n.pulsePhase += 0.02;
            const pulse = n.accent ? Math.sin(n.pulsePhase) * 0.5 + 0.5 : 0;

            /* Outer glow for accent or mouse-near */
            if (n.accent || mouseFactor > 0.3) {
                const glowR = n.r + 6 + (n.accent ? pulse * 4 : mouseFactor * 6);
                const grad = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, glowR);
                grad.addColorStop(0, `rgba(217,119,6,${0.06 + (n.accent ? pulse * 0.04 : mouseFactor * 0.08)})`);
                grad.addColorStop(1, 'rgba(217,119,6,0)');
                ctx.beginPath();
                ctx.arc(n.x, n.y, glowR, 0, Math.PI * 2);
                ctx.fillStyle = grad;
                ctx.fill();
            }

            /* Core dot */
            const baseR = n.r + mouseFactor * 1.5 + (n.accent ? pulse * 0.5 : 0);
            ctx.beginPath();
            ctx.arc(n.x, n.y, Math.max(0.5, baseR), 0, Math.PI * 2);
            ctx.fillStyle = n.accent
                ? `rgba(217,119,6,${0.5 + pulse * 0.3 + mouseFactor * 0.2})`
                : `rgba(209,113,6,${0.2 + mouseFactor * 0.4})`;
            ctx.fill();
        }

        requestAnimationFrame(draw);
    }

    /* Track mouse */
    document.addEventListener('mousemove', e => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });
    document.addEventListener('mouseleave', () => {
        mouse.x = -9999;
        mouse.y = -9999;
    });
    /* Touch support */
    document.addEventListener('touchmove', e => {
        if (e.touches.length) {
            mouse.x = e.touches[0].clientX;
            mouse.y = e.touches[0].clientY;
        }
    }, { passive: true });
    document.addEventListener('touchend', () => {
        mouse.x = -9999;
        mouse.y = -9999;
    });

    /* Init */
    resize();
    createNodes();
    draw();

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => { resize(); createNodes(); }, 200);
    });
})();


/* =============================================
   APP LOGIC
   ============================================= */
const API = 'case_api.php';
let users = [], selIdx = -1, token = null, vUser = null;

const $ = id => document.getElementById(id);
const err = t => { hideMsg(); $('errText').textContent = t; $('errMsg').classList.add('show'); clearTimeout(err._t); err._t = setTimeout(hideMsg, 4000); };
const ok = t => { hideMsg(); $('okText').textContent = t; $('okMsg').classList.add('show'); clearTimeout(ok._t); ok._t = setTimeout(hideMsg, 4000); };
const hideMsg = () => { $('errMsg').classList.remove('show'); $('okMsg').classList.remove('show'); };
const spin = (id, on) => $(id).classList.toggle('show', on);

/* Password toggle */
document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = $(btn.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.classList.toggle('showing', input.type === 'text');
    });
});

/* Panel navigation */
const panels = { login:'panelLogin', forgot:'panelForgot', otp:'panelOTP', reset:'panelReset' };
const titles = {
    login:  ['CNET Case Management', 'Sign in to your account'],
    forgot: ['Reset Password',        'Verify your identity'],
    otp:    ['Enter Code',            'Check your email'],
    reset:  ['New Password',          'Choose a strong password']
};
let currentPanel = 'login', switching = false;

function go(to) {
    if (to === currentPanel || switching) return;
    switching = true;
    hideMsg();
    const oldEl = $(panels[currentPanel]);
    const newEl = $(panels[to]);
    oldEl.classList.remove('active');
    oldEl.classList.add('exit');
    setTimeout(() => {
        oldEl.classList.remove('exit');
        oldEl.style.display = 'none';
        newEl.style.display = 'block';
        newEl.style.opacity = '0';
        newEl.style.transform = 'translateX(12px)';
        void newEl.offsetWidth;
        newEl.classList.add('active');
        newEl.style.opacity = '';
        newEl.style.transform = '';
        $('pageTitle').textContent = titles[to][0];
        $('pageSub').textContent = titles[to][1];
        currentPanel = to;
        switching = false;
        const first = newEl.querySelector('input');
        if (first) setTimeout(() => first.focus(), 80);
    }, 200);
}

/* Password strength */
function pwStr() {
    const v = $('newPw').value, bar = $('pwBar'), hint = $('pwHint');
    let s = 0;
    if (v.length >= 8) s++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
    if (/\d/.test(v)) s++;
    if (/[^a-zA-Z0-9]/.test(v)) s++;
    bar.className = '';
    if (!v) { hint.textContent = ''; return; }
    if (s <= 1) { bar.classList.add('w'); hint.textContent = 'Weak password'; }
    else if (s <= 2) { bar.classList.add('m'); hint.textContent = 'Fair password'; }
    else { bar.classList.add('s'); hint.textContent = 'Strong password'; }
}

/* Username suggestions */
async function loadUsers() {
    try {
        const r = await fetch(`${API}?action=getAvailableUsernames`);
        const d = await r.json();
        users = d.success && d.users ? d.users : Array.isArray(d) ? d : ['admin'];
    } catch { users = ['admin']; }
}

function showSug(q) {
    const box = $('sugBox');
    if (!q) { box.classList.remove('open'); return; }
    const list = users.filter(u => u.toLowerCase().includes(q.toLowerCase())).slice(0, 6);
    if (!list.length) { box.classList.remove('open'); return; }
    box.innerHTML = list.map((u, i) =>
        `<div data-v="${u}" data-i="${i}"><span class="material-icons-round">person</span>${u}</div>`
    ).join('');
    box.classList.add('open');
    selIdx = -1;
    box.querySelectorAll('div').forEach(el => {
        el.onclick = () => { $('loginUser').value = el.dataset.v; box.classList.remove('open'); $('loginPw').focus(); };
    });
}

 $('loginUser').addEventListener('input', e => showSug(e.target.value));
 $('loginUser').addEventListener('keydown', e => {
    const items = $('sugBox').children;
    if (!items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); selIdx = Math.min(selIdx + 1, items.length - 1); hl(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); selIdx = Math.max(selIdx - 1, -1); hl(items); }
    else if (e.key === 'Enter' && selIdx >= 0) { e.preventDefault(); $('loginUser').value = items[selIdx].dataset.v; $('sugBox').classList.remove('open'); $('loginPw').focus(); }
    else if (e.key === 'Escape') { $('sugBox').classList.remove('open'); selIdx = -1; }
});
function hl(items) { [...items].forEach((el, i) => el.classList.toggle('active', i === selIdx)); }
document.addEventListener('click', e => { if (!e.target.closest('.field')) $('sugBox').classList.remove('open'); });

/* Login */
 $('loginForm').addEventListener('submit', async e => {
    e.preventDefault();
    const u = $('loginUser').value.trim(), p = $('loginPw').value;
    if (!u || !p) return err('Enter username and password');
    spin('spinLogin', true); hideMsg();
    try {
        const r = await fetch(`${API}?action=authenticateUser`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({username:u, password:p})
        });
        const d = await r.json();
        if (d.success) { ok('Signing in...'); setTimeout(() => location.href = 'case_dashboard.php', 400); }
        else { err(d.error || 'Invalid credentials'); $('loginPw').value = ''; $('loginPw').focus(); }
    } catch { err('Connection error'); }
    spin('spinLogin', false);
});

/* Forgot: send OTP */
async function sendOTP() {
    const u = $('fUser').value.trim(), em = $('fEmail').value.trim();
    if (!u || !em) return err('Fill in both fields');
    spin('spinForgot', true); hideMsg();
    try {
        const r = await fetch(`${API}?action=requestPasswordReset`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({username:u, email:em})
        });
        const d = await r.json();
        if (d.success) { ok('Code sent!'); setTimeout(() => go('otp'), 1200); }
        else err(d.error || 'Not found');
    } catch { err('Connection error'); }
    spin('spinForgot', false);
}

/* Verify OTP */
async function verifyOTP() {
    const code = $('otpInput').value.trim(), u = $('fUser').value.trim();
    if (code.length !== 6) return err('Enter 6-digit code');
    spin('spinOTP', true); hideMsg();
    try {
        const r = await fetch(`${API}?action=verifyOTP`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({username:u, otp:code})
        });
        const d = await r.json();
        if (d.success) { token = d.token; vUser = u; ok('Verified!'); setTimeout(() => go('reset'), 800); }
        else err(d.error || 'Invalid code');
    } catch { err('Connection error'); }
    spin('spinOTP', false);
}

/* Reset password */
async function doReset() {
    const pw = $('newPw').value, cf = $('cfmPw').value;
    if (!pw || !cf) return err('Fill in both fields');
    if (pw.length < 6) return err('Min 6 characters');
    if (pw !== cf) return err('Passwords don\'t match');
    spin('spinReset', true); hideMsg();
    try {
        const r = await fetch(`${API}?action=resetPassword`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ username: vUser, token, newPassword: pw })
        });
        const d = await r.json();
        if (d.success) { ok('Done! Sign in with your new password.'); setTimeout(() => go('login'), 1500); }
        else err(d.error || 'Reset failed');
    } catch { err('Connection error'); }
    spin('spinReset', false);
}

loadUsers();
 $('loginUser').focus();
</script>
</body>
</html>