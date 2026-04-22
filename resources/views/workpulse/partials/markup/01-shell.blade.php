<!-- ═══════════ LOGIN SCREEN ═══════════ -->
<div id="login-screen">
  <div class="login-box">
    <div class="login-logo">Work<span>Pulse</span></div>
    <div class="login-sub">Attendance Management System</div>
    <div class="login-tabs">
      <div class="login-tab active" id="lt-admin" onclick="window.selectLoginRole('admin')">Admin / HR</div>
      <div class="login-tab" id="lt-emp" onclick="window.selectLoginRole('employee')">Employee</div>
    </div>
    <div class="lf-group">
      <label class="lf-label">Email Address <span class="req-star">*</span></label>
      <input type="email" class="lf-input" id="l-email" placeholder="Enter your email" value="admin@workpulse.com">
    </div>
    <div class="lf-group">
      <label class="lf-label">Password <span class="req-star">*</span></label>
      <input type="password" class="lf-input" id="l-pass" placeholder="Enter password" value="admin123">
    </div>
    <div class="lf-err" id="l-err">Invalid credentials. Please try again.</div>
    <button class="lf-btn" onclick="window.doLogin()">Sign In</button>
    <div class="lf-hint">
      <strong>Admin / HR:</strong> <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="08696c656166487f677a63787d647b6d266b6765">[email&#160;protected]</a> · admin123<br>
      <strong>Employee:</strong> <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="ddb8b0adb1b2a4b8b89daab2afb6ada8b1aeb8f3beb2b0">[email&#160;protected]</a> · emp123
    </div>
  </div>
</div>

<!-- ═══════════ MAIN APP ═══════════ -->
<div id="app">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sb-logo">
      <h1>Work<span>Pulse</span></h1>
      <p id="sb-version">Attendance Management System</p>
    </div>
    <div class="sb-user">
      <div class="av av-32" id="sb-avatar" style="background:var(--accent);color:#fff;">AK</div>
      <div class="sb-user-info">
        <div class="name" id="sb-name">Ahmed Karim</div>
        <div class="role" id="sb-role">Administrator</div>
      </div>
    </div>
    <div id="sidebar-nav"></div>
    <div class="sb-footer">
      <p>WorkPulse v2.4.1</p>
      <p style="margin-top:2px;">Backup: Today 03:00 AM ✓</p>
      <button class="btn btn-sm" style="margin-top:10px;width:100%;justify-content:center;color:var(--red);border-color:rgba(255,255,255,.1);background:transparent;font-size:12px;" onclick="window.doLogout()"">Sign Out</button>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main">
    <div class="topbar">
      <div class="topbar-title" id="page-title">Dashboard</div>
      <div style="display:flex;align-items:center;gap:9px;">
        <div class="tb-clock" id="tb-clock">00:00:00</div>
        <div id="topbar-actions"></div>
        <div class="notif-wrap">
          <button class="btn btn-sm" onclick="window.openNotificationsPage ? window.openNotificationsPage() : window.showPage('leave')" title="Notifications">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1a5.5 5.5 0 015.5 5.5c0 3-1 5-1.5 6H4c-.5-1-1.5-3-1.5-6A5.5 5.5 0 018 1z"/><path d="M6 12v.5a2 2 0 004 0V12"/></svg>
          </button>
          <div class="notif-dot" id="notif-dot"></div>
        </div>
      </div>
    </div>
    <div class="content" id="main-content"></div>
  </div>
</div>
