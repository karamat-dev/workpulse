//  MODAL HELPERS
// ══════════════════════════════════════════════════
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m=>{
  m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('open'); });
});

// ══════════════════════════════════════════════════
//  LOGIN
// ══════════════════════════════════════════════════
let loginRole = 'admin';
function selectLoginRole(r){
  loginRole = r;
  document.getElementById('lt-admin').classList.toggle('active', r==='admin');
  document.getElementById('lt-emp').classList.toggle('active', r==='employee');
  if(r==='admin'){
    document.getElementById('l-email').value='admin@workpulse.com';
    document.getElementById('l-pass').value='admin123';
  } else {
    document.getElementById('l-email').value='employee@workpulse.com';
    document.getElementById('l-pass').value='emp123';
  }
}

function doLogin(){
  const email = document.getElementById('l-email').value.trim();
  const pass = document.getElementById('l-pass').value.trim();
  const err = document.getElementById('l-err');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  fetch('/login', {
    method:'POST',
    credentials:'same-origin',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',
      'Accept':'application/json',
      ...(csrf ? {'X-CSRF-TOKEN': csrf} : {})
    },
    body: new URLSearchParams({
      email,
      password: pass,
    }).toString(),
  })
    .then(async (res)=>{
      const ct = res.headers.get('content-type') || '';
      const data = ct.includes('application/json') ? await res.json().catch(()=>null) : null;
      if(!res.ok){
        const message = data?.message || data?.errors?.email?.[0] || 'Invalid credentials. Please try again.';
        throw new Error(message);
      }
      err.style.display='none';
      if(typeof window.bootWorkpulse === 'function'){
        await window.bootWorkpulse();
      } else {
        window.location.href='/workpulse';
      }
    })
    .catch((e)=>{
      err.textContent = e?.message || 'Invalid credentials. Please try again.';
      err.style.display='block';
    });
}

function doLogout(){
  // Save punch state before logout — do NOT reset it
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  fetch('/logout', {
    method:'POST',
    credentials:'same-origin',
    headers:{
      'Accept':'application/json',
      ...(csrf ? {'X-CSRF-TOKEN': csrf} : {})
    }
  }).finally(()=>{
    DB.currentUser=null; DB.currentRole=null;
    document.getElementById('app').classList.remove('visible');
    document.getElementById('login-screen').style.display='flex';
    const err = document.getElementById('l-err');
    if(err){
      err.style.display='none';
    }
  });
}

function savePunchState(empId){
  try{
    const ps = DB.punchState;
    const snapshot = {
      punchedIn: ps.punchedIn,
      onBreak: ps.onBreak,
      clockInTime: ps.clockInTime ? ps.clockInTime.getTime() : null,
      clockOutTime: ps.clockOutTime ? ps.clockOutTime.getTime() : null,
      breakOutTime: ps.breakOutTime ? ps.breakOutTime.getTime() : null,
      breakInTime: ps.breakInTime ? ps.breakInTime.getTime() : null,
      totalBreakMs: ps.totalBreakMs,
      sessionLogs: ps.sessionLogs,
      savedDate: new Date().toISOString().split('T')[0],
    };
    localStorage.setItem('punchState_'+empId, JSON.stringify(snapshot));
  } catch(e){}
}

function loadPunchState(empId){
  try{
    const raw = localStorage.getItem('punchState_'+empId);
    if(!raw) return false;
    const snap = JSON.parse(raw);
    const today = new Date().toISOString().split('T')[0];
    const now = new Date();

    // Reset if saved date is not today OR if current time is past midnight (00:00) of next day
    // Policy: punch state resets at 00:00 (midnight) — not at logout
    if(snap.savedDate !== today){
      localStorage.removeItem('punchState_'+empId);
      return false;
    }

    // Restore state
    DB.punchState.punchedIn   = snap.punchedIn;
    DB.punchState.onBreak     = snap.onBreak;
    DB.punchState.clockInTime  = snap.clockInTime  ? new Date(snap.clockInTime)  : null;
    DB.punchState.clockOutTime = snap.clockOutTime ? new Date(snap.clockOutTime) : null;
    DB.punchState.breakOutTime = snap.breakOutTime ? new Date(snap.breakOutTime) : null;
    DB.punchState.breakInTime  = snap.breakInTime  ? new Date(snap.breakInTime)  : null;
    DB.punchState.totalBreakMs = snap.totalBreakMs || 0;
    DB.punchState.sessionLogs  = snap.sessionLogs  || [];
    return true;
  } catch(e){ return false; }
}

// ══════════════════════════════════════════════════
//  INIT APP
// ══════════════════════════════════════════════════
function initApp(){
  const u = DB.currentUser;
  document.getElementById('sb-name').textContent = u.fname+' '+u.lname;
  document.getElementById('sb-role').textContent = DB.currentRole==='admin'?'Administrator':DB.currentRole==='hr'?'HR Manager':'Employee';
  document.getElementById('sb-avatar').textContent = u.avatar;
  document.getElementById('sb-avatar').style.background = u.avatarColor;

  // Always reset punchState to clean defaults first, then restore from storage
  DB.punchState = {punchedIn:false,onBreak:false,clockInTime:null,clockOutTime:null,breakOutTime:null,breakInTime:null,totalBreakMs:0,sessionLogs:[]};

  loadPunchState(u.id);

  buildNav();
  startClock();
  scheduleMidnightReset();

  showPage(getDefaultPageForRole(DB.currentRole));
  buildTopbarActions();
}

function getDefaultPageForRole(role){
  if(role==='employee') return 'emp-dashboard';
  if(role==='hr') return 'hr-dashboard';
  return 'dashboard';
}

function getNavForRole(role){
  if(role==='employee') return empNav;
  if(role==='hr') return hrNav;
  return adminNav;
}

function canAccessPage(pageId){
  const nav = getNavForRole(DB.currentRole);
  return nav.some(section => section.items.some(item => item.page === pageId));
}

// Auto-reset punch state at midnight (00:00) each day
function scheduleMidnightReset(){
  const now = new Date();
  const midnight = new Date(now);
  midnight.setDate(midnight.getDate()+1);
  midnight.setHours(0,0,5,0); // 00:00:05 next day
  const msUntilMidnight = midnight - now;
  setTimeout(function(){
    if(DB.currentUser){
      DB.punchState={punchedIn:false,onBreak:false,clockInTime:null,clockOutTime:null,breakOutTime:null,breakInTime:null,totalBreakMs:0,sessionLogs:[]};
      try{ localStorage.removeItem('punchState_'+DB.currentUser.id); } catch(e){}
      refreshPunchUI();
      showToast('New shift started — Clock In when ready','green');
    }
    scheduleMidnightReset(); // reschedule for next day
  }, msUntilMidnight);
}

function buildTopbarActions(){
  const el = document.getElementById('topbar-actions');
  if(DB.currentRole==='employee'){
    el.innerHTML=`<button class="btn btn-sm" onclick="window.openModal('leaveModal')">Apply Leave</button>
    <button class="btn btn-sm btn-ghost" onclick="window.openModal('regulationModal')">Regulation</button>`;
  } else {
    el.innerHTML=`<button class="btn btn-sm btn-primary" onclick="window.openModal('announcementModal')">+ Announce</button>
    <button class="btn btn-sm" onclick="window.openModal('addEmpModal')">+ Employee</button>`;
  }
}

// ══════════════════════════════════════════════════
//  NAVIGATION BUILD
// ══════════════════════════════════════════════════
const adminNav = [
  {sect:'Overview', items:[{label:'Dashboard',page:'dashboard',icon:'grid'}]},
  {sect:'Attendance', items:[
    {label:'Attendance',page:'attendance',icon:'clock'},
    {label:'Real-Time Monitor',page:'realtime',icon:'monitor',badge:'live'},
  ]},
  {sect:'Leave', items:[{label:'Leave Management',page:'leave',icon:'calendar',badge:'3'}]},
  {sect:'People', items:[
    {label:'Employees',page:'employees',icon:'users'},
    {label:'Departments',page:'departments',icon:'chart'},
    {label:'Org Chart',page:'orgchart',icon:'hierarchy'},
  ]},
  {sect:'Admin', items:[
    {label:'Calendar & Events',page:'calendar',icon:'cal'},
    {label:'Reports',page:'reports',icon:'report'},
    {label:'Announcements',page:'announcements',icon:'megaphone'},
    {label:'Company Details',page:'company',icon:'building'},
  ]},
];

const hrNav = [
  {sect:'Overview', items:[{label:'HR Dashboard',page:'hr-dashboard',icon:'grid'}]},
  {sect:'People', items:[
    {label:'Employees',page:'employees',icon:'users'},
    {label:'Departments',page:'departments',icon:'chart'},
  ]},
  {sect:'Leave & Reports', items:[
    {label:'Leave Management',page:'leave',icon:'calendar',badge:'3'},
    {label:'Reports',page:'reports',icon:'report'},
  ]},
  {sect:'Communication', items:[
    {label:'Announcements',page:'announcements',icon:'megaphone'},
    {label:'Calendar & Events',page:'calendar',icon:'cal'},
  ]},
];

const empNav = [
  {sect:'My Workspace', items:[
    {label:'Dashboard',page:'emp-dashboard',icon:'grid'},
    {label:'My Attendance',page:'emp-attendance',icon:'clock'},
    {label:'My Leaves',page:'emp-leaves',icon:'calendar'},
  ]},
  {sect:'Profile & Team', items:[
    {label:'My Profile',page:'emp-profile',icon:'user'},
    {label:'My Team',page:'emp-team',icon:'users'},
  ]},
  {sect:'Company', items:[
    {label:'Announcements',page:'emp-announcements',icon:'megaphone'},
    {label:'Events & Calendar',page:'emp-calendar',icon:'cal'},
  ]},
];

const icons={
  grid:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>`,
  clock:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5V8.5L10.5 10"/></svg>`,
  monitor:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="2"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2"/><path d="M3.05 3.05l1.41 1.41M11.54 11.54l1.41 1.41"/></svg>`,
  calendar:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M5 1v4M11 1v4M1 7h14"/></svg>`,
  cal:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M5 1v4M11 1v4M1 7h14"/><circle cx="5.5" cy="10.5" r="1"/></svg>`,
  users:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="6" cy="5" r="3"/><path d="M1 14c0-3 2-5 5-5s5 2 5 5"/><circle cx="12.5" cy="5.5" r="2"/><path d="M15 13c0-2-1.5-3.5-2.5-3.5"/></svg>`,
  chart:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="9" width="4" height="5" rx="1"/><rect x="6" y="6" width="4" height="8" rx="1"/><rect x="11" y="3" width="4" height="11" rx="1"/></svg>`,
  hierarchy:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="5" y="1" width="6" height="4" rx="1"/><rect x="1" y="11" width="4" height="4" rx="1"/><rect x="6" y="11" width="4" height="4" rx="1"/><rect x="11" y="11" width="4" height="4" rx="1"/><path d="M8 5v3M8 8H3v3M8 8h5v3"/></svg>`,
  report:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 12L6 8l3 3 5-6"/><rect x="1" y="1" width="14" height="13" rx="1.5"/></svg>`,
  megaphone:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 3L3 6v4l10 3V3z"/><path d="M3 10v3a1 1 0 002 0v-3"/></svg>`,
  building:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M5 9h6M5 12h4"/><circle cx="8" cy="6" r="1.5"/></svg>`,
  user:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3 2.7-5 6-5s6 2 6 5"/></svg>`,
};

function buildNav(){
  const nav = getNavForRole(DB.currentRole);
  let html = '';
  nav.forEach(section=>{
    html += `<div class="sb-sect">${section.sect}</div>`;
    section.items.forEach(item=>{
      const isCurrent = false;
      let extra = '';
      if(item.badge==='live') extra=`<span class="live-dot"></span>`;
      else if(item.badge) extra=`<span class="nav-badge">${item.badge}</span>`;
      html += `<div class="nav-item" id="nav-${item.page}" onclick="window.showPage('${item.page}')">${icons[item.icon]||''}${item.label}${extra}</div>`;
    });
  });
  document.getElementById('sidebar-nav').innerHTML = html;
}

// ══════════════════════════════════════════════════
//  PAGE ROUTER
// ══════════════════════════════════════════════════
const pageTitles = {
  dashboard:'Dashboard',attendance:'Attendance',realtime:'Real-Time Monitor',
  leave:'Leave Management',employees:'Employees',departments:'Departments',
  orgchart:'Organization Chart',calendar:'Calendar & Events',reports:'Reports',
  announcements:'Announcements',company:'Company Details',
  'hr-dashboard':'HR Dashboard',
  'emp-dashboard':'My Dashboard','emp-attendance':'My Attendance',
  'emp-leaves':'My Leaves','emp-profile':'My Profile',
  'emp-team':'My Team','emp-announcements':'Announcements','emp-calendar':'Events & Calendar',
  'emp-profile-detail':'Employee Profile',
};

function showPage(id){
  if(!canAccessPage(id) && id!=='emp-profile-detail'){
    id = getDefaultPageForRole(DB.currentRole);
  }
  window.__workpulseCurrentPage = id;
  document.getElementById('page-title').textContent = pageTitles[id]||id;
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  const navEl = document.getElementById('nav-'+id);
  if(navEl) navEl.classList.add('active');
  const main = document.getElementById('main-content');
  try{
    main.innerHTML = renderPage(id);
  }catch(err){
    console.error('showPage/renderPage error for', id, err);
    main.innerHTML = `<div class="card"><div class="card-title">Render Error</div><p style="margin-top:8px;color:var(--muted);">Could not render page: ${id}</p><p style="margin-top:6px;color:var(--red);font-size:12px;">${String(err && err.message ? err.message : err)}</p></div>`;
  }
  startClock(); // re-hook clock elements
  if(typeof window.setupLiveAttendanceRefresh === 'function'){
    window.setupLiveAttendanceRefresh(id);
  }
  if(id==='reports' && typeof window.loadAttendanceReport === 'function'){
    setTimeout(()=>{
      window.loadAttendanceReport();
      if(typeof window.loadMonthlyAttendanceReport === 'function') window.loadMonthlyAttendanceReport();
      if(typeof window.loadMonthlySummary === 'function') window.loadMonthlySummary();
      if(typeof window.exportEmployeeRecordsCSV === 'function'){
        // preload table
        if(typeof window.loadEmployeeRecords === 'function') window.loadEmployeeRecords();
      }
    }, 0);
  }
}

function renderPage(id){
  try{
    switch(id){
      case 'dashboard': return pageAdminDashboard();
      case 'hr-dashboard': return pageHrDashboard();
      case 'attendance': return pageAttendance();
      case 'realtime': return typeof pageRealtimeLive === 'function' ? pageRealtimeLive() : pageRealtime();
      case 'leave': return pageLeave();
      case 'employees': return pageEmployees();
      case 'departments': return pageDepartments();
      case 'orgchart': return pageOrgChart();
      case 'calendar': return pageCalendar();
      case 'reports': return pageReports();
      case 'announcements': return pageAnnouncements();
      case 'company': return pageCompany();
      // Employee pages
      case 'emp-dashboard': return pageEmpDashboard();
      case 'emp-attendance': return pageEmpAttendance();
      case 'emp-leaves': return pageEmpLeaves();
      case 'emp-profile': return pageEmpProfile();
      case 'emp-team': return pageEmpTeam();
      case 'emp-announcements': return pageAnnouncements(true);
      case 'emp-calendar': return pageCalendar(true);
      case 'emp-profile-detail': return pageEmpProfileDetail();
      default: return `<div class="card"><p>Page not found: ${id}</p></div>`;
    }
  }catch(err){
    console.error('renderPage internal error', id, err);
    return `<div class="card"><div class="card-title">Page Error</div><p style="margin-top:8px;color:var(--muted);">Failed to render <strong>${id}</strong>.</p><p style="margin-top:6px;color:var(--red);font-size:12px;">${String(err && err.message ? err.message : err)}</p></div>`;
  }
}

// ══════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════
function statusBadge(s){
  const map={
    'Active':'bg-green','Probation':'bg-amber','Inactive':'bg-gray','On Leave':'bg-purple',
    'Approved':'bg-green','Rejected':'bg-red','Pending':'bg-amber','Waiting':'bg-gray',
    'Present':'bg-green','Absent':'bg-red','Leave':'bg-purple','Late':'bg-amber',
    'National':'bg-blue','Religious':'bg-amber','Optional':'bg-gray',
  };
  return `<span class="badge ${map[s]||'bg-gray'}">${s}</span>`;
}

function calcWorkHours(attRecord){
  if(!attRecord.in || !attRecord.out) return '—';
  const [ih,im]=attRecord.in.split(':').map(Number);
  const [oh,om]=attRecord.out.split(':').map(Number);
  let mins = calcWorkMinutes(attRecord);
  const h=Math.floor(mins/60), m=mins%60;
  return `${h}h ${m}m`;
}

function calcWorkMinutes(attRecord){
  if(!attRecord.in || !attRecord.out) return 0;
  const [ih,im]=attRecord.in.split(':').map(Number);
  const [oh,om]=attRecord.out.split(':').map(Number);
  let mins=(oh*60+om)-(ih*60+im);
  if(attRecord.breakOut&&attRecord.breakIn){
    const [boh,bom]=attRecord.breakOut.split(':').map(Number);
    const [bih,bim]=attRecord.breakIn.split(':').map(Number);
    mins -= (bih*60+bim)-(boh*60+bom);
  }
  return Math.max(0, mins);
}

function formatDate(d){ return d ? new Date(d+'T00:00:00').toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '—'; }

function now(){ return new Date(); }
function nowTime(){ return new Date().toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'}); }

// ══════════════════════════════════════════════════
//  PUNCH SYSTEM (fully functional)
