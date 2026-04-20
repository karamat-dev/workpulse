<style>
:root {
  --font-main:'Averta-Regular','Averta','Segoe UI','Segoe UI Variable','Helvetica Neue',Arial,sans-serif;
  --bg:#EEF5F8;--surface:#FFFFFF;--surface2:#F3F8FA;--border:#D6E4EA;
  --text:#173037;--muted:#688089;--faint:#9AB0B8;
  --accent:#147A88;--accent-bg:#E5F5F7;--accent-dark:#0F5C66;
  --green:#1E8B57;--green-bg:#E7F7EF;
  --red:#D94B43;--red-bg:#FDEDEC;
  --amber:#B36A14;--amber-bg:#FEF1DE;
  --purple:#7B57C8;--purple-bg:#F2EDFF;
  --teal:#147A88;--teal-bg:#E5F5F7;
  --sidebar:240px;--hdr:58px;
  --shadow-sm:0 10px 24px rgba(20,48,55,.06);
  --shadow-md:0 18px 48px rgba(20,48,55,.10);
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{background:var(--bg);}
body{
  font-family:var(--font-main);
  background:
    radial-gradient(circle at top right, rgba(20,122,136,.10), transparent 24%),
    radial-gradient(circle at left 20%, rgba(123,87,200,.06), transparent 22%),
    linear-gradient(180deg, #F7FBFC 0%, var(--bg) 100%);
  color:var(--text);
  font-size:14px;
  line-height:1.55;
  height:100vh;
  overflow:hidden;
}

/* ── LOGIN SCREEN ── */
#login-screen{
  position:fixed;inset:0;background:
    radial-gradient(circle at top left, rgba(20,122,136,.28), transparent 30%),
    radial-gradient(circle at bottom right, rgba(123,87,200,.18), transparent 24%),
    linear-gradient(135deg,#0D1E23 0%, #12323A 55%, #0C5964 100%);
  display:flex;align-items:center;justify-content:center;z-index:9999;
}
.login-box{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-radius:24px;padding:40px;width:380px;box-shadow:0 32px 64px rgba(0,0,0,0.20);border:1px solid rgba(255,255,255,.35);}
.login-logo{font-family:var(--font-main);font-size:26px;font-weight:800;color:var(--text);margin-bottom:4px;}
.login-logo span{color:var(--accent);}
.login-sub{font-size:13px;color:var(--muted);margin-bottom:28px;}
.login-tabs{display:flex;gap:0;background:var(--surface2);border-radius:8px;padding:3px;margin-bottom:24px;}
.login-tab{flex:1;padding:7px;text-align:center;font-size:13px;font-weight:500;border-radius:6px;cursor:pointer;color:var(--muted);transition:.15s;}
.login-tab.active{background:var(--surface);color:var(--text);box-shadow:0 1px 3px rgba(0,0,0,0.1);}
.lf-group{margin-bottom:14px;}
.lf-label{display:block;font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
.lf-input{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:var(--font-main);font-size:14px;color:var(--text);background:var(--surface);outline:none;transition:border .15s;}
.lf-input:focus{border-color:var(--accent);}
.lf-btn{width:100%;padding:12px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-family:var(--font-main);font-size:14px;font-weight:600;cursor:pointer;margin-top:6px;transition:.15s;}
.lf-btn:hover{background:var(--accent-dark);}
.lf-hint{font-size:11px;color:var(--muted);margin-top:14px;text-align:center;line-height:1.6;}
.lf-err{color:var(--red);font-size:12px;margin-top:6px;display:none;}

/* ── LAYOUT ── */
#app{display:none;height:100vh;overflow:hidden;flex-direction:row;background:transparent;}
#app.visible{display:flex;}

/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar);background:linear-gradient(180deg,#10282E 0%,#173A41 58%,#10282E 100%);color:#fff;display:flex;flex-direction:column;height:100vh;overflow-y:auto;flex-shrink:0;box-shadow:18px 0 38px rgba(12,39,44,.18);}
.sb-logo{padding:20px 18px 14px;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-logo h1{font-family:var(--font-main);font-size:17px;font-weight:800;color:#fff;}
.sb-logo h1 span{color:var(--accent);}
.sb-logo p{font-size:10px;color:rgba(255,255,255,.3);margin-top:2px;}
.sb-user{padding:14px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-user-info .name{font-size:13px;font-weight:500;color:#fff;}
.sb-user-info .role{font-size:10px;color:rgba(255,255,255,.4);}
.sb-sect{padding:14px 16px 5px;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgba(255,255,255,.25);}
.nav-item{display:flex;align-items:center;gap:9px;padding:10px 16px 10px 18px;cursor:pointer;color:rgba(255,255,255,.62);font-size:13px;transition:.16s;position:relative;border-radius:0 14px 14px 0;margin-right:12px;}
.nav-item:hover{background:rgba(255,255,255,.06);color:#fff;transform:translateX(2px);}
.nav-item.active{background:linear-gradient(90deg, rgba(20,122,136,.34), rgba(20,122,136,.10));color:#D8F7FA;}
.nav-item svg{width:15px;height:15px;flex-shrink:0;}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:10px;}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#1B7A42;margin-left:auto;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.sb-footer{padding:16px 18px;margin-top:auto;border-top:1px solid rgba(255,255,255,.08);}
.sb-footer p{font-size:10px;color:rgba(255,255,255,.2);}

/* ── AVATAR ── */
.av{border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;}
.av-32{width:32px;height:32px;font-size:12px;}
.av-28{width:28px;height:28px;font-size:11px;}
.av-40{width:40px;height:40px;font-size:14px;}
.av-64{width:64px;height:64px;font-size:22px;}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:transparent;}
.topbar{height:var(--hdr);background:rgba(255,255,255,.86);backdrop-filter:blur(14px);border-bottom:1px solid rgba(214,228,234,.8);display:flex;align-items:center;padding:0 22px;gap:12px;flex-shrink:0;box-shadow:0 10px 28px rgba(20,48,55,.06);}
.topbar-title{font-family:var(--font-main);font-size:15px;font-weight:700;flex:1;}
.tb-clock{font-family:var(--font-main);font-size:20px;font-weight:700;color:var(--accent);letter-spacing:-.5px;font-variant-numeric:tabular-nums;}
.content{flex:1;overflow-y:auto;padding:22px;background:linear-gradient(180deg, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 100%);}

/* ── PAGE / TAB SYSTEM ── */
.page{display:none;}.page.active{display:block;}
.tab-content{display:none;}.tab-content.active{display:block;}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:10px;border:1.5px solid var(--border);background:rgba(255,255,255,.92);color:var(--text);font-family:var(--font-main);font-size:13px;font-weight:600;cursor:pointer;transition:.15s;white-space:nowrap;box-shadow:0 6px 16px rgba(20,48,55,.04);}
.btn:hover{border-color:var(--accent);color:var(--accent);transform:translateY(-1px);}
.btn-sm{padding:5px 10px;font-size:12px;}
.btn-primary{background:var(--accent);border-color:var(--accent);color:#fff;}
.btn-primary:hover{background:var(--accent-dark);border-color:var(--accent-dark);color:#fff;}
.btn-green{background:var(--green);border-color:var(--green);color:#fff;}
.btn-red{background:var(--red);border-color:var(--red);color:#fff;}
.btn-danger{border-color:var(--red);color:var(--red);}
.btn-danger:hover{background:var(--red);color:#fff;}
.btn-ghost{border-color:transparent;background:transparent;}
.btn-ghost:hover{background:var(--surface2);border-color:var(--border);}

/* ── CARDS ── */
.card{background:linear-gradient(180deg, rgba(255,255,255,.98), rgba(249,252,253,.98));border-radius:18px;border:1px solid rgba(214,228,234,.95);padding:18px;box-shadow:var(--shadow-sm);}
.card-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.card-title{font-family:var(--font-main);font-size:13px;font-weight:700;letter-spacing:.1px;}
.stat-card{background:linear-gradient(180deg,#FFFFFF 0%, #F7FBFC 100%);border-radius:18px;border:1px solid rgba(214,228,234,.95);padding:16px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--accent), rgba(20,122,136,.15));}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);letter-spacing:.3px;margin-bottom:5px;}
.stat-val{font-family:var(--font-main);font-size:26px;font-weight:700;letter-spacing:-.5px;line-height:1.1;}
.stat-sub{font-size:11px;color:var(--muted);margin-top:3px;}

/* ── GRID ── */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
@media(max-width:1100px){.g4{grid-template-columns:repeat(2,1fr);}.g3{grid-template-columns:repeat(2,1fr);}}

/* ── BADGE ── */
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:500;}
.bg-green{background:var(--green-bg);color:var(--green);}
.bg-red{background:var(--red-bg);color:var(--red);}
.bg-amber{background:var(--amber-bg);color:var(--amber);}
.bg-blue{background:var(--accent-bg);color:var(--accent);}
.bg-purple{background:var(--purple-bg);color:var(--purple);}
.bg-gray{background:var(--surface2);color:var(--muted);}
.bg-teal{background:var(--teal-bg);color:var(--teal);}

/* ── TABLE ── */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead th{text-align:left;padding:10px 11px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:#F4F9FA;}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s;}
tbody tr:hover{background:#F7FBFC;}
tbody tr:last-child{border-bottom:none;}
tbody td{padding:10px 11px;vertical-align:middle;}

/* ── USER CELL ── */
.ucell{display:flex;align-items:center;gap:8px;}
.ucell-info .n{font-weight:500;font-size:13px;}
.ucell-info .s{font-size:11px;color:var(--muted);}

/* ── TABS ── */
.tabs{display:flex;gap:2px;border-bottom:1px solid var(--border);margin-bottom:18px;}
.tab{padding:9px 14px;font-size:13px;font-weight:500;cursor:pointer;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-1px;transition:.15s;}
.tab.active{color:var(--accent);border-bottom-color:var(--accent);}
.tab:hover:not(.active){color:var(--text);}

/* ── FORM ── */
.fg{margin-bottom:13px;}
.fl{display:block;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;}
.fi{width:100%;padding:9px 11px;border:1.5px solid var(--border);border-radius:7px;font-family:var(--font-main);font-size:13px;color:var(--text);background:var(--surface);outline:none;transition:border .15s;}
.fi:focus{border-color:var(--accent);}
.fi:disabled{background:var(--surface2);color:var(--muted);}
select.fi{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%236E6C63'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}

/* ── ROWS ── */
.irow{padding:9px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;}
.irow:last-child{border-bottom:none;}
.ikey{font-size:12px;color:var(--muted);font-weight:500;}
.ival{font-size:13px;font-weight:500;text-align:right;}

/* ── ALERT ── */
.alert{padding:13px 16px;border-radius:14px;display:flex;align-items:flex-start;gap:9px;margin-bottom:10px;font-size:13px;border:1px solid transparent;box-shadow:var(--shadow-sm);}
.al-info{background:var(--accent-bg);border-color:var(--accent);color:var(--accent-dark);}
.al-warn{background:var(--amber-bg);border-color:var(--amber);color:var(--amber);}
.al-success{background:var(--green-bg);border-color:var(--green);color:var(--green);}
.al-danger{background:var(--red-bg);border-color:var(--red);color:var(--red);}

/* ── CLOCK WIDGET ── */
.clock-widget{background:#18170F;border-radius:14px;padding:24px;color:#fff;text-align:center;position:relative;overflow:hidden;}
.clock-widget::before{content:'';position:absolute;top:-40px;right:-40px;width:130px;height:130px;border-radius:50%;background:rgba(38,134,147,.12);}
.cw-time{font-family:var(--font-main);font-size:40px;font-weight:800;letter-spacing:-1px;font-variant-numeric:tabular-nums;}
.cw-date{font-size:12px;color:rgba(255,255,255,.45);margin-top:3px;}
.cw-status{margin:10px 0 6px;}
.punch-btn{display:block;width:100%;padding:11px;border-radius:9px;border:none;font-family:var(--font-main);font-size:14px;font-weight:600;cursor:pointer;transition:.2s;margin-top:7px;}
.pb-in{background:var(--accent);color:#fff;}
.pb-in:hover{background:var(--accent-dark);}
.pb-out{background:var(--red);color:#fff;}
.pb-out:hover{background:#9E2E1E;}
.pb-break{background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);}
.pb-break:hover{background:rgba(255,255,255,.18);}
.pb-break-in{background:var(--amber);color:#fff;}
.pb-break-in:hover{background:#7A4400;}

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
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,252,253,.98));border-radius:20px;padding:24px;width:500px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-md);border:1px solid rgba(214,228,234,.95);}
.modal-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.modal-title{font-family:var(--font-main);font-size:15px;font-weight:700;}
.modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:var(--muted);line-height:1;}

/* ── CHART BARS ── */
.chart-area{background:linear-gradient(180deg,#F5FBFC 0%, #EDF6F8 100%);border-radius:16px;height:160px;display:flex;align-items:flex-end;padding:14px;gap:6px;border:1px solid rgba(214,228,234,.8);}
.cb-wrap{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;}
.cb-bar{width:100%;border-radius:3px 3px 0 0;min-height:4px;transition:height .5s;}
.cb-lbl{font-size:10px;color:var(--muted);}

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
.ph-name{font-family:var(--font-main);font-size:20px;font-weight:800;}
.ph-role{font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;}
.ph-tags{margin-top:8px;display:flex;gap:6px;}

/* ── DEPT CARD ── */
.dept-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.dept-card .dc-bar{height:4px;}
.dept-card .dc-body{padding:14px;}
.dept-card .dc-name{font-family:var(--font-main);font-weight:700;font-size:14px;}

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
.ecw-time{font-family:var(--font-main);font-size:52px;font-weight:800;letter-spacing:-2px;font-variant-numeric:tabular-nums;}
.ecw-date{font-size:13px;color:rgba(255,255,255,.4);margin-top:4px;}
.ecw-info{display:flex;justify-content:center;gap:24px;margin:16px 0 20px;}
.ecw-stat{text-align:center;}
.ecw-stat .v{font-family:var(--font-main);font-size:20px;font-weight:700;color:#fff;}
.ecw-stat .l{font-size:11px;color:rgba(255,255,255,.4);margin-top:1px;}
.ecw-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.ecw-btns .punch-btn{margin-top:0;}

/* scrollbar */
::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}

/* search */
.search-input{padding:7px 11px;border:1.5px solid var(--border);border-radius:7px;font-family:var(--font-main);font-size:13px;color:var(--text);background:var(--surface);outline:none;transition:border .15s;}
.search-input:focus{border-color:var(--accent);}

/* upgraded workspace ui */
.hero-panel{background:linear-gradient(135deg,#102327 0%,#1B4E55 55%,#268693 100%);border-radius:18px;padding:20px 22px;color:#fff;position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.06);}
.hero-panel::before{content:'';position:absolute;inset:auto -40px -60px auto;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.08);}
.hero-title{font-family:var(--font-main);font-size:24px;font-weight:800;letter-spacing:-.4px;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.72);max-width:760px;margin-top:6px;}
.hero-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;}
.hero-chip-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;}
.hero-chip{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:10px 12px;min-width:130px;}
.hero-chip .k{font-size:11px;color:rgba(255,255,255,.58);text-transform:uppercase;letter-spacing:.4px;}
.hero-chip .v{font-family:var(--font-main);font-size:20px;font-weight:700;margin-top:3px;}
.toolbar-card{background:linear-gradient(180deg,#FFFFFF 0%, #F8FCFD 100%);border:1px solid var(--border);border-radius:16px;padding:14px;box-shadow:var(--shadow-sm);}
.toolbar-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:end;}
.metric-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;}
.metric-box{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:15px 16px;}
.metric-box .eyebrow{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.45px;}
.metric-box .value{font-family:var(--font-main);font-size:28px;font-weight:800;letter-spacing:-.8px;margin-top:6px;}
.metric-box .meta{font-size:12px;color:var(--muted);margin-top:4px;}
.split-panel{display:grid;grid-template-columns:1.25fr .95fr;gap:14px;}
.data-pill-row{display:flex;gap:8px;flex-wrap:wrap;}
.data-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--surface2);color:var(--text);font-size:12px;font-weight:500;}
.data-pill strong{font-family:var(--font-main);font-size:15px;}
.directory-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:14px;}
.directory-stat{background:linear-gradient(180deg,#FFFFFF 0%, #F8FCFD 100%);border:1px solid var(--border);border-radius:16px;padding:16px;box-shadow:var(--shadow-sm);}
.directory-stat .label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.45px;}
.directory-stat .num{font-family:var(--font-main);font-size:28px;font-weight:800;margin-top:5px;}
.directory-stat .hint{font-size:12px;color:var(--muted);margin-top:4px;}
.directory-card{background:linear-gradient(180deg,#FFFFFF 0%, #F8FCFD 100%);border:1px solid var(--border);border-radius:18px;padding:16px;box-shadow:var(--shadow-sm);}
.directory-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px;flex-wrap:wrap;}
.profile-shell{display:grid;grid-template-columns:320px 1fr 1.05fr;gap:14px;align-items:start;}
.profile-summary{background:linear-gradient(180deg,#FFFFFF 0%, #F8FCFD 100%);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:var(--shadow-sm);}
.profile-cover{height:120px;background:radial-gradient(circle at 20% 20%, rgba(38,134,147,.35), transparent 35%),radial-gradient(circle at 80% 30%, rgba(29,107,117,.25), transparent 32%),linear-gradient(135deg,#edf8f8,#f6fbfb);}
.profile-summary-body{padding:18px;}
.profile-avatar-wrap{margin-top:-48px;display:flex;justify-content:center;}
.summary-stack{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
.summary-item{display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);}
.summary-item:last-child{border-bottom:none;}
.summary-key{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.35px;}
.summary-val{font-size:13px;font-weight:600;text-align:right;}
.panel-card{background:linear-gradient(180deg,#FFFFFF 0%, #F8FCFD 100%);border:1px solid var(--border);border-radius:18px;padding:16px;box-shadow:var(--shadow-sm);}
.panel-card + .panel-card{margin-top:14px;}
.panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:12px;}
.panel-title{font-family:var(--font-main);font-size:14px;font-weight:700;}
.team-mini-list{display:flex;flex-direction:column;gap:10px;}
.team-mini-item{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);}
.team-mini-item:last-child{border-bottom:none;}
.leave-mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
.leave-mini-card{border:1px solid var(--border);border-radius:12px;padding:12px;background:linear-gradient(180deg,var(--surface),var(--surface2));}
.leave-mini-card .big{font-family:var(--font-main);font-size:22px;font-weight:800;}
.doc-upload{display:grid;grid-template-columns:1.3fr .9fr;gap:12px;margin-bottom:14px;}
.doc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.doc-card{border:1px solid var(--border);border-radius:12px;padding:12px;background:var(--surface);}
.doc-card .t{font-size:13px;font-weight:600;}
.doc-card .m{font-size:11px;color:var(--muted);margin-top:4px;}
.mini-kpi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.mini-kpi{border:1px solid var(--border);border-radius:12px;padding:12px;background:var(--surface2);}
.mini-kpi .n{font-family:var(--font-main);font-size:22px;font-weight:800;}
.soft-table{border:1px solid var(--border);border-radius:16px;overflow:hidden;background:var(--surface);box-shadow:var(--shadow-sm);}
.soft-table table thead th{background:#F4F9FA;}

/* PayPeople-like employee dashboard */
.emp-pp-tabs{display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;}
.emp-pp-tabs button{padding:6px 12px;border:1px solid var(--border);background:var(--surface);border-radius:999px;font-size:12px;color:var(--muted);cursor:pointer;}
.emp-pp-tabs button.active{background:var(--accent);color:#fff;border-color:var(--accent);}
.emp-pp-layout{display:grid;grid-template-columns:2fr 1fr;gap:14px;}
.emp-pp-left,.emp-pp-right{display:flex;flex-direction:column;gap:12px;}
.emp-pp-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px;}
.emp-pp-title{font-family:var(--font-main);font-size:14px;font-weight:700;}
.emp-pp-sub{font-size:11px;color:var(--muted);margin-top:3px;}
.emp-pp-clock{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;}
.emp-pp-clock-lines{display:flex;flex-direction:column;gap:4px;margin-top:8px;font-size:12px;color:var(--muted);}
.emp-pp-hours{font-family:var(--font-main);font-size:34px;font-weight:800;margin-top:8px;line-height:1;}
.emp-pp-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}
.emp-pp-illus{font-size:56px;opacity:.6;line-height:1;}
.emp-pp-leaves{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.emp-pp-leaf{border:1px solid var(--border);border-radius:10px;padding:10px 12px;background:#faf9f6;display:flex;justify-content:space-between;gap:10px;align-items:center;}
.emp-pp-leaf span{font-size:12px;color:var(--muted);}
.emp-pp-leaf strong{font-size:13px;}
.emp-pp-empty{min-height:82px;display:flex;align-items:center;justify-content:center;text-align:center;color:var(--muted);font-size:12px;background:#f6f5f1;border:1px dashed var(--border);border-radius:10px;}
.notif-list{display:flex;flex-direction:column;gap:12px;}
.notif-card{border:1px solid var(--border);border-radius:16px;padding:16px;background:linear-gradient(180deg,#FFFFFF 0%, #F8FCFD 100%);}
.notif-card.unread{border-color:rgba(20,122,136,.38);box-shadow:0 0 0 3px rgba(20,122,136,.08);}
.notif-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;}
.notif-card-title{font-size:14px;font-weight:700;color:var(--text);}
.notif-card-meta{font-size:11px;color:var(--muted);margin-top:3px;}
.notif-card-body{font-size:13px;color:var(--text);}
.notif-card-ref{font-size:11px;color:var(--muted);margin-top:10px;}
.notif-empty{padding:26px 18px;border:1px dashed var(--border);border-radius:16px;text-align:center;color:var(--muted);background:var(--surface2);}

@media(max-width:1180px){
  .toolbar-grid,.metric-strip,.directory-stats,.mini-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  .split-panel,.doc-upload,.profile-shell{grid-template-columns:1fr;}
  .doc-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  .emp-pp-layout{grid-template-columns:1fr;}
}

@media(max-width:760px){
  .g2,.g3,.g4,.toolbar-grid,.metric-strip,.directory-stats,.mini-kpi-grid,.leave-mini-grid,.doc-grid{grid-template-columns:1fr;}
  .content{padding:14px;}
  .topbar{padding:0 14px;}
}
</style>
