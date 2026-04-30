<!-- LOGIN SCREEN -->
<div id="login-screen">
  <div class="login-shell">
    <div class="login-panel">
      <div class="login-brand-row">
        <div class="login-logo-mark">
          <img src="{{ asset('uploads/logo/logo.png') }}" alt="muSharp Logo">
        </div>

      </div>
      <div class="login-copy-block">
        <h1 class="login-headline">Modernize your HR operations</h1>
        <p class="login-sub">Welcome back. Please sign in to your workspace and continue managing attendance, teams, and approvals.</p>
      </div>
      <div class="login-form-card">
        <div class="lf-group">
          <label class="lf-label">Email Address <span class="req-star">*</span></label>
          <input type="email" class="lf-input" id="l-email" placeholder="Enter your email" value="admin@musharp.com">
        </div>
        <div class="lf-group">
          <label class="lf-label">Password <span class="req-star">*</span></label>
          <input type="password" class="lf-input" id="l-pass" placeholder="Enter password" value="admin123">
        </div>
        <div class="lf-err" id="l-err">Invalid credentials. Please try again.</div>
        <div class="lf-err" id="forgot-msg" style="display:none;"></div>
        <div class="login-form-meta">
          <span>Use your official muSharp account credentials</span>
          <button type="button" onclick="window.sendForgotPassword && window.sendForgotPassword()" style="border:0;background:transparent;color:var(--accent);font:inherit;font-weight:700;cursor:pointer;padding:0;">Forgot password?</button>
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
      <button class="mobile-menu-btn" type="button" onclick="window.toggleMobileNav && window.toggleMobileNav()" aria-label="Open navigation">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 4h12M2 8h12M2 12h12"></path></svg>
      </button>
      <div class="sb-brand-mark">
        <img src="{{ asset('uploads/logo/logo.png') }}" alt="muSharp Logo">
      </div>
      <div class="sb-brand-copy">
        <h1>muSharp</h1>
        <p>People operations workspace</p>
      </div>
    </div>
    <div class="topbar-main">
      <div class="topbar-title-block">
        <div class="topbar-kicker">Workspace</div>
        <div class="topbar-title" id="page-title">Dashboard</div>
      </div>
      <div class="tb-clock-mobile" id="tb-clock-mobile">00:00:00</div>
      <div class="topbar-mobile-spacer" aria-hidden="true"></div>
      <div class="topbar-tools">
        <div class="tb-clock" id="tb-clock">00:00:00</div>
        <div id="topbar-actions"></div>
        <div class="notif-wrap">
          <button class="topbar-icon-btn" onclick="window.openNotificationsPage ? window.openNotificationsPage() : window.showPage('leave')" title="Notifications">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1a5.5 5.5 0 015.5 5.5c0 3-1 5-1.5 6H4c-.5-1-1.5-3-1.5-6A5.5 5.5 0 018 1z"/><path d="M6 12v.5a2 2 0 004 0V12"/></svg>
          </button>
          <div class="notif-dot" id="notif-dot"></div>
        </div>
        <div class="topbar-user-menu" id="topbar-user-menu">
          <button class="topbar-user-chip topbar-user-trigger" type="button" onclick="window.toggleTopbarUserMenu && window.toggleTopbarUserMenu()" aria-haspopup="menu" aria-expanded="false" id="topbar-user-trigger" aria-label="Open profile menu">
            <div class="av av-32 topbar-user-avatar" id="tb-avatar" style="background:var(--accent);color:#fff;">AK</div>
            <div class="topbar-user-copy">
              <strong id="tb-name">Ahmed Karim</strong>
              <span id="tb-email">hello@musharp.com</span>
            </div>
            <svg class="topbar-user-caret" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6l4 4 4-4"></path></svg>
          </button>
          <div class="topbar-user-dropdown" id="topbar-user-dropdown" role="menu">
            <div class="topbar-user-dropdown-head">
              <div class="av av-40 topbar-user-dropdown-avatar" id="tb-dropdown-avatar" style="background:var(--accent);color:#fff;">AK</div>
              <div class="topbar-user-dropdown-copy">
                <strong id="tb-name-menu">Ahmed Karim</strong>
                <span id="tb-email-menu">hello@musharp.com</span>
              </div>
            </div>
            <button class="topbar-user-dropdown-item" type="button" onclick="window.toggleTopbarUserMenu && window.toggleTopbarUserMenu(false); window.openAccountSettings && window.openAccountSettings()">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="8" cy="8" r="2.5"></circle><path d="M8 1.5l1 .4.8 1.7 1.9.3 1.1 1.6-.8 1.7.8 1.8-1.1 1.5-1.9.4-.8 1.7-1 .4-1-.4-.8-1.7-1.9-.4-1.1-1.5.8-1.8-.8-1.7 1.1-1.6 1.9-.3.8-1.7 1-.4z"></path></svg>
              <span>Account Settings</span>
            </button>
            <button class="topbar-user-dropdown-item danger" type="button" onclick="window.toggleTopbarUserMenu && window.toggleTopbarUserMenu(false); window.doLogout && window.doLogout()">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 2.5H3.8A1.3 1.3 0 002.5 3.8v8.4a1.3 1.3 0 001.3 1.3H6"></path><path d="M9.5 11.5l3.5-3.5-3.5-3.5"></path><path d="M13 8H5.5"></path></svg>
              <span>Logout</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="workspace-shell">
    <button class="mobile-sidebar-backdrop" type="button" id="mobile-sidebar-backdrop" onclick="window.toggleMobileNav && window.toggleMobileNav(false)" aria-label="Close navigation"></button>
    <aside class="sidebar" id="sidebar">
      <div id="sidebar-nav"></div>

      <div class="sb-footer">
        <div class="sidebar-toggle-row">
          <button class="sidebar-toggle" type="button" id="sidebar-toggle" onclick="window.toggleSidebarCollapse && window.toggleSidebarCollapse()" title="Collapse sidebar" aria-label="Collapse sidebar">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M10.5 3.5L5.5 8l5 4.5"></path></svg>
          </button>
        </div>
        <div class="sb-footer-meta">
          <p>muSharp v1.1</p>
        </div>
      </div>
    </aside>

    <div class="main">
      <div class="content" id="main-content"></div>
    </div>
  </div>

  <nav class="mobile-bottom-nav" id="mobile-bottom-nav" aria-label="Mobile navigation"></nav>
</div>
