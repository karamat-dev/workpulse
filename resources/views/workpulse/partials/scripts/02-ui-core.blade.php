//  MODAL HELPERS
// ══════════════════════════════════════════════════
const __workpulseMojibakeMap = [
  ['\u00C2\u00B7', ' - '],
  ['\u00E2\u20AC\u201D', '-'],
  ['\u00E2\u20AC\u201C', '-'],
  ['\u00E2\u20AC\u00A2', ' - '],
  ['\u00E2\u20A0\u00B9', '<'],
  ['\u00E2\u20A0\u00BA', '>'],
  ['\u00E2\u20A0\u2122', '->'],
  ['\u00E2\u20A0\u0090', '<-'],
  ['\u00E2\u20A0\u00A9', '<-'],
  ['\u00E2\u0153\u201C', 'OK'],
  ['\u00E2\u0153\u2026', 'OK'],
  ['\u00E2\u20AC\u0179', 'Info'],
  ['\u00E2\u0161\u00A0\u00EF\u00B8\u008F', 'Warning'],
  ['\u00E2\u02DC\u2022', 'Break'],
  ['\u00E2\u008F\u00B1', 'Clock'],
  ['\u00E2\u008F\u00B3', 'Pending'],
  ['\u00E2\u0087\u201E', 'Transfer'],
  ['\u00E2\u0161\u2122\u00EF\u00B8\u008F', 'Settings'],
  ['\u00F0\u0178\u201C\u00A6', 'Days:'],
  ['\u00F0\u0178\u201C\u0160', 'Info'],
  ['\u00F0\u0178\u201D\u00B4', 'Alert'],
  ['\u00F0\u0178\u008F\u2013\u00EF\u00B8\u008F', 'Holiday'],
  ['\u00C3\u2014', 'x'],
  ['\u00E2\u20AC\u00A6', '...'],
  ['\u00EF\u00B8\u008F', ''],
];

function normalizeBrokenText(value){
  let text = String(value ?? '');
  __workpulseMojibakeMap.forEach(([bad, good]) => {
    text = text.split(bad).join(good);
  });
  return text;
}

function normalizeMojibake(root = document.body){
  if(!root) return;

  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
  const textNodes = [];
  while(walker.nextNode()){
    textNodes.push(walker.currentNode);
  }

  textNodes.forEach(node => {
    const fixed = normalizeBrokenText(node.nodeValue);
    if(fixed !== node.nodeValue){
      node.nodeValue = fixed;
    }
  });

  root.querySelectorAll('*').forEach(el => {
    ['title','placeholder','aria-label'].forEach(attr => {
      const value = el.getAttribute(attr);
      if(!value) return;
      const fixed = normalizeBrokenText(value);
      if(fixed !== value){
        el.setAttribute(attr, fixed);
      }
    });
  });
}

let __workpulseNormalizeTimer = null;
let __workpulseNormalizeObserver = null;

function scheduleMojibakeNormalization(root = document.body){
  clearTimeout(__workpulseNormalizeTimer);
  __workpulseNormalizeTimer = setTimeout(() => normalizeMojibake(root || document.body), 0);
}

function observeMojibakeChanges(){
  if(__workpulseNormalizeObserver || typeof MutationObserver === 'undefined' || !document.body) return;

  __workpulseNormalizeObserver = new MutationObserver(mutations => {
    const shouldNormalize = mutations.some(mutation =>
      mutation.type === 'characterData' ||
      mutation.addedNodes?.length ||
      (mutation.type === 'attributes' && ['title','placeholder','aria-label'].includes(mutation.attributeName))
    );

    if(shouldNormalize){
      scheduleMojibakeNormalization(document.body);
    }
  });

  __workpulseNormalizeObserver.observe(document.body, {
    childList: true,
    subtree: true,
    characterData: true,
    attributes: true,
    attributeFilter: ['title','placeholder','aria-label']
  });
}

function openModal(id){
  document.getElementById(id).classList.add('open');
  if(id === 'addEmpModal' && typeof syncEmployeeManagerOptions === 'function'){
    syncEmployeeManagerOptions('ne-manager', '');
  }
  if(id === 'announcementModal'){
    if(typeof syncAnnouncementAudienceOptions === 'function') syncAnnouncementAudienceOptions();
    if(typeof syncAnnouncementRecipientOptions === 'function') syncAnnouncementRecipientOptions();
  }
  if(id === 'notificationModal'){
    if(typeof syncNotificationAudienceOptions === 'function') syncNotificationAudienceOptions();
    if(typeof syncNotificationRecipientOptions === 'function') syncNotificationRecipientOptions();
  }
  normalizeMojibake(document.getElementById(id));
}
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
setTimeout(() => normalizeMojibake(document.body), 0);
setTimeout(() => observeMojibakeChanges(), 0);
document.querySelectorAll('.modal-overlay').forEach(m=>{
  m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('open'); });
});

function getCookieValue(name){
  const match = document.cookie.split('; ').find(row => row.startsWith(name+'='));
  return match ? match.substring(name.length + 1) : null;
}

function syncCsrfTokenFromCookie(){
  const cookieToken = getCookieValue('XSRF-TOKEN');
  if(!cookieToken) return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const decodedToken = decodeURIComponent(cookieToken);
  const meta = document.querySelector('meta[name="csrf-token"]');
  if(meta){
    meta.setAttribute('content', decodedToken);
  }
  return decodedToken;
}

function getCsrfToken(){
  return syncCsrfTokenFromCookie() || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function refreshCsrfToken(){
  const res = await fetch('/csrf-token', {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  });
  const data = await res.json().catch(() => null);
  const token = data?.token || syncCsrfTokenFromCookie() || '';
  const meta = document.querySelector('meta[name="csrf-token"]');
  if(meta && token){
    meta.setAttribute('content', token);
  }
  return token;
}

async function fetchWithCsrfRetry(url, options = {}){
  let response = await fetch(url, options);
  if(response.status !== 419){
    return response;
  }

  const freshToken = await refreshCsrfToken();
  const nextHeaders = {
    ...(options.headers || {}),
    ...(freshToken ? {'X-CSRF-TOKEN': freshToken, 'X-XSRF-TOKEN': freshToken} : {}),
  };
  let nextBody = options.body;

  if(typeof nextBody === 'string' && (nextHeaders['Content-Type'] || '').includes('application/x-www-form-urlencoded')){
    const params = new URLSearchParams(nextBody);
    if(freshToken){
      params.set('_token', freshToken);
    }
    nextBody = params.toString();
  }

  response = await fetch(url, {
    ...options,
    headers: nextHeaders,
    body: nextBody,
  });

  return response;
}

// ══════════════════════════════════════════════════
//  LOGIN
// ══════════════════════════════════════════════════
function doLogin(){
  const email = document.getElementById('l-email').value.trim();
  const pass = document.getElementById('l-pass').value.trim();
  const err = document.getElementById('l-err');
  const csrf = getCsrfToken();
  fetchWithCsrfRetry('/login', {
    method:'POST',
    credentials:'same-origin',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',
      'Accept':'application/json',
      ...(csrf ? {'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': csrf} : {})
    },
    body: new URLSearchParams({
      email,
      password: pass,
      _token: csrf,
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
  const csrf = getCsrfToken();
  fetchWithCsrfRetry('/logout', {
    method:'POST',
    credentials:'same-origin',
    headers:{
      'Accept':'application/json',
      ...(csrf ? {'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': csrf} : {})
    }
  }).finally(()=>{
    resetBrowserNotificationPolling();
    DB.currentUser=null; DB.currentRole=null;
    document.getElementById('app').classList.remove('visible');
    document.getElementById('login-screen').style.display='flex';
    const err = document.getElementById('l-err');
    if(err){
      err.style.display='none';
    }
  });
}

function closeTopbarUserMenu(){
  const menu = document.getElementById('topbar-user-menu');
  const trigger = document.getElementById('topbar-user-trigger');
  if(menu) menu.classList.remove('open');
  if(trigger) trigger.setAttribute('aria-expanded', 'false');
}

function toggleTopbarUserMenu(forceOpen){
  const menu = document.getElementById('topbar-user-menu');
  const trigger = document.getElementById('topbar-user-trigger');
  if(!menu) return;
  const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !menu.classList.contains('open');
  menu.classList.toggle('open', shouldOpen);
  if(trigger) trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
}

document.addEventListener('click', function(e){
  const menu = document.getElementById('topbar-user-menu');
  if(menu && !menu.contains(e.target)) closeTopbarUserMenu();
});

document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeTopbarUserMenu();
});

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
      currentSessionBreakMs: ps.currentSessionBreakMs || 0,
      sessionLogs: ps.sessionLogs,
      savedDate: `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}-${String(new Date().getDate()).padStart(2,'0')}`,
    };
    localStorage.setItem('punchState_'+empId, JSON.stringify(snapshot));
  } catch(e){}
}

function loadPunchState(empId){
  try{
    const raw = localStorage.getItem('punchState_'+empId);
    if(!raw) return false;
    const snap = JSON.parse(raw);
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;

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
    DB.punchState.currentSessionBreakMs = snap.currentSessionBreakMs || 0;
    DB.punchState.sessionLogs  = snap.sessionLogs  || [];
    return true;
  } catch(e){ return false; }
}

// ══════════════════════════════════════════════════
//  INIT APP
// ══════════════════════════════════════════════════
let browserNotificationPollTimer = null;

function browserNotificationsSupported(){
  return typeof window !== 'undefined' && 'Notification' in window;
}

function getBrowserNotificationUserKey(){
  const user = DB.currentUser || {};
  return String(user.id || user.email || 'guest');
}

function getBrowserNotificationStorageKey(){
  return `workpulse_browser_notifications_${getBrowserNotificationUserKey()}`;
}

function getBrowserNotificationPromptKey(){
  return `workpulse_browser_notifications_prompted_${getBrowserNotificationUserKey()}`;
}

function syncBrowserNotificationPermission(){
  const permission = browserNotificationsSupported() ? Notification.permission : 'unsupported';
  if(DB.browserNotifications){
    DB.browserNotifications.permission = permission;
  }
  return permission;
}

function hydrateBrowserNotificationState(){
  if(!DB.browserNotifications){
    DB.browserNotifications = {initialized:false, permission:'default', sentIds:[], promptRequested:false};
  }

  syncBrowserNotificationPermission();

  try{
    const raw = localStorage.getItem(getBrowserNotificationStorageKey());
    const parsed = raw ? JSON.parse(raw) : {};
    DB.browserNotifications.sentIds = Array.isArray(parsed.sentIds) ? parsed.sentIds.map(String).slice(-200) : [];
    DB.browserNotifications.initialized = Boolean(parsed.initialized);
  } catch(e){
    DB.browserNotifications.sentIds = [];
    DB.browserNotifications.initialized = false;
  }

  try{
    DB.browserNotifications.promptRequested = localStorage.getItem(getBrowserNotificationPromptKey()) === '1';
  } catch(e){
    DB.browserNotifications.promptRequested = false;
  }
}

function persistBrowserNotificationState(){
  if(!DB.currentUser || !DB.browserNotifications) return;

  try{
    localStorage.setItem(getBrowserNotificationStorageKey(), JSON.stringify({
      initialized: Boolean(DB.browserNotifications.initialized),
      sentIds: Array.isArray(DB.browserNotifications.sentIds) ? DB.browserNotifications.sentIds.slice(-200) : [],
    }));
    if(DB.browserNotifications.promptRequested){
      localStorage.setItem(getBrowserNotificationPromptKey(), '1');
    }
  } catch(e){}
}

function resetBrowserNotificationPolling(){
  if(browserNotificationPollTimer){
    clearInterval(browserNotificationPollTimer);
    browserNotificationPollTimer = null;
  }
}

function seedBrowserNotificationState(notifications){
  if(!DB.browserNotifications) return;
  DB.browserNotifications.sentIds = Array.isArray(notifications)
    ? notifications.map(item => String(item.id)).filter(Boolean).slice(-200)
    : [];
  DB.browserNotifications.initialized = true;
  persistBrowserNotificationState();
}

function openNotificationFromBrowser(){
  try{ window.focus(); } catch(e){}
  if(typeof openNotificationsPage === 'function'){
    openNotificationsPage();
  }
}

function showBrowserNotificationAlert(notification){
  if(!browserNotificationsSupported() || Notification.permission !== 'granted') return;

  const title = normalizeBrokenText(notification?.title || 'WorkPulse Notification');
  const body = normalizeBrokenText(notification?.message || notification?.referenceCode || 'You have a new update.');

  try{
    const desktopNotification = new Notification(title, {
      body,
      tag: `workpulse-${notification?.id || Date.now()}`,
      renotify: false,
      silent: false,
    });

    desktopNotification.onclick = function(){
      desktopNotification.close();
      openNotificationFromBrowser();
    };

    setTimeout(() => desktopNotification.close(), 10000);
  } catch(e){}
}

function handleBrowserNotifications(notifications, {seedOnly=false} = {}){
  if(!DB.currentUser) return;

  if(!DB.browserNotifications){
    DB.browserNotifications = {initialized:false, permission:'default', sentIds:[], promptRequested:false};
  }

  const items = Array.isArray(notifications) ? notifications : [];
  const trackedIds = new Set((DB.browserNotifications.sentIds || []).map(String));

  if(seedOnly || !DB.browserNotifications.initialized){
    seedBrowserNotificationState(items);
    return;
  }

  items
    .filter(item => item && item.id !== undefined && item.id !== null)
    .forEach(item => {
      const id = String(item.id);
      if(!trackedIds.has(id) && !item.isRead){
        showBrowserNotificationAlert(item);
      }
      trackedIds.add(id);
    });

  DB.browserNotifications.sentIds = Array.from(trackedIds).slice(-200);
  persistBrowserNotificationState();
}

async function ensureBrowserNotificationPermission({prompt=false} = {}){
  if(!browserNotificationsSupported()) return 'unsupported';

  const currentPermission = syncBrowserNotificationPermission();
  if(currentPermission !== 'default' || !prompt) return currentPermission;
  if(DB.browserNotifications?.promptRequested) return currentPermission;

  try{
    DB.browserNotifications.promptRequested = true;
    persistBrowserNotificationState();
    const nextPermission = await Notification.requestPermission();
    if(DB.browserNotifications){
      DB.browserNotifications.permission = nextPermission;
    }
    persistBrowserNotificationState();
    return nextPermission;
  } catch(e){
    return currentPermission;
  }
}

async function pollBrowserNotifications(){
  if(!DB.currentUser) return;

  try{
    const response = await fetch('/api/me/notifications', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if(!response.ok) return;

    const payload = await response.json().catch(() => null);
    if(!payload?.ok) return;

    DB.notifications = payload.notifications || [];
    DB.notificationCount = payload.unreadCount || 0;
    handleBrowserNotifications(DB.notifications);

    if(typeof updateNotificationUI === 'function'){
      updateNotificationUI();
    }

    if(window.__workpulseCurrentPage === 'notifications' || window.__workpulseCurrentPage === 'emp-notifications'){
      showPage(window.__workpulseCurrentPage);
    }
  } catch(e){}
}

function startBrowserNotificationPolling(){
  resetBrowserNotificationPolling();
  if(!DB.currentUser) return;
  browserNotificationPollTimer = setInterval(() => {
    pollBrowserNotifications();
  }, 30000);
}

function initApp(){
  const u = DB.currentUser;
  const fullName = (u.fname+' '+u.lname).trim();
  const sbName = document.getElementById('sb-name');
  const sbRole = document.getElementById('sb-role');
  const sbAvatar = document.getElementById('sb-avatar');
  if(sbName) sbName.textContent = u.fname+' '+u.lname;
  if(sbRole) sbRole.textContent =
    DB.currentRole==='admin' ? 'Administrator'
    : DB.currentRole==='hr' ? 'HR Manager'
    : DB.currentRole==='manager' ? 'Manager'
    : 'Employee';
  const tbName = document.getElementById('tb-name');
  const tbEmail = document.getElementById('tb-email');
  if(tbName) tbName.textContent = fullName;
  if(tbEmail) tbEmail.textContent = u.email || '';
  if(u.profilePhotoUrl){
    if(sbAvatar){
      sbAvatar.innerHTML = `<img src="${u.profilePhotoUrl}" alt="Profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">`;
      sbAvatar.style.background = 'transparent';
    }
    const tbAvatar = document.getElementById('tb-avatar');
    if(tbAvatar){
      tbAvatar.innerHTML = `<img src="${u.profilePhotoUrl}" alt="Profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">`;
      tbAvatar.style.background = 'transparent';
    }
  }else{
    if(sbAvatar){
      sbAvatar.textContent = u.avatar;
      sbAvatar.style.background = u.avatarColor;
    }
    const tbAvatar = document.getElementById('tb-avatar');
    if(tbAvatar){
      tbAvatar.textContent = u.avatar;
      tbAvatar.style.background = u.avatarColor;
    }
  }

  // Always reset punchState to clean defaults first, then restore from storage
  DB.punchState = {punchedIn:false,onBreak:false,clockInTime:null,clockOutTime:null,breakOutTime:null,breakInTime:null,totalBreakMs:0,currentSessionBreakMs:0,sessionLogs:[]};

  loadPunchState(u.id);
  syncPunchStateFromBootstrap();

  hydrateSidebarCollapseState();
  buildNav();
  updateNotificationUI();
  hydrateBrowserNotificationState();
  handleBrowserNotifications(DB.notifications, {seedOnly:true});
  startBrowserNotificationPolling();
  setTimeout(() => ensureBrowserNotificationPermission({prompt:true}), 1200);
  startClock();
  scheduleMidnightReset();

  showPage(getDefaultPageForRole(DB.currentRole));
  buildTopbarActions();
  normalizeMojibake(document.body);
}

function getDefaultPageForRole(role){
  if(role==='employee') return 'emp-dashboard';
  if(role==='hr' || role==='manager') return 'hr-dashboard';
  return 'dashboard';
}

function getNavForRole(role){
  if(role==='employee') return empNav;
  if(role==='hr' || role==='manager') return hrNav;
  return adminNav;
}

function getNotificationPageForRole(){
  return DB.currentRole === 'employee' ? 'emp-notifications' : 'notifications';
}

function getUnreadNotificationCount(){
  return Number(DB.notificationCount || 0);
}

function updateNotificationUI(){
  const dot = document.getElementById('notif-dot');
  if(dot){
    dot.style.display = getUnreadNotificationCount() > 0 ? 'block' : 'none';
  }

  document.querySelectorAll('.notif-wrap button').forEach(button => {
    button.title = getUnreadNotificationCount() > 0 ? `Notifications (${getUnreadNotificationCount()} unread)` : 'Notifications';
  });
}

function openNotificationsPage(){
  const targetPage = getNotificationPageForRole();
  if(typeof window.wpReload === 'function'){
    window.wpReload()
      .catch(() => {})
      .finally(() => showPage(targetPage));
    return;
  }
  showPage(targetPage);
}

function canAccessPage(pageId){
  if(pageId === 'notifications') return true;
  const nav = getNavForRole(DB.currentRole);
  return nav.some(section => section.items.some(item => item.page === pageId));
}

function runPageAfterRender(pageId){
  if(pageId === 'dashboard'){
    setTimeout(() => {
      if(typeof renderDepartmentAttendanceChart === 'function'){
        renderDepartmentAttendanceChart();
      }
      if(typeof window.applyDashboardRecentSearchState === 'function'){
        window.applyDashboardRecentSearchState();
      }
    }, 0);
  }
  if(pageId === 'leave'){
    setTimeout(() => {
      if(typeof updateLeaveTodayFilters === 'function'){
        updateLeaveTodayFilters();
      }
      if(typeof applyLeaveTodayFilters === 'function'){
        applyLeaveTodayFilters();
      }
    }, 0);
  }
}

// Auto-reset punch state at midnight (00:00) each day
function scheduleMidnightReset(){
  const now = new Date();
  const midnight = new Date(now);
  midnight.setDate(midnight.getDate()+1);
  midnight.setHours(0,0,5,0); // 00:00:05 next day
  const msUntilMidnight = midnight - now;
  setTimeout(async function(){
    if(DB.currentUser){
      try{
        if(typeof wpAutoCloseStaleAttendance === 'function'){
          await wpAutoCloseStaleAttendance();
        }
      } catch(e){}
      DB.punchState={punchedIn:false,onBreak:false,clockInTime:null,clockOutTime:null,breakOutTime:null,breakInTime:null,totalBreakMs:0,currentSessionBreakMs:0,sessionLogs:[]};
      try{ localStorage.removeItem('punchState_'+DB.currentUser.id); } catch(e){}
      if(typeof wpReload === 'function'){
        try{ await wpReload(); } catch(e){}
      }
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
    <button class="btn btn-sm btn-ghost" onclick="window.openRegulationModal()">Regulation</button>`;
  } else if(DB.currentRole==='manager'){
    el.innerHTML=`<button class="btn btn-sm" onclick="window.showPage('leave')">Review Leaves</button>
    <button class="btn btn-sm btn-ghost" onclick="window.showPage('reports')">Team Reports</button>`;
  } else {
    el.innerHTML=`<button class="btn btn-sm btn-primary" onclick="window.openAnnouncementModal()">+ Announce</button>
    <button class="btn btn-sm" onclick="window.openModal('addEmpModal')">+ Employee</button>`;
  }
}

function applySidebarCollapseState(){
  const app = document.getElementById('app');
  const toggle = document.getElementById('sidebar-toggle');
  const collapsed = !!window.__sidebarCollapsed;
  if(app){
    app.classList.toggle('sidebar-collapsed', collapsed);
  }
  if(toggle){
    toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
    toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
  }
}

function toggleSidebarCollapse(forceValue){
  const nextValue = typeof forceValue === 'boolean' ? forceValue : !window.__sidebarCollapsed;
  window.__sidebarCollapsed = nextValue;
  try{
    localStorage.setItem('workpulse_sidebar_collapsed', nextValue ? '1' : '0');
  }catch(e){}
  applySidebarCollapseState();
}

function hydrateSidebarCollapseState(){
  try{
    window.__sidebarCollapsed = localStorage.getItem('workpulse_sidebar_collapsed') === '1';
  }catch(e){
    window.__sidebarCollapsed = false;
  }
  applySidebarCollapseState();
}
window.toggleSidebarCollapse = toggleSidebarCollapse;

// ══════════════════════════════════════════════════
//  NAVIGATION BUILD
// ══════════════════════════════════════════════════
const adminNav = [
  {sect:'Overview', items:[{label:'Dashboard',page:'dashboard',icon:'grid'}]},
  {sect:'Attendance', items:[
    {label:'Attendance',page:'attendance',icon:'clock'},
    {label:'Real-Time Monitor',page:'realtime',icon:'monitor',badge:'live'},
  ]},
  {sect:'Leave', items:[{label:'Leave Management',page:'leave',icon:'calendar'}]},
  {sect:'People', items:[
    {label:'Employees',page:'employees',icon:'users'},
    {label:'Roles & Permissions',page:'roles',icon:'shield'},
    {label:'Teams',page:'departments',icon:'chart'},
    {label:'Org Chart',page:'orgchart',icon:'hierarchy'},
  ]},
  {sect:'Admin', items:[
    {label:'Calendar & Events',page:'calendar',icon:'cal'},
    {label:'Reports',page:'reports',icon:'report'},
    {label:'Announcements',page:'announcements',icon:'megaphone'},
    {label:'Policies',page:'policies',icon:'doc'},
    {label:'Company Details',page:'company',icon:'building'},
  ]},
];

const hrNav = [
  {sect:'Overview', items:[{label:'HR Dashboard',page:'hr-dashboard',icon:'grid'}]},
  {sect:'People', items:[
    {label:'Employees',page:'employees',icon:'users'},
    {label:'Teams',page:'departments',icon:'chart'},
  ]},
  {sect:'Leave & Reports', items:[
    {label:'Leave Management',page:'leave',icon:'calendar'},
    {label:'Reports',page:'reports',icon:'report'},
  ]},
  {sect:'Communication', items:[
    {label:'Announcements',page:'announcements',icon:'megaphone'},
    {label:'Policies',page:'policies',icon:'doc'},
    {label:'Calendar & Events',page:'calendar',icon:'cal'},
  ]},
];

const empNav = [
  {sect:'My Workspace', items:[
    {label:'Dashboard',page:'emp-dashboard',icon:'grid'},
    {label:'My Attendance',page:'emp-attendance',icon:'clock'},
    {label:'My Leaves',page:'emp-leaves',icon:'calendar'},
    {label:'Notifications',page:'emp-notifications',icon:'bell'},
  ]},
  {sect:'Profile & Team', items:[
    {label:'My Profile',page:'emp-profile',icon:'user'},
    {label:'My Team',page:'emp-team',icon:'users'},
  ]},
  {sect:'Company', items:[
    {label:'Announcements',page:'emp-announcements',icon:'megaphone'},
    {label:'Policies',page:'emp-policies',icon:'doc'},
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
  bell:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1a4.5 4.5 0 014.5 4.5c0 2.2.6 3.5 1.3 4.7H2.2c.7-1.2 1.3-2.5 1.3-4.7A4.5 4.5 0 018 1z"/><path d="M6.5 12.2a1.6 1.6 0 003 0"/></svg>`,
  building:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M5 9h6M5 12h4"/><circle cx="8" cy="6" r="1.5"/></svg>`,
  user:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3 2.7-5 6-5s6 2 6 5"/></svg>`,
  shield:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 1l5 2v4c0 3.3-2.1 6.2-5 7.3C5.1 13.2 3 10.3 3 7V3l5-2z"/><path d="M6.2 8l1.2 1.2L10 6.5"/></svg>`,
  doc:`<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 1.5h5l3 3V14a1 1 0 01-1 1H4a1 1 0 01-1-1v-11a1.5 1.5 0 011.5-1.5z"/><path d="M9 1.5V5h3"/><path d="M5.5 8h5M5.5 10.5h5M5.5 13h3.5"/></svg>`,
};

function buildNav(){
  const nav = getNavForRole(DB.currentRole);
  const pendingLeaveCount = Array.isArray(DB.leaves)
    ? DB.leaves.filter(leave => leave && leave.status === 'Pending').length
    : 0;
  let html = '';
  nav.forEach(section=>{
    html += `<div class="sb-sect">${section.sect}</div>`;
    section.items.forEach(item=>{
      const isCurrent = false;
      let extra = '';
      if(item.badge==='live') extra=`<span class="live-dot"></span>`;
      else if(item.page==='emp-notifications' && getUnreadNotificationCount() > 0) extra=`<span class="nav-badge">${getUnreadNotificationCount()}</span>`;
      else if(item.page==='leave' && pendingLeaveCount > 0) extra=`<span class="nav-badge">${pendingLeaveCount}</span>`;
      else if(item.badge) extra=`<span class="nav-badge">${item.badge}</span>`;
      html += `<div class="nav-item" id="nav-${item.page}" title="${item.label}" onclick="window.showPage('${item.page}')">${icons[item.icon]||''}<span class="nav-label">${item.label}</span>${extra}</div>`;
    });
  });
  document.getElementById('sidebar-nav').innerHTML = html;
}

// ══════════════════════════════════════════════════
//  PAGE ROUTER
// ══════════════════════════════════════════════════
const pageTitles = {
  dashboard:'Dashboard',attendance:'Attendance',realtime:'Real-Time Monitor',
  leave:'Leave Management',employees:'Employees',roles:'Roles & Permissions',departments:'Teams',
  orgchart:'Organization Chart',calendar:'Calendar & Events',reports:'Reports',
  announcements:'Announcements',policies:'Company Policies',company:'Company Details',
  notifications:'Notifications',
  'hr-dashboard':'HR Dashboard',
  'emp-dashboard':'My Dashboard','emp-attendance':'My Attendance',
  'emp-leaves':'My Leaves','emp-notifications':'Notifications','emp-profile':'My Profile',
  'emp-team':'My Team','emp-announcements':'Announcements','emp-policies':'Company Policies','emp-calendar':'Events & Calendar',
  'emp-profile-detail':'Employee Profile',
};

function showPage(id){
  closeTopbarUserMenu();
  if(!canAccessPage(id) && id!=='emp-profile-detail'){
    id = getDefaultPageForRole(DB.currentRole);
  }
  window.__workpulseCurrentPage = id;
  document.getElementById('page-title').textContent = pageTitles[id]||id;
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  const navEl = document.getElementById('nav-'+id);
  if(navEl) navEl.classList.add('active');
  updateNotificationUI();
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
  normalizeMojibake(main);
  runPageAfterRender(id);
  if(id==='realtime' && typeof window.filterMonitor === 'function'){
    window.filterMonitor();
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
      case 'roles': return pageRoles();
      case 'departments': return pageDepartments();
      case 'orgchart': return pageOrgChart();
      case 'calendar': return pageCalendar();
      case 'reports': return pageReports();
      case 'announcements': return pageAnnouncements();
      case 'policies': return pagePolicies();
      case 'company': return pageCompany();
      case 'notifications': return pageNotifications();
      // Employee pages
      case 'emp-dashboard': return pageEmpDashboard();
      case 'emp-attendance': return pageEmpAttendance();
      case 'emp-leaves': return pageEmpLeaves();
      case 'emp-notifications': return pageEmpNotifications();
      case 'emp-profile': return pageEmpProfile();
      case 'emp-team': return pageEmpTeam();
      case 'emp-announcements': return pageAnnouncements(true);
      case 'emp-policies': return pagePolicies(true);
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
    'Active':'bg-green','Probation':'bg-amber','Offboarding':'bg-blue','Inactive':'bg-gray','On Leave':'bg-purple',
    'Approved':'bg-green','Rejected':'bg-red','Pending':'bg-amber','Waiting':'bg-gray',
    'Present':'bg-green','Absent':'bg-red','Leave':'bg-purple','Late':'bg-amber',
    'Clocked In':'bg-green','Not Clocked In':'bg-gray','Clocked Out':'bg-blue',
    'National':'bg-blue','Religious':'bg-amber','Optional':'bg-gray',
  };
  return `<span class="badge ${map[s]||'bg-gray'}">${s}</span>`;
}

function calcWorkHours(attRecord){
  if(typeof attRecord?.workedMinutes === 'number'){
    const mins = Math.max(0, attRecord.workedMinutes);
    const h=Math.floor(mins/60), m=mins%60;
    return `${h}h ${m}m`;
  }
  if(!attRecord.in || !attRecord.out) return '—';
  const [ih,im]=attRecord.in.split(':').map(Number);
  const [oh,om]=attRecord.out.split(':').map(Number);
  let mins = calcWorkMinutes(attRecord);
  const h=Math.floor(mins/60), m=mins%60;
  return `${h}h ${m}m`;
}

function calcWorkMinutes(attRecord){
  if(typeof attRecord?.workedMinutes === 'number'){
    return Math.max(0, attRecord.workedMinutes);
  }
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

function parseAttendanceTimeForDate(timeStr, dateStr){
  if(!timeStr || !dateStr) return null;
  const parsed = new Date(`${dateStr}T${timeStr}:00`);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function syncPunchStateFromBootstrap(){
  const currentUser = DB.currentUser;
  if(!currentUser) return;

  const today = getTodayLocalDate();
  const todayRecord = Array.isArray(DB.attendance)
    ? DB.attendance.find(a => a.empId === currentUser.id && a.date === today)
    : null;
  const liveEntry = Array.isArray(DB.liveAttendance)
    ? DB.liveAttendance.find(item => String(item.empId) === String(currentUser.id))
    : null;

  if(!todayRecord && !liveEntry) return;

  const ps = DB.punchState || {};
  const liveStatus = liveEntry?.status || '';
  const punchedIn = liveStatus === 'in' || liveStatus === 'break';
  const onBreak = liveStatus === 'break';
  const activeClockTime = punchedIn ? parseAttendanceTimeForDate(liveEntry?.since, today) : null;
  const recordClockInTime = parseAttendanceTimeForDate(todayRecord?.in, today);
  const breakOutTime = onBreak
    ? (parseAttendanceTimeForDate(todayRecord?.breakOut, today) || ps.breakOutTime || null)
    : null;
  const breakInTime = !onBreak ? (parseAttendanceTimeForDate(todayRecord?.breakIn, today) || null) : null;
  const clockOutTime = !punchedIn ? (parseAttendanceTimeForDate(todayRecord?.out, today) || null) : null;

  DB.punchState = {
    ...ps,
    punchedIn,
    onBreak,
    clockInTime: punchedIn
      // Prefer today's persisted attendance clock-in over live "since" value.
      ? (recordClockInTime || activeClockTime || ps.clockInTime)
      : null,
    clockOutTime,
    breakOutTime,
    breakInTime,
    currentSessionBreakMs: Math.max(
      0,
      Number(todayRecord?.currentSessionBreakMinutes || 0) * 60000,
      onBreak ? Number(ps.currentSessionBreakMs || 0) : 0
    ),
  };
}

function formatWorkedMinutesLabel(totalMinutes, includeSeconds=false){
  const safeMinutes = Math.max(0, Number(totalMinutes || 0));
  const totalSeconds = Math.max(0, Math.floor(safeMinutes * 60));
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  if(!includeSeconds){
    return `${h}h ${m}m`;
  }
  const s = totalSeconds % 60;
  return `${h}h ${m}m ${s}s`;
}

function getTodayAttendanceRecord(){
  const currentUser = DB.currentUser;
  if(!currentUser || !Array.isArray(DB.attendance)) return null;
  const today = getTodayLocalDate();
  return DB.attendance.find(a => a.empId === currentUser.id && a.date === today) || null;
}

function getCompletedWorkedMinutesToday(){
  const record = getTodayAttendanceRecord();
  if(typeof record?.completedWorkedMinutes === 'number'){
    return Math.max(0, Number(record.completedWorkedMinutes || 0));
  }
  return Math.max(0, Number(record?.workedMinutes || 0));
}

function getCurrentSessionWorkedMinutes(asOf = new Date()){
  const ps = DB.punchState || {};
  if(!ps.punchedIn || !ps.clockInTime) return 0;
  const endTime = ps.onBreak && ps.breakOutTime ? ps.breakOutTime : asOf;
  const elapsedMs = Math.max(0, endTime - ps.clockInTime);
  const breakMs = Math.max(0, Number(ps.currentSessionBreakMs || 0));
  return Math.max(0, Math.floor((elapsedMs - breakMs) / 60000));
}

function getLiveWorkedMinutesToday(asOf = new Date()){
  return getCompletedWorkedMinutesToday() + getCurrentSessionWorkedMinutes(asOf);
}

function getLiveWorkedTimeLabel(asOf = new Date()){
  const ps = DB.punchState || {};
  const includeSeconds = !!ps.punchedIn;
  let totalMinutes = getLiveWorkedMinutesToday(asOf);

  if(includeSeconds && ps.clockInTime){
    const completedMs = getCompletedWorkedMinutesToday() * 60000;
    const endTime = ps.onBreak && ps.breakOutTime ? ps.breakOutTime : asOf;
    const elapsedMs = Math.max(0, endTime - ps.clockInTime);
    const breakMs = Math.max(0, Number(ps.currentSessionBreakMs || 0));
    const totalMs = Math.max(0, completedMs + elapsedMs - breakMs);
    return formatWorkedMinutesLabel(totalMs / 60000, true);
  }

  return formatWorkedMinutesLabel(totalMinutes, false);
}

function formatDate(d){
  if(!d) return '—';
  const raw = String(d).trim();
  if(!raw) return '—';
  const normalized = /^\d{4}-\d{2}-\d{2}$/.test(raw)
    ? `${raw}T00:00:00`
    : raw.replace(' ', 'T');
  const parsed = new Date(normalized);
  return Number.isNaN(parsed.getTime())
    ? '—'
    : parsed.toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'});
}
function formatDateTime(dt){
  if(!dt) return '—';
  const parsed = new Date(dt);
  if(Number.isNaN(parsed.getTime())) return String(dt);
  return parsed.toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

function now(){ return new Date(); }
function nowTime(){ return new Date().toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'}); }

// ══════════════════════════════════════════════════
//  PUNCH SYSTEM (fully functional)

function formatPunchTimeValue(value, fallback = '—'){
  if(value instanceof Date && !Number.isNaN(value.getTime())){
    return value.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  }
  if(typeof value === 'string' && value.trim() !== ''){
    return value;
  }
  if(typeof fallback === 'string' && fallback.trim() !== ''){
    return fallback;
  }
  return '—';
}

function getBreakDurationMinutes(asOf = new Date()){
  const record = getTodayAttendanceRecord() || {};
  const ps = DB.punchState || {};
  let totalMinutes = 0;

  if(record.breakOut && record.breakIn){
    const [startHour, startMinute] = String(record.breakOut).split(':').map(Number);
    const [endHour, endMinute] = String(record.breakIn).split(':').map(Number);
    const startTotal = (startHour * 60) + startMinute;
    const endTotal = (endHour * 60) + endMinute;
    totalMinutes += Math.max(0, endTotal - startTotal);
  }

  if(ps.onBreak && ps.breakOutTime instanceof Date && !Number.isNaN(ps.breakOutTime.getTime())){
    totalMinutes += Math.max(0, Math.floor((asOf - ps.breakOutTime) / 60000));
  }

  return totalMinutes;
}

function formatBreakDurationLabel(asOf = new Date()){
  const totalMinutes = getBreakDurationMinutes(asOf);
  if(!totalMinutes) return '—';
  if(totalMinutes % 60 === 0){
    return `${totalMinutes / 60}h`;
  }
  if(totalMinutes > 60){
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    return `${hours}h ${mins}m`;
  }
  return `${totalMinutes} min`;
}

function getTodayPunchDisplay(asOf = new Date()){
  const record = getTodayAttendanceRecord() || {};
  const ps = DB.punchState || {};
  const isActiveSession = !!ps.punchedIn;

  return {
    clockIn: isActiveSession
      ? formatPunchTimeValue(ps.clockInTime, '—')
      : formatPunchTimeValue(ps.clockInTime, record.in || '—'),
    clockOut: isActiveSession
      ? formatPunchTimeValue(ps.clockOutTime, '—')
      : formatPunchTimeValue(ps.clockOutTime, record.out || '—'),
    breakIn: formatPunchTimeValue(ps.onBreak ? ps.breakOutTime : null, record.breakOut || '—'),
    breakOut: formatPunchTimeValue(ps.breakInTime, record.breakIn || '—'),
    breakDuration: formatBreakDurationLabel(asOf),
  };
}

function getTodayWorkedBreakdown(asOf = new Date()){
  const completedMinutes = getCompletedWorkedMinutesToday();
  const currentSessionMinutes = getCurrentSessionWorkedMinutes(asOf);
  const totalMinutes = completedMinutes + currentSessionMinutes;

  return {
    completedMinutes,
    currentSessionMinutes,
    totalMinutes,
    completedLabel: formatWorkedMinutesLabel(completedMinutes, false),
    currentSessionLabel: formatWorkedMinutesLabel(currentSessionMinutes, false),
    totalLabel: formatWorkedMinutesLabel(totalMinutes, false),
  };
}

function getTodaySessionWorkedBreakdown(asOf = new Date()){
  const record = getTodayAttendanceRecord() || {};
  const ps = DB.punchState || {};
  const currentSessionMinutes = getCurrentSessionWorkedMinutes(asOf);
  const closedSessionMinutes = Math.max(0, Number(record?.sessionWorkedMinutes || 0));
  const totalMinutes = ps.punchedIn ? currentSessionMinutes : closedSessionMinutes;

  return {
    totalMinutes,
    totalLabel: formatWorkedMinutesLabel(totalMinutes, !!ps.punchedIn),
    compactLabel: formatWorkedHoursClockLabel(totalMinutes),
    sessionClockIn: record?.sessionClockIn || record?.in || null,
    sessionClockOut: record?.sessionClockOut || record?.out || null,
  };
}

function formatWorkedHoursClockLabel(totalMinutes){
  const safeMinutes = Math.max(0, Number(totalMinutes || 0));
  const totalSeconds = Math.max(0, Math.floor(safeMinutes * 60));
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')} hrs`;
}

function formatPunchMoment(value, dateStr = null, fallback = '--:--', includeDate = false){
  if(value instanceof Date && !Number.isNaN(value.getTime())){
    return value.toLocaleString('en-GB', includeDate
      ? {day:'2-digit', month:'short', hour:'numeric', minute:'2-digit', hour12:true}
      : {hour:'numeric', minute:'2-digit', hour12:true}
    );
  }

  if(typeof value === 'string' && value.trim() !== ''){
    if(includeDate && dateStr){
      const parsed = new Date(`${dateStr}T${value}:00`);
      if(!Number.isNaN(parsed.getTime())){
        return parsed.toLocaleString('en-GB', {day:'2-digit', month:'short', hour:'numeric', minute:'2-digit', hour12:true});
      }
    }
    return value;
  }

  return fallback;
}
