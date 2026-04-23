<style>
:root {
  --font-main:'Averta-Regular','Averta','Segoe UI','Segoe UI Variable','Helvetica Neue',Arial,sans-serif;
  --bg:#F4F8F3;--surface:#FFFFFF;--surface2:#F7FAF5;--surface3:#EEF4EE;--border:#D9E6D8;
  --text:#24372B;--muted:#6E8373;--faint:#A7B7A8;
  --accent:#268693;--accent-bg:#E5F4F6;--accent-dark:#1B6671;
  --green:#268693;--green-bg:#E5F4F6;--green-dark:#1B6671;
  --red:#D95B52;--red-bg:#FCEDEB;
  --amber:#BA7A22;--amber-bg:#FFF3E1;
  --purple:#7E6BB3;--purple-bg:#F3EEFF;
  --teal:#147A88;--teal-bg:#E4F3F4;
  --sidebar:254px;--hdr:72px;
  --radius-sm:12px;--radius-md:18px;--radius-lg:24px;
  --shadow-sm:0 8px 24px rgba(52,84,61,.06);
  --shadow-md:0 20px 48px rgba(52,84,61,.10);
  --shadow-lg:0 28px 60px rgba(52,84,61,.14);
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{background:var(--bg);}
body{
  font-family:var(--font-main);
  background:
    radial-gradient(circle at top left, rgba(38,134,147,.10), transparent 22%),
    radial-gradient(circle at top right, rgba(38,134,147,.08), transparent 24%),
    linear-gradient(180deg, #FBFDF9 0%, var(--bg) 100%);
  color:var(--text);
  font-size:14px;
  line-height:1.55;
  height:100vh;
  overflow:hidden;
}

/* ── LOGIN SCREEN ── */
#login-screen{
  position:fixed;inset:0;background:
    radial-gradient(circle at top left, rgba(38,134,147,.18), transparent 28%),
    radial-gradient(circle at right 18%, rgba(38,134,147,.18), transparent 22%),
    linear-gradient(135deg,#EEF5EC 0%, #F7FBF5 48%, #EAF4F5 100%);
  display:flex;align-items:center;justify-content:center;z-index:9999;
}
.login-box{background:rgba(255,255,255,.98);backdrop-filter:blur(14px);border-radius:28px;padding:42px;width:392px;box-shadow:var(--shadow-lg);border:1px solid rgba(217,230,216,.95);position:relative;overflow:hidden;}
.login-box::before{content:'';position:absolute;inset:0 0 auto 0;height:6px;background:linear-gradient(90deg,var(--green), var(--accent));}
.login-logo{font-family:var(--font-main);font-size:26px;font-weight:800;color:var(--text);margin-bottom:4px;}
.login-logo span{color:var(--accent);}
.login-sub{font-size:13px;color:var(--muted);margin-bottom:28px;}
.login-tabs{display:flex;gap:6px;background:var(--surface2);border-radius:999px;padding:5px;margin-bottom:24px;border:1px solid var(--border);}
.login-tab{flex:1;padding:9px 10px;text-align:center;font-size:13px;font-weight:700;border-radius:999px;cursor:pointer;color:var(--muted);transition:.18s;}
.login-tab.active{background:linear-gradient(180deg,#FFFFFF 0%, #F3FAFB 100%);color:var(--green-dark);box-shadow:0 4px 10px rgba(38,134,147,.08);}
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
.sidebar{width:var(--sidebar);background:linear-gradient(180deg,#F8FBF7 0%,#F1F7F1 100%);color:var(--text);display:flex;flex-direction:column;height:100vh;overflow-y:auto;flex-shrink:0;border-right:1px solid rgba(217,230,216,.95);box-shadow:18px 0 36px rgba(52,84,61,.05);}
.sb-logo{padding:24px 22px 16px;border-bottom:1px solid rgba(217,230,216,.88);}
.sb-logo h1{font-family:var(--font-main);font-size:19px;font-weight:800;color:var(--text);}
.sb-logo h1 span{color:var(--accent);}
.sb-logo p{font-size:10px;color:var(--muted);margin-top:4px;}
.sb-user{padding:16px 22px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(217,230,216,.88);}
.sb-user-info .name{font-size:13px;font-weight:700;color:var(--text);}
.sb-user-info .role{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;}
.sb-sect{padding:16px 20px 7px;font-size:10px;font-weight:800;letter-spacing:.9px;text-transform:uppercase;color:#89A08C;}
.nav-item{display:flex;align-items:center;gap:10px;padding:11px 14px 11px 18px;cursor:pointer;color:#5F7365;font-size:13px;font-weight:600;transition:.18s;position:relative;border-radius:14px;margin:0 14px 4px;}
.nav-item:hover{background:#EDF5EC;color:var(--green-dark);transform:none;}
.nav-item.active{background:linear-gradient(180deg,#E9F6F8 0%, #E1F1F3 100%);color:var(--green-dark);box-shadow:inset 0 0 0 1px rgba(38,134,147,.14);}
.nav-item.active::before{content:'';position:absolute;left:-14px;top:10px;bottom:10px;width:4px;border-radius:999px;background:linear-gradient(180deg,var(--green), var(--accent));}
.nav-item svg{width:15px;height:15px;flex-shrink:0;}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:10px;}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#1B7A42;margin-left:auto;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.sb-footer{padding:18px 20px;margin-top:auto;border-top:1px solid rgba(217,230,216,.88);background:linear-gradient(180deg,rgba(255,255,255,.55),rgba(255,255,255,.8));}
.sb-footer p{font-size:10px;color:var(--muted);}

/* ── AVATAR ── */
.av{border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;}
.av-32{width:32px;height:32px;font-size:12px;}
.av-28{width:28px;height:28px;font-size:11px;}
.av-40{width:40px;height:40px;font-size:14px;}
.av-64{width:64px;height:64px;font-size:22px;}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:transparent;}
.topbar{height:var(--hdr);background:rgba(255,255,255,.92);backdrop-filter:blur(14px);border-bottom:1px solid rgba(217,230,216,.9);display:flex;align-items:center;padding:0 28px;gap:14px;flex-shrink:0;box-shadow:0 12px 28px rgba(52,84,61,.05);}
.topbar-title{font-family:var(--font-main);font-size:24px;font-weight:800;flex:1;letter-spacing:-.6px;color:#1F4B53;}
.tb-clock{font-family:var(--font-main);font-size:26px;font-weight:800;color:var(--green-dark);letter-spacing:-1px;font-variant-numeric:tabular-nums;}
.content{flex:1;overflow-y:auto;padding:28px;background:linear-gradient(180deg, rgba(255,255,255,.42) 0%, rgba(255,255,255,0) 100%);}

/* ── PAGE / TAB SYSTEM ── */
.page{display:none;}.page.active{display:block;}
.tab-content{display:none;}.tab-content.active{display:block;}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 15px;border-radius:999px;border:1px solid var(--border);background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);color:var(--text);font-family:var(--font-main);font-size:13px;font-weight:700;cursor:pointer;transition:.18s;white-space:nowrap;box-shadow:0 6px 14px rgba(52,84,61,.04);}
.btn:hover{border-color:rgba(38,134,147,.35);color:var(--green-dark);transform:translateY(-1px);box-shadow:0 10px 18px rgba(38,134,147,.08);}
.btn-sm{padding:5px 10px;font-size:12px;}
.btn-primary{background:linear-gradient(180deg,var(--green) 0%, var(--green-dark) 100%);border-color:var(--green);color:#fff;box-shadow:0 12px 20px rgba(38,134,147,.18);}
.btn-primary:hover{background:linear-gradient(180deg,#2C94A2 0%, #185A64 100%);border-color:var(--green-dark);color:#fff;}
.btn-green{background:linear-gradient(180deg,var(--green) 0%, var(--green-dark) 100%);border-color:var(--green);color:#fff;}
.btn-red{background:var(--red);border-color:var(--red);color:#fff;}
.btn-danger{border-color:var(--red);color:var(--red);}
.btn-danger:hover{background:var(--red);color:#fff;}
.btn-ghost{border-color:transparent;background:transparent;}
.btn-ghost:hover{background:var(--surface2);border-color:var(--border);}

/* ── CARDS ── */
.card{
  background:
    radial-gradient(circle at top right, rgba(38,134,147,.06), transparent 26%),
    linear-gradient(180deg, rgba(255,255,255,.99), rgba(248,252,249,.98));
  border-radius:22px;
  border:1px solid rgba(217,230,216,.96);
  padding:22px;
  box-shadow:0 18px 36px rgba(38,84,92,.08), inset 0 1px 0 rgba(255,255,255,.92);
  position:relative;
  overflow:hidden;
}
.card::before{
  content:'';
  position:absolute;
  inset:0 0 auto 0;
  height:1px;
  background:linear-gradient(90deg, rgba(255,255,255,.95), rgba(38,134,147,.10), rgba(255,255,255,.95));
  pointer-events:none;
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
.card-title{
  font-family:var(--font-main);
  font-size:16px;
  font-weight:800;
  letter-spacing:-.25px;
  color:#1F4B53;
  line-height:1.15;
}
.stat-card{
  background:
    radial-gradient(circle at top right, rgba(38,134,147,.08), transparent 28%),
    linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);
  border-radius:22px;
  border:1px solid rgba(217,230,216,.96);
  padding:20px 22px 18px;
  box-shadow:0 18px 34px rgba(38,84,92,.07), inset 0 1px 0 rgba(255,255,255,.92);
  position:relative;
  overflow:hidden;
}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,var(--green), rgba(38,134,147,.18));}
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
.stat-val{font-family:var(--font-main);font-size:32px;font-weight:800;letter-spacing:-1px;line-height:1.05;color:#1F4B53;}
.stat-sub{font-size:11px;color:var(--muted);margin-top:6px;line-height:1.45;}
.card > *,.stat-card > *{position:relative;z-index:1;}

/* ── GRID ── */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
@media(max-width:1100px){.g4{grid-template-columns:repeat(2,1fr);}.g3{grid-template-columns:repeat(2,1fr);}}

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
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead th{text-align:left;padding:13px 14px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:#7A8F7E;border-bottom:1px solid var(--border);background:#F6FAF4;}
tbody tr{border-bottom:1px solid var(--border);transition:background .12s;}
tbody tr:hover{background:#F8FBF7;}
tbody tr:last-child{border-bottom:none;}
tbody td{padding:12px 14px;vertical-align:middle;}

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
.fi{width:100%;padding:11px 13px;border:1px solid var(--border);border-radius:12px;font-family:var(--font-main);font-size:13px;color:var(--text);background:linear-gradient(180deg,#FFFFFF 0%, #FBFDFC 100%);outline:none;transition:border .15s, box-shadow .15s, background .15s;}
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
  background:linear-gradient(180deg,#FFFFFF 0%, #FBFDFC 100%);
  color:var(--text);
  font-family:var(--font-main);
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
  background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);
  box-shadow:0 20px 36px rgba(38,84,92,.16);
  overflow:hidden;
}
.manager-dropdown.open{display:block;}
.manager-search-wrap{
  padding:10px;
  border-bottom:1px solid rgba(217,230,216,.9);
  background:rgba(255,255,255,.92);
}
.manager-search-input{
  width:100%;
  height:36px;
  padding:0 12px;
  border:1px solid var(--border);
  border-radius:10px;
  background:#F9FCFB;
  color:var(--text);
  font-family:var(--font-main);
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
  font-family:var(--font-main);
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
.cw-time{font-family:var(--font-main);font-size:42px;font-weight:800;letter-spacing:-1.2px;font-variant-numeric:tabular-nums;position:relative;z-index:1;}
.cw-date{font-size:12px;color:rgba(255,255,255,.72);margin-top:4px;position:relative;z-index:1;}
.cw-status{margin:12px 0 8px;position:relative;z-index:1;}
.cw-meta{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin:0 0 14px;position:relative;z-index:1;}
.cw-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.14);color:rgba(255,255,255,.88);font-size:11px;font-weight:600;backdrop-filter:blur(10px);}
.punch-stack{display:flex;flex-direction:column;gap:8px;position:relative;z-index:1;}
.punch-btn{display:flex;width:100%;align-items:center;justify-content:center;min-height:46px;padding:12px 16px;border-radius:14px;border:1px solid transparent;font-family:var(--font-main);font-size:14px;font-weight:700;cursor:pointer;transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease, color .18s ease;margin-top:0;box-shadow:0 10px 24px rgba(0,0,0,.14);}
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
  font-family:var(--font-main);
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
.admin-att-summary{display:flex;flex-direction:column;justify-content:flex-start;}
.admin-att-summary-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
  margin-top:4px;
  margin-bottom:16px;
}
.admin-att-stat{
  padding:16px;
  border-radius:18px;
  border:1px solid rgba(217,230,216,.96);
  background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.92);
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
  font-family:var(--font-main);
  font-size:26px;
  font-weight:800;
  line-height:1.05;
  letter-spacing:-.8px;
  color:#1F4B53;
}
.admin-att-details{
  border-radius:18px;
  border:1px solid rgba(217,230,216,.88);
  background:linear-gradient(180deg,#FBFDFC 0%, #F6FAF7 100%);
  padding:8px 16px;
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
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(31,44,33,.28);backdrop-filter:blur(12px);z-index:1000;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{
  background:
    radial-gradient(circle at top right, rgba(38,134,147,.08), transparent 22%),
    linear-gradient(180deg, rgba(255,255,255,.995), rgba(249,252,248,.99));
  border-radius:30px;
  padding:30px;
  width:min(680px,96vw);
  max-width:96vw;
  max-height:min(92vh,980px);
  overflow-y:auto;
  overflow-x:hidden;
  box-shadow:0 32px 80px rgba(31,64,69,.18);
  border:1px solid rgba(217,230,216,.95);
}
.modal-wide{width:min(920px,96vw);}
.modal-xl{width:min(1220px,96vw);}
.modal-hdr{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:18px;
  margin-bottom:22px;
  padding-bottom:18px;
  border-bottom:1px solid rgba(217,230,216,.88);
}
.modal-title{font-family:var(--font-main);font-size:22px;font-weight:800;color:#1F4B53;letter-spacing:-.4px;line-height:1.1;}
.modal-subtitle{font-size:12px;color:var(--muted);margin-top:6px;line-height:1.55;max-width:720px;}
.modal-close{
  position:relative;
  width:34px;
  height:34px;
  border:none;
  border-radius:10px;
  background:transparent;
  cursor:pointer;
  color:transparent;
  font-size:0;
  line-height:0;
  flex-shrink:0;
  transition:background .15s ease, transform .15s ease;
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
  background:var(--surface2);
  transform:scale(1.04);
}
.modal-close:hover::before,
.modal-close:hover::after{background:var(--text);}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:18px;}
.employee-modal{width:min(980px,96vw);}
.employee-modal .modal-hdr{
  position:sticky;
  top:-30px;
  z-index:2;
  background:linear-gradient(180deg, rgba(255,255,255,.985), rgba(255,255,255,.95));
  margin:-30px -30px 20px;
  padding:26px 30px 18px;
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
  border:1px solid rgba(217,230,216,.85);
  border-radius:18px;
  background:linear-gradient(180deg,#FFFFFF 0%, #FAFCFB 100%);
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
  border-radius:18px;
  background:linear-gradient(180deg,#F4FAFB 0%, #ECF5F6 100%);
  border-color:rgba(38,134,147,.14);
}
.employee-modal .g2{gap:14px;}
.employee-modal .fg{margin-bottom:14px;}
.employee-modal > .g2 .fg,
.employee-modal > .employee-salary-grid .fg{margin-bottom:0;}
.employee-modal .manager-field{margin-top:2px;}
.modal-section{
  border:1px solid rgba(217,230,216,.92);
  border-radius:22px;
  padding:18px 18px 14px;
  background:linear-gradient(180deg,#FFFFFF 0%, #F9FCFA 100%);
  box-shadow:0 10px 22px rgba(38,84,92,.05);
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
  border-radius:14px;
  background:var(--surface2);
  border:1px solid rgba(217,230,216,.85);
}
.employee-salary-grid{
  background:linear-gradient(180deg,#F7FBFA 0%, #F1F7F5 100%);
  border-radius:18px;
  padding:14px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px 14px;
  border:1px solid rgba(217,230,216,.85);
}

@media(max-width:760px){
  .modal-overlay{padding:12px;}
  .modal{padding:22px;border-radius:24px;width:min(100%,96vw);}
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
.chart-area{background:linear-gradient(180deg,#FBFDF9 0%, #F1F7F1 100%);border-radius:18px;height:160px;display:flex;align-items:flex-end;padding:18px 16px 14px;gap:6px;border:1px solid rgba(217,230,216,.8);}
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
.toolbar-card{background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);border:1px solid var(--border);border-radius:20px;padding:16px;box-shadow:var(--shadow-sm);}
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
.directory-card{background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);border:1px solid var(--border);border-radius:20px;padding:18px;box-shadow:var(--shadow-sm);}
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
.pp-profile-shell{display:grid;grid-template-columns:minmax(300px,340px) minmax(420px,1fr) minmax(360px,.95fr);gap:16px;align-items:start;}
.pp-summary-card,.pp-main-card,.pp-side-card{background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);border:1px solid var(--border);border-radius:24px;box-shadow:var(--shadow-sm);}
.pp-summary-card{overflow:hidden;}
.pp-cover{height:132px;background:
  radial-gradient(circle at 18% 30%, rgba(75,143,67,.22), transparent 30%),
  radial-gradient(circle at 76% 22%, rgba(20,122,136,.14), transparent 26%),
  radial-gradient(circle at 52% 78%, rgba(186,122,34,.12), transparent 20%),
  linear-gradient(135deg,#F2F8EE 0%, #EEF7F5 52%, #F8FBF7 100%);
}
.pp-summary-body{padding:18px 18px 20px;}
.pp-avatar-stage{display:flex;justify-content:center;margin-top:-50px;}
.pp-name{text-align:center;font-family:var(--font-main);font-size:24px;font-weight:800;letter-spacing:-.4px;margin-top:10px;}
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
.pp-card-title h3{font-family:var(--font-main);font-size:14px;font-weight:800;}
.pp-info-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px 18px;}
.pp-info-row{display:grid;grid-template-columns:minmax(118px,140px) minmax(0,1fr);gap:10px;align-items:start;padding:6px 0;min-width:0;}
.pp-info-row .label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.35px;font-weight:700;}
.pp-info-row .value{font-size:13px;font-weight:600;color:var(--text);min-width:0;overflow-wrap:anywhere;word-break:normal;}
.pp-info-row .value .badge{display:inline-flex;align-items:center;white-space:nowrap;max-width:100%;}
.pp-reporting-empty{min-height:82px;display:flex;align-items:center;justify-content:center;text-align:center;border:1px dashed var(--border);border-radius:14px;background:var(--surface2);font-size:12px;color:var(--muted);}
.pp-tab-shell{background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);border:1px solid var(--border);border-radius:24px;padding:20px;box-shadow:var(--shadow-sm);}
.pp-tab-shell .tabs{display:flex;flex-wrap:wrap;gap:10px;border-bottom:none;margin-bottom:16px;}
.pp-tab-shell .tab{border:1px solid var(--border);border-radius:999px;padding:9px 15px;margin-bottom:0;background:var(--surface);font-size:12px;white-space:nowrap;}
.pp-tab-shell .tab.active{background:linear-gradient(180deg,#EEF8FA 0%, #E4F2F4 100%);color:var(--green-dark);border-color:rgba(38,134,147,.24);}
.pp-leave-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.pp-leave-card{border:1px solid var(--border);border-radius:16px;padding:14px;background:linear-gradient(180deg,#F8FCFD 0%, #EFF6F8 100%);}
.pp-leave-card h4{font-size:13px;font-weight:700;margin-bottom:10px;}
.pp-leave-stat{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:var(--muted);padding:4px 0;}
.pp-leave-stat strong{color:var(--text);font-size:13px;}
.pp-mini-empty{padding:24px 16px;border:1px dashed var(--border);border-radius:16px;background:var(--surface2);text-align:center;color:var(--muted);font-size:12px;}
.pp-timeline{display:flex;flex-direction:column;gap:14px;}
.pp-timeline-item{display:grid;grid-template-columns:16px 1fr;gap:12px;align-items:start;}
.pp-timeline-dot{width:12px;height:12px;border-radius:50%;background:var(--accent);margin-top:4px;box-shadow:0 0 0 4px rgba(38,134,147,.12);}
.pp-timeline-copy{padding-bottom:14px;border-bottom:1px solid var(--border);}
.pp-timeline-item:last-child .pp-timeline-copy{padding-bottom:0;border-bottom:none;}
.pp-doc-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.pp-doc-card{border:1px solid var(--border);border-radius:16px;padding:14px;background:var(--surface);}
.pp-doc-card .meta{font-size:12px;color:var(--muted);margin-top:4px;}
.pp-doc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;}
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
.soft-table{
  border:1px solid rgba(217,230,216,.96);
  border-radius:20px;
  overflow:hidden;
  background:linear-gradient(180deg,#FFFFFF 0%, #F9FCFA 100%);
  box-shadow:0 14px 28px rgba(38,84,92,.06);
}
.soft-table table thead th{background:#F5FAF8;}

/* PayPeople-like employee dashboard */
.emp-pp-tabs{display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;}
.emp-pp-tabs button{padding:8px 14px;border:1px solid var(--border);background:var(--surface);border-radius:999px;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer;}
.emp-pp-tabs button.active{background:linear-gradient(180deg,#EEF8FA 0%, #E4F2F4 100%);color:var(--green-dark);border-color:rgba(38,134,147,.24);}
.emp-pp-layout{display:grid;grid-template-columns:2fr 1fr;gap:14px;}
.emp-pp-left,.emp-pp-right{display:flex;flex-direction:column;gap:12px;}
.emp-pp-card{
  background:
    radial-gradient(circle at top right, rgba(38,134,147,.06), transparent 24%),
    linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);
  border:1px solid rgba(217,230,216,.96);
  border-radius:22px;
  padding:18px;
  box-shadow:0 16px 30px rgba(38,84,92,.06), inset 0 1px 0 rgba(255,255,255,.92);
  position:relative;
  overflow:hidden;
}
.emp-pp-card::before{
  content:'';
  position:absolute;
  inset:0 0 auto 0;
  height:1px;
  background:linear-gradient(90deg, rgba(255,255,255,.95), rgba(38,134,147,.10), rgba(255,255,255,.95));
  pointer-events:none;
}
.emp-pp-title{font-family:var(--font-main);font-size:16px;font-weight:800;color:#1F4B53;}
.emp-pp-sub{font-size:11px;color:var(--muted);margin-top:3px;}
.emp-pp-clock{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(210px,.85fr);gap:24px;align-items:center;padding:22px;}
.emp-pp-clock-main{min-width:0;}
.emp-pp-kicker{margin-top:16px;font-size:13px;font-weight:800;color:var(--green-dark);letter-spacing:.7px;text-transform:uppercase;}
.emp-pp-clock-lines{display:flex;flex-direction:column;gap:14px;margin-top:16px;}
.emp-pp-clock-line{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px;}
.emp-pp-line-dot{width:11px;height:11px;border-radius:50%;border:2px solid currentColor;display:inline-flex;flex-shrink:0;}
.emp-pp-line-dot.dot-in{color:#27C58B;}
.emp-pp-line-dot.dot-out{color:#F04848;}
.emp-pp-line-label{font-weight:800;color:var(--text);}
.emp-pp-line-value{color:var(--muted);}
.emp-pp-hours-wrap{display:flex;align-items:flex-end;gap:10px;margin-top:18px;flex-wrap:wrap;}
.emp-pp-hours{font-family:var(--font-main);font-size:52px;font-weight:800;line-height:.95;letter-spacing:-1.8px;color:#1F4B53;}
.emp-pp-hours-note{font-size:14px;color:#8D9D90;font-weight:700;padding-bottom:6px;}
.emp-pp-breakdown{margin-top:8px;font-size:11px;color:var(--muted);line-height:1.5;}
.emp-pp-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;}
.emp-pp-break{margin-top:10px;font-size:12px;color:var(--muted);}
.emp-pp-policy{margin-top:12px;font-size:11px;color:var(--muted);}
.emp-pp-illus-card{position:relative;min-height:236px;border-radius:24px;background:linear-gradient(180deg,#FCFEFB 0%, #F3F8F1 100%);overflow:hidden;border:1px solid rgba(217,230,216,.8);}
.emp-pp-illus-board{position:absolute;right:38px;top:42px;width:138px;height:106px;border-radius:20px;background:linear-gradient(180deg,#FFFFFF 0%, #F1F6F0 100%);border:1px solid #DDE8DC;box-shadow:0 18px 34px rgba(52,84,61,.08);}
.emp-pp-illus-clock{position:absolute;right:24px;bottom:22px;width:76px;height:76px;border-radius:50%;border:4px solid #F0A53A;background:#fff;box-shadow:0 10px 24px rgba(186,122,34,.15);}
.emp-pp-illus-clock::before{content:'';position:absolute;left:50%;top:50%;width:2px;height:22px;background:#F0A53A;transform:translate(-50%,-88%) rotate(12deg);transform-origin:bottom center;}
.emp-pp-illus-clock::after{content:'';position:absolute;left:50%;top:50%;width:18px;height:2px;background:#F0A53A;transform:translate(-6%, -50%) rotate(42deg);transform-origin:left center;}
.emp-pp-illus-blob{position:absolute;border-radius:28px;opacity:.92;}
.emp-pp-illus-blob.blob-a{right:86px;top:20px;width:92px;height:58px;background:#F6C24A;border-top-left-radius:52px;border-top-right-radius:14px;border-bottom-left-radius:20px;border-bottom-right-radius:44px;}
.emp-pp-illus-blob.blob-b{right:28px;top:26px;width:74px;height:52px;background:#D68BE7;border-top-left-radius:28px;border-top-right-radius:52px;border-bottom-left-radius:38px;border-bottom-right-radius:14px;}
.emp-pp-illus-blob.blob-c{left:26px;bottom:26px;width:96px;height:96px;background:radial-gradient(circle at 38% 36%, #3A4A9F 0%, #2B326F 62%, #241B49 100%);border-radius:48px 48px 22px 48px;opacity:.95;}
.emp-pp-illus{position:absolute;right:74px;bottom:52px;font-size:72px;line-height:1;opacity:.7;filter:grayscale(.08);}
.emp-pp-leaves{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.emp-pp-leaf{border:1px solid var(--border);border-radius:16px;padding:14px 16px;background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);display:flex;justify-content:space-between;gap:10px;align-items:center;}
.emp-pp-leaf span{font-size:12px;color:var(--muted);}
.emp-pp-leaf strong{font-size:13px;}
.emp-pp-leaf-meta{font-size:11px;color:var(--muted);margin-top:4px;}
.emp-pp-empty{min-height:92px;display:flex;align-items:center;justify-content:center;text-align:center;color:var(--muted);font-size:12px;background:#F8FBF7;border:1px dashed var(--border);border-radius:16px;}
.notif-list{display:flex;flex-direction:column;gap:12px;}
.notif-card{border:1px solid var(--border);border-radius:20px;padding:18px;background:linear-gradient(180deg,#FFFFFF 0%, #F8FBF7 100%);box-shadow:var(--shadow-sm);}
.notif-card.unread{border-color:rgba(38,134,147,.38);box-shadow:0 0 0 3px rgba(38,134,147,.08);}
.notif-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;}
.notif-card-title{font-size:14px;font-weight:700;color:var(--text);}
.notif-card-meta{font-size:11px;color:var(--muted);margin-top:3px;}
.notif-card-body{font-size:13px;color:var(--text);}
.notif-card-ref{font-size:11px;color:var(--muted);margin-top:10px;}
.notif-empty{padding:26px 18px;border:1px dashed var(--border);border-radius:16px;text-align:center;color:var(--muted);background:var(--surface2);}

@media(max-width:1600px){
  .pp-profile-shell{grid-template-columns:minmax(280px,320px) minmax(0,1fr);}
  .pp-main-stack{grid-column:2;}
  .pp-side-stack{grid-column:1 / -1;}
  .pp-side-stack .pp-side-card,
  .pp-side-stack .pp-tab-shell{width:100%;}
}

@media(max-width:1360px){
  .pp-profile-shell{grid-template-columns:minmax(280px,320px) minmax(0,1fr);}
  .pp-main-stack{grid-column:2;}
  .pp-side-stack{grid-column:1 / -1;}
  .pp-info-grid{grid-template-columns:1fr;}
}

@media(max-width:1180px){
  .toolbar-grid,.metric-strip,.directory-stats,.mini-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  .split-panel,.doc-upload,.profile-shell,.pp-profile-shell{grid-template-columns:1fr;}
  .doc-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  .admin-att-shell{grid-template-columns:1fr;}
  .emp-pp-layout{grid-template-columns:1fr;}
  .emp-pp-clock{grid-template-columns:1fr;}
  .cw-meta{justify-content:flex-start;}
  .punch-btn-inline{width:100%;}
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell{width:100%;}
  .pp-profile-shell{gap:14px;}
  .pp-summary-body{padding:18px;}
  .pp-main-stack,.pp-side-stack{grid-column:auto;}
}

@media(max-width:760px){
  .g2,.g3,.g4,.toolbar-grid,.metric-strip,.directory-stats,.mini-kpi-grid,.leave-mini-grid,.doc-grid,.pp-leave-grid,.pp-doc-grid,.pp-info-grid{grid-template-columns:1fr;}
  .content{padding:16px;}
  .topbar{padding:0 16px;}
  .hero-panel{padding:16px;}
  .hero-title{font-size:20px;line-height:1.15;}
  .hero-sub{font-size:12px;max-width:none;}
  .admin-att-time{font-size:42px;}
  .admin-att-summary-grid{grid-template-columns:1fr;}
  .pp-profile-shell{gap:12px;}
  .pp-summary-body,.pp-main-card,.pp-side-card,.pp-tab-shell{padding:14px;}
  .pp-cover{height:104px;}
  .pp-avatar-stage{margin-top:-42px;}
  .pp-name{font-size:20px;line-height:1.18;word-break:break-word;}
  .pp-role{font-size:12px;word-break:break-word;}
  .emp-pp-kicker{margin-top:18px;font-size:14px;}
  .emp-pp-hours{font-size:40px;}
  .emp-pp-illus-card{min-height:180px;}
  .pp-meta-row{grid-template-columns:1fr;gap:4px;padding:10px 0;}
  .pp-meta-val{text-align:left;}
  .pp-card-title{align-items:flex-start;flex-direction:column;}
  .pp-card-title .btn,.pp-card-title .badge{align-self:flex-start;}
  .pp-info-row{grid-template-columns:1fr;gap:4px;padding:4px 0;}
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
  .content{padding:12px;}
  .hero-panel{padding:14px;}
  .pp-summary-card,.pp-main-card,.pp-side-card,.pp-tab-shell{border-radius:16px;}
  .pp-summary-body,.pp-main-card,.pp-side-card,.pp-tab-shell{padding:12px;}
  .pp-name{font-size:18px;}
  .pp-tab-shell .tab{padding:7px 12px;font-size:11px;}
  .pp-leave-card,.pp-doc-card{padding:12px;}
  .pp-reporting-empty,.pp-mini-empty{padding:18px 12px;}
}
</style>
