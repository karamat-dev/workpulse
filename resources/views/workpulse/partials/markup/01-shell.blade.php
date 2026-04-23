<!-- LOGIN SCREEN -->
<div id="login-screen">
  <div class="login-box">
    <div class="login-logo">Work<span>Pulse</span></div>
    <div class="login-sub">Attendance Management System</div>
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
      <strong>Admin / HR:</strong> admin@workpulse.com | admin123<br>
      <strong>Employee:</strong> employee@workpulse.com | emp123
    </div>
  </div>
</div>

<!-- MAIN APP -->
<div id="app">
  <aside class="sidebar" id="sidebar">
    <div class="sb-logo">
      <div class="sb-brand-mark">
        <img src="{{ asset('uploads/logo/logo.png') }}" alt="WorkPulse Logo">
      </div>
      <div class="sb-brand-copy">
        <h1>WorkPulse</h1>
        <p>People operations workspace</p>
      </div>
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
      <div class="sb-promo">
        <div class="sb-promo-title">Built for fast HR decisions</div>
        <div class="sb-promo-copy">Track attendance, approvals, team health, and company updates from one clean workspace.</div>
      </div>
      <p>WorkPulse v2.4.1</p>
      <p style="margin-top:2px;">Backup: Today 03:00 AM</p>
      <button class="btn btn-sm" style="margin-top:10px;width:100%;justify-content:center;color:var(--red);border-color:rgba(255,255,255,.1);background:transparent;font-size:12px;" onclick="window.doLogout()">Sign Out</button>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div class="topbar-title-block">
        <div class="topbar-kicker">Workspace</div>
        <div class="topbar-title" id="page-title">Dashboard</div>
      </div>
      <div class="topbar-tools">
        <button class="topbar-icon-btn" type="button" title="Search">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="7" cy="7" r="4.8"></circle><path d="M10.8 10.8L14 14"></path></svg>
        </button>
        <div class="tb-clock" id="tb-clock">00:00:00</div>
        <div id="topbar-actions"></div>
        <div class="notif-wrap">
          <button class="topbar-icon-btn" onclick="window.openNotificationsPage ? window.openNotificationsPage() : window.showPage('leave')" title="Notifications">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1a5.5 5.5 0 015.5 5.5c0 3-1 5-1.5 6H4c-.5-1-1.5-3-1.5-6A5.5 5.5 0 018 1z"/><path d="M6 12v.5a2 2 0 004 0V12"/></svg>
          </button>
          <div class="notif-dot" id="notif-dot"></div>
        </div>
        <div class="topbar-user-chip">
          <div class="av av-32 topbar-user-avatar" id="tb-avatar" style="background:var(--accent);color:#fff;">AK</div>
          <div class="topbar-user-copy">
            <strong id="tb-name">Ahmed Karim</strong>
            <span id="tb-email">hello@workpulse.com</span>
          </div>
        </div>
      </div>
    </div>

    <div class="content" id="main-content"></div>
  </div>
</div>
