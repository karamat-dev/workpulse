<!-- LOGIN SCREEN -->
<div id="login-screen">
  <div class="login-shell">
    <div class="login-panel">
      <div class="login-brand-row">
        <div class="login-logo-mark">
          <img src="{{ asset('uploads/logo/logo.png') }}" alt="WorkPulse Logo">
        </div>
        <div>
          <div class="login-logo">Work<span>Pulse</span></div>
          <div class="login-brand-copy">Build stronger HR operations</div>
        </div>
      </div>
      <div class="login-copy-block">
        <h1 class="login-headline">Modernize your HR operations</h1>
        <p class="login-sub">Welcome back. Please sign in to your workspace and continue managing attendance, teams, and approvals.</p>
      </div>
      <div class="login-form-card">
        <div class="lf-group">
          <label class="lf-label">Email Address <span class="req-star">*</span></label>
          <input type="email" class="lf-input" id="l-email" placeholder="Enter your email" value="admin@workpulse.com">
        </div>
        <div class="lf-group">
          <label class="lf-label">Password <span class="req-star">*</span></label>
          <input type="password" class="lf-input" id="l-pass" placeholder="Enter password" value="admin123">
        </div>
        <div class="lf-err" id="l-err">Invalid credentials. Please try again.</div>
        <div class="login-form-meta">
          <span>Use your official WorkPulse account credentials</span>
          <span>Need help? Contact HR admin</span>
        </div>
        <button class="lf-btn" onclick="window.doLogin()">Sign In</button>
      </div>
      <div class="login-footer-note">
        Secure workspace access for attendance, leave, approvals, and employee management.
      </div>
    </div>
    <div class="login-hero">
      <div class="login-hero-grid"></div>
      <div class="login-balloon balloon-a"></div>
      <div class="login-balloon balloon-b"></div>
      <div class="login-balloon balloon-c"></div>
      <div class="login-cityline">
        <div class="city-tower tower-a"></div>
        <div class="city-tower tower-b"></div>
        <div class="city-tower tower-c"></div>
        <div class="city-wheel"></div>
        <div class="city-arch"></div>
      </div>
      <div class="login-hero-copy">
        <span class="login-hero-kicker">People Operations</span>
        <h2>One calmer place for HR, attendance, leave, and daily team visibility.</h2>
        <p>Designed for clean workflows, quick approvals, and better focus across your organization.</p>
      </div>
    </div>
  </div>
</div>

<!-- MAIN APP -->
<div id="app">
  <div class="topbar">
    <div class="topbar-brand">
      <div class="sb-brand-mark">
        <img src="{{ asset('uploads/logo/logo.png') }}" alt="WorkPulse Logo">
      </div>
      <div class="sb-brand-copy">
        <h1>WorkPulse</h1>
        <p>People operations workspace</p>
      </div>
    </div>
    <div class="topbar-main">
      <div class="topbar-title-block">
        <div class="topbar-kicker">Workspace</div>
        <div class="topbar-title" id="page-title">Dashboard</div>
      </div>
      <div class="topbar-tools">
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
  </div>

  <aside class="sidebar" id="sidebar">
    <div id="sidebar-nav"></div>

    <div class="sb-footer">
      <div class="sidebar-toggle-row">
        <button class="sidebar-toggle" type="button" id="sidebar-toggle" onclick="window.toggleSidebarCollapse && window.toggleSidebarCollapse()" title="Collapse sidebar" aria-label="Collapse sidebar">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M10.5 3.5L5.5 8l5 4.5"></path></svg>
        </button>
      </div>
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

    <div class="content" id="main-content"></div>
  </div>
</div>
