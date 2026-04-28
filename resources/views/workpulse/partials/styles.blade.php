<style>
:root {
  --font-heading:'Averta-Regular','Averta','Segoe UI','Segoe UI Variable','Helvetica Neue',Arial,sans-serif;
  --font-body:'Segoe UI','Segoe UI Variable','Helvetica Neue',Arial,sans-serif;
  --bg:#EEF2F9;--surface:#FFFFFF;--surface2:#F7FAFF;--surface3:#ECF1F8;--border:#DCE4F2;
  --text:#1D2438;--muted:#6E7691;--faint:#A6B0C4;
  --accent:#268693;--accent-bg:#E7F5F7;--accent-dark:#1B6671;--accent-strong:#205E77;
  --green:#32B57C;--green-bg:#EAF9F2;--green-dark:#1E8B5C;
  --red:#E46667;--red-bg:#FDF0F1;
  --amber:#E0A23A;--amber-bg:#FFF4E2;
  --purple:#268693;--purple-bg:#E7F5F7;
  --teal:#268693;--teal-bg:#E7F5F7;
  --sidebar:220px;--sidebar-collapsed:78px;--hdr:86px;
  --radius-sm:15px;--radius-md:15px;--radius-lg:15px;
  --radius-ui:15px;
  --shadow-sm:0 12px 30px rgba(67,84,125,.08);
  --shadow-md:0 22px 54px rgba(67,84,125,.12);
  --shadow-lg:0 30px 70px rgba(67,84,125,.18);
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{background:var(--bg);}
body{
  font-family:var(--font-body);
  background:
    radial-gradient(circle at top left, rgba(38,134,147,.14), transparent 26%),
    radial-gradient(circle at top right, rgba(38,134,147,.12), transparent 28%),
    linear-gradient(180deg, #F8FAFF 0%, var(--bg) 100%);
  color:var(--text);
  font-size:14px;
  line-height:1.55;
  height:100vh;
  overflow:hidden;
}

/* ── LOGIN SCREEN ── */
#login-screen{
  position:fixed;inset:0;background:
    radial-gradient(circle at top left, rgba(38,134,147,.14), transparent 24%),
    radial-gradient(circle at 82% 12%, rgba(38,134,147,.12), transparent 18%),
    linear-gradient(135deg,#F4F5FF 0%, #FCFDFF 42%, #EEF5FF 100%);
  display:flex;align-items:center;justify-content:center;z-index:9999;padding:36px;
}
.login-shell{
  width:min(calc(100vw - 72px), 1280px);
  min-height:min(760px, calc(100vh - 72px));
  max-height:calc(100vh - 72px);
  background:rgba(255,255,255,.9);
  border:1px solid rgba(220,228,242,.96);
  box-shadow:0 34px 84px rgba(78,92,148,.18);
  border-radius:36px;
  overflow:hidden;
  display:grid;
  grid-template-columns:minmax(420px, 44%) minmax(0, 1fr);
  position:relative;
}
.login-shell::before{
  content:'';
  position:absolute;
  inset:14px;
  border-radius:28px;
  border:1px solid rgba(255,255,255,.74);
  pointer-events:none;
}
.login-panel{
  background:
    linear-gradient(135deg, rgba(255,255,255,.98) 0%, rgba(250,251,255,.96) 100%);
  padding:34px 42px 28px;
  display:flex;
  flex-direction:column;
  justify-content:flex-start;
  position:relative;
}
.login-panel::after{
  content:'';
  position:absolute;
  top:0;
  right:0;
  width:1px;
  height:100%;
  background:linear-gradient(180deg, rgba(213,221,241,.2), rgba(213,221,241,.9), rgba(213,221,241,.2));
}
.login-brand-row{display:flex;align-items:center;gap:16px;margin-bottom:26px;}
.login-logo-mark{
  width:62px;height:62px;border-radius:20px;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(180deg,#FFFFFF 0%, #EEF4FF 100%);
  border:1px solid rgba(220,228,242,.96);box-shadow:0 16px 28px rgba(103,118,173,.12);
}
.login-logo-mark img{max-width:38px;max-height:38px;display:block;}
.login-logo{font-family:var(--font-heading);font-size:34px;font-weight:800;color:#1F4B53;line-height:1;}
.login-logo span{color:#268693;}
.login-brand-copy{font-size:12px;color:#8A92AD;margin-top:6px;}
.login-copy-block{max-width:560px;margin-bottom:20px;}
.login-headline{font-family:var(--font-heading);font-size:48px;line-height:1.02;letter-spacing:-1.7px;color:#1F2740;margin-bottom:12px;}
.login-sub{font-size:14px;color:#6F7792;line-height:1.7;margin-bottom:0;max-width:500px;}
.login-form-card{
  margin-top:8px;max-width:540px;background:rgba(255,255,255,.94);border:1px solid rgba(220,228,242,.96);
  border-radius:24px;padding:22px;box-shadow:0 20px 38px rgba(103,118,173,.10);
  position:relative;
  overflow:hidden;
}

.lf-group{margin-bottom:12px;}
.lf-label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#7983A1;margin-bottom:7px;}
.lf-input{width:100%;min-height:54px;padding:13px 16px;border:1.5px solid rgba(214,222,241,.96);border-radius:16px;font-family:var(--font-body);font-size:15px;color:var(--text);background:linear-gradient(180deg,#FFFFFF 0%, #FBFCFF 100%);outline:none;transition:border .15s, box-shadow .15s, transform .15s;}
.lf-input:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(38,134,147,.10);transform:translateY(-1px);}
.lf-btn{width:100%;padding:14px 18px;border-radius:16px;border:none;background:linear-gradient(135deg,#268693 0%, #1F6F7A 100%);color:#fff;font-family:var(--font-body);font-size:16px;font-weight:800;cursor:pointer;margin-top:8px;transition:.15s;box-shadow:0 18px 30px rgba(38,134,147,.20);}
.lf-btn:hover{background:linear-gradient(135deg,#227985 0%, #195F69 100%);transform:translateY(-1px);}
.lf-hint{font-size:11px;color:var(--muted);margin-top:14px;text-align:center;line-height:1.6;}
.lf-err{color:var(--red);font-size:12px;margin-top:6px;display:none;}
.login-form-meta{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:8px 0 10px;font-size:12px;color:var(--muted);}
.login-footer-note{max-width:540px;margin-top:auto;padding-top:14px;font-size:11px;color:#8A93AC;}
.login-hero{
  position:relative;overflow:hidden;background:
    radial-gradient(circle at top right, rgba(118,136,240,.22), transparent 24%),
    linear-gradient(180deg,#FBFCFF 0%, #EEF3FF 100%);
  padding:30px 30px 24px;display:flex;align-items:flex-end;justify-content:flex-start;
}
.login-hero-grid{position:absolute;inset:0;background:
    linear-gradient(135deg, rgba(38,134,147,.05) 0%, transparent 28%),
    linear-gradient(45deg, rgba(38,134,147,.04) 0%, transparent 30%);
}
.login-hero-copy{position:relative;z-index:2;max-width:420px;margin-bottom:65px;}
.login-hero-kicker{display:inline-flex;padding:8px 12px;border-radius:999px;background:rgba(38,134,147,.10);color:#268693;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px;}
.login-hero-copy h2{font-size:34px;line-height:1.08;letter-spacing:-1.1px;color:#2B3560;margin-bottom:12px;}
.login-hero-copy p{font-size:13px;line-height:1.65;color:#7280A4;}
.login-balloon{position:absolute;width:54px;height:70px;border-radius:28px 28px 20px 20px;border:3px solid rgba(38,134,147,.16);background:rgba(255,255,255,.34);box-shadow:inset 0 0 0 10px rgba(38,134,147,.02), 0 20px 30px rgba(38,134,147,.05);}
.login-balloon::after{content:'';position:absolute;left:50%;bottom:-26px;width:2px;height:26px;background:rgba(38,134,147,.12);transform:translateX(-50%);}
.balloon-a{top:68px;left:42px;transform:scale(.78);}
.balloon-b{top:24px;right:130px;transform:scale(1.1);}
.balloon-c{top:86px;right:28px;transform:scale(.88);}
.login-cityline{position:absolute;inset:auto 18px 0 18px;height:64%;min-height:280px;}
.city-tower,.city-wheel,.city-arch{position:absolute;bottom:0;}
.city-tower{background:linear-gradient(180deg,rgba(145,216,225,.92) 0%, rgba(38,134,147,.58) 100%);opacity:.48;}
.tower-a{left:12%;width:70px;height:44%;clip-path:polygon(20% 100%,20% 28%,36% 14%,50% 28%,50% 100%);}
.tower-b{left:25%;width:28px;height:58%;clip-path:polygon(45% 100%,45% 24%,50% 0,55% 24%,55% 100%);}
.tower-c{left:36%;width:116px;height:68%;clip-path:polygon(26% 100%,26% 22%,41% 22%,44% 0,56% 0,59% 22%,74% 22%,74% 100%);}
.city-wheel{right:0;width:270px;height:270px;border-radius:50%;border:6px solid rgba(38,134,147,.18);opacity:.42;filter:drop-shadow(0 18px 24px rgba(38,134,147,.04));}
.city-wheel::before,.city-wheel::after{content:'';position:absolute;inset:18px;border-radius:50%;border:2px solid rgba(38,134,147,.12);}
.city-wheel::after{inset:48%;border-width:10px;border-color:rgba(38,134,147,.16);}
.city-arch{right:62px;width:106px;height:42%;background:linear-gradient(180deg,rgba(38,134,147,.72) 0%, rgba(22,104,114,.72) 100%);border-radius:16px 16px 0 0;clip-path:polygon(0 100%,0 18%,12% 0,88% 0,100% 18%,100% 100%,82% 100%,82% 20%,68% 20%,68% 100%,32% 100%,32% 20%,18% 20%,18% 100%);box-shadow:0 18px 30px rgba(38,134,147,.08);}

/* UI radius normalization */
:where(
  .login-shell,.login-shell::before,.login-logo-mark,.login-form-card,.lf-input,.lf-btn,
  .sidebar,.main,.topbar,.topbar-brand,.topbar-icon-btn,.topbar-user-chip,.sidebar-toggle,
  .sb-brand-mark,.nav-item,.sb-promo,.btn,.fi,.search-input,.alert,.clock-widget,.clock-widget::after,
  .punch-btn,.card,.stat-card,.soft-table,.toolbar-card,.metric-box,.directory-stat,.directory-card,
  .profile-summary,.panel-card,.pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell,.pp-leave-card,
  .pp-mini-empty,.pp-doc-card,.leave-mini-card,.doc-card,.mini-kpi,.notif-card,.notif-empty,.hero-panel,
  .hero-chip,.emp-pp-card,.emp-pp-illus-card,.emp-pp-illus-board,.emp-pp-leaf,.emp-pp-empty,
  .dashboard-recent-card,.dashboard-leave-card,.dashboard-leave-card-compact,.dashboard-upcoming-card,
  .dashboard-attendance-card,.dashboard-team-card,.dashboard-checkedin-card,.dashboard-absent-card,
  .dashboard-leave-request,.dashboard-recent-search,.dashboard-filter-btn,.dashboard-event-date,
  .dashboard-event-tag,.admin-att-card,.admin-att-summary,.admin-att-mini,.modal,.chart-area,
  .profile-hero,.dept-card,.ann,.emp-clock-widget,.org-node,.mon-card,.content > .page.active
){
  border-radius:var(--radius-ui) !important;
}

:where(
  [style*="border-radius:7px"],[style*="border-radius:8px"],[style*="border-radius:9px"],
  [style*="border-radius:10px"],[style*="border-radius:12px"],[style*="border-radius:14px"],
  [style*="border-radius:16px"],[style*="border-radius:18px"],[style*="border-radius:20px"],
  [style*="border-radius:22px"],[style*="border-radius:24px"],[style*="border-radius:28px"],
  [style*="border-radius:30px"],[style*="border-radius:36px"]
){
  border-radius:var(--radius-ui) !important;
}

/* ── LAYOUT ── */
#app{
  display:none;
  height:100vh;
  margin:0;
  overflow:hidden;
  flex-direction:column;
  flex-wrap:nowrap;
  column-gap:0;
  row-gap:16px;
  padding:0;
  background:
    linear-gradient(180deg, rgba(232,236,255,.92) 0%, rgba(242,246,255,.94) 88px, rgba(247,250,255,.98) 88px, rgba(244,248,255,.98) 100%),
    radial-gradient(circle at top left, rgba(38,134,147,.14), transparent 28%),
    radial-gradient(circle at top right, rgba(38,134,147,.10), transparent 24%);
  border:1px solid rgba(219,227,242,.96);
  border-radius:0;
  box-shadow:0 24px 60px rgba(65,79,115,.14);
  position:relative;
}
#app.visible{display:flex;}
#app::before{
  display:none;
}
#app::after{
  display:none;
}

/* ── SIDEBAR ── */
.topbar{
  width:100%;
  margin:0;
  height:var(--hdr);
  background:transparent;
  border-bottom:1px solid rgba(190,201,228,.95);
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 24px;
  gap:24px;
  flex-shrink:0;
  border-radius:0 !important;
  box-shadow:inset 0 -1px 0 rgba(255,255,255,.75);
}
.topbar-brand{
  display:flex;
  align-items:center;
  gap:14px;
  min-width:0;
  flex:0 0 var(--sidebar);
  width:var(--sidebar);
  max-width:var(--sidebar);
  padding:0;
  margin-right:0;
  border-right:none;
}
.sidebar-toggle{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:44px;
  height:44px;
  border:none;
  border-radius:14px;
  background:linear-gradient(180deg,#FFFFFF 0%, #EEF8F9 100%);
  color:var(--accent);
  cursor:pointer;
  box-shadow:0 12px 24px rgba(38,134,147,.10), inset 0 1px 0 rgba(255,255,255,.85);
  transition:.18s;
  border:1px solid rgba(38,134,147,.16);
}
.sidebar-toggle:hover{transform:translateY(-1px);box-shadow:0 16px 28px rgba(38,134,147,.14);color:var(--accent-dark);}
.sidebar-toggle svg{transition:transform .18s ease;}
.topbar-main{
  flex:1;
  display:flex;
  align-items:center;
  gap:18px;
  min-width:0;
  padding-left:0;
}
.workspace-shell{
  width:100%;
  flex:1;
  min-height:0;
  display:grid;
  grid-template-columns:var(--sidebar) minmax(0,1fr);
  gap:16px;
  align-items:stretch;
  overflow:hidden;
  padding:0 20px 20px;
}
.sidebar{
  width:var(--sidebar);
  background:linear-gradient(180deg, rgba(255,255,255,.42) 0%, rgba(255,255,255,.68) 100%);
  backdrop-filter:blur(12px);
  color:var(--text);
  display:flex;
  flex-direction:column;
  height:100%;
  flex-shrink:0;
  min-height:0;
  border:1px solid rgba(214,222,241,.92);
  border-right:1px solid rgba(212,220,238,.88);
  border-radius:24px;
  padding:12px 10px 16px;
  position:relative;
  z-index:1;
  transition:width .18s ease, padding .18s ease;
  box-shadow:0 18px 36px rgba(103,118,173,.08);
  overflow:hidden;
}
.sidebar-toggle-row{
  display:flex;
  justify-content:center;
  align-items:center;
  padding:0 0 14px;
}
.sidebar::-webkit-scrollbar{width:6px;}
.sb-brand-mark{width:52px;height:52px;border-radius:18px;background:linear-gradient(145deg,#FFFFFF 0%, #EEF3FF 100%);display:flex;align-items:center;justify-content:center;box-shadow:inset 0 1px 0 rgba(255,255,255,.9), 0 10px 24px rgba(67,84,125,.12);}
.sb-brand-mark img{max-width:34px;max-height:34px;display:block;}
.sb-brand-copy h1{font-family:var(--font-heading);font-size:20px;font-weight:800;color:var(--text);letter-spacing:-.4px;}
.sb-brand-copy p{font-size:11px;color:var(--muted);margin-top:4px;}
.sb-sect{display:none;}
#sidebar-nav{
  display:flex;
  flex-direction:column;
  gap:4px;
  flex:1;
  min-height:0;
  overflow-y:auto;
  padding-right:2px;
  padding-bottom:10px;
}
#sidebar-nav::-webkit-scrollbar{width:6px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 14px;cursor:pointer;color:#4E556F;font-size:13px;font-weight:700;transition:.18s;position:relative;border-radius:12px;margin:0;}
.nav-item:hover{background:rgba(38,134,147,.08);color:var(--accent-dark);transform:none;}
.nav-item.active{background:linear-gradient(180deg,#268693 0%, #1F6F7A 100%);color:#fff;box-shadow:0 10px 22px rgba(38,134,147,.24);}
.nav-item.active::before{display:none;}
.nav-item svg{width:14px;height:14px;flex-shrink:0;}
.nav-label{white-space:nowrap;}
.nav-item .live-dot{margin-left:auto;background:#9FE2BE;}
.nav-item.active .live-dot{background:#FFFFFF;}
.nav-item.active svg{color:currentColor;}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:10px;}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#1B7A42;margin-left:auto;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.sb-footer{
  padding:18px 6px 6px;
  margin-top:auto;
  flex-shrink:0;
  border-top:none;
  background:linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(248,250,255,.92) 18%, rgba(248,250,255,.98) 100%);
  position:relative;
  z-index:2;
}
.sb-footer-meta{display:block;}
.sb-footer p{font-size:10px;color:var(--muted);}
.sb-promo{padding:14px 14px 12px;border-radius:14px;background:linear-gradient(180deg,#E8F7F8 0%, #D8F0F2 100%);border:1px solid rgba(38,134,147,.16);margin-bottom:14px;box-shadow:inset 0 1px 0 rgba(255,255,255,.5);}
.sb-promo-title{font-size:13px;font-weight:800;color:#2F3758;line-height:1.2;}
.sb-promo-copy{margin-top:6px;font-size:10px;color:#6E7691;line-height:1.45;}

#app.sidebar-collapsed .sidebar{
  width:var(--sidebar-collapsed);
  padding-left:8px;
  padding-right:8px;
}
#app.sidebar-collapsed .workspace-shell{
  grid-template-columns:var(--sidebar-collapsed) minmax(0,1fr);
}
#app.sidebar-collapsed .sidebar-toggle-row{
  justify-content:center;
  padding-bottom:10px;
}
#app.sidebar-collapsed .nav-item{
  justify-content:center;
  padding:11px 0;
  gap:0;
}
#app.sidebar-collapsed .nav-label,
#app.sidebar-collapsed .sb-footer-meta,
#app.sidebar-collapsed .sb-sect{
  display:none;
}
#app.sidebar-collapsed .sb-footer{
  padding-top:12px;
}
#app.sidebar-collapsed #sidebar-nav{
  gap:8px;
}
#app.sidebar-collapsed .nav-item .nav-badge{
  position:absolute;
  top:4px;
  right:4px;
  margin-left:0;
}
#app.sidebar-collapsed .nav-item .live-dot{
  position:absolute;
  top:7px;
  right:7px;
  margin-left:0;
}
#app.sidebar-collapsed .sidebar-toggle svg{
  transform:rotate(180deg);
}

/* ── AVATAR ── */
.av{border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;}
.av-32{width:32px;height:32px;font-size:12px;}
.av-28{width:28px;height:28px;font-size:11px;}
.av-40{width:40px;height:40px;font-size:14px;}
.av-64{width:64px;height:64px;font-size:22px;}

/* ── MAIN ── */
.main{
  flex:1;
  display:flex;
  flex-direction:column;
  height:100%;
  overflow:hidden;
  background:linear-gradient(180deg, rgba(255,255,255,.38) 0%, rgba(248,251,255,.62) 100%);
  position:relative;
  min-width:0;
  min-height:0;
  border:1px solid rgba(214,222,241,.92);
  border-radius:24px;
  box-shadow:0 18px 36px rgba(103,118,173,.08);
}
.topbar-title-block{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1;}
.topbar-kicker{font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#98A3B9;}
.topbar-title{font-family:var(--font-heading);font-size:28px;font-weight:800;letter-spacing:-.8px;color:#232B45;line-height:1;}
.topbar-tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.topbar-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:14px;border:1px solid rgba(38,134,147,.14);background:linear-gradient(180deg,#FFFFFF 0%, #EFF8F9 100%);color:var(--accent);cursor:pointer;box-shadow:0 10px 20px rgba(38,134,147,.08);}
.topbar-icon-btn:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(38,134,147,.12);}
.tb-clock{font-family:var(--font-heading);font-size:30px;font-weight:800;color:var(--accent-strong);letter-spacing:-1px;font-variant-numeric:tabular-nums;padding:0 8px;}
.topbar-user-chip{display:flex;align-items:center;gap:10px;padding:8px 10px 8px 8px;border-radius:18px;background:linear-gradient(180deg,#FFFFFF 0%, #F4F7FF 100%);border:1px solid rgba(220,228,242,.96);box-shadow:0 12px 24px rgba(103,118,173,.08);}
.topbar-user-menu{position:relative;display:flex;align-items:center;}
.topbar-user-trigger{cursor:pointer;min-width:0;color:inherit;}
.topbar-user-trigger .topbar-user-copy{flex:1;}
.topbar-user-caret{flex-shrink:0;color:#8B95B1;transition:transform .18s ease,color .18s ease;}
.topbar-user-menu.open .topbar-user-caret{transform:rotate(180deg);color:var(--accent);}
.topbar-user-dropdown{
  position:absolute;
  top:calc(100% + 10px);
  right:0;
  min-width:210px;
  padding:8px;
  border-radius:18px;
  border:1px solid rgba(220,228,242,.98);
  background:linear-gradient(180deg,#FFFFFF 0%, #F1FAFB 100%);
  box-shadow:0 22px 46px rgba(73,86,132,.18);
  display:none;
  z-index:70;
}
.topbar-user-menu.open .topbar-user-dropdown{display:block;}
.topbar-user-dropdown-item{
  width:100%;
  border:0;
  background:transparent;
  display:flex;
  align-items:center;
  gap:10px;
  padding:11px 12px;
  border-radius:12px;
  color:#2C3552;
  font-size:13px;
  font-weight:700;
  text-align:left;
  cursor:pointer;
  transition:background .18s ease,color .18s ease,transform .18s ease;
}
.topbar-user-dropdown-item:hover{
  background:rgba(38,134,147,.10);
  color:var(--accent-dark);
  transform:translateY(-1px);
}
.topbar-user-dropdown-item.danger:hover{
  background:rgba(239,89,89,.1);
  color:#D43F3F;
}
.topbar-user-avatar{width:40px;height:40px;box-shadow:0 8px 18px rgba(103,118,173,.14);}
.topbar-user-copy{display:flex;flex-direction:column;min-width:0;}
.topbar-user-copy strong{font-size:13px;color:#2C3552;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.topbar-user-copy span{font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.content{
  flex:1;
  height:100%;
  overflow-y:auto;
  overflow-x:hidden;
  padding:22px 22px 26px;
  background:transparent;
  min-height:0;
  overscroll-behavior:contain;
}
.content > .page.active{
  min-height:calc(100% - 4px);
  padding:18px;
  border-radius:28px;
  border:1px solid rgba(221,229,244,.9);
  background:
    linear-gradient(180deg, rgba(255,255,255,.74) 0%, rgba(250,252,255,.92) 100%),
    radial-gradient(circle at top right, rgba(128,138,241,.08), transparent 22%);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.82);
}

@media(max-width:1180px){
  .login-shell{grid-template-columns:1fr;min-height:auto;}
  .login-panel{padding:38px 32px;}
  .login-panel::after{display:none;}
  .login-copy-block,.login-form-card,.login-footer-note{max-width:none;}
  .login-headline{font-size:46px;}
  .login-hero{min-height:360px;padding:30px;align-items:flex-start;}
  .login-hero-copy{max-width:560px;margin-bottom:0;}
  .login-hero-copy h2{font-size:32px;}
  .login-cityline{height:58%;min-height:280px;right:14px;left:14px;}
  .city-wheel{width:260px;height:260px;}
  .city-arch{right:54px;}
  #app{height:100vh;margin:0;flex-direction:column;row-gap:12px;padding:0 12px 12px;}
  #app::before{left:0;right:0;}
  .topbar{width:100%;margin:0;padding:12px 16px;gap:12px;flex-wrap:wrap;height:auto;min-height:var(--hdr);align-items:flex-start;}
}

@media(max-width:900px){
  #login-screen{padding:24px 18px;}
  .login-shell{
    width:min(calc(100vw - 36px), 760px);
    min-height:calc(100vh - 48px);
    max-height:calc(100vh - 48px);
    grid-template-columns:1fr;
    border-radius:28px;
  }
  .login-shell::before{inset:10px;border-radius:22px;}
  .login-panel{padding:32px 24px 24px;}
  .login-brand-row{margin-bottom:26px;}
  .login-headline{font-size:38px;letter-spacing:-1.4px;}
  .login-sub{font-size:14px;line-height:1.7;}
  .login-form-card{padding:22px;border-radius:22px;}
  .login-hero{
    min-height:300px;
    padding:24px 22px;
    border-top:1px solid rgba(220,228,242,.9);
  }
  .login-hero-copy{max-width:100%;}
  .login-hero-copy h2{font-size:28px;}
  .login-hero-copy p{font-size:13px;max-width:460px;}
  .login-balloon{display:none;}
  .login-cityline{height:62%;min-height:220px;opacity:.92;}
  .tower-a{left:8%;}
  .tower-b{left:22%;}
  .tower-c{left:32%;}
  .city-wheel{width:220px;height:220px;right:-10px;}
  .city-arch{width:102px;right:34px;}
}

@media(max-height:820px){
  #login-screen{padding:18px 24px;}
  .login-shell{min-height:min(680px, calc(100vh - 36px));max-height:calc(100vh - 36px);}
  .login-panel{padding:28px 34px 22px;}
  .login-brand-row{margin-bottom:18px;}
  .login-copy-block{margin-bottom:16px;}
  .login-headline{font-size:40px;}
  .login-sub{font-size:13px;line-height:1.6;}
  .login-form-card{padding:18px;}
  .lf-input{min-height:50px;}
  .lf-btn{padding:13px 16px;}
  .login-hero{padding:24px 24px 18px;}
  .login-hero-copy h2{font-size:28px;}
  .login-cityline{min-height:220px;height:56%;}
  .city-wheel{width:220px;height:220px;}
  .city-arch{width:92px;right:44px;}
}

/* ── PAGE / TAB SYSTEM ── */
.page{display:none;}.page.active{display:block;}
.tab-content{display:none;}.tab-content.active{display:block;}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;border-radius:16px;border:1px solid var(--border);background:linear-gradient(180deg,#FFFFFF 0%, #F5F8FF 100%);color:var(--text);font-family:var(--font-body);font-size:13px;font-weight:700;cursor:pointer;transition:.18s;white-space:nowrap;box-shadow:0 8px 18px rgba(103,118,173,.06);}
.btn:hover{border-color:rgba(38,134,147,.28);color:var(--accent-strong);transform:translateY(-1px);box-shadow:0 12px 22px rgba(38,134,147,.10);}
.btn-sm{padding:5px 10px;font-size:12px;}
.btn-primary{background:linear-gradient(135deg,#2C93A0 0%, #268693 55%, #1F6F7A 100%);border-color:transparent;color:#fff;box-shadow:0 14px 24px rgba(38,134,147,.18);}
.btn-primary:hover{background:linear-gradient(135deg,#258592 0%, #207985 55%, #195F69 100%);border-color:transparent;color:#fff;}
.btn-green{background:linear-gradient(180deg,var(--green) 0%, var(--green-dark) 100%);border-color:var(--green);color:#fff;}
.btn-red{background:var(--red);border-color:var(--red);color:#fff;}
.btn-danger{border-color:var(--red);color:var(--red);}
.btn-danger:hover{background:var(--red);color:#fff;}
.btn-ghost{border-color:transparent;background:transparent;}
.btn-ghost:hover{background:var(--surface2);border-color:var(--border);}

/* ── CARDS ── */
.card{
  background:
    radial-gradient(circle at top right, rgba(128,138,241,.08), transparent 26%),
    radial-gradient(circle at top left, rgba(38,134,147,.05), transparent 20%),
    linear-gradient(180deg, rgba(255,255,255,.98), rgba(246,249,255,.96));
  border-radius:22px;
  border:1px solid rgba(220,228,242,.96);
  padding:22px;
  box-shadow:0 14px 28px rgba(67,84,125,.06), inset 0 1px 0 rgba(255,255,255,.92);
  position:relative;
  overflow:hidden;
}
.card::before{
  display:none;
}
.card::after{
  content:'';
  position:absolute;
  top:16px;
  right:16px;
  width:72px;
  height:72px;
  border-radius:50%;
  background:radial-gradient(circle, rgba(38,134,147,.08) 0%, rgba(38,134,147,0) 72%);
  pointer-events:none;
}
.card-hdr{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom:18px;
  position:relative;
  z-index:1;
}
.card-title{font-family:var(--font-heading);font-size:18px;font-weight:800;letter-spacing:-.35px;color:#273152;line-height:1.15;}
.stat-card{
  background:
    radial-gradient(circle at top right, rgba(128,138,241,.10), transparent 28%),
    linear-gradient(180deg,#FFFFFF 0%, #F6F8FF 100%);
  border-radius:20px;
  border:1px solid rgba(220,228,242,.96);
  padding:20px 22px 18px;
  box-shadow:0 12px 24px rgba(38,84,92,.06), inset 0 1px 0 rgba(255,255,255,.92);
  position:relative;
  overflow:hidden;
}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,#2C93A0, var(--accent), rgba(38,134,147,.18));}
.stat-card::before{display:none;}
.stat-card::after{
  content:'';
  position:absolute;
  right:-18px;
  top:-18px;
  width:96px;
  height:96px;
  border-radius:50%;
  background:radial-gradient(circle, rgba(38,134,147,.08) 0%, rgba(38,134,147,0) 68%);
  pointer-events:none;
}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);letter-spacing:.3px;margin-bottom:5px;}
.stat-val{font-family:var(--font-heading);font-size:34px;font-weight:800;letter-spacing:-1px;line-height:1.05;color:#273152;}
.stat-sub{font-size:11px;color:var(--muted);margin-top:6px;line-height:1.45;}
.card > *,.stat-card > *{position:relative;z-index:1;}
.dash-stat-grid .stat-card{min-height:132px;}
.dash-stat-grid .stat-label{font-size:12px;text-transform:none;letter-spacing:0;}
.dash-stat-grid .stat-val{font-size:30px;}
.dash-stat-grid .stat-sub{font-size:12px;}

:where(
  .toolbar-card,.metric-box,.directory-stat,.directory-card,.profile-summary,.panel-card,
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell,
  .pp-leave-card,.pp-doc-card,.pp-mini-empty,.leave-mini-card,.doc-card,.mini-kpi,
  .notif-card,.notif-empty,.emp-pp-card,.emp-pp-leaf,.emp-pp-empty,.dept-card,.mon-card
){
  background:
    radial-gradient(circle at top right, rgba(128,138,241,.08), transparent 26%),
    radial-gradient(circle at top left, rgba(38,134,147,.05), transparent 20%),
    linear-gradient(180deg, rgba(255,255,255,.98), rgba(246,249,255,.96)) !important;
  border:1px solid rgba(220,228,242,.96) !important;
  box-shadow:0 14px 28px rgba(67,84,125,.06), inset 0 1px 0 rgba(255,255,255,.92) !important;
  position:relative;
  overflow:hidden;
}

:where(
  .toolbar-card,.metric-box,.directory-stat,.directory-card,.profile-summary,.panel-card,
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell,
  .pp-leave-card,.pp-doc-card,.pp-mini-empty,.leave-mini-card,.doc-card,.mini-kpi,
  .notif-card,.notif-empty,.emp-pp-card,.emp-pp-empty,.dept-card,.mon-card
)::before{
  content:'';
  position:absolute;
  top:16px;
  right:16px;
  width:72px;
  height:72px;
  border-radius:50%;
  background:radial-gradient(circle, rgba(38,134,147,.08) 0%, rgba(38,134,147,0) 72%);
  pointer-events:none;
}

:where(
  .toolbar-card,.metric-box,.directory-stat,.directory-card,.profile-summary,.panel-card,
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell,
  .pp-leave-card,.pp-doc-card,.pp-mini-empty,.leave-mini-card,.doc-card,.mini-kpi,
  .notif-card,.notif-empty,.emp-pp-card,.emp-pp-leaf,.emp-pp-empty,.dept-card,.mon-card
) > *{
  position:relative;
  z-index:1;
}

/* ── GRID ── */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
.dashboard-trio-grid{grid-template-columns:minmax(0,.94fr) minmax(300px,1.12fr) minmax(230px,.74fr);}
@media(max-width:1100px){.g4{grid-template-columns:repeat(2,1fr);}.g3{grid-template-columns:repeat(2,1fr);}}

@media(max-width:1320px){
  .dashboard-trio-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
}

/* ── BADGE ── */
.badge{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid transparent;}
.bg-green{background:var(--green-bg);color:var(--green);}
.bg-red{background:var(--red-bg);color:var(--red);}
.bg-amber{background:var(--amber-bg);color:var(--amber);}
.bg-blue{background:var(--accent-bg);color:var(--accent);}
.bg-purple{background:var(--purple-bg);color:var(--purple);}
.bg-gray{background:var(--surface2);color:var(--muted);}
.bg-teal{background:var(--teal-bg);color:var(--teal);}

/* ── TABLE ── */
.table-wrap{
  overflow-x:auto;
  border:1px solid rgba(220,228,242,.96);
  border-radius:15px;
  background:linear-gradient(180deg,#FFFFFF 0%, #FBFDFF 100%);
  box-shadow:0 14px 28px rgba(103,118,173,.06);
}
table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:13px;
}
thead th{
  text-align:left;
  padding:14px 16px;
  font-size:10px;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.9px;
  color:#73829D;
  border-bottom:1px solid rgba(220,228,242,.96);
  background:linear-gradient(180deg,#F7FAFF 0%, #EEF4FF 100%);
  white-space:nowrap;
}
thead th:first-child{border-top-left-radius:15px;}
thead th:last-child{border-top-right-radius:15px;}
tbody tr{
  transition:background .16s ease, box-shadow .16s ease;
}
tbody tr:nth-child(even){background:rgba(245,248,255,.55);}
tbody tr:hover{
  background:linear-gradient(180deg,#F8FCFF 0%, #EEF8FA 100%);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.85), inset 0 -1px 0 rgba(223,231,245,.9);
}
tbody td{
  padding:13px 16px;
  vertical-align:middle;
  border-bottom:1px solid rgba(228,234,246,.88);
  color:#2F3956;
}
tbody tr:last-child td{border-bottom:none;}
tbody tr:last-child td:first-child{border-bottom-left-radius:15px;}
tbody tr:last-child td:last-child{border-bottom-right-radius:15px;}
.table-wrap table .btn.btn-sm{min-height:34px;}
.table-wrap table .badge{font-size:10px;}

/* ── USER CELL ── */
.ucell{display:flex;align-items:center;gap:8px;}
.ucell-info .n{font-weight:500;font-size:13px;}
.ucell-info .s{font-size:11px;color:var(--muted);}

/* ── TABS ── */
.tabs{display:flex;gap:8px;border-bottom:none;margin-bottom:18px;flex-wrap:wrap;}
.tab{padding:10px 16px;font-size:13px;font-weight:700;cursor:pointer;color:var(--muted);border:1px solid var(--border);border-radius:999px;transition:.18s;background:var(--surface);}
.tab.active{color:var(--green-dark);border-color:rgba(38,134,147,.24);background:linear-gradient(180deg,#EEF8FA 0%, #E4F2F4 100%);}
.tab:hover:not(.active){color:var(--text);background:var(--surface2);}

/* ── FORM ── */
.fg{margin-bottom:13px;}
.fl{display:block;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;}
.req-star{color:var(--red);margin-left:3px;font-weight:800;}
.fi{width:100%;padding:11px 13px;border:1px solid var(--border);border-radius:12px;font-family:var(--font-body);font-size:13px;color:var(--text);background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);outline:none;transition:border .15s, box-shadow .15s, background .15s;}
.fi:focus{border-color:rgba(38,134,147,.38);box-shadow:0 0 0 4px rgba(38,134,147,.08);background:#fff;}
.fi:disabled{background:var(--surface2);color:var(--muted);}
select.fi{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%236E6C63'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
.manager-field{position:relative;}
.manager-trigger{
  width:100%;
  min-height:46px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:0 14px;
  border:1px solid var(--border);
  border-radius:14px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);
  color:var(--text);
  font-family:var(--font-body);
  font-size:13px;
  text-align:left;
  cursor:pointer;
  transition:border-color .15s, box-shadow .15s, transform .15s;
}
.manager-trigger:hover{border-color:rgba(38,134,147,.32);}
.manager-trigger.open{
  border-color:rgba(38,134,147,.48);
  box-shadow:0 0 0 4px rgba(38,134,147,.10);
}
.manager-trigger-value{
  min-width:0;
  overflow:hidden;
  white-space:nowrap;
  text-overflow:ellipsis;
  font-weight:600;
}
.manager-placeholder{color:var(--faint);font-weight:500;}
.manager-trigger-arrow{
  flex-shrink:0;
  color:var(--muted);
  font-size:12px;
  font-weight:800;
  line-height:1;
  transition:transform .15s ease,color .15s ease;
}
.manager-trigger-arrow.up{
  transform:rotate(180deg);
  color:var(--green-dark);
}
.manager-dropdown{
  position:absolute;
  top:calc(100% + 6px);
  left:0;
  right:0;
  z-index:60;
  display:none;
  border:1px solid rgba(38,134,147,.18);
  border-radius:16px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);
  box-shadow:0 20px 36px rgba(160,176,214,.16);
  overflow:hidden;
}
.manager-dropdown.open{display:block;}
.manager-search-wrap{
  padding:10px;
  border-bottom:1px solid rgba(220,228,242,.9);
  background:rgba(255,255,255,.92);
}
.manager-search-input{
  width:100%;
  height:36px;
  padding:0 12px;
  border:1px solid var(--border);
  border-radius:10px;
  background:#F7FAFF;
  color:var(--text);
  font-family:var(--font-body);
  font-size:13px;
  outline:none;
  transition:border-color .15s, box-shadow .15s, background .15s;
}
.manager-search-input:focus{
  border-color:rgba(38,134,147,.42);
  box-shadow:0 0 0 3px rgba(38,134,147,.08);
  background:#fff;
}
.manager-options-list{
  max-height:220px;
  overflow-y:auto;
  padding:6px;
}
.manager-option{
  display:flex;
  align-items:flex-start;
  gap:10px;
  width:100%;
  padding:10px 12px;
  border:none;
  border-radius:12px;
  background:transparent;
  color:var(--text);
  font-family:var(--font-body);
  text-align:left;
  cursor:pointer;
  transition:background .12s ease, color .12s ease;
}
.manager-option:hover{background:#EEF8FA;}
.manager-option.selected{
  background:linear-gradient(180deg,#E9F6F8 0%, #DFF0F2 100%);
  color:var(--green-dark);
}
.manager-option-copy{min-width:0;display:flex;flex-direction:column;gap:2px;}
.manager-option-name{
  font-size:13px;
  font-weight:700;
  line-height:1.35;
  word-break:break-word;
}
.manager-option-meta{
  font-size:11px;
  color:var(--muted);
  line-height:1.45;
  word-break:break-word;
}
.manager-option.selected .manager-option-meta{color:rgba(27,102,113,.86);}
.manager-option-check{
  margin-left:auto;
  flex-shrink:0;
  font-size:12px;
  font-weight:800;
  color:var(--green-dark);
  opacity:0;
}
.manager-option.selected .manager-option-check{opacity:1;}
.manager-no-results{
  display:none;
  padding:12px 14px 14px;
  font-size:12px;
  color:var(--muted);
}

/* ── ROWS ── */
.irow{padding:9px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;}
.irow:last-child{border-bottom:none;}
.ikey{font-size:12px;color:var(--muted);font-weight:500;}
.ival{font-size:13px;font-weight:500;text-align:right;}
.emp-pp-card .irow > .badge{
  flex:0 0 auto;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:72px;
  padding:8px 14px;
  white-space:nowrap;
  line-height:1;
  text-align:center;
}

/* ── ALERT ── */
.alert{padding:15px 18px;border-radius:18px;display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;font-size:13px;border:1px solid transparent;box-shadow:var(--shadow-sm);}
.al-info{background:var(--accent-bg);border-color:var(--accent);color:var(--accent-dark);}
.al-warn{background:var(--amber-bg);border-color:var(--amber);color:var(--amber);}
.al-success{background:var(--green-bg);border-color:var(--green);color:var(--green);}
.al-danger{background:var(--red-bg);border-color:var(--red);color:var(--red);}

/* ── CLOCK WIDGET ── */
.clock-widget{background:radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 26%),radial-gradient(circle at bottom left, rgba(133,214,226,.16), transparent 24%),linear-gradient(145deg,#123038 0%, #184C56 55%, #0F6976 100%);border-radius:22px;padding:24px;color:#fff;text-align:center;position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.08);box-shadow:0 18px 40px rgba(12,39,44,.18);}
.clock-widget::before{content:'';position:absolute;inset:auto auto -42px -42px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.08);filter:blur(2px);}
.clock-widget::after{content:'';position:absolute;inset:18px;border-radius:18px;border:1px solid rgba(255,255,255,.08);pointer-events:none;}
.cw-time{font-family:var(--font-heading);font-size:42px;font-weight:800;letter-spacing:-1.2px;font-variant-numeric:tabular-nums;position:relative;z-index:1;}
.cw-date{font-size:12px;color:rgba(255,255,255,.72);margin-top:4px;position:relative;z-index:1;}
.cw-status{margin:12px 0 8px;position:relative;z-index:1;}
.cw-meta{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin:0 0 14px;position:relative;z-index:1;}
.cw-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.14);color:rgba(255,255,255,.88);font-size:11px;font-weight:600;backdrop-filter:blur(10px);}
.punch-stack{display:flex;flex-direction:column;gap:8px;position:relative;z-index:1;}
.punch-btn{display:flex;width:100%;align-items:center;justify-content:center;min-height:46px;padding:12px 16px;border-radius:14px;border:1px solid transparent;font-family:var(--font-body);font-size:14px;font-weight:700;cursor:pointer;transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease, color .18s ease;margin-top:0;box-shadow:0 10px 24px rgba(0,0,0,.14);}
.punch-btn:hover{transform:translateY(-1px);}
.punch-btn-inline{width:auto;min-width:132px;box-shadow:0 10px 24px rgba(20,48,55,.10);}
.punch-btn-muted{background:rgba(255,255,255,.14)!important;color:rgba(255,255,255,.76)!important;border-color:rgba(255,255,255,.12)!important;box-shadow:none;}
.pb-in{background:linear-gradient(135deg,#1A9870 0%, #157D5C 100%);border-color:rgba(255,255,255,.14);color:#fff;}
.pb-in:hover{box-shadow:0 14px 28px rgba(30,139,87,.30);}
.pb-out{background:linear-gradient(135deg,#D94B43 0%, #BB3A32 100%);border-color:rgba(255,255,255,.14);color:#fff;}
.pb-out:hover{box-shadow:0 14px 28px rgba(217,75,67,.30);}
.pb-break{background:linear-gradient(135deg,#F4F8FA 0%, #DCECF2 100%);border-color:#C8DDE5;color:#124A57;box-shadow:0 12px 26px rgba(12,39,44,.12);}
.pb-break:hover{border-color:#9FC7D1;box-shadow:0 14px 28px rgba(18,74,87,.18);}
.pb-break-in{background:linear-gradient(135deg,#D9922B 0%, #B36A14 100%);border-color:rgba(255,255,255,.14);color:#fff;}
.pb-break-in:hover{box-shadow:0 14px 28px rgba(179,106,20,.30);}

/* ── ADMIN ATTENDANCE ── */
.admin-att-shell{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(360px,.95fr);gap:16px;align-items:stretch;}
.admin-att-hero{
  background:
    radial-gradient(circle at top right, rgba(255,255,255,.12), transparent 28%),
    radial-gradient(circle at bottom left, rgba(38,134,147,.22), transparent 34%),
    linear-gradient(145deg,#194A53 0%, #1E6673 58%, #268693 100%);
  border-radius:28px;
  padding:18px;
  border:1px solid rgba(255,255,255,.10);
  box-shadow:0 22px 42px rgba(24,77,87,.22);
  position:relative;
  overflow:hidden;
}
.admin-att-hero::before{
  content:'';
  position:absolute;
  inset:auto auto -52px -40px;
  width:180px;
  height:180px;
  border-radius:50%;
  background:rgba(255,255,255,.08);
  filter:blur(2px);
}
.admin-att-hero-panel{
  position:relative;
  z-index:1;
  min-height:100%;
  border-radius:22px;
  border:1px solid rgba(255,255,255,.10);
  background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
  padding:24px 24px 20px;
  display:flex;
  flex-direction:column;
  align-items:center;
  text-align:center;
}
.admin-att-eyebrow{
  font-size:11px;
  font-weight:800;
  letter-spacing:.8px;
  text-transform:uppercase;
  color:rgba(255,255,255,.68);
}
.admin-att-time{
  margin-top:10px;
  font-family:var(--font-heading);
  font-size:56px;
  font-weight:800;
  line-height:.95;
  letter-spacing:-2px;
  color:#fff;
  font-variant-numeric:tabular-nums;
}
.admin-att-date{
  margin-top:10px;
  font-size:14px;
  color:rgba(255,255,255,.78);
}
.admin-att-status{margin-top:16px;}
.admin-att-status .badge{font-size:12px;padding:7px 12px;}
.admin-att-meta{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  justify-content:center;
  margin-top:14px;
}
.admin-att-chip{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:9px 14px;
  border-radius:999px;
  background:rgba(255,255,255,.12);
  border:1px solid rgba(255,255,255,.14);
  color:#F4FCFD;
  font-size:12px;
  font-weight:700;
}
.admin-att-actions{
  width:100%;
  display:flex;
  flex-direction:row;
  gap:10px;
  margin-top:20px;
  justify-content:center;
  flex-wrap:wrap;
}
.admin-att-actions .punch-btn{
  border-radius:16px;
  width:auto;
  min-width:160px;
  min-height:36px;
  padding:9px 16px;
  font-size:13px;
  box-shadow:0 12px 22px rgba(10,34,38,.14);
}
.admin-att-actions .pb-out{
  background:linear-gradient(180deg,#268693 0%, #1B6671 100%);
  border-color:#268693;
}
.admin-att-actions .pb-out:hover{
  box-shadow:0 14px 24px rgba(38,134,147,.24);
}
.admin-att-actions .pb-break{
  background:linear-gradient(180deg,#F4FBFC 0%, #E2F2F4 100%);
  color:#1B6671;
  border-color:rgba(38,134,147,.20);
}
.admin-att-actions .pb-break-in{
  background:linear-gradient(180deg,#268693 0%, #1B6671 100%);
  border-color:#268693;
}
.admin-att-actions .pb-break-in:hover{
  box-shadow:0 14px 24px rgba(38,134,147,.24);
}
.admin-att-summary{
  display:flex;
  flex-direction:column;
  justify-content:flex-start;
  gap:0;
  padding:22px;
}
.att-summary-top{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:16px;
  margin-bottom:14px;
}
.att-summary-subtitle{
  margin-top:6px;
  font-size:13px;
  line-height:1.5;
  color:#6E7E97;
}
.att-summary-pill{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:34px;
  padding:0 14px;
  border-radius:999px;
  border:1px solid rgba(167,187,233,.44);
  background:linear-gradient(180deg,rgba(255,255,255,.94) 0%, rgba(237,242,255,.9) 100%);
  box-shadow:0 10px 20px rgba(150,171,214,.14);
  font-size:11px;
  font-weight:800;
  letter-spacing:.55px;
  text-transform:uppercase;
  color:#5A66C9;
  white-space:nowrap;
}
.admin-att-summary-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
  margin-top:2px;
  margin-bottom:16px;
}
.admin-att-stat{
  padding:16px 17px;
  border-radius:15px;
  border:1px solid rgba(220,228,242,.96);
  background:linear-gradient(180deg,rgba(255,255,255,.97) 0%, rgba(245,249,255,.96) 100%);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.92),
    0 10px 22px rgba(189,202,226,.12);
}
.admin-att-stat-highlight{
  background:linear-gradient(135deg,rgba(38,134,147,.96) 0%, rgba(61,99,224,.9) 100%);
  border-color:rgba(76,113,214,.3);
  box-shadow:
    0 18px 32px rgba(73,102,191,.2),
    inset 0 1px 0 rgba(255,255,255,.12);
}
.admin-att-stat-highlight .admin-att-stat-label{
  color:rgba(255,255,255,.76);
}
.admin-att-stat-highlight .admin-att-stat-value{
  color:#fff;
}
.admin-att-stat-label{
  display:block;
  font-size:11px;
  font-weight:800;
  letter-spacing:.6px;
  text-transform:uppercase;
  color:var(--muted);
}
.admin-att-stat-value{
  display:block;
  margin-top:8px;
  font-family:var(--font-heading);
  font-size:24px;
  font-weight:800;
  line-height:1.05;
  letter-spacing:-.8px;
  color:#1F4B53;
}
.admin-att-details{
  display:grid;
  grid-template-columns:1.2fr .8fr .75fr;
  gap:12px;
  border-radius:15px;
  border:1px solid rgba(220,228,242,.9);
  background:linear-gradient(180deg,rgba(252,253,255,.98) 0%, rgba(245,248,255,.94) 100%);
  padding:12px;
}
.att-summary-detail{
  min-width:0;
  padding:14px 15px;
  border-radius:14px;
  background:rgba(255,255,255,.74);
  border:1px solid rgba(225,232,246,.8);
}
.att-summary-detail-label{
  display:block;
  font-size:11px;
  font-weight:800;
  letter-spacing:.58px;
  text-transform:uppercase;
  color:#7A88A0;
}
.att-summary-detail-value{
  display:block;
  margin-top:8px;
  font-size:14px;
  line-height:1.5;
  color:#22314D;
  word-break:break-word;
}
.att-summary-detail-badge{
  display:flex;
  align-items:center;
  min-height:34px;
  margin-top:8px;
}
.att-summary-detail-badge .badge{
  max-width:100%;
}

/* ── TIMELINE ── */
.tl{display:flex;flex-direction:column;gap:0;}
.tl-item{display:flex;gap:10px;position:relative;padding-bottom:14px;}
.tl-item:last-child{padding-bottom:0;}
.tl-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:4px;position:relative;z-index:1;}
.tl-line{position:absolute;left:4px;top:13px;bottom:0;width:1px;background:var(--border);}
.tl-item:last-child .tl-line{display:none;}

/* ── PROGRESS ── */
.prog-bar{height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;margin-top:5px;}
.prog-fill{height:100%;border-radius:3px;transition:width .5s;}

/* ── CALENDAR ── */
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-top:10px;}
.cal-dh{text-align:center;font-size:10px;font-weight:700;color:var(--muted);padding:4px;text-transform:uppercase;}
.cal-day{aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:12px;border-radius:6px;cursor:pointer;position:relative;}
.cal-day:hover{background:var(--surface2);}
.cal-today{background:var(--accent)!important;color:#fff!important;font-weight:700;}
.cal-leave{background:var(--green-bg);color:var(--green);}
.cal-holiday{background:var(--amber-bg);color:var(--amber);}
.cal-event{background:var(--purple-bg);color:var(--purple);}

/* ── MONITOR ── */
.monitor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px;}
.mon-card{background:var(--surface);border:1px solid var(--border);border-radius:9px;padding:10px 12px;display:flex;align-items:center;gap:9px;}
.mon-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.md-in{background:var(--green);}
.md-out{background:var(--faint);}
.md-break{background:var(--amber);}
.md-leave{background:var(--purple);}

/* ── MODAL ── */
.modal-overlay{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(28,42,72,.22);
  backdrop-filter:blur(16px);
  z-index:1000;
  align-items:center;
  justify-content:center;
  padding:22px;
}
.modal-overlay.open{display:flex;}
.modal{
  background:
    radial-gradient(circle at top right, rgba(38,134,147,.1), transparent 24%),
    linear-gradient(180deg, rgba(255,255,255,.998), rgba(245,249,255,.985));
  border-radius:15px;
  padding:28px;
  width:min(680px,96vw);
  max-width:96vw;
  max-height:min(92vh,980px);
  overflow-y:auto;
  overflow-x:hidden;
  box-shadow:
    0 28px 72px rgba(52,76,128,.18),
    0 8px 22px rgba(179,194,223,.16);
  border:1px solid rgba(220,228,242,.96);
}
.modal-wide{width:min(920px,96vw);}
.modal-xl{width:min(1220px,96vw);}
.regulation-modal{width:min(1420px,98vw);}
.modal-hdr{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:18px;
  margin-bottom:22px;
  padding-bottom:18px;
  border-bottom:1px solid rgba(225,232,246,.92);
}
.modal-title{font-family:var(--font-heading);font-size:22px;font-weight:800;color:#1F4B53;letter-spacing:-.4px;line-height:1.1;}
.modal-subtitle{font-size:12px;color:#74839B;margin-top:6px;line-height:1.6;max-width:720px;}
.modal-close{
  position:relative;
  width:38px;
  height:38px;
  border:none;
  border-radius:12px;
  background:linear-gradient(180deg,#FFFFFF 0%, #EFF4FF 100%);
  cursor:pointer;
  color:transparent;
  font-size:0;
  line-height:0;
  flex-shrink:0;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.92), 0 8px 18px rgba(176,190,220,.14);
  transition:background .15s ease, transform .15s ease, box-shadow .15s ease;
}
.modal-close::before,
.modal-close::after{
  content:'';
  position:absolute;
  top:50%;
  left:50%;
  width:16px;
  height:2px;
  border-radius:999px;
  background:var(--muted);
  transform-origin:center;
}
.modal-close::before{transform:translate(-50%,-50%) rotate(45deg);}
.modal-close::after{transform:translate(-50%,-50%) rotate(-45deg);}
.modal-close:hover{
  background:linear-gradient(180deg,#FFFFFF 0%, #E8F0FF 100%);
  transform:scale(1.04);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.96), 0 12px 22px rgba(160,176,214,.2);
}
.modal-close:hover::before,
.modal-close:hover::after{background:var(--text);}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:18px;}
.modal > .fg,
.modal > .g2,
.modal > .g3,
.modal > .tabs,
.modal > .table-wrap,
.modal > #approval-details,
.modal > #edit-leave-balance-grid,
.modal > #edit-leave-policy-grid{
  position:relative;
}
.modal > .fg,
.modal > .g2,
.modal > .g3,
.modal > .tabs,
.modal > .table-wrap,
.modal > #approval-details,
.modal > #edit-leave-balance-grid,
.modal > #edit-leave-policy-grid{
  padding:14px 16px;
  border:1px solid rgba(220,228,242,.92);
  border-radius:15px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);
  box-shadow:0 10px 20px rgba(187,200,225,.08);
}
.modal > .fg + .fg,
.modal > .fg + .g2,
.modal > .fg + .g3,
.modal > .g2 + .fg,
.modal > .g2 + .g2,
.modal > .g2 + .g3,
.modal > .g3 + .fg,
.modal > .g3 + .g2,
.modal > .g3 + .g3,
.modal > .tabs + div,
.modal > #approval-details + .fg,
.modal > #edit-leave-balance-grid + div,
.modal > #edit-leave-policy-grid + .modal-actions,
.modal > .table-wrap + .modal-actions{
  margin-top:14px;
}
.modal > .table-wrap{
  padding:0;
  overflow:hidden;
}
.modal > .table-wrap table{
  margin:0;
}
.employee-modal{width:min(980px,96vw);}
.employee-modal .modal-hdr{
  position:sticky;
  top:-28px;
  z-index:2;
  background:linear-gradient(180deg, rgba(255,255,255,.99), rgba(247,250,255,.96));
  margin:-28px -28px 20px;
  padding:24px 28px 18px;
  backdrop-filter:blur(12px);
}
.employee-modal > .g2,
.employee-modal > .fg,
.employee-modal > .modal-note,
.employee-modal > .employee-salary-grid{
  position:relative;
}
.employee-modal > .g2,
.employee-modal > .fg{
  padding:14px 16px;
  border:1px solid rgba(220,228,242,.9);
  border-radius:15px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);
  box-shadow:0 10px 20px rgba(38,84,92,.04);
}
.employee-modal > .g2 + .g2,
.employee-modal > .fg + .fg,
.employee-modal > .g2 + .fg,
.employee-modal > .fg + .g2,
.employee-modal > .modal-note + .fg,
.employee-modal > .modal-note + .g2,
.employee-modal > .g2 + .modal-note,
.employee-modal > .fg + .modal-note,
.employee-modal > .employee-salary-grid + .modal-actions{
  margin-top:14px;
}
.employee-modal .modal-note{
  border-radius:15px;
  background:linear-gradient(180deg,#F6FAFF 0%, #EEF4FF 100%);
  border-color:rgba(220,228,242,.88);
}
.employee-modal .g2{gap:14px;}
.employee-modal .fg{margin-bottom:14px;}
.employee-modal > .g2 .fg,
.employee-modal > .employee-salary-grid .fg{margin-bottom:0;}
.employee-modal .manager-field{margin-top:2px;}
.modal-section{
  border:1px solid rgba(220,228,242,.92);
  border-radius:15px;
  padding:18px 18px 14px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);
  box-shadow:0 10px 22px rgba(187,200,225,.08);
}
.modal-section + .modal-section{margin-top:14px;}
.modal-section-head{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
}
.modal-section-title{
  font-size:14px;
  font-weight:800;
  color:#1F4B53;
  letter-spacing:-.2px;
}
.modal-section-note{
  font-size:11px;
  color:var(--muted);
  line-height:1.5;
  margin-top:4px;
}
.modal-note{
  font-size:12px;
  color:var(--muted);
  line-height:1.55;
  padding:12px 14px;
  border-radius:15px;
  background:linear-gradient(180deg,#F6FAFF 0%, #EEF4FF 100%);
  border:1px solid rgba(220,228,242,.88);
}
.employee-salary-grid{
  background:linear-gradient(180deg,#F8FBFF 0%, #F1F6FF 100%);
  border-radius:15px;
  padding:14px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px 14px;
  border:1px solid rgba(220,228,242,.9);
}

@media(max-width:760px){
  .modal-overlay{padding:12px;}
  .modal{padding:22px;border-radius:15px;width:min(100%,96vw);}
  .modal-title{font-size:19px;}
  .modal-hdr{margin-bottom:18px;padding-bottom:14px;}
  .employee-modal .modal-hdr{
    top:-22px;
    margin:-22px -22px 18px;
    padding:20px 22px 14px;
  }
  .employee-salary-grid{grid-template-columns:1fr;}
}

/* ── CHART BARS ── */
.chart-area{background:linear-gradient(180deg,#FBFCFF 0%, #F2F6FF 100%);border-radius:15px;height:160px;display:flex;align-items:flex-end;padding:18px 16px 14px;gap:6px;border:1px solid rgba(220,228,242,.88);}
.cb-wrap{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;}
.cb-bar{width:100%;border-radius:3px 3px 0 0;min-height:4px;transition:height .5s;}
.cb-lbl{font-size:10px;color:var(--muted);}
.attendance-mini-card{
  background:linear-gradient(180deg,#F9FBFF 0%, #F3F6FF 100%);
  border:1px solid rgba(220,228,242,.92);
  border-radius:22px;
  padding:18px 18px 16px;
}
.attendance-mini-bar{
  height:10px;
  display:flex;
  gap:6px;
  align-items:center;
  margin-bottom:16px;
}
.attendance-mini-fill{
  display:block;
  height:8px;
  min-width:10px;
  border-radius:999px;
}
.attendance-mini-fill.absence,.attendance-mini-dot.absence{background:#AEEFD8;}
.attendance-mini-fill.present,.attendance-mini-dot.present{background:#4C5FE7;}
.attendance-mini-fill.leave,.attendance-mini-dot.leave{background:#ECECEC;}
.attendance-mini-fill.sick,.attendance-mini-dot.sick{background:#D9DDFF;}
.attendance-mini-stats{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:12px;
}
.attendance-mini-item{
  display:flex;
  flex-direction:column;
  gap:6px;
}
.attendance-mini-meta{
  display:flex;
  align-items:center;
  gap:7px;
  font-size:11px;
  color:var(--muted);
  white-space:nowrap;
}
.attendance-mini-dot{
  width:10px;
  height:10px;
  border-radius:50%;
  flex-shrink:0;
}
.attendance-mini-item strong{
  font-size:26px;
  line-height:1;
  letter-spacing:-.6px;
  color:#273152;
}
.dept-summary-card{
  display:grid;
  grid-template-columns:172px minmax(0,1fr);
  gap:22px;
  align-items:center;
  min-height:178px;
  background:
    linear-gradient(180deg,#FCFCFF 0%, #F4F6FF 100%);
  border:1px solid rgba(228,233,248,.96);
  border-radius:24px;
  padding:20px 22px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.96);
}
.dept-donut-wrap{
  display:flex;
  align-items:center;
  justify-content:center;
}
.dept-chart-canvas{
  width:144px;
  height:144px;
  display:flex;
  align-items:center;
  justify-content:center;
  filter:drop-shadow(0 12px 20px rgba(128,138,200,.10));
}
.dept-chart-fallback{
  font-size:11px;
  color:#8B95B1;
  text-align:center;
}
.dept-summary-list{
  display:flex;
  flex-direction:column;
  gap:10px;
}
.dept-summary-item{
  display:flex;
  align-items:center;
  gap:12px;
  font-size:13px;
  padding:0;
}
.dept-summary-meta{
  display:flex;
  align-items:flex-start;
  gap:10px;
  min-width:0;
  color:#39425E;
}
.dept-summary-copy{
  display:flex;
  flex-direction:column;
  gap:2px;
  min-width:0;
}
.dept-summary-copy span{
  font-size:12px;
  font-weight:700;
  color:#303756;
  display:flex;
  align-items:center;
  gap:8px;
  flex-wrap:wrap;
  line-height:1.35;
}
.dept-summary-copy span strong{
  color:#273152;
  font-size:13px;
  font-weight:800;
  white-space:nowrap;
}
.dept-summary-copy small{
  font-size:11px;
  color:#6F7896;
  line-height:1.45;
}
.dept-summary-dot{
  width:10px;
  height:10px;
  border-radius:50%;
  flex-shrink:0;
  margin-top:3px;
  box-shadow:none;
}
.dashboard-recent-head{
  align-items:center;
  margin-bottom:16px;
}
.dashboard-recent-tools{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap;
}
.dashboard-recent-search{
  display:flex;
  align-items:center;
  gap:8px;
  min-width:220px;
  padding:0 12px;
  height:40px;
  border:1px solid rgba(220,228,242,.96);
  border-radius:14px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F7F9FF 100%);
  color:#8A93AE;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.92);
}
.dashboard-recent-search input{
  width:100%;
  border:none;
  outline:none;
  background:transparent;
  color:#34405F;
  font-size:12px;
  font-family:var(--font-body);
}
.dashboard-recent-search input::placeholder{color:#98A1BC;}
.dashboard-filter-btn{
  height:40px;
  padding:0 14px;
  border-radius:14px;
  border:1px solid rgba(128,138,241,.24);
  background:linear-gradient(180deg,#F8F8FF 0%, #EEF1FF 100%);
  color:#5A67C8;
  font-family:var(--font-body);
  font-size:12px;
  font-weight:800;
  cursor:pointer;
  box-shadow:0 8px 16px rgba(128,138,241,.10);
}
.dashboard-filter-btn:hover{transform:translateY(-1px);}
.dashboard-recent-table{
  border:1px solid rgba(228,233,248,.96);
  border-radius:18px;
  overflow:hidden;
  background:linear-gradient(180deg,#FFFFFF 0%, #F8FAFF 100%);
}
.dashboard-recent-thead,
.dashboard-recent-row{
  display:grid;
  grid-template-columns:96px minmax(0,1.45fr) minmax(0,1fr) 110px;
  gap:12px;
  align-items:start;
}
.dashboard-recent-thead{
  padding:11px 14px;
  background:linear-gradient(180deg,#EDEFFF 0%, #E7EBFF 100%);
  color:#7A82A7;
  font-size:10px;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.05em;
}
.dashboard-recent-tbody{padding:4px 0;}
.dashboard-recent-row{
  padding:12px 14px;
  border-bottom:1px solid rgba(232,236,248,.92);
  font-size:12px;
  color:#47506D;
}
.dashboard-recent-thead > span,
.dashboard-recent-row > span{
  min-width:0;
}
.dashboard-recent-row > span{
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:normal;
  word-break:break-word;
}
.dashboard-recent-row:last-child{border-bottom:none;}
.dashboard-recent-row:hover{background:rgba(245,247,255,.88);}
.dashboard-recent-user{
  display:flex;
  align-items:flex-start;
  gap:10px;
  min-width:0;
}
.dashboard-recent-user strong{
  font-size:12px;
  color:#303756;
  line-height:1.35;
  white-space:normal;
  overflow:hidden;
  text-overflow:unset;
}
.dashboard-recent-empty{
  padding:26px 16px;
  text-align:center;
  color:var(--muted);
  font-size:13px;
}
.dashboard-leave-requests{
  display:flex;
  flex-direction:column;
  gap:12px;
}
.dashboard-leave-card-compact .card-hdr{
  align-items:center;
  margin-bottom:14px;
}
.dashboard-leave-card-compact{
  min-width:0;
}
.dashboard-leave-card-compact .card-title{
  font-size:17px;
}
.dashboard-leave-requests.compact{
  gap:10px;
}
.dashboard-leave-request{
  width:100%;
  border:1px solid rgba(227,232,246,.98);
  background:linear-gradient(180deg,#FFFFFF 0%, #F8FAFF 100%);
  border-radius:18px;
  padding:14px 14px 14px 12px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  cursor:pointer;
  text-align:left;
  box-shadow:0 12px 24px rgba(103,118,173,.06);
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.dashboard-leave-request.compact{
  padding:10px 10px 10px 10px;
  border-radius:16px;
}
.dashboard-leave-card-compact .dashboard-leave-request.compact{
  padding:9px 10px;
}
.dashboard-leave-request:hover{
  transform:translateY(-1px);
  border-color:rgba(128,138,241,.22);
  box-shadow:0 16px 28px rgba(103,118,173,.1);
}
.dashboard-leave-request-user{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
  flex:1;
}
.dashboard-leave-request-copy{
  min-width:0;
  display:flex;
  flex-direction:column;
  gap:3px;
}
.dashboard-leave-request-copy strong{
  font-size:13px;
  color:#29324D;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.dashboard-leave-request-copy span{
  font-size:11px;
  color:var(--muted);
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.dashboard-leave-request-arrow{
  width:26px;
  height:26px;
  border-radius:50%;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(180deg,#FFFFFF 0%, #EEF2FF 100%);
  color:#7A84D9;
  border:1px solid rgba(219,226,246,.96);
  font-size:20px;
  line-height:1;
  flex-shrink:0;
}
.dashboard-leave-request-empty{
  min-height:170px;
  border:1px dashed rgba(220,228,242,.98);
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  padding:18px;
  font-size:13px;
  color:var(--muted);
  background:linear-gradient(180deg,#FBFCFF 0%, #F5F8FF 100%);
}
.dashboard-leave-request-empty.compact{
  min-height:126px;
  border-radius:16px;
}

@media(max-width:1380px){
  .dashboard-recent-thead,
  .dashboard-recent-row{
    grid-template-columns:84px minmax(0,1.3fr) minmax(0,.95fr) 92px;
    gap:10px;
  }
  .dashboard-recent-thead{font-size:9px;}
  .dashboard-recent-row{font-size:11px;}
}

/* ── ORG CHART ── */
.org-wrap{overflow-x:auto;padding:20px;}
.org-tree{display:flex;flex-direction:column;align-items:center;gap:0;}
.org-node{background:var(--surface);border:1.5px solid var(--border);border-radius:10px;padding:11px 14px;text-align:center;min-width:130px;cursor:pointer;transition:.15s;}
.org-node:hover{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-bg);}
.org-node.root{border-color:var(--accent);background:var(--accent-bg);}
.org-node .oname{font-weight:700;font-size:12px;}
.org-node .orole{font-size:10px;color:var(--muted);}
.org-row{display:flex;gap:20px;align-items:flex-start;}
.org-branch{display:flex;flex-direction:column;align-items:center;}
.org-vline{width:1px;background:var(--border);}
.org-hline{height:1px;background:var(--border);}

/* ── PROFILE HERO ── */
.profile-hero{background:#18170F;border-radius:12px;padding:24px;color:#fff;display:flex;align-items:center;gap:18px;margin-bottom:18px;}
.ph-name{font-family:var(--font-heading);font-size:20px;font-weight:800;}
.ph-role{font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;}
.ph-tags{margin-top:8px;display:flex;gap:6px;}

/* ── DEPT CARD ── */
.dept-card{
  background:linear-gradient(180deg,#FFFFFF 0%, #F8FBFF 100%);
  border:1px solid rgba(214,222,241,.96);
  overflow:hidden;
  box-shadow:0 16px 30px rgba(103,118,173,.08);
  transition:border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.dept-card:hover{
  transform:translateY(-1px);
  border-color:rgba(38,134,147,.24);
  box-shadow:0 20px 34px rgba(103,118,173,.12);
}
.dept-card .dc-bar{display:none;}
.dept-card .dc-body{padding:18px;}
.dept-card .dc-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:12px;
  margin-bottom:12px;
}
.dept-card .dc-name{font-family:var(--font-heading);font-weight:800;font-size:18px;line-height:1.2;color:#26314D;}
.dept-card .dc-sub{font-size:12px;color:var(--muted);margin-top:6px;}
.dept-card .irow{padding:11px 0;}
.dept-card .ikey{font-size:12px;font-weight:600;color:#64708D;}
.dept-card .ival{font-size:14px;font-weight:700;}
.dept-card .dept-progress{margin-top:12px;height:6px;background:#EDF3FB;border-radius:999px;}
.dept-card .dept-progress .prog-fill{border-radius:999px;}
.dept-card .dc-rate{font-size:11px;color:var(--muted);margin-top:8px;}
.dept-card .dc-actions{display:flex;gap:8px;margin-top:14px;}
.dept-card .dc-actions .btn{min-height:38px;}

/* ── ANNOUNCEMENT ── */
.ann{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:11px;border-left:3px solid var(--accent);}
.ann-title{font-weight:600;font-size:14px;margin-bottom:6px;}
.ann-meta{font-size:11px;color:var(--muted);margin-top:6px;}

/* ── LEAVE TYPE ROW ── */
.ltr{padding:11px 0;border-bottom:1px solid var(--border);}
.ltr:last-child{border-bottom:none;}
.ltr-hdr{display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px;}
.ltr-name{font-weight:500;}
.ltr-cnt{color:var(--muted);}

/* ── NOTIFICATION BELL ── */
.notif-wrap{position:relative;}
.notif-dot{position:absolute;top:-1px;right:-1px;width:7px;height:7px;border-radius:50%;background:var(--red);border:2px solid var(--surface);}

/* ── EMPLOYEE VIEW CLOCK ── */
.emp-clock-widget{background:#18170F;border-radius:16px;padding:28px;text-align:center;color:#fff;}
.ecw-time{font-family:var(--font-heading);font-size:52px;font-weight:800;letter-spacing:-2px;font-variant-numeric:tabular-nums;}
.ecw-date{font-size:13px;color:rgba(255,255,255,.4);margin-top:4px;}
.ecw-info{display:flex;justify-content:center;gap:24px;margin:16px 0 20px;}
.ecw-stat{text-align:center;}
.ecw-stat .v{font-family:var(--font-heading);font-size:20px;font-weight:700;color:#fff;}
.ecw-stat .l{font-size:11px;color:rgba(255,255,255,.4);margin-top:1px;}
.ecw-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.ecw-btns .punch-btn{margin-top:0;}

/* scrollbar */
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}

/* search */
.search-input{padding:7px 11px;border:1.5px solid var(--border);border-radius:7px;font-family:var(--font-body);font-size:13px;color:var(--text);background:var(--surface);outline:none;transition:border .15s;}
.search-input:focus{border-color:var(--accent);}

/* upgraded workspace ui */
.hero-panel{
  background:
    radial-gradient(circle at top right, rgba(38,134,147,.12), transparent 24%),
    linear-gradient(180deg, rgba(255,255,255,.99) 0%, rgba(245,249,255,.97) 100%);
  border-radius:15px;
  padding:22px 24px;
  color:var(--text);
  position:relative;
  overflow:hidden;
  border:1px solid rgba(220,228,242,.96);
  box-shadow:
    0 18px 32px rgba(196,206,226,.12),
    inset 0 1px 0 rgba(255,255,255,.94);
}
.hero-panel::before{
  content:'';
  position:absolute;
  inset:auto -38px -54px auto;
  width:170px;
  height:170px;
  border-radius:50%;
  background:radial-gradient(circle, rgba(38,134,147,.12) 0%, rgba(38,134,147,.02) 66%, transparent 72%);
  pointer-events:none;
}
.hero-panel > *{position:relative;z-index:1;}
.hero-title{
  font-family:var(--font-heading);
  font-size:24px;
  font-weight:800;
  letter-spacing:-.4px;
  color:#23304A;
}
.hero-sub{
  font-size:13px;
  color:#6D7D97;
  max-width:760px;
  margin-top:8px;
  line-height:1.7;
}
.hero-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;}
.hero-chip-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;}
.hero-chip{
  background:linear-gradient(180deg,rgba(255,255,255,.96) 0%, rgba(243,247,255,.92) 100%);
  border:1px solid rgba(220,228,242,.92);
  border-radius:15px;
  padding:12px 14px;
  min-width:130px;
  box-shadow:0 10px 22px rgba(195,206,227,.1);
}
.hero-chip .k{
  font-size:11px;
  color:#7B89A0;
  text-transform:uppercase;
  letter-spacing:.45px;
}
.hero-chip .v{
  font-family:var(--font-heading);
  font-size:20px;
  font-weight:700;
  margin-top:4px;
  color:#22324A;
}
.toolbar-card{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;padding:16px;box-shadow:var(--shadow-sm);}
.toolbar-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:end;}
.metric-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;}
.metric-box{
  background:linear-gradient(180deg,rgba(255,255,255,.97) 0%, rgba(244,248,255,.94) 100%);
  border:1px solid rgba(220,228,242,.94);
  border-radius:15px;
  padding:15px 16px;
  box-shadow:0 10px 22px rgba(194,205,226,.1);
}
.metric-box .eyebrow{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.45px;}
.metric-box .value{font-family:var(--font-heading);font-size:28px;font-weight:800;letter-spacing:-.8px;margin-top:6px;color:#22324A;}
.metric-box .meta{font-size:12px;color:var(--muted);margin-top:4px;}
.split-panel{display:grid;grid-template-columns:1.25fr .95fr;gap:14px;}
.data-pill-row{display:flex;gap:8px;flex-wrap:wrap;}
.data-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--surface2);color:var(--text);font-size:12px;font-weight:500;}
.data-pill strong{font-family:var(--font-heading);font-size:15px;}
.directory-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:14px;}
.directory-stat{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;padding:16px;box-shadow:var(--shadow-sm);}
.directory-stat .label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.45px;}
.directory-stat .num{font-family:var(--font-heading);font-size:28px;font-weight:800;margin-top:5px;}
.directory-stat .hint{font-size:12px;color:var(--muted);margin-top:4px;}
.directory-card{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;padding:18px;box-shadow:var(--shadow-sm);}
.directory-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px;flex-wrap:wrap;}
.profile-shell{display:grid;grid-template-columns:320px 1fr 1.05fr;gap:14px;align-items:start;}
.profile-summary{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;overflow:hidden;box-shadow:var(--shadow-sm);}
.profile-cover{height:120px;background:radial-gradient(circle at 20% 20%, rgba(38,134,147,.35), transparent 35%),radial-gradient(circle at 80% 30%, rgba(29,107,117,.25), transparent 32%),linear-gradient(135deg,#edf8f8,#f6fbfb);}
.profile-summary-body{padding:18px;}
.profile-avatar-wrap{margin-top:-48px;display:flex;justify-content:center;}
.summary-stack{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
.summary-item{display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);}
.summary-item:last-child{border-bottom:none;}
.summary-key{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.35px;}
.summary-val{font-size:13px;font-weight:600;text-align:right;}
.panel-card{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;padding:16px;box-shadow:var(--shadow-sm);}
.panel-card + .panel-card{margin-top:14px;}
.panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:12px;}
.panel-title{font-family:var(--font-heading);font-size:14px;font-weight:700;}
.pp-profile-shell{display:grid;grid-template-columns:minmax(300px,340px) minmax(420px,1fr) minmax(360px,.95fr);gap:16px;align-items:start;}
.pp-summary-card,.pp-main-card,.pp-side-card{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;box-shadow:var(--shadow-sm);}
.pp-summary-card{overflow:hidden;}
.pp-cover{height:132px;background:
  radial-gradient(circle at 18% 30%, rgba(128,138,241,.18), transparent 30%),
  radial-gradient(circle at 76% 22%, rgba(38,134,147,.12), transparent 26%),
  radial-gradient(circle at 52% 78%, rgba(73,99,219,.1), transparent 20%),
  linear-gradient(135deg,#F6F8FF 0%, #EEF4FF 52%, #F8FBFF 100%);
}
.pp-summary-body{padding:18px 18px 20px;}
.pp-avatar-stage{display:flex;justify-content:center;margin-top:-50px;}
.pp-name{text-align:center;font-family:var(--font-heading);font-size:24px;font-weight:800;letter-spacing:-.4px;margin-top:10px;}
.pp-role{text-align:center;font-size:13px;color:var(--muted);margin-top:4px;}
.pp-chipbar{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:10px;}
.pp-meta-list{display:flex;flex-direction:column;gap:0;margin-top:18px;}
.pp-meta-row{display:grid;grid-template-columns:1fr auto;gap:10px;padding:12px 0;border-bottom:1px solid var(--border);}
.pp-meta-row:last-child{border-bottom:none;}
.pp-meta-key{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.35px;}
.pp-meta-val{font-size:13px;font-weight:600;text-align:right;word-break:break-word;}
.pp-main-stack,.pp-side-stack{display:flex;flex-direction:column;gap:14px;min-width:0;}
.pp-main-card,.pp-side-card{padding:18px;min-width:0;}
.pp-card-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;}
.pp-card-title h3{font-family:var(--font-heading);font-size:14px;font-weight:800;}
.pp-info-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px 18px;}
.pp-info-row{display:grid;grid-template-columns:minmax(118px,140px) minmax(0,1fr);gap:10px;align-items:start;padding:6px 0;min-width:0;}
.pp-info-row .label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.35px;font-weight:700;}
.pp-info-row .value{font-size:13px;font-weight:600;color:var(--text);min-width:0;overflow-wrap:anywhere;word-break:normal;}
.pp-info-row .value .badge{display:inline-flex;align-items:center;white-space:nowrap;max-width:100%;}
.pp-reporting-empty{min-height:82px;display:flex;align-items:center;justify-content:center;text-align:center;border:1px dashed var(--border);border-radius:14px;background:var(--surface2);font-size:12px;color:var(--muted);}
.pp-tab-shell{background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);border:1px solid rgba(220,228,242,.94);border-radius:15px;padding:20px;box-shadow:var(--shadow-sm);}
.pp-tab-shell .tabs{display:flex;flex-wrap:wrap;gap:10px;border-bottom:none;margin-bottom:16px;}
.pp-tab-shell .tab{border:1px solid var(--border);border-radius:999px;padding:9px 15px;margin-bottom:0;background:var(--surface);font-size:12px;white-space:nowrap;}
.pp-tab-shell .tab.active{background:linear-gradient(180deg,#EEF8FA 0%, #E4F2F4 100%);color:var(--green-dark);border-color:rgba(38,134,147,.24);}
.pp-leave-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.pp-leave-card{border:1px solid rgba(220,228,242,.92);border-radius:15px;padding:14px;background:linear-gradient(180deg,#F8FBFF 0%, #F1F6FF 100%);}
.pp-leave-card h4{font-size:13px;font-weight:700;margin-bottom:10px;}
.pp-leave-stat{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:var(--muted);padding:4px 0;}
.pp-leave-stat strong{color:var(--text);font-size:13px;}
.pp-mini-empty{padding:24px 16px;border:1px dashed rgba(220,228,242,.96);border-radius:15px;background:transparent;text-align:center;color:var(--muted);font-size:12px;}
.pp-timeline{display:flex;flex-direction:column;gap:14px;}
.pp-timeline-item{display:grid;grid-template-columns:16px 1fr;gap:12px;align-items:start;}
.pp-timeline-dot{width:12px;height:12px;border-radius:50%;background:var(--accent);margin-top:4px;box-shadow:0 0 0 4px rgba(38,134,147,.12);}
.pp-timeline-copy{padding-bottom:14px;border-bottom:1px solid var(--border);}
.pp-timeline-item:last-child .pp-timeline-copy{padding-bottom:0;border-bottom:none;}
.pp-doc-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.pp-doc-card{border:1px solid rgba(220,228,242,.92);border-radius:15px;padding:14px;background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);}
.pp-doc-card .meta{font-size:12px;color:var(--muted);margin-top:4px;}
.pp-doc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;}
.team-mini-list{display:flex;flex-direction:column;gap:10px;}
.team-mini-item{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);}
.team-mini-item:last-child{border-bottom:none;}
.leave-mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
.leave-mini-card{border:1px solid rgba(220,228,242,.92);border-radius:15px;padding:12px;background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);}
.leave-mini-card .big{font-family:var(--font-heading);font-size:22px;font-weight:800;}
.doc-upload{display:grid;grid-template-columns:1.3fr .9fr;gap:12px;margin-bottom:14px;}
.doc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.doc-card{border:1px solid rgba(220,228,242,.92);border-radius:15px;padding:12px;background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);}
.doc-card .t{font-size:13px;font-weight:600;}
.doc-card .m{font-size:11px;color:var(--muted);margin-top:4px;}
.mini-kpi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.mini-kpi{border:1px solid rgba(220,228,242,.92);border-radius:15px;padding:12px;background:linear-gradient(180deg,#FFFFFF 0%, #F5F8FF 100%);}
.mini-kpi .n{font-family:var(--font-heading);font-size:22px;font-weight:800;}
.soft-table{
    border:1px solid rgba(220,228,242,.96);
    border-radius:15px;
    overflow:hidden;
    background:linear-gradient(180deg,#FFFFFF 0%, #FBFDFF 100%);
    box-shadow:0 14px 28px rgba(103,118,173,.06);
  }
  .soft-table table thead th{background:linear-gradient(180deg,#F7FAFF 0%, #EEF4FF 100%);}

/* PayPeople-like employee dashboard */
.emp-pp-tabs{display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;}
.emp-pp-tabs button{padding:8px 14px;border:1px solid var(--border);background:var(--surface);border-radius:999px;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer;}
.emp-pp-tabs button.active{background:linear-gradient(180deg,#EEF8FA 0%, #E4F2F4 100%);color:var(--green-dark);border-color:rgba(38,134,147,.24);}
.emp-pp-layout{display:grid;grid-template-columns:2fr 1fr;gap:18px;}
.emp-pp-left,.emp-pp-right{display:flex;flex-direction:column;gap:12px;}
.emp-pp-card{
  background:
    radial-gradient(circle at top right, rgba(128,138,241,.10), transparent 28%),
    linear-gradient(180deg,#FFFFFF 0%, #F6F8FF 100%);
  border:1px solid rgba(220,228,242,.96);
  border-radius:15px;
  padding:20px 22px 18px;
  box-shadow:0 12px 24px rgba(38,84,92,.06), inset 0 1px 0 rgba(255,255,255,.92);
  position:relative;
  overflow:hidden;
}
.emp-pp-card::before{
  content:'';
  position:absolute;
  right:-18px;
  top:-18px;
  width:96px;
  height:96px;
  border-radius:50%;
  background:radial-gradient(circle, rgba(38,134,147,.08) 0%, rgba(38,134,147,0) 72%);
  pointer-events:none;
}
.emp-pp-title{font-family:var(--font-heading);font-size:18px;font-weight:800;letter-spacing:-.35px;color:#273152;line-height:1.15;}
.emp-pp-sub{font-size:11px;color:var(--muted);margin-top:3px;}
.emp-pp-clock{
  display:grid;
  grid-template-columns:minmax(0,1.15fr) minmax(240px,.85fr);
  gap:30px;
  align-items:center;
  padding:24px 26px 22px;
}
.emp-pp-clock-main{min-width:0;}
.emp-pp-kicker{margin-top:20px;font-size:13px;font-weight:800;color:var(--green-dark);letter-spacing:.7px;text-transform:uppercase;}
.emp-pp-clock-lines{display:flex;flex-direction:column;gap:16px;margin-top:18px;}
.emp-pp-clock-line{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px;}
.emp-pp-line-dot{width:11px;height:11px;border-radius:50%;border:2px solid currentColor;display:inline-flex;flex-shrink:0;}
.emp-pp-line-dot.dot-in{color:#27C58B;}
.emp-pp-line-dot.dot-out{color:#F04848;}
.emp-pp-line-label{font-weight:800;color:var(--text);}
.emp-pp-line-value{color:var(--muted);}
.emp-pp-hours-wrap{display:flex;align-items:flex-end;gap:12px;margin-top:24px;flex-wrap:wrap;}
.emp-pp-hours{font-family:var(--font-heading);font-size:52px;font-weight:800;line-height:.95;letter-spacing:-1.8px;color:#1F4B53;}
.emp-pp-hours-note{font-size:14px;color:#8D9D90;font-weight:700;padding-bottom:6px;}
.emp-pp-breakdown{margin-top:8px;font-size:11px;color:var(--muted);line-height:1.5;}
.emp-pp-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px;}
.emp-pp-break{margin-top:14px;font-size:12px;color:var(--muted);}
.emp-pp-policy{margin-top:14px;font-size:11px;color:var(--muted);}
.emp-pp-illus-card{
  position:relative;
  min-height:236px;
  min-width:0;
  padding:14px;
  border-radius:15px;
  background:linear-gradient(180deg,#FCFDFF 0%, #F3F7FF 100%);
  overflow:hidden;
  border:1px solid rgba(220,228,242,.88);
}
.emp-pp-illus-board{position:absolute;right:38px;top:42px;width:138px;height:106px;border-radius:15px;background:linear-gradient(180deg,#FFFFFF 0%, #F2F6FF 100%);border:1px solid rgba(220,228,242,.92);box-shadow:0 18px 34px rgba(160,176,214,.12);}
.emp-pp-illus-clock{position:absolute;right:24px;bottom:22px;width:76px;height:76px;border-radius:50%;border:4px solid #F0A53A;background:#fff;box-shadow:0 10px 24px rgba(186,122,34,.15);}
.emp-pp-illus-clock::before{content:'';position:absolute;left:50%;top:50%;width:2px;height:22px;background:#F0A53A;transform:translate(-50%,-88%) rotate(12deg);transform-origin:bottom center;}
.emp-pp-illus-clock::after{content:'';position:absolute;left:50%;top:50%;width:18px;height:2px;background:#F0A53A;transform:translate(-6%, -50%) rotate(42deg);transform-origin:left center;}
.emp-pp-illus-blob{position:absolute;border-radius:28px;opacity:.92;}
.emp-pp-illus-blob.blob-a{right:86px;top:20px;width:92px;height:58px;background:#F6C24A;border-top-left-radius:52px;border-top-right-radius:14px;border-bottom-left-radius:20px;border-bottom-right-radius:44px;}
.emp-pp-illus-blob.blob-b{right:28px;top:26px;width:74px;height:52px;background:#D68BE7;border-top-left-radius:28px;border-top-right-radius:52px;border-bottom-left-radius:38px;border-bottom-right-radius:14px;}
.emp-pp-illus-blob.blob-c{left:26px;bottom:26px;width:96px;height:96px;background:radial-gradient(circle at 38% 36%, #3A4A9F 0%, #2B326F 62%, #241B49 100%);border-radius:48px 48px 22px 48px;opacity:.95;}
.emp-pp-illus{position:absolute;right:74px;bottom:52px;font-size:72px;line-height:1;opacity:.7;filter:grayscale(.08);}
.emp-pp-leaves{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.emp-pp-leaf{border:1px solid rgba(220,228,242,.96);border-radius:15px;padding:14px 16px;background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);display:flex;justify-content:space-between;gap:10px;align-items:center;box-shadow:0 10px 22px rgba(103,118,173,.04);}
.emp-pp-leaf span{font-size:12px;color:var(--muted);}
.emp-pp-leaf strong{font-size:13px;}
.emp-pp-leaf-meta{font-size:11px;color:var(--muted);margin-top:4px;}
.emp-pp-empty{min-height:92px;display:flex;align-items:center;justify-content:center;text-align:center;color:var(--muted);font-size:12px;background:transparent;border:1px dashed rgba(220,228,242,.96);border-radius:15px;}
.notif-list{display:flex;flex-direction:column;gap:12px;}
.notif-card{border:1px solid rgba(220,228,242,.94);border-radius:15px;padding:18px;background:linear-gradient(180deg,#FFFFFF 0%, #F7FAFF 100%);box-shadow:var(--shadow-sm);}
.notif-card.unread{border-color:rgba(38,134,147,.38);box-shadow:0 0 0 3px rgba(38,134,147,.08);}
.notif-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;}
.notif-card-title{font-size:14px;font-weight:700;color:var(--text);}
.notif-card-meta{font-size:11px;color:var(--muted);margin-top:3px;}
.notif-card-body{font-size:13px;color:var(--text);}
.notif-card-ref{font-size:11px;color:var(--muted);margin-top:10px;}
.notif-empty{padding:26px 18px;border:1px dashed rgba(220,228,242,.96);border-radius:15px;text-align:center;color:var(--muted);background:transparent;}
.event-feed-item{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:14px;
  padding:12px 0;
  border-bottom:1px solid var(--border);
}
.event-feed-item:last-of-type{border-bottom:none;}
.event-feed-main{min-width:0;display:flex;flex-direction:column;gap:6px;}
.event-feed-tag{
  display:inline-flex;
  align-items:center;
  gap:6px;
  width:max-content;
  padding:4px 9px;
  border-radius:999px;
  background:rgba(128,138,241,.10);
  color:#6670D8;
  font-size:10px;
  font-weight:800;
  letter-spacing:.08em;
  text-transform:uppercase;
}
.event-feed-icon{font-size:11px;line-height:1;}
.event-feed-title{
  font-size:14px;
  line-height:1.35;
  color:var(--text);
}
.event-feed-desc{
  font-size:12px;
  line-height:1.6;
  color:var(--muted);
}
.event-feed-item > .badge{
  flex:0 0 auto;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:58px;
  padding:8px 12px;
  white-space:nowrap;
  line-height:1;
  text-align:center;
}
.dash-welcome-card{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap:18px;
  padding:28px;
  background:
    radial-gradient(circle at top right, rgba(128,138,241,.14), transparent 28%),
    radial-gradient(circle at bottom left, rgba(38,134,147,.12), transparent 26%),
    linear-gradient(135deg,#FFFFFF 0%, #F4F6FF 52%, #EEF7F9 100%);
}
.dash-welcome-copy{max-width:720px;}
.dash-kicker{font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#808AF1;}
.dash-welcome-copy h2{margin-top:10px;font-size:38px;line-height:1;letter-spacing:-1.4px;color:#232B45;}
.dash-welcome-copy p{margin-top:10px;font-size:14px;color:var(--muted);max-width:620px;}
.dash-welcome-pills{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;}
.dash-pill{display:inline-flex;align-items:center;padding:9px 14px;border-radius:999px;background:rgba(38,134,147,.10);color:var(--accent-strong);font-size:12px;font-weight:800;border:1px solid rgba(38,134,147,.10);}
.dash-pill.soft{background:rgba(128,138,241,.10);color:#6670D8;border-color:rgba(128,138,241,.12);}
.dash-pill.danger{background:var(--red-bg);color:var(--red);border-color:rgba(228,102,103,.10);}
.dash-stat-grid .stat-card{min-height:160px;}

@media(max-width:1600px){
  .dash-welcome-card{flex-direction:column;align-items:flex-start;}
  .dash-welcome-pills{justify-content:flex-start;}
  .pp-profile-shell{grid-template-columns:minmax(280px,320px) minmax(0,1fr);}
  .pp-main-stack{grid-column:2;}
  .pp-side-stack{grid-column:1 / -1;}
  .pp-side-stack .pp-side-card,
  .pp-side-stack .pp-tab-shell{width:100%;}
  .dashboard-trio-grid{grid-template-columns:minmax(0,.92fr) minmax(280px,1.08fr) minmax(212px,.68fr);}
}

@media(max-width:1360px){
  .pp-profile-shell{grid-template-columns:minmax(280px,320px) minmax(0,1fr);}
  .pp-main-stack{grid-column:2;}
  .pp-side-stack{grid-column:1 / -1;}
  .pp-info-grid{grid-template-columns:1fr;}
}

@media(max-width:1180px){
  #app{height:100vh;margin:0;flex-direction:column;}
  #app::before{left:0;right:0;}
  .topbar{padding:0 16px;gap:12px;flex-wrap:wrap;height:auto;min-height:var(--hdr);align-items:flex-start;padding-top:12px;padding-bottom:12px;}
  .topbar-brand,.topbar-main{width:100%;}
  .topbar-brand{
    max-width:none;
    flex-basis:auto;
    padding-right:0;
    margin-right:0;
    border-right:none;
  }
  .topbar-main{flex-wrap:wrap;}
  .workspace-shell{grid-template-columns:1fr;gap:12px;padding:0 16px 16px;}
  .sidebar{width:100%;height:auto;max-height:42vh;border-right:none;border-top:1px solid rgba(212,220,238,.88);border-radius:22px;}
  .main{height:auto;min-height:0;}
  .content{height:auto;}
  .topbar-title{font-size:22px;}
  .topbar-tools{gap:8px;}
  .tb-clock{font-size:22px;}
  .topbar-user-chip{width:100%;justify-content:flex-start;}
  .toolbar-grid,.metric-strip,.directory-stats,.mini-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  .split-panel,.doc-upload,.profile-shell,.pp-profile-shell{grid-template-columns:1fr;}
  .doc-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  .admin-att-shell{grid-template-columns:1fr;}
  .emp-pp-layout{grid-template-columns:1fr;}
  .emp-pp-clock{grid-template-columns:minmax(0,1fr);}
  .cw-meta{justify-content:flex-start;}
  .punch-btn-inline{width:100%;}
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell{width:100%;}
  .pp-profile-shell{gap:14px;}
  .pp-summary-body{padding:18px;}
  .pp-main-stack,.pp-side-stack{grid-column:auto;}
  .dept-summary-card{
    grid-template-columns:132px minmax(0,1fr);
    gap:16px;
    padding:16px;
  }
  .dept-chart-canvas{
    width:118px;
    height:118px;
  }
  .event-feed-item{gap:12px;}
  .event-feed-item > .badge{
    min-width:54px;
    padding:8px 10px;
    font-size:12px;
  }
}

@media(max-width:760px){
  #login-screen{padding:16px 10px;align-items:center;}
  .login-shell{width:min(calc(100vw - 20px), 100%);min-height:calc(100vh - 32px);max-height:calc(100vh - 32px);border-radius:20px;}
  .login-shell::before{display:none;}
  .login-panel{padding:24px 18px 18px;}
  .login-brand-row{gap:12px;margin-bottom:20px;}
  .login-logo-mark{width:52px;height:52px;border-radius:16px;}
  .login-logo{font-size:28px;}
  .login-brand-copy{font-size:11px;}
  .login-copy-block{margin-bottom:18px;}
  .login-headline{font-size:31px;line-height:1.06;letter-spacing:-1px;margin-bottom:12px;}
  .login-sub{font-size:13px;line-height:1.65;}
  .login-form-card{padding:18px 16px;border-radius:18px;}
  .lf-label{font-size:10px;}
  .lf-input{min-height:52px;padding:13px 14px;border-radius:14px;font-size:14px;}
  .lf-btn{padding:14px 16px;border-radius:14px;font-size:15px;}
  .login-form-meta{flex-direction:column;gap:4px;font-size:11px;}
  .login-footer-note{margin-top:14px;padding-top:0;font-size:11px;line-height:1.6;}
  .login-hero{min-height:220px;padding:18px 16px;}
  .login-hero-kicker{font-size:10px;padding:6px 10px;margin-bottom:12px;}
  .login-hero-copy h2{font-size:22px;line-height:1.1;}
  .login-hero-copy p{font-size:12px;line-height:1.6;max-width:none;}
  .login-cityline{height:54%;min-height:150px;opacity:.75;}
  .city-wheel{width:160px;height:160px;right:-14px;}
  .city-arch{width:82px;right:22px;}
  #app{height:100vh;margin:0;border-radius:0;padding:0;}
  #app::before{top:64px;left:0;right:0;}
  .topbar-brand{gap:10px;}
  .topbar-main{gap:12px;}
  .dash-welcome-copy h2{font-size:30px;}
  .g2,.g3,.g4,.toolbar-grid,.metric-strip,.directory-stats,.mini-kpi-grid,.leave-mini-grid,.doc-grid,.pp-leave-grid,.pp-doc-grid,.pp-info-grid{grid-template-columns:1fr;}
  .content{padding:16px;}
  .content > .page.active{padding:14px;border-radius:20px;}
  .topbar{padding:12px 14px;}
  .workspace-shell{gap:10px;padding:0 12px 12px;}
  .sidebar,.main{border-radius:18px;}
  .hero-panel{padding:16px;}
  .hero-title{font-size:20px;line-height:1.15;}
  .hero-sub{font-size:12px;max-width:none;}
  .admin-att-time{font-size:42px;}
  .att-summary-top{
    flex-direction:column;
    align-items:flex-start;
  }
  .admin-att-summary-grid{grid-template-columns:1fr;}
  .admin-att-details{grid-template-columns:1fr;}
  .pp-profile-shell{gap:12px;}
  .pp-summary-body,.pp-main-card,.pp-side-card,.pp-tab-shell{padding:14px;}
  .pp-cover{height:104px;}
  .pp-avatar-stage{margin-top:-42px;}
  .pp-name{font-size:20px;line-height:1.18;word-break:break-word;}
  .pp-role{font-size:12px;word-break:break-word;}
  .emp-pp-kicker{margin-top:18px;font-size:14px;}
  .emp-pp-hours{font-size:40px;}
  .emp-pp-clock{gap:18px;}
  .emp-pp-illus-card{
    min-height:160px;
    max-width:100%;
  }
  .emp-pp-illus-board{
    right:18px;
    top:18px;
    width:108px;
    height:86px;
  }
  .emp-pp-illus-clock{
    right:16px;
    bottom:16px;
    width:62px;
    height:62px;
  }
  .emp-pp-illus{
    right:44px;
    bottom:32px;
    font-size:52px;
  }
  .emp-pp-illus-blob.blob-a{
    right:58px;
    top:14px;
    width:72px;
    height:46px;
  }
  .emp-pp-illus-blob.blob-b{
    right:14px;
    top:18px;
    width:58px;
    height:40px;
  }
  .emp-pp-illus-blob.blob-c{
    left:18px;
    bottom:18px;
    width:74px;
    height:74px;
  }
  .pp-meta-row{grid-template-columns:1fr;gap:4px;padding:10px 0;}
  .pp-meta-val{text-align:left;}
  .pp-card-title{align-items:flex-start;flex-direction:column;}
  .pp-card-title .btn,.pp-card-title .badge{align-self:flex-start;}
  .pp-info-row{grid-template-columns:1fr;gap:4px;padding:4px 0;}
  .dept-summary-card{
    grid-template-columns:104px minmax(0,1fr);
    gap:12px;
    padding:14px;
    min-height:auto;
  }
  .dept-chart-canvas{
    width:96px;
    height:96px;
  }
  .event-feed-item{
    align-items:flex-start;
    gap:10px;
  }
  .event-feed-item > .badge{
    min-width:52px;
    padding:7px 10px;
    font-size:11px;
  }
  .emp-pp-card .irow > .badge{
    min-width:52px;
    padding:7px 10px;
    font-size:11px;
  }
  .dept-summary-list{gap:8px;}
  .dept-summary-copy span{
    font-size:11px;
    gap:6px;
  }
  .dept-summary-copy span strong{font-size:12px;}
  .dept-summary-copy small{font-size:10px;}
  .pp-tab-shell .tabs{
    flex-wrap:nowrap;
    overflow-x:auto;
    overflow-y:hidden;
    padding-bottom:4px;
    margin:0 -2px 16px;
    scrollbar-width:none;
  }
  .pp-tab-shell .tabs::-webkit-scrollbar{display:none;}
  .pp-tab-shell .tab{flex:0 0 auto;}
  .team-mini-item{align-items:flex-start;flex-wrap:wrap;}
  .team-mini-item .badge{margin-left:42px;}
  .soft-table{border-radius:14px;}
}

@media(max-width:480px){
  #login-screen{padding:10px;}
  .login-shell{border-radius:18px;min-height:calc(100vh - 20px);max-height:calc(100vh - 20px);border:1px solid rgba(220,228,242,.96);}
  .login-panel{padding:22px 14px 16px;}
  .login-brand-row{margin-bottom:16px;}
  .login-headline{font-size:28px;}
  .login-sub{font-size:12px;}
  .login-form-card{padding:16px 14px;border-radius:16px;}
  .login-hero{min-height:190px;padding:16px 14px;}
  .login-hero-copy h2{font-size:20px;}
  .city-wheel{width:132px;height:132px;right:-8px;}
  .city-arch{width:68px;right:16px;}
  .content{padding:10px;}
  .content > .page.active{padding:12px;border-radius:18px;}
  .topbar-user-chip{width:100%;justify-content:flex-start;}
  .hero-panel{padding:14px;}
  .emp-pp-clock{
    gap:14px;
  }
  .emp-pp-illus-card{
    min-height:138px;
  }
  .emp-pp-illus-board{
    right:14px;
    top:14px;
    width:90px;
    height:72px;
  }
  .emp-pp-illus-clock{
    right:12px;
    bottom:12px;
    width:54px;
    height:54px;
  }
  .emp-pp-illus{
    right:32px;
    bottom:24px;
    font-size:42px;
  }
  .emp-pp-illus-blob.blob-a{
    right:46px;
    width:58px;
    height:38px;
  }
  .emp-pp-illus-blob.blob-b{
    right:12px;
    width:46px;
    height:34px;
  }
  .emp-pp-illus-blob.blob-c{
    left:14px;
    bottom:14px;
    width:62px;
    height:62px;
  }
  .dept-summary-card{
    grid-template-columns:1fr;
    justify-items:center;
    text-align:left;
    gap:14px;
  }
  .dept-donut-wrap{width:100%;}
  .dept-chart-canvas{
    width:88px;
    height:88px;
  }
  .dept-summary-list{
    width:100%;
    gap:10px;
  }
  .dept-summary-item{
    align-items:flex-start;
  }
  .dept-summary-meta{
    width:100%;
  }
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell{border-radius:16px;}
  .pp-summary-body,.pp-main-card,.pp-side-card,.pp-tab-shell{padding:12px;}
  .pp-name{font-size:18px;}
  .pp-tab-shell .tab{padding:7px 12px;font-size:11px;}
  .pp-leave-card,.pp-doc-card{padding:12px;}
  .pp-reporting-empty,.pp-mini-empty{padding:18px 12px;}
}
</style>
