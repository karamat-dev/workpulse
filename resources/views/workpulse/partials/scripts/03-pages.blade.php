//  PUNCH SYSTEM (fully functional)
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function wpApi(path, opts={}){
  const csrf = typeof getCsrfToken === 'function'
    ? getCsrfToken()
    : (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
  const isFormData = typeof FormData !== 'undefined' && opts.body instanceof FormData;
  const headers = {
    'Accept':'application/json',
    ...(csrf ? {'X-CSRF-TOKEN': csrf, 'X-XSRF-TOKEN': csrf} : {}),
    ...(opts.headers||{})
  };

  if(!isFormData){
    headers['Content-Type'] = 'application/json';
  }

  const requestOptions = {
    credentials: 'same-origin',
    headers,
    ...opts,
  };
  const res = typeof fetchWithCsrfRetry === 'function'
    ? await fetchWithCsrfRetry(path, requestOptions)
    : await fetch(path, requestOptions);
  const ct = res.headers.get('content-type') || '';
  const data = ct.includes('application/json') ? await res.json().catch(()=>null) : null;
  if(!res.ok) throw new Error((data && (data.message||data.error)) || ('HTTP '+res.status));
  return data;
}

async function wpReload(){
  try{
    const data = await wpApi('/api/bootstrap', {method:'GET', headers:{}});
    if(typeof DB === 'object' && DB){
      DB.currentUser = data.currentUser;
      DB.currentRole = data.currentRole;
      DB.users = [data.currentUser];
      DB.employees = data.employees || [];
      DB.departments = data.departments || [];
      DB.shifts = data.shifts || [];
      DB.attendance = data.attendance || [];
      DB.liveAttendance = data.liveAttendance || [];
      DB.leaves = data.leaves || [];
      DB.leaveTypes = data.leaveTypes || [];
      DB.leavePolicies = data.leavePolicies || [];
      DB.leaveBalances = data.leaveBalances || [];
      DB.regulations = data.regulations || [];
      DB.announcements = data.announcements || [];
      DB.holidays = data.holidays || [];
      DB.events = data.events || [];
      DB.notifications = data.notifications || [];
      DB.notificationCount = data.notificationCount || 0;
      DB.customNotifications = data.customNotifications || [];
      DB.company = data.company || {};
      DB.companyPolicies = data.companyPolicies || [];
      DB.backups = data.backups || [];
      DB.deletedBackups = data.deletedBackups || [];
      DB.recoveryItems = data.recoveryItems || [];
      if(typeof handleBrowserNotifications === 'function'){
        handleBrowserNotifications(DB.notifications);
      }
    }
    if(typeof syncPunchStateFromBootstrap === 'function'){
      syncPunchStateFromBootstrap();
    }
    if(typeof buildNav === 'function'){
      buildNav();
      const navEl = document.getElementById('nav-'+window.__workpulseCurrentPage);
      if(navEl) navEl.classList.add('active');
    }
    if(typeof updateNotificationUI === 'function') updateNotificationUI();
    syncLeaveTypeOptions();
    syncDepartmentOptions('ne-dept');
    syncDepartmentOptions('ee-dept');
    syncShiftOptions('ne-shift');
    syncShiftOptions('ee-shift');
    if(typeof syncNewEmployeeManagerOptions === 'function') syncNewEmployeeManagerOptions();
    syncAnnouncementAudienceOptions();
    syncAnnouncementRecipientOptions();
    syncNotificationAudienceOptions();
    syncNotificationRecipientOptions();
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

async function refreshEmployeeWorkspaceSnapshot(pageId){
  if(DB.currentRole !== 'employee' || !DB.currentUser) return;

  try{
    const data = await wpApi('/api/bootstrap', {method:'GET', headers:{}});
    DB.employees = data.employees || DB.employees || [];
    DB.liveAttendance = data.liveAttendance || DB.liveAttendance || [];
    DB.leaves = data.leaves || DB.leaves || [];
    DB.leaveBalances = data.leaveBalances || [];
    DB.announcements = data.announcements || [];
    DB.events = data.events || [];
    DB.notifications = data.notifications || [];
    DB.notificationCount = data.notificationCount || 0;
    if(typeof handleBrowserNotifications === 'function'){
      handleBrowserNotifications(DB.notifications);
    }
    if(window.__workpulseCurrentPage === pageId){
      if(typeof updateNotificationUI === 'function') updateNotificationUI();
      showPage(pageId);
    }
  }catch(e){}
}

function getLeaveTypesList(){
  if(Array.isArray(DB.leaveTypes) && DB.leaveTypes.length){
    return DB.leaveTypes.map(type=>({
      name: type.name || type.code || 'Leave',
      code: type.code || getLeaveTypeCode(type.name || ''),
      paid: Boolean(type.paid),
    }));
  }

  return [
    {name:'Annual Leave', code:'annual', paid:true},
    {name:'Sick Leave', code:'sick', paid:true},
    {name:'Casual Leave', code:'casual', paid:true},
    {name:'Paternity Leave', code:'paternity', paid:true},
    {name:'Maternity Leave', code:'maternity', paid:true},
    {name:'Marriage Leave', code:'marriage', paid:true},
    {name:'Bereavement Leave', code:'bereavement', paid:true},
    {name:'Unpaid Leave', code:'unpaid', paid:false},
  ];
}

function getLeavePoliciesList(){
  if(Array.isArray(DB.leaveTypes) && DB.leaveTypes.length){
    const policyMap = new Map((Array.isArray(DB.leavePolicies) ? DB.leavePolicies : []).map(policy => [policy.code, policy]));
    return DB.leaveTypes.map(type => {
      const policy = policyMap.get(type.code) || {};
      return {
        code: type.code,
        name: policy.name || type.name || type.code || 'Leave',
        paid: Boolean(type.paid ?? policy.paid),
        quota_days: Number(policy.quota_days ?? 0),
        pro_rata: Boolean(policy.pro_rata),
        carry_forward_days: Number(policy.carry_forward_days ?? 0),
      };
    });
  }

  return getLeaveTypesList().map(type => ({
    code: type.code,
    name: type.name,
    paid: Boolean(type.paid),
    quota_days: 0,
    pro_rata: false,
    carry_forward_days: 0,
  }));
}

function syncLeaveTypeOptions(){
  const select = document.getElementById('lv-type');
  if(!select) return;
  const types = getLeaveTypesList();
  if(!types.length) return;
  const current = select.value;
  select.innerHTML = types.map(type => `<option value="${type.code}">${type.name}</option>`).join('');

  if(current && types.some(type => type.code === current)){
    select.value = current;
  }
}

function getDepartmentList(){
  if(Array.isArray(DB.departments) && DB.departments.length){
    return DB.departments
      .map(department => department?.name)
      .filter(Boolean);
  }

  return ['Engineering','Human Resources','Finance','Marketing','Product','Operations','Management'];
}

function syncDepartmentOptions(targetId, preferredValue=''){
  const select = document.getElementById(targetId);
  if(!select) return;

  const currentValue = preferredValue || select.value || '';
  const departments = getDepartmentList();
  const values = currentValue && !departments.includes(currentValue)
    ? [...departments, currentValue]
    : departments;

  select.innerHTML = values.map(name => `<option value="${name}">${name}</option>`).join('');

  if(currentValue){
    select.value = currentValue;
  } else if(values.length){
    select.value = values[0];
  }
}

function getShiftList(){
  return Array.isArray(DB.shifts) ? DB.shifts : [];
}

function getCurrentShiftPolicy(){
  const currentUser = DB.currentUser || {};
  const shiftStart = currentUser.shiftStart || '11:00';
  const shiftEnd = currentUser.shiftEnd || '20:00';
  const shiftGrace = Number.isFinite(Number(currentUser.shiftGrace)) ? Number(currentUser.shiftGrace) : 10;
  const shiftBreak = Number.isFinite(Number(currentUser.shiftBreak)) ? Number(currentUser.shiftBreak) : 60;
  const breakLabel = shiftBreak === 60 ? '1h break' : `${shiftBreak} min break`;
  const [startHour, startMinute] = String(shiftStart).split(':').map(Number);
  const lateAt = new Date();
  lateAt.setHours(startHour || 0, (startMinute || 0) + shiftGrace, 0, 0);
  const lateLabel = lateAt.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'});
  return `${shiftStart} - ${shiftEnd} Â· ${breakLabel} Â· Late after ${lateLabel}`;
}

function getEmployeeShiftSummary(employee){
  const shiftStart = employee?.shiftStart || '11:00';
  const shiftEnd = employee?.shiftEnd || '20:00';
  const shiftBreak = Number.isFinite(Number(employee?.shiftBreak)) ? Number(employee.shiftBreak) : 60;
  const breakLabel = shiftBreak === 60 ? '1h break' : `${shiftBreak} min break`;
  return `${shiftStart} - ${shiftEnd} Â· ${breakLabel}`;
}

function getEmployeeWorkingDays(employee){
  return employee?.shiftWorkingDays || 'Mon - Fri';
}

function canModifyEmployee(employee){
  return !(employee?.role === 'manager' && DB.currentRole !== 'manager');
}

function getBreakMinutesFromAttendanceRecord(record){
  if(!record?.breakOut || !record?.breakIn) return 0;
  const [boh,bom] = String(record.breakOut).split(':').map(Number);
  const [bih,bim] = String(record.breakIn).split(':').map(Number);
  const start = (boh * 60) + bom;
  const end = (bih * 60) + bim;
  return Math.max(0, end - start);
}

function formatBreakMinutesLabel(minutes){
  const safeMinutes = Math.max(0, Number(minutes || 0));
  if(!safeMinutes) return 'â€”';
  if(safeMinutes % 60 === 0){
    return `${safeMinutes / 60}h`;
  }
  if(safeMinutes > 60){
    const hours = Math.floor(safeMinutes / 60);
    const mins = safeMinutes % 60;
    return `${hours}h ${mins}m`;
  }
  return `${safeMinutes} min`;
}

function syncShiftOptions(targetId, preferredValue=''){
  const select = document.getElementById(targetId);
  if(!select) return;

  const currentValue = preferredValue !== undefined && preferredValue !== null ? String(preferredValue) : String(select.value || '');
  const options = ['<option value="">No Shift Assigned</option>'].concat(
    getShiftList().map(shift => `<option value="${shift.id}">${shift.name} (${shift.start} - ${shift.end})</option>`)
  );
  select.innerHTML = options.join('');
  if(currentValue) select.value = currentValue;
}

function syncAnnouncementAudienceOptions(){
  const select = document.getElementById('ann-aud');
  if(!select) return;

  const currentValue = select.value || 'all';
  const options = [
    {value:'all', label:'All Employees'},
    {value:'role:employee', label:'Employees Only'},
    {value:'role:manager', label:'Super-Admins Only'},
    {value:'role:admin', label:'Admins Only'},
        ...getDepartmentList().map(name => ({value:`department:${name}`, label:`Team: ${name}`})),
    {value:'specific', label:'Specific Employees'},
  ];
  select.innerHTML = options.map(option => `<option value="${option.value}">${option.label}</option>`).join('');
  select.value = options.some(option => option.value === currentValue) ? currentValue : 'all';
  toggleAnnouncementRecipients();
}

function syncAnnouncementRecipientOptions(){
  const select = document.getElementById('ann-targets');
  if(!select) return;

  const selected = Array.from(select.selectedOptions || []).map(option => option.value);
  select.innerHTML = (Array.isArray(DB.employees) ? DB.employees : [])
    .map(employee => `<option value="${employee.id}">${employee.fname} ${employee.lname} (${employee.id})</option>`)
    .join('');

  selected.forEach(value => {
    const option = Array.from(select.options).find(item => item.value === value);
    if(option) option.selected = true;
  });
}

function toggleAnnouncementRecipients(){
  const audience = document.getElementById('ann-aud')?.value || 'all';
  const wrap = document.getElementById('ann-recipient-wrap');
  if(wrap) wrap.style.display = audience === 'specific' ? 'block' : 'none';
}

function syncNotificationAudienceOptions(){
  const select = document.getElementById('ntf-aud');
  if(!select) return;

  const currentValue = select.value || 'all';
  const options = [
    {value:'all', label:'All Employees'},
    {value:'role:employee', label:'Employees Only'},
    {value:'role:manager', label:'Super-Admins Only'},
    {value:'role:admin', label:'Admins Only'},
    ...getDepartmentList().map(name => ({value:`department:${name}`, label:`Team: ${name}`})),
    {value:'specific', label:'Specific Employees'},
  ];

  select.innerHTML = options.map(option => `<option value="${option.value}">${option.label}</option>`).join('');
  select.value = options.some(option => option.value === currentValue) ? currentValue : 'all';
  toggleNotificationRecipients();
}

function syncNotificationRecipientOptions(){
  const select = document.getElementById('ntf-targets');
  if(!select) return;

  const selected = Array.from(select.selectedOptions || []).map(option => option.value);
  select.innerHTML = (Array.isArray(DB.employees) ? DB.employees : [])
    .map(employee => `<option value="${employee.id}">${employee.fname} ${employee.lname} (${employee.id})</option>`)
    .join('');

  selected.forEach(value => {
    const option = Array.from(select.options).find(item => item.value === value);
    if(option) option.selected = true;
  });
}

function toggleNotificationRecipients(){
  const audience = document.getElementById('ntf-aud')?.value || 'all';
  const wrap = document.getElementById('ntf-recipient-wrap');
  if(wrap) wrap.style.display = audience === 'specific' ? 'block' : 'none';
}

function getLeaveTypeCode(label){
  const normalized = String(label || '').toLowerCase();
  const dynamicMatch = getLeaveTypesList().find(type => String(type.name || '').toLowerCase() === normalized);
  if(dynamicMatch){
    return dynamicMatch.code;
  }

  const typeMap = {
    'annual leave':'annual',
    'sick leave':'sick',
    'unpaid leave':'unpaid',
    'paternity leave':'paternity',
    'maternity leave':'maternity',
    'marriage leave':'marriage',
    'bereavement leave':'bereavement',
    'casual leave':'casual',
  };

  return typeMap[normalized] || 'annual';
}

function findLeaveBalance(code){
  const list = getLeaveBalancesList();
  return list.find(balance => balance.code === code);
}

function getLeaveBalancesList(){
  const raw = DB.leaveBalances;
  if(Array.isArray(raw)){
    return raw.map(balance=>({
      code: balance.code || getLeaveTypeCode(balance.name || ''),
      name: balance.name || balance.code || 'Leave',
      remaining: Number(balance.remaining ?? 0),
      allocated: Number(balance.allocated ?? 0),
      used: Number(balance.used ?? Math.max(0, Number(balance.allocated ?? 0) - Number(balance.remaining ?? 0))),
    }));
  }

  if(raw && typeof raw === 'object'){
    const userId = DB.currentUser?.id;
    const bucket = userId && raw[userId] && typeof raw[userId] === 'object' ? raw[userId] : raw;
    const labelByCode = {
      annual:'Annual Leave',
      sick:'Sick Leave',
      casual:'Casual Leave',
      paternity:'Paternity Leave',
      maternity:'Maternity Leave',
      marriage:'Marriage Leave',
      bereavement:'Bereavement Leave',
      unpaid:'Unpaid Leave',
    };
    return Object.entries(bucket).map(([code,val])=>{
      const remaining = Number(
        (val && typeof val === 'object' && 'remaining' in val)
          ? val.remaining
          : val ?? 0
      );
      const allocated = Number(
        (val && typeof val === 'object' && 'allocated' in val)
          ? val.allocated
          : (remaining > 0 ? remaining : 0)
      );
      return {
        code,
        name: labelByCode[code] || code,
        remaining: Number.isFinite(remaining) ? remaining : 0,
        allocated: Number.isFinite(allocated) ? allocated : 0,
        used: Math.max(0, allocated - remaining),
      };
    });
  }

  return [];
}

function getEmployeesOnLeaveToday(){
  const today = getTodayLocalDate();
  const employeesById = new Map((DB.employees || []).map(employee => [String(employee.id), employee]));

  return (DB.leaves || [])
    .filter(leave => leave.status === 'Approved' && leave.from <= today && leave.to >= today)
    .map(leave => {
      const employee = employeesById.get(String(leave.empId)) || {};
      return {
        id: leave.id,
        empId: leave.empId,
        empName: leave.empName,
        dept: leave.dept || employee.dept || '-',
        manager: employee.manager || '-',
        type: leave.type,
        from: leave.from,
        to: leave.to,
        duration: formatLeaveDuration(leave),
        reason: leave.reason || '-',
      };
    });
}

function getLeaveTeamManagers(){
  return [...new Set((DB.employees || []).map(employee => employee.manager).filter(Boolean).filter(manager => manager !== '-'))].sort();
}

function renderOnLeaveTodayRows(rows){
  if(!rows.length){
    return '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No employees are on leave for this filter.</td></tr>';
  }

  return rows.map(row => `
    <tr>
      <td>${row.empName}</td>
      <td>${row.dept || '-'}</td>
      <td>${row.manager || '-'}</td>
      <td>${row.type}</td>
      <td>${formatDate(row.from)}</td>
      <td>${formatDate(row.to)}</td>
      <td>${row.duration}</td>
    </tr>
  `).join('');
}

function updateLeaveTodayFilters(){
  const scope = document.getElementById('lv-today-scope')?.value || 'company';
  const detailSelect = document.getElementById('lv-today-detail');
  if(!detailSelect) return;

  let options = [{value:'', label:'All Employees'}];
  if(scope === 'department'){
    options = [{value:'', label:'All Teams'}, ...getDepartmentList().map(name => ({value:name, label:name}))];
  } else if(scope === 'team'){
    options = [{value:'', label:'All Teams'}, ...getLeaveTeamManagers().map(name => ({value:name, label:name}))];
  }

  detailSelect.innerHTML = options.map(option => `<option value="${option.value}">${option.label}</option>`).join('');
}

function applyLeaveTodayFilters(){
  const scope = document.getElementById('lv-today-scope')?.value || 'company';
  const detail = document.getElementById('lv-today-detail')?.value || '';
  let rows = getEmployeesOnLeaveToday();

  if(scope === 'department' && detail){
    rows = rows.filter(row => (row.dept || '') === detail);
  } else if(scope === 'team' && detail){
    rows = rows.filter(row => (row.manager || '') === detail);
  }

  const tbody = document.getElementById('lv-today-tbody');
  if(tbody){
    tbody.innerHTML = renderOnLeaveTodayRows(rows);
  }

  const countEl = document.getElementById('lv-today-count');
  if(countEl){
    countEl.textContent = String(rows.length);
  }
}

async function wpPunch(type){
  const now = new Date();
  const localTimestamp = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
  return wpApi('/api/attendance/punch', {method:'POST', body: JSON.stringify({type, punched_at: localTimestamp})});
}

async function wpAutoCloseStaleAttendance(){
  return wpApi('/api/attendance/auto-close-stale', {method:'POST', body: JSON.stringify({})});
}

function getTodayLocalDate(){
  const now = new Date();
  return formatLocalDateValue(now);
}

function formatLocalDateValue(date){
  if(!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth()+1).padStart(2,'0');
  const day = String(date.getDate()).padStart(2,'0');
  return `${year}-${month}-${day}`;
}

function getDateRangeValues(from, to){
  if(!from || !to) return [];
  const dates = [];
  let cursor = new Date(from+'T00:00:00');
  const end = new Date(to+'T00:00:00');
  while(cursor <= end){
    dates.push(formatLocalDateValue(cursor));
    cursor.setDate(cursor.getDate() + 1);
  }
  return dates;
}

function getShiftEndForDate(dateStr){
  const currentUser = DB.currentUser || {};
  const shiftEnd = currentUser.shiftEnd || '20:00';
  const shiftStart = currentUser.shiftStart || '11:00';
  const [startHour, startMinute] = String(shiftStart).split(':').map(Number);
  const [endHour, endMinute] = String(shiftEnd).split(':').map(Number);
  const shiftEndDate = new Date(`${dateStr}T${String(endHour).padStart(2,'0')}:${String(endMinute).padStart(2,'0')}:00`);
  const shiftStartDate = new Date(`${dateStr}T${String(startHour).padStart(2,'0')}:${String(startMinute).padStart(2,'0')}:00`);

  if(shiftEndDate <= shiftStartDate){
    shiftEndDate.setDate(shiftEndDate.getDate()+1);
  }

  return shiftEndDate;
}

function isShiftCompletedForDate(dateStr){
  if(!dateStr) return false;
  return new Date() >= getShiftEndForDate(dateStr);
}

function clonePunchState(){
  const ps = DB.punchState || {};
  return {
    punchedIn: !!ps.punchedIn,
    onBreak: !!ps.onBreak,
    clockInTime: ps.clockInTime ? new Date(ps.clockInTime.getTime()) : null,
    clockOutTime: ps.clockOutTime ? new Date(ps.clockOutTime.getTime()) : null,
    breakOutTime: ps.breakOutTime ? new Date(ps.breakOutTime.getTime()) : null,
    breakInTime: ps.breakInTime ? new Date(ps.breakInTime.getTime()) : null,
    totalBreakMs: ps.totalBreakMs || 0,
    currentSessionBreakMs: ps.currentSessionBreakMs || 0,
    sessionLogs: Array.isArray(ps.sessionLogs) ? ps.sessionLogs.map(item => ({...item})) : [],
  };
}

function restorePunchState(snapshot){
  DB.punchState = snapshot;
}

async function punchIn(){
  const ps = DB.punchState;
  if(ps.punchedIn) return;
  if(isShiftCompletedForDate(getTodayLocalDate())){
    showToast('Shift is already completed for today','red');
    return;
  }
  const snapshot = clonePunchState();
  const actionTime = new Date();
  const actionLabel = actionTime.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  ps.punchedIn = true;
  ps.onBreak = false;
  ps.clockInTime = actionTime;
  ps.clockOutTime = null;
  ps.breakOutTime = null;
  ps.breakInTime = null;
  ps.currentSessionBreakMs = 0;
  ps.sessionLogs.push({event:'Clock In',time:actionLabel});

  const today = getTodayLocalDate();
  const empId = DB.currentUser.id;

  try{
    const punchResponse = await wpPunch('clock_in');
    await wpReload();
    savePunchState(empId);
    refreshPunchUI();
    showToast(punchResponse?.message || ('Clocked in at '+actionLabel),'green');
  }catch(e){
    restorePunchState(snapshot);
    await wpReload();
    refreshPunchUI();
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

async function punchOut(){
  const ps = DB.punchState;
  if(!ps.punchedIn) return;
  const snapshot = clonePunchState();
  const actionTime = new Date();
  const actionLabel = actionTime.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  ps.punchedIn = false;
  ps.onBreak = false;
  ps.clockOutTime = actionTime;
  ps.breakOutTime = null;
  ps.breakInTime = null;
  ps.currentSessionBreakMs = 0;
  ps.sessionLogs.push({event:'Clock Out',time:actionLabel});

  const today = getTodayLocalDate();
  const empId = DB.currentUser.id;

  try{
    await wpPunch('clock_out');
    await wpReload();
    savePunchState(empId);
    refreshPunchUI();
    showToast('Clocked out at '+actionLabel,'red');
  }catch(e){
    restorePunchState(snapshot);
    await wpReload();
    refreshPunchUI();
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

async function breakOut(){
  const ps = DB.punchState;
  if(!ps.punchedIn||ps.onBreak) return;
  const snapshot = clonePunchState();
  const actionTime = new Date();
  const actionLabel = actionTime.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  ps.onBreak = true;
  ps.breakOutTime = actionTime;
  ps.sessionLogs.push({event:'Break In',time:actionLabel});

  const today = getTodayLocalDate();
  const empId = DB.currentUser.id;

  try{
    await wpPunch('break_out');
    await wpReload();
    savePunchState(empId);
    refreshPunchUI();
    showToast('Break in started at '+actionLabel,'amber');
  }catch(e){
    restorePunchState(snapshot);
    await wpReload();
    refreshPunchUI();
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

async function breakIn(){
  const ps = DB.punchState;
  if(!ps.onBreak) return;
  const snapshot = clonePunchState();
  const actionTime = new Date();
  const actionLabel = actionTime.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
  ps.onBreak = false;
  const diff = actionTime - ps.breakOutTime;
  ps.totalBreakMs += diff;
  ps.currentSessionBreakMs += diff;
  ps.breakOutTime = null;
  ps.breakInTime = actionTime;
  ps.sessionLogs.push({event:'Break Out',time:actionLabel});

  const today = getTodayLocalDate();
  const empId = DB.currentUser.id;

  try{
    await wpPunch('break_in');
    await wpReload();
    savePunchState(empId);
    refreshPunchUI();
    showToast('Break out ended at '+actionLabel,'green');
  }catch(e){
    restorePunchState(snapshot);
    await wpReload();
    refreshPunchUI();
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

function refreshPunchUI(){
  const ps = DB.punchState;
  const currentTitle = document.getElementById('page-title').textContent;
  const mc = document.getElementById('main-content');
  if(!mc) return;

  // Pages that need full re-render when punch state changes
  const adminAttTitle = pageTitles['attendance'];
  const empDashTitle  = pageTitles['emp-dashboard'];
  const empAttTitle   = pageTitles['emp-attendance'];

  if(currentTitle === adminAttTitle){
    mc.innerHTML = renderPage('attendance');
    startClock();
  } else if(currentTitle === empDashTitle){
    // Re-render whole dashboard so buttons + stats update
    mc.innerHTML = renderPage('emp-dashboard');
    startClock();
  } else if(currentTitle === empAttTitle){
    mc.innerHTML = renderPage('emp-attendance');
    startClock();
  }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TOAST
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function showToast(msg, type='green'){
  const colors={green:'var(--green)',red:'var(--red)',amber:'var(--amber)'};
  const t=document.createElement('div');
  t.style.cssText=`position:fixed;bottom:24px;right:24px;background:${colors[type]||colors.green};color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:500;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:opacity .3s;`;
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(()=>{t.style.opacity='0';setTimeout(()=>t.remove(),300);},2500);
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  SUBMIT ACTIONS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function submitLeave(){
  const typeCode=document.getElementById('lv-type').value;
  const from=document.getElementById('lv-from').value;
  const to=document.getElementById('lv-to').value;
  const reason=document.getElementById('lv-reason').value;
  const handover=document.getElementById('lv-handover').value;
  renderLeaveBreakdownRows();
  const dailyBreakdown = getLeaveDailyBreakdown();
  if(!from||!to||!reason){ showToast('Please fill all required fields','red'); return; }
  const weekendDates = getLeaveWeekendDates();
  if(weekendDates.length){
    showToast('Weekend dates cannot be used for leave: '+weekendDates.map(formatDate).join(', '),'red');
    return;
  }
  if(!dailyBreakdown.length){ showToast('Choose leave duration for each selected day','red'); return; }
  wpApi('/api/leave/apply', {
    method:'POST',
    body: JSON.stringify({
      leave_type_code: typeCode || 'annual',
      from_date: from,
      to_date: to,
      daily_breakdown: dailyBreakdown,
      reason,
      handover_to: handover,
    })
  })
    .then(()=>wpReload())
    .then(()=>{
      const page = DB.currentRole==='employee' ? 'emp-leaves' : 'leave';
      closeModal('leaveModal');
      showToast('Leave request submitted!','green');
      if(document.getElementById('page-title').textContent===pageTitles[page]) showPage(page);
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function formatLeaveDuration(leave){
  const dailyBreakdown = Array.isArray(leave?.dailyBreakdown || leave?.daily_breakdown)
    ? (leave.dailyBreakdown || leave.daily_breakdown)
    : [];
  if(dailyBreakdown.length){
    const fullDays = dailyBreakdown.filter(day => (day.durationType || day.duration_type) === 'full_day').length;
    const firstHalf = dailyBreakdown.filter(day => (day.halfDaySlot || day.half_day_slot) === 'first_half').length;
    const secondHalf = dailyBreakdown.filter(day => (day.halfDaySlot || day.half_day_slot) === 'second_half').length;
    const parts = [];
    if(fullDays) parts.push(`${fullDays} full day${fullDays===1 ? '' : 's'}`);
    if(firstHalf) parts.push(`${firstHalf} first half`);
    if(secondHalf) parts.push(`${secondHalf} second half`);
    return parts.join(' + ');
  }
  if((leave?.durationType || leave?.duration_type) === 'half_day'){
    return (leave?.halfDaySlot || leave?.half_day_slot) === 'second_half' ? 'Second Half (0.5)' : 'First Half (0.5)';
  }
  const days = Number(leave?.days || 0);
  return `${days} day${days===1 ? '' : 's'}`;
}

function formatLeaveBalanceValue(value){
  const num = Number(value || 0);
  if(!Number.isFinite(num)) return '0';
  const roundedHalf = Math.round(num * 2) / 2;
  if(Math.abs(num - roundedHalf) < 0.001 && !Number.isInteger(roundedHalf)){
    return roundedHalf.toFixed(1);
  }
  return String(Math.round(num));
}

function getLeaveDateRange(){
  const from = document.getElementById('lv-from')?.value;
  const to = document.getElementById('lv-to')?.value || from;
  return getDateRangeValues(from, to);
}

function isWeekendDateValue(date){
  const day = new Date(date+'T00:00:00').getDay();
  return day === 0 || day === 6;
}

function getLeaveWeekendDates(){
  return getLeaveDateRange().filter(isWeekendDateValue);
}

function updateLeaveQuotaDisplay(){
  const quotaEl = document.getElementById('lv-quota');
  const typeCode = document.getElementById('lv-type')?.value || '';
  if(!quotaEl || !typeCode) return;

  const type = getLeaveTypesList().find(item => item.code === typeCode);
  const balance = typeof findLeaveBalance === 'function' ? findLeaveBalance(typeCode) : null;
  if(balance){
    quotaEl.style.display = 'block';
    quotaEl.innerHTML = `Available quota: <strong>${formatLeaveBalanceValue(balance.remaining)}</strong> day(s) remaining from ${formatLeaveBalanceValue(balance.allocated)} entitled. Used: ${formatLeaveBalanceValue(balance.used)}.`;
    return;
  }

  if(type && type.paid === false){
    quotaEl.style.display = 'block';
    quotaEl.innerHTML = 'Unpaid leave does not use paid leave quota.';
    return;
  }

  quotaEl.style.display = 'block';
  quotaEl.innerHTML = 'Available quota will appear after your leave balance is loaded.';
}

function renderLeaveBreakdownRows(){
  const wrap = document.getElementById('lv-breakdown-wrap');
  const tbody = document.getElementById('lv-breakdown-rows');
  const dates = getLeaveDateRange();
  if(!wrap || !tbody) return;

  if(!dates.length){
    wrap.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px;">Choose leave dates to build the leave plan.</td></tr>`;
    calcLeaveDays();
    return;
  }

  wrap.style.display = 'block';
  const weekendDates = dates.filter(isWeekendDateValue);
  if(weekendDates.length){
    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--red);padding:20px;">Weekend dates cannot be used for leave: ${weekendDates.map(formatDate).join(', ')}.</td></tr>`;
    calcLeaveDays();
    return;
  }

  const existing = new Map(
    Array.from(tbody.querySelectorAll('tr[data-leave-date]')).map(row => [
      row.getAttribute('data-leave-date'),
      row.querySelector('select[data-leave-day-duration]')?.value || 'full_day'
    ])
  );

  tbody.innerHTML = dates.map(date => {
    const selected = existing.get(date) || 'full_day';
    const dayLabel = new Date(date+'T00:00:00').toLocaleDateString('en-GB', {weekday:'short'});
    return `<tr data-leave-date="${date}">
      <td>${formatDate(date)}</td>
      <td>${dayLabel}</td>
      <td>
        <select class="fi" data-leave-day-duration onchange="window.calcLeaveDays()" style="max-width:220px;">
          <option value="full_day" ${selected==='full_day' ? 'selected' : ''}>Full Day</option>
          <option value="first_half" ${selected==='first_half' ? 'selected' : ''}>First Half</option>
          <option value="second_half" ${selected==='second_half' ? 'selected' : ''}>Second Half</option>
        </select>
      </td>
    </tr>`;
  }).join('');

  calcLeaveDays();
}

function getLeaveDailyBreakdown(){
  return Array.from(document.querySelectorAll('#lv-breakdown-rows tr[data-leave-date]')).map(row => {
    const date = row.getAttribute('data-leave-date');
    const duration = row.querySelector('select[data-leave-day-duration]')?.value || 'full_day';
    return {
      date,
      duration_type: duration === 'full_day' ? 'full_day' : 'half_day',
      half_day_slot: duration === 'full_day' ? null : duration,
    };
  });
}

function resetLeaveForm(){
  const typeSelect = document.getElementById('lv-type');
  if(typeSelect && typeSelect.options.length) typeSelect.selectedIndex = 0;
  ['lv-from','lv-to','lv-reason','lv-handover'].forEach(id => {
    const el = document.getElementById(id);
    if(el) el.value = '';
  });
  const wrap = document.getElementById('lv-breakdown-wrap');
  const rows = document.getElementById('lv-breakdown-rows');
  if(wrap) wrap.style.display = 'none';
  if(rows) rows.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px;">Choose leave dates to build the leave plan.</td></tr>`;
  const calc = document.getElementById('lv-calc');
  const days = document.getElementById('lv-days');
  if(calc) calc.style.display = 'none';
  if(days) days.textContent = '0';
  if(typeof updateLeaveQuotaDisplay === 'function') updateLeaveQuotaDisplay();
}

function calcLeaveDays(){
  const rows = getLeaveDailyBreakdown();
  const days = rows.reduce((sum, row) => sum + (row.duration_type === 'half_day' ? 0.5 : 1), 0);
  const c=document.getElementById('lv-calc');
  const d=document.getElementById('lv-days');
  if(c&&d){
    c.style.display = rows.length ? 'block' : 'none';
    d.textContent = rows.length ? formatLeaveBalanceValue(days) : '0';
  }
}

document.getElementById('leaveModal').addEventListener('input',calcLeaveDays);
document.getElementById('lv-type')?.addEventListener('change',updateLeaveQuotaDisplay);
document.getElementById('lv-from')?.addEventListener('change',renderLeaveBreakdownRows);
document.getElementById('lv-to')?.addEventListener('change',renderLeaveBreakdownRows);
document.getElementById('reg-from')?.addEventListener('change',syncRegulationDateLimits);
document.getElementById('reg-to')?.addEventListener('change',syncRegulationDateLimits);
renderLeaveBreakdownRows();
syncLeaveTypeOptions();
syncDepartmentOptions('ne-dept');
syncDepartmentOptions('ee-dept');
syncShiftOptions('ne-shift');
syncShiftOptions('ee-shift');
syncAnnouncementAudienceOptions();
syncAnnouncementRecipientOptions();

function getRegulationDateRange(){
  const from = document.getElementById('reg-from')?.value;
  const to = document.getElementById('reg-to')?.value || from;
  if(!from || !to) return [];
  if(to < from){
    showToast('To date must be after from date','red');
    return [];
  }
  const dates = [];
  let cursor = new Date(from+'T00:00:00');
  const end = new Date(to+'T00:00:00');
  while(cursor <= end){
    dates.push(cursor.toISOString().split('T')[0]);
    cursor.setDate(cursor.getDate() + 1);
  }
  return dates;
}

function syncRegulationDateLimits(){
  const fromField = document.getElementById('reg-from');
  const toField = document.getElementById('reg-to');
  if(!fromField || !toField) return;
  toField.min = fromField.value || '';
  if(fromField.value && toField.value && toField.value < fromField.value){
    toField.value = fromField.value;
  }
}

function openRegulationModal(){
  const user = DB.currentUser || {};
  const today = getTodayLocalDate();
  resetRegulationForm();
  const employeeField = document.getElementById('reg-employee');
  const fromField = document.getElementById('reg-from');
  const toField = document.getElementById('reg-to');
  const rows = document.getElementById('reg-rows');
  if(employeeField) employeeField.value = `${user.fname || ''} ${user.lname || ''}`.trim() || user.name || '-';
  if(fromField) fromField.value = today;
  if(toField) toField.value = today;
  syncRegulationDateLimits();
  if(rows){
    rows.innerHTML = `<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px;">Choose a date range and click Fetch Attendance.</td></tr>`;
  }
  openModal('regulationModal');
}

function resetRegulationForm(){
  ['reg-from','reg-to'].forEach(id => {
    const el = document.getElementById(id);
    if(el) el.value = '';
  });
  const rows = document.getElementById('reg-rows');
  if(rows) rows.innerHTML = `<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px;">Choose a date range and click Fetch Attendance.</td></tr>`;
  syncRegulationDateLimits();
}

function toggleRegulationRow(checkbox){
  const row = checkbox.closest('tr');
  if(!row) return;
  row.querySelectorAll('input[data-reg-edit="1"], textarea[data-reg-edit="1"]').forEach(field => {
    field.disabled = !checkbox.checked;
  });
}

function removeRegulationRow(button){
  const row = button.closest('tr');
  if(row) row.remove();
  const rows = document.getElementById('reg-rows');
  if(rows && !rows.querySelector('tr[data-reg-date]')){
    rows.innerHTML = `<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px;">No rows left. Fetch attendance again to rebuild the list.</td></tr>`;
  }
}

async function loadRegulationRows(){
  const dates = getRegulationDateRange();
  const tbody = document.getElementById('reg-rows');
  const user = DB.currentUser || {};
  if(!tbody) return;
  if(!dates.length){
    showToast('Select a valid date range first','red');
    return;
  }

  const from = dates[0];
  const to = dates[dates.length - 1];
  tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px;">Loading attendance records...</td></tr>`;

  try{
    const query = new URLSearchParams({
      from,
      to,
      employee_code: user.id || '',
    });
    const data = await wpApi('/api/attendance/records?'+query.toString(), {method:'GET'});
    const attendance = Array.isArray(data.rows) ? data.rows : [];

    tbody.innerHTML = dates.map((date, index) => {
      const att = attendance.find(a => a.date === date) || {};
      const oldIn = att.in || '--:--';
      const oldOut = att.out || '--:--';
      return `<tr data-reg-date="${date}">
      <td><label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" onchange="window.toggleRegulationRow(this)"><span>${index + 1}</span></label></td>
      <td>${formatDate(date)}</td>
      <td>${oldIn} - ${oldOut}</td>
      <td><input type="date" class="fi" value="${date}" data-reg-edit="1" data-field="inDate" disabled></td>
      <td><input type="time" class="fi" value="${att.in || ''}" data-reg-edit="1" data-field="inTime" disabled></td>
      <td><input type="date" class="fi" value="${date}" data-reg-edit="1" data-field="outDate" disabled></td>
      <td><input type="time" class="fi" value="${att.out || ''}" data-reg-edit="1" data-field="outTime" disabled></td>
      <td><textarea class="fi" rows="2" placeholder="Write remarks / reason" data-reg-edit="1" data-field="reason" disabled></textarea></td>
      <td><button type="button" class="btn btn-sm" onclick="window.removeRegulationRow(this)">Remove</button></td>
    </tr>`;
    }).join('');
  }catch(e){
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:var(--red);padding:24px;">Could not fetch attendance records.</td></tr>`;
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

async function submitRegulation(){
  const type = 'Attendance Regulation';
  const rows = Array.from(document.querySelectorAll('#reg-rows tr[data-reg-date]'));
  const selected = rows.filter(row => row.querySelector('input[type="checkbox"]')?.checked);
  if(!selected.length){ showToast('Select at least one row to submit','red'); return; }

  const payloads = [];
  for(const row of selected){
    const date = row.getAttribute('data-reg-date');
    const oldLabel = (row.children[2]?.textContent || '').trim();
    const inDate = row.querySelector('[data-field="inDate"]')?.value || date;
    const inTime = row.querySelector('[data-field="inTime"]')?.value || '';
    const outDate = row.querySelector('[data-field="outDate"]')?.value || date;
    const outTime = row.querySelector('[data-field="outTime"]')?.value || '';
    const reason = row.querySelector('[data-field="reason"]')?.value.trim() || '';

    if(!reason){
      showToast('Each selected row needs remarks','red');
      return;
    }

    if(!inTime && !outTime){
      showToast('Add at least one requested time in each selected row','red');
      return;
    }

    const requestedParts = [];
    if(inTime) requestedParts.push(`In ${inDate} ${inTime}`);
    if(outTime) requestedParts.push(`Out ${outDate} ${outTime}`);

    payloads.push({
      date,
      type,
      original_value: oldLabel || '-',
      requested_value: requestedParts.join(' | '),
      reason,
    });
  }

  try{
    for(const payload of payloads){
      await wpApi('/api/attendance/regulations', {method:'POST', body: JSON.stringify(payload)});
    }
    await wpReload();
    closeModal('regulationModal');
    showToast(`${payloads.length} regulation request(s) submitted`,'green');
    if(window.__workpulseCurrentPage === 'attendance' || window.__workpulseCurrentPage === 'emp-attendance'){
      showPage(window.__workpulseCurrentPage);
    }
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

window.__workpulseAnnouncementEditId = '';
window.__workpulseVoteResults = null;
window.__workpulseVoteDetailAnnouncementId = '';
window.__workpulseVoteResultsAnnouncementId = '';
window.__workpulseVoteResultsFilter = 'all';

function getAnnouncementNumericId(announcementId){
  return String(announcementId || '').replace('AN-','');
}

function getAnnouncementById(announcementId){
  return (Array.isArray(DB.announcements) ? DB.announcements : []).find(a => String(a.id) === String(announcementId)) || null;
}

function toggleAnnouncementVoteFields(){
  const enabled = !!document.getElementById('ann-has-vote')?.checked;
  const wrap = document.getElementById('ann-vote-fields');
  if(wrap) wrap.style.display = enabled ? 'block' : 'none';
  if(enabled && !document.querySelectorAll('#ann-vote-options .ann-vote-option-row').length){
    addAnnouncementVoteOption('', '');
    addAnnouncementVoteOption('', '');
  }
}

function addAnnouncementVoteOption(label='', optionId=''){
  const wrap = document.getElementById('ann-vote-options');
  if(!wrap) return;
  const row = document.createElement('div');
  row.className = 'ann-vote-option-row';
  row.style.cssText = 'display:flex;gap:8px;align-items:center;';
  row.dataset.optionId = optionId ? String(optionId) : '';
  row.innerHTML = `<input type="text" class="fi ann-vote-option-label" value="${escapeHtmlAttr(label)}" placeholder="Choice option" style="flex:1;"><button type="button" class="btn btn-sm" onclick="window.removeAnnouncementVoteOption(this)">Remove</button>`;
  wrap.appendChild(row);
}

function removeAnnouncementVoteOption(button){
  const row = button?.closest('.ann-vote-option-row');
  if(row) row.remove();
}

function getAnnouncementVotePayload(){
  const hasVote = !!document.getElementById('ann-has-vote')?.checked;
  const options = Array.from(document.querySelectorAll('#ann-vote-options .ann-vote-option-row')).map(row => ({
    id: row.dataset.optionId || null,
    label: row.querySelector('.ann-vote-option-label')?.value?.trim() || '',
  })).filter(option => option.label);

  return {
    has_vote: hasVote,
    vote_question: hasVote ? (document.getElementById('ann-vote-question')?.value || '') : '',
    vote_options: hasVote ? options : [],
    show_results_to_employees_after_close: hasVote && !!document.getElementById('ann-vote-show-results')?.checked,
  };
}

function resetAnnouncementForm(){
  window.__workpulseAnnouncementEditId = '';
  document.getElementById('ann-title').value='';
  document.getElementById('ann-cat').value='General';
  document.getElementById('ann-effective-from').value='';
  document.getElementById('ann-effective-to').value='';
  document.getElementById('ann-msg').value='';
  document.getElementById('ann-aud').value='all';
  document.getElementById('ann-has-vote').checked=false;
  document.getElementById('ann-vote-question').value='';
  document.getElementById('ann-vote-show-results').checked=false;
  const voteOptionsWrap = document.getElementById('ann-vote-options');
  if(voteOptionsWrap) voteOptionsWrap.innerHTML = '';
  document.getElementById('announcement-modal-title').textContent='Post Announcement';
  document.getElementById('announcement-submit-btn').textContent='Publish';
  Array.from(document.getElementById('ann-targets')?.options || []).forEach(option => { option.selected = false; });
  toggleAnnouncementRecipients();
  toggleAnnouncementVoteFields();
}

function submitAnnouncement(){
  const announcementId=window.__workpulseAnnouncementEditId || '';
  const title=document.getElementById('ann-title').value;
  const cat=document.getElementById('ann-cat').value;
  const audience=document.getElementById('ann-aud').value;
  const effectiveFrom=document.getElementById('ann-effective-from').value;
  const effectiveTo=document.getElementById('ann-effective-to').value;
  const msg=document.getElementById('ann-msg').value;
  const recipientCodes = Array.from(document.getElementById('ann-targets')?.selectedOptions || []).map(option => option.value);
  const votePayload = getAnnouncementVotePayload();
  if(!title||!msg){ showToast('Title and message required','red'); return; }
  if(audience==='specific' && !recipientCodes.length){ showToast('Select at least one employee','red'); return; }
  if(votePayload.has_vote && (!votePayload.vote_question || votePayload.vote_options.length < 2)){ showToast('Voting question and at least two choices required','red'); return; }
  const path = announcementId ? `/api/announcements/${announcementId}` : '/api/announcements';
  const method = announcementId ? 'PATCH' : 'POST';
  const successMessage = announcementId ? 'Announcement updated!' : 'Announcement published!';
  wpApi(path, {method, body: JSON.stringify({title,category:cat,audience:audience||'all',message:msg,effective_from:effectiveFrom||null,effective_to:effectiveTo||null,recipient_employee_codes:recipientCodes,...votePayload})})
    .then(()=>wpReload())
    .then(()=>{
      resetAnnouncementForm();
      closeModal('announcementModal');
      showToast(successMessage,'green');
      if(document.getElementById('page-title').textContent==='Announcements') showPage('announcements');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function openAnnouncementModal(announcementId=''){
  syncAnnouncementAudienceOptions();
  syncAnnouncementRecipientOptions();
  resetAnnouncementForm();

  if(announcementId){
    const current = (Array.isArray(DB.announcements) ? DB.announcements : []).find(a => String(a.id) === String(announcementId));
    if(current){
      window.__workpulseAnnouncementEditId = current.id.replace('AN-','');
      document.getElementById('ann-title').value = current.title || '';
      document.getElementById('ann-cat').value = current.cat || 'General';
      document.getElementById('ann-aud').value = current.audienceKey || 'all';
      document.getElementById('ann-effective-from').value = current.effectiveFrom || '';
      document.getElementById('ann-effective-to').value = current.effectiveTo || '';
      document.getElementById('ann-msg').value = current.msg || '';
      document.getElementById('ann-has-vote').checked = !!current.hasVote;
      document.getElementById('ann-vote-question').value = current.voteQuestion || '';
      document.getElementById('ann-vote-show-results').checked = !!current.showResultsToEmployeesAfterClose;
      const voteOptionsWrap = document.getElementById('ann-vote-options');
      if(voteOptionsWrap) voteOptionsWrap.innerHTML = '';
      (current.voteOptions || []).forEach(option => addAnnouncementVoteOption(option.label || '', option.id || ''));
      document.getElementById('announcement-modal-title').textContent='Edit Announcement';
      document.getElementById('announcement-submit-btn').textContent='Update';
      Array.from(document.getElementById('ann-targets')?.options || []).forEach(option => {
        option.selected = Array.isArray(current.recipients) && current.recipients.some(recipient => recipient.employeeCode === option.value);
      });
    }
  }

  toggleAnnouncementRecipients();
  toggleAnnouncementVoteFields();
  openModal('announcementModal');
}

function deleteAnnouncement(announcementId){
  const id = String(announcementId || '').replace('AN-','');
  if(!id) return;
  if(!confirm('Delete this announcement?')) return;

  wpApi(`/api/announcements/${id}`, {method:'DELETE'})
    .then(()=>wpReload())
    .then(()=>{
      showToast('Announcement deleted!','green');
      if(document.getElementById('page-title').textContent==='Announcements') showPage('announcements');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function openAnnouncementVote(announcementId){
  window.__workpulseVoteDetailAnnouncementId = announcementId;
  showPage('announcement-vote');
}

function submitAnnouncementVote(announcementId){
  const announcement = getAnnouncementById(announcementId);
  const selected = document.querySelector('input[name="announcement-vote-option"]:checked')?.value;
  if(!announcement || !selected){ showToast('Select a voting option','red'); return; }

  wpApi(`/api/announcements/${getAnnouncementNumericId(announcementId)}/vote`, {method:'POST', body: JSON.stringify({option_id:selected})})
    .then(()=>wpReload())
    .then(()=>{
      showToast('Vote submitted','green');
      window.__workpulseVoteDetailAnnouncementId = announcementId;
      showPage('announcement-vote');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function closeAnnouncementVote(announcementId){
  if(!confirm('Close voting for this announcement? Employees will no longer be able to vote.')) return;
  wpApi(`/api/announcements/${getAnnouncementNumericId(announcementId)}/vote/close`, {method:'PATCH', body: JSON.stringify({})})
    .then(()=>wpReload())
    .then(()=>{
      showToast('Voting closed','green');
      if(window.__workpulseCurrentPage === 'announcement-vote-results'){
        openAnnouncementVoteResults(announcementId);
      } else {
        showPage('announcements');
      }
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function openAnnouncementVoteResults(announcementId){
  window.__workpulseVoteResultsAnnouncementId = announcementId;
  window.__workpulseVoteResults = null;
  window.__workpulseVoteResultsFilter = 'all';
  showPage('announcement-vote-results');
  wpApi(`/api/announcements/${getAnnouncementNumericId(announcementId)}/vote/results`, {method:'GET', headers:{}})
    .then(data => {
      window.__workpulseVoteResults = data.results || null;
      if(window.__workpulseCurrentPage === 'announcement-vote-results') showPage('announcement-vote-results');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function exportAnnouncementVoteResults(announcementId){
  window.location.href = `/api/announcements/${getAnnouncementNumericId(announcementId)}/vote/results.csv`;
}

function filterAnnouncementVoteResults(value){
  window.__workpulseVoteResultsFilter = value || 'all';
  showPage('announcement-vote-results');
}

function resetNotificationForm(){
  document.getElementById('ntf-reference-code').value = '';
  document.getElementById('ntf-title').value = '';
  document.getElementById('ntf-msg').value = '';
  document.getElementById('ntf-aud').value = 'all';
  Array.from(document.getElementById('ntf-targets')?.options || []).forEach(option => { option.selected = false; });
  toggleNotificationRecipients();
}

function openNotificationModal(referenceCode=''){
  if(typeof syncNotificationAudienceOptions === 'function') syncNotificationAudienceOptions();
  if(typeof syncNotificationRecipientOptions === 'function') syncNotificationRecipientOptions();
  resetNotificationForm();

  if(referenceCode){
    const current = (Array.isArray(DB.customNotifications) ? DB.customNotifications : []).find(item => item.referenceCode === referenceCode);
    if(current){
      document.getElementById('ntf-reference-code').value = current.referenceCode || '';
      document.getElementById('ntf-title').value = current.title || '';
      document.getElementById('ntf-msg').value = current.message || '';
      document.getElementById('ntf-aud').value = current.audience || 'all';
      Array.from(document.getElementById('ntf-targets')?.options || []).forEach(option => {
        option.selected = (current.recipientEmployeeCodes || []).includes(option.value);
      });
      toggleNotificationRecipients();
    }
  }

  openModal('notificationModal');
}

function submitNotification(){
  const referenceCode = document.getElementById('ntf-reference-code').value.trim();
  const title = document.getElementById('ntf-title').value.trim();
  const audience = document.getElementById('ntf-aud').value;
  const msg = document.getElementById('ntf-msg').value.trim();
  const recipientCodes = Array.from(document.getElementById('ntf-targets')?.selectedOptions || []).map(option => option.value);

  if(!title || !msg){ showToast('Title and message required','red'); return; }
  if(audience === 'specific' && !recipientCodes.length){ showToast('Select at least one employee','red'); return; }

  const path = referenceCode ? `/api/notifications/${referenceCode}` : '/api/notifications';
  const method = referenceCode ? 'PATCH' : 'POST';
  const successMessage = referenceCode ? 'Notification updated!' : 'Notification sent!';

  wpApi(path, {
    method,
    body: JSON.stringify({
      title,
      audience: audience || 'all',
      message: msg,
      recipient_employee_codes: recipientCodes,
    })
  })
    .then(() => wpReload())
    .then(() => {
      resetNotificationForm();
      closeModal('notificationModal');
      showToast(successMessage,'green');
      if(window.__workpulseCurrentPage === 'notifications') showPage('notifications');
    })
    .catch(e => showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function deleteNotification(referenceCode){
  if(!referenceCode) return;
  if(!confirm('Delete this notification for all recipients?')) return;

  wpApi(`/api/notifications/${referenceCode}`, {method:'DELETE'})
    .then(() => wpReload())
    .then(() => {
      showToast('Notification deleted','green');
      if(window.__workpulseCurrentPage === 'notifications') showPage('notifications');
    })
    .catch(e => showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function submitAddEmployee(){
  const fn=document.getElementById('ne-fname').value;
  const ln=document.getElementById('ne-lname').value;
  const email=document.getElementById('ne-email').value;
  const password=document.getElementById('ne-password').value;
  const phone=document.getElementById('ne-phone').value;
  const personalEmail=document.getElementById('ne-personal-email').value;
  const dept=document.getElementById('ne-dept').value;
  const desg=document.getElementById('ne-desg').value;
  const doj=document.getElementById('ne-doj').value;
  const dop=document.getElementById('ne-dop').value;
  const lwd=document.getElementById('ne-lwd').value;
  const confirmationDate=document.getElementById('ne-confirmation-date').value;
  const type=document.getElementById('ne-type').value;
  const role=document.getElementById('ne-role').value;
  const workLocation=document.getElementById('ne-work-location').value;
  const manager=document.getElementById('ne-manager').value;
  const dob=document.getElementById('ne-dob').value;
  const gender=document.getElementById('ne-gender').value;
  const cnic=document.getElementById('ne-cnic').value;
  const passportNo=document.getElementById('ne-passport-no').value;
  const address=document.getElementById('ne-address').value;
  const maritalStatus=document.getElementById('ne-marital-status').value;
  const blood=document.getElementById('ne-blood').value;
  const kin=document.getElementById('ne-kin').value;
  const kinRel=document.getElementById('ne-kinRel').value;
  const kinPhone=document.getElementById('ne-kinPhone').value;
  const basic=document.getElementById('ne-basic').value;
  const house=document.getElementById('ne-house').value;
  const transport=document.getElementById('ne-transport').value;
  const payPeriod=document.getElementById('ne-pay-period').value;
  const salaryStartDate=document.getElementById('ne-salary-start-date').value;
  const contribution=document.getElementById('ne-contribution').value;
  const otherDeductions=document.getElementById('ne-other-deductions').value;
  const tax=document.getElementById('ne-tax').value;
  const bank=document.getElementById('ne-bank').value;
  const acct=document.getElementById('ne-acct').value;
  const iban=document.getElementById('ne-iban').value;
  const shiftId=document.getElementById('ne-shift').value;
  const cnicDocument=document.getElementById('ne-cnic-document').files?.[0];
  if(!fn||!ln||!email||!personalEmail||!dept||!desg||!doj||!cnicDocument){ showToast('Please fill all required fields, including official email, personal email, and CNIC document','red'); return; }
  if(email.trim().toLowerCase() === personalEmail.trim().toLowerCase()){ showToast('Official email and personal email must be different','red'); return; }
  const formData = new FormData();
  formData.append('fname', fn);
  formData.append('lname', ln);
  formData.append('email', email);
  if(password) formData.append('password', password);
  if(phone) formData.append('phone', phone);
  formData.append('personal_email', personalEmail);
  formData.append('dept', dept);
  formData.append('desg', desg);
  formData.append('doj', doj);
  if(dop) formData.append('dop', dop);
  if(lwd) formData.append('lwd', lwd);
  if(confirmationDate) formData.append('confirmation_date', confirmationDate);
  if(type) formData.append('type', type);
  if(role) formData.append('role', role);
  if(workLocation) formData.append('work_location', workLocation);
  if(manager) formData.append('manager', manager);
  formData.append('shift_id', shiftId || '');
  if(dob) formData.append('dob', dob);
  if(gender) formData.append('gender', gender);
  if(cnic) formData.append('cnic', cnic);
  if(passportNo) formData.append('passport_no', passportNo);
  if(address) formData.append('address', address);
  if(maritalStatus) formData.append('marital_status', maritalStatus);
  if(blood) formData.append('blood', blood);
  if(kin) formData.append('kin', kin);
  if(kinRel) formData.append('kinRel', kinRel);
  if(kinPhone) formData.append('kinPhone', kinPhone);
  if(basic) formData.append('basic', basic);
  if(house) formData.append('house', house);
  if(transport) formData.append('transport', transport);
  if(payPeriod) formData.append('pay_period', payPeriod);
  if(salaryStartDate) formData.append('salary_start_date', salaryStartDate);
  if(contribution) formData.append('contribution', contribution);
  if(otherDeductions) formData.append('other_deductions', otherDeductions);
  if(tax) formData.append('tax', tax);
  if(bank) formData.append('bank', bank);
  if(acct) formData.append('acct', acct);
  if(iban) formData.append('iban', iban);
  formData.append('cnic_document', cnicDocument);
  wpApi('/api/employees', {method:'POST', body: formData})
    .then((data)=>{
      const tempMsg = data && data.temporary_password ? (' Temporary password: '+data.temporary_password) : '';
      showToast('Employee added: '+fn+' '+ln+tempMsg,'green');
      return wpReload().then(()=>{ if(document.getElementById('page-title').textContent==='Employees') showPage('employees'); });
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
  closeModal('addEmpModal');
  ['ne-fname','ne-lname','ne-email','ne-password','ne-phone','ne-personal-email','ne-work-location','ne-desg','ne-manager-search','ne-manager','ne-dop','ne-lwd','ne-confirmation-date','ne-cnic-document','ne-dob','ne-gender','ne-cnic','ne-passport-no','ne-address','ne-marital-status','ne-blood','ne-kin','ne-kinRel','ne-kinPhone','ne-basic','ne-house','ne-transport','ne-pay-period','ne-salary-start-date','ne-contribution','ne-other-deductions','ne-tax','ne-bank','ne-acct','ne-iban'].forEach(i=>{const el=document.getElementById(i); if(el) el.value='';});
  if(document.getElementById('ne-role')) document.getElementById('ne-role').value='employee';
  if(document.getElementById('ne-shift')) document.getElementById('ne-shift').value='';
  if(typeof syncNewEmployeeManagerOptions === 'function') syncNewEmployeeManagerOptions();
  if(document.getElementById('page-title').textContent==='Employees') showPage('employees');
}

function generateEmployeePasswordValue(){
  const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  const lower = 'abcdefghijkmnopqrstuvwxyz';
  const nums = '23456789';
  const symbols = '@#$%';
  const all = upper + lower + nums + symbols;
  const pick = chars => chars[Math.floor(Math.random() * chars.length)];
  let password = pick(upper) + pick(lower) + pick(nums) + pick(symbols);
  for(let i=0;i<8;i++) password += pick(all);
  return password.split('').sort(() => Math.random() - 0.5).join('');
}

function setNewEmployeePasswordMode(mode){
  const input = document.getElementById('ne-password');
  const auto = document.getElementById('ne-password-auto');
  const manual = document.getElementById('ne-password-manual');
  if(!input) return;

  const isManual = mode === 'manual';
  if(auto) auto.checked = !isManual;
  if(manual) manual.checked = isManual;
  input.type = isManual ? 'password' : 'text';
  const toggle = input.closest('.password-input-wrap')?.querySelector('[data-password-toggle]');
  if(toggle){
    if(typeof syncPasswordToggleIcon === 'function'){
      syncPasswordToggleIcon(toggle, !isManual);
    }
  }
  input.readOnly = !isManual;
  input.placeholder = isManual ? 'Create employee password' : 'Auto generated password';
  if(!isManual && !input.value){
    input.value = generateEmployeePasswordValue();
  }
  if(isManual){
    input.focus();
    input.select();
  }
}

function generateNewEmployeePassword(){
  const input = document.getElementById('ne-password');
  if(!input) return;
  input.value = generateEmployeePasswordValue();
  setNewEmployeePasswordMode('auto');
}

function initializeNewEmployeePassword(){
  const input = document.getElementById('ne-password');
  if(!input) return;
  if(!input.value){
    input.value = generateEmployeePasswordValue();
  }
  setNewEmployeePasswordMode(document.getElementById('ne-password-manual')?.checked ? 'manual' : 'auto');
}

window.setNewEmployeePasswordMode = setNewEmployeePasswordMode;
window.generateNewEmployeePassword = generateNewEmployeePassword;
window.initializeNewEmployeePassword = initializeNewEmployeePassword;

function submitHoliday(){
  const name=document.getElementById('hol-name').value;
  const date=document.getElementById('hol-date').value;
  const type=document.getElementById('hol-type').value;
  if(!name||!date){ showToast('Please fill all fields','red'); return; }
  wpApi('/api/holidays', {method:'POST', body: JSON.stringify({name,date,type})})
    .then(()=>wpReload())
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
  closeModal('holidayModal');
  showToast('Holiday added!','green');
  if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
}

function deleteHoliday(date){
  if(!date) return;
  if(!confirm('Remove this holiday?')) return;
  wpApi('/api/holidays/'+encodeURIComponent(date), {method:'DELETE'})
    .then(()=>wpReload())
    .then(()=>{
      showToast('Holiday removed','amber');
      if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
      if(window.__workpulseCurrentPage === 'emp-calendar') showPage('emp-calendar');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

let currentApprovalId=null;
function openApproval(leaveId){
  currentApprovalId=leaveId;
  const lv=DB.leaves.find(l=>l.id===leaveId);
  if(!lv) return;
  document.getElementById('approval-details').innerHTML=`
    <div class="card" style="background:var(--surface2);">
      <div class="irow"><span class="ikey">Employee</span><span class="ival">${escapeHtml(lv.empName)}</span></div>
          <div class="irow"><span class="ikey">Team</span><span class="ival">${escapeHtml(lv.dept)}</span></div>
      <div class="irow"><span class="ikey">Leave Type</span><span class="ival">${escapeHtml(lv.type)}</span></div>
      <div class="irow"><span class="ikey">Duration</span><span class="ival">${formatDate(lv.from)} â†’ ${formatDate(lv.to)} (${formatLeaveDuration(lv)})</span></div>
      <div class="irow"><span class="ikey">Reason</span><span class="ival">${escapeHtml(lv.reason)}</span></div>
      <div class="irow"><span class="ikey">Handover</span><span class="ival">${lv.handover||'â€”'}</span></div>
    </div>`;
  openModal('approvalModal');
}

function approveLeave(decision){
  const lv=DB.leaves.find(l=>l.id===currentApprovalId);
  if(!lv) return;
  wpApi('/api/leave/'+encodeURIComponent(lv.id)+'/review', {method:'PATCH', body: JSON.stringify({status: decision, notes: ''})})
    .then(()=>wpReload())
    .then(()=>{
      if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
      closeModal('approvalModal');
      showToast(`Leave ${decision.toLowerCase()}!`, decision==='Approved'?'green':'red');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function reviewRegulationRequest(id, decision){
  if(!id) return;
  wpApi('/api/attendance/regulations/'+encodeURIComponent(id)+'/review', {method:'PATCH', body: JSON.stringify({status: decision})})
    .then(()=>wpReload())
    .then(()=>{
      if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
      showToast(`Regulation request ${decision.toLowerCase()}!`, decision==='Approved'?'green':'red');
    })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

async function markAllNotificationsRead(){
  await wpApi('/api/me/notifications/read-all', {method:'PATCH'});
  await wpReload();
  if(window.__workpulseCurrentPage === 'emp-notifications' || window.__workpulseCurrentPage === 'notifications'){
    showPage(window.__workpulseCurrentPage);
  }
}

async function setNotificationReadState(notificationId, isRead, event){
  if(event) event.stopPropagation();
  await wpApi('/api/me/notifications/'+encodeURIComponent(notificationId)+'/read-state', {
    method:'PATCH',
    body: JSON.stringify({is_read: Boolean(isRead)}),
  });
  await wpReload();
  if(window.__workpulseCurrentPage === 'emp-notifications' || window.__workpulseCurrentPage === 'notifications'){
    showPage(window.__workpulseCurrentPage);
  }
}

function getNotificationTarget(notification){
  const type = String(notification?.type || '');
  const referenceType = String(notification?.referenceType || '');
  const isEmployee = DB.currentRole === 'employee';

  if(type === 'regulation_request_submitted' || (!isEmployee && referenceType === 'attendance_regulation')){
    return {page:'leave', tabGroup:'lv', tab:'regulations'};
  }

  if(type === 'leave_request_submitted' || (!isEmployee && referenceType === 'leave_request')){
    return {page:'leave', tabGroup:'lv', tab:'pending'};
  }

  if(type === 'regulation_review' || (isEmployee && referenceType === 'attendance_regulation')){
    return {page:'emp-attendance', tabGroup:'ema', tab:'reg'};
  }

  if(type === 'leave_review' || (isEmployee && referenceType === 'leave_request')){
    return {page:'emp-leaves'};
  }

  if(referenceType === 'announcement'){
    return {page:isEmployee ? 'emp-announcements' : 'announcements'};
  }

  if(referenceType === 'company_policy'){
    return {page:isEmployee ? 'emp-policies' : 'policies'};
  }

  if(referenceType === 'holiday' || referenceType === 'event'){
    return {page:isEmployee ? 'emp-calendar' : 'calendar'};
  }

  if(referenceType === 'employee'){
    return {page:'employees'};
  }

  return null;
}

function openNotificationTarget(notificationId){
  const notification = (DB.notifications || []).find(item => String(item.id) === String(notificationId));
  const target = getNotificationTarget(notification);
  if(!target){
    showToast('No linked page for this notification','amber');
    return;
  }

  window.__workpulseTabState = window.__workpulseTabState || {};
  if(target.tabGroup && target.tab){
    window.__workpulseTabState[target.tabGroup] = target.tab;
  }
  showPage(target.page);
}

function formatNotificationAudienceLabel(audience){
  if(!audience || audience === 'all') return 'All Employees';
  if(audience === 'specific') return 'Specific Employees';
  if(String(audience).startsWith('role:')){
    const role = String(audience).slice(5);
    return role.charAt(0).toUpperCase() + role.slice(1);
  }
  if(String(audience).startsWith('department:')){
    return `Team: ${String(audience).slice(11)}`;
  }
  return audience;
}

function deleteEmployee(id){
  const target = (DB.employees || []).find(employee => employee.id === id);
  if(target?.role === 'manager' && DB.currentRole !== 'manager'){
    showToast('Only a Super-Admin can delete Super-Admin accounts','red');
    return;
  }
  if(!confirm('Are you sure you want to remove this employee from the active directory? This will move the record to Ex-employee.')) return;
  wpApi('/api/employees/'+encodeURIComponent(id), {method:'DELETE'})
    .then(()=>wpReload())
    .then(()=>{ if(document.getElementById('page-title').textContent==='Employees') showPage('employees'); })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
  showToast('Employee moved to Ex-employee','amber');
}

function filterTable(inputId, tableId){
  const val=document.getElementById(inputId)?.value.toLowerCase()||'';
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row=>{
    row.style.display=row.textContent.toLowerCase().includes(val)?'':'none';
  });
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  TAB SWITCHER
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function switchTab(group, tab){
  window.__workpulseTabState = window.__workpulseTabState || {};
  window.__workpulseTabState[group] = tab;
  document.querySelectorAll(`[id^="${group}-tc-"]`).forEach(el=>el.classList.remove('active'));
  document.querySelectorAll(`[data-tab-group="${group}"]`).forEach(el=>el.classList.remove('active'));
  const tc=document.getElementById(`${group}-tc-${tab}`);
  if(tc) tc.classList.add('active');
  document.querySelectorAll(`[data-tab-group="${group}"][data-tab="${tab}"]`).forEach(el=>el.classList.add('active'));
  if(group === 'employees-directory' && typeof applyEmployeeDirectoryFilters === 'function'){
    applyEmployeeDirectoryFilters();
  }
}

function buildTabs(group, tabs, activeTab){
  window.__workpulseTabState = window.__workpulseTabState || {};
  const tabIds = tabs.map(t => t.id);
  const resolvedActiveTab = tabIds.includes(window.__workpulseTabState[group])
    ? window.__workpulseTabState[group]
    : (tabIds.includes(activeTab) ? activeTab : tabIds[0]);
  window.__workpulseTabState[group] = resolvedActiveTab;

  const tabHtml=tabs.map(t=>`<div class="tab${t.id===resolvedActiveTab?' active':''}" data-tab-group="${group}" data-tab="${t.id}" onclick="switchTab('${group}','${t.id}')">${t.label}</div>`).join('');
  const contentHtml=tabs.map(t=>`<div class="tab-content${t.id===resolvedActiveTab?' active':''}" id="${group}-tc-${t.id}">${t.content}</div>`).join('');
  return `<div class="tabs">${tabHtml}</div>${contentHtml}`;
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  â–ˆâ–ˆâ–ˆâ–ˆâ–ˆâ–ˆ ADMIN PAGES â–ˆâ–ˆâ–ˆâ–ˆâ–ˆâ–ˆ
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

function dashboardWeekDays(){
  const now = new Date();
  const monday = new Date(now);
  const day = (now.getDay()+6)%7; // Mon=0..Sun=6
  monday.setDate(now.getDate()-day);
  return Array.from({length:7}, (_,i)=>{
    const d = new Date(monday);
    d.setDate(monday.getDate()+i);
    return d;
  });
}

function dashboardAttendancePctForDate(dateObj){
  const date = dateObj.toISOString().slice(0,10);
  const recs = DB.attendance.filter(a=>a.date===date);
  if(!recs.length) return 0;
  const present = recs.filter(a=>a.status==='Present').length;
  return Math.round((present / recs.length) * 100);
}

function renderDepartmentAttendanceChart(){
  const chartEl = document.getElementById('dept-attendance-chart');
  if(!chartEl) return;

  const items = Array.isArray(DB.departments) ? DB.departments : [];
  const palette = ['#BDEFD9', '#6E83D9', '#4058E2', '#D6DBFF', '#A7B2F3', '#C9CCF4'];
  const labels = items.map(item => item.name);
  const series = items.map(item => {
    const present = Number(item.present || 0);
    const count = Number(item.count || 0);
    return present > 0 ? present : (count > 0 ? 1 : 0);
  });
  const colors = items.map((item, index) => palette[index % palette.length]);

  if(window.__workpulseDeptChart){
    try{ window.__workpulseDeptChart.destroy(); } catch(e){}
    window.__workpulseDeptChart = null;
  }

  if(!window.ApexCharts){
    chartEl.innerHTML = `<div class="dept-chart-fallback">Chart library unavailable</div>`;
    return;
  }

  const chart = new ApexCharts(chartEl, {
    chart: {
      type: 'donut',
      height: 220,
      width: '100%',
      toolbar: { show: false },
      animations: { enabled: true, speed: 450 },
      background: 'transparent'
    },
    series,
    labels,
    colors,
    legend: { show: false },
    dataLabels: { enabled: false },
    stroke: {
      width: 0
    },
    states: {
      hover: {
        filter: { type: 'lighten', value: 0.08 }
      },
      active: {
        allowMultipleDataPointsSelection: false,
        filter: { type: 'darken', value: 0.02 }
      }
    },
    plotOptions: {
      pie: {
        startAngle: -90,
        endAngle: 270,
        donut: {
          size: '64%',
          background: 'transparent',
          labels: {
            show: true,
            name: {
              show: true,
              offsetY: -4,
              color: '#8B95B1',
              fontSize: '11px',
              fontWeight: 700
            },
            value: {
              show: true,
              offsetY: 8,
              color: '#24304B',
              fontSize: '24px',
              fontWeight: 800,
              formatter: function(val){
                return String(Math.round(Number(val || 0))).padStart(2, '0');
              }
            },
            total: {
              show: true,
              showAlways: true,
              label: 'Teams',
              color: '#8B95B1',
              fontSize: '11px',
              fontWeight: 700,
              formatter: function(){
                return String(items.length);
              }
            }
          }
        }
      }
    },
    tooltip: {
      theme: 'dark',
      fillSeriesColor: false,
      y: {
        formatter: function(value, { seriesIndex }){
          const item = items[seriesIndex] || {};
          const present = Number(item.present || 0);
          const count = Number(item.count || 0);
          return `${present}/${count} marked`;
        }
      }
    },
    responsive: [{
      breakpoint: 760,
      options: {
        chart: { height: 190 }
      }
    }]
  });

  chart.render();
  window.__workpulseDeptChart = chart;
}

function dashboardRecentActivity(){
  const attEvents = DB.attendance
    .filter(a=>a.in||a.out||a.breakOut||a.breakIn)
    .map(a=>{
      const emp = DB.employees.find(e=>e.id===a.empId);
      const name = emp ? `${emp.fname} ${emp.lname}` : a.empId;
      const dept = emp?.dept || 'â€”';
      const label = a.out ? `${name} clocked out` : `${name} clocked in`;
      const time = a.out || a.in || 'â€”';
      return {label, meta:`${time} Â· ${dept}`, color:'var(--green)', sortKey:`${a.date} ${time}`};
    });

  const leaveEvents = DB.leaves
    .filter(l=>l.status==='Pending')
    .map(l=>({
      label:`Leave request: ${l.empName}`,
      meta:`${l.type} Â· Awaiting approval`,
      color:'var(--amber)',
      sortKey:`${l.applied||l.from} 00:00`
    }));

  const regEvents = DB.regulations
    .filter(r=>r.status==='Pending')
    .map(r=>({
      label:`Regulation request: ${r.empId}`,
      meta:`${r.type} Â· ${formatDate(r.date)}`,
      color:'var(--purple)',
      sortKey:`${r.date} 00:00`
    }));

  return [...attEvents, ...leaveEvents, ...regEvents]
    .sort((a,b)=>String(b.sortKey).localeCompare(String(a.sortKey)))
    .slice(0,6);
}

function dashboardRecentRows(){
  const today = new Date().toISOString().slice(0,10);
  const attendanceRows = (DB.attendance || [])
    .filter(a => a.in || a.out || a.breakOut || a.breakIn)
    .map(a => {
      const emp = (DB.employees || []).find(e => e.id === a.empId);
      if(!emp) return null;
      const status = a.in ? (a.out ? 'Clocked Out' : 'Clocked In') : 'Not Clocked In';
      return {
        date: a.date || today,
        empId: emp.id,
        name: `${emp.fname} ${emp.lname}`.trim(),
        role: emp.desg || '-',
        dept: emp.dept || '-',
        avatar: emp.avatar || initials(emp.fname, emp.lname),
        avatarColor: emp.avatarColor || 'var(--accent)',
        status,
        sortKey: `${a.date || today} ${a.out || a.in || ''}`
      };
    })
    .filter(Boolean);

  const leaveRows = (DB.leaves || [])
    .filter(l => l.status === 'Pending')
    .map(l => {
      const emp = (DB.employees || []).find(e => e.id === l.empId);
      const parts = String(l.empName || '').trim().split(/\s+/);
      return {
        date: l.applied || l.from || today,
        empId: l.empId || '-',
        name: l.empName || (emp ? `${emp.fname} ${emp.lname}` : 'Unknown'),
        role: emp?.desg || l.type || '-',
        dept: emp?.dept || l.dept || '-',
        avatar: emp?.avatar || initials(parts[0] || 'U', parts.slice(1).join(' ') || 'N'),
        avatarColor: emp?.avatarColor || 'var(--amber)',
        status: 'Pending',
        sortKey: `${l.applied || l.from || today} 00:00`
      };
    });

  return [...attendanceRows, ...leaveRows]
    .sort((a,b)=>String(b.sortKey).localeCompare(String(a.sortKey)))
    .slice(0,5);
}

function normalizeDashboardSearchText(value=''){
  return String(value || '')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();
}

function applyDashboardRecentSearchState(){
  const query = String(window.__dashboardRecentSearch || '');
  const input = document.getElementById('dashboard-recent-search-input');
  if(input && input.value !== query){
    input.value = query;
  }
  filterDashboardRecentRows(query);
}

function filterDashboardRecentRows(value=''){
  const query = normalizeDashboardSearchText(value);
  window.__dashboardRecentSearch = value || '';
  let visibleCount = 0;

  document.querySelectorAll('[data-dashboard-recent-row]').forEach(row => {
    const attrText = normalizeDashboardSearchText(row.getAttribute('data-search') || '');
    const rowText = normalizeDashboardSearchText(row.textContent || '');
    const matches = !query || attrText.includes(query) || rowText.includes(query);

    row.style.display = matches ? '' : 'none';
    if(matches) visibleCount += 1;
  });

  document.querySelectorAll('[data-dashboard-recent-empty-search]').forEach(emptyState => {
    emptyState.style.display = visibleCount === 0 ? '' : 'none';
  });
}
window.filterDashboardRecentRows = filterDashboardRecentRows;
window.applyDashboardRecentSearchState = applyDashboardRecentSearchState;

function dashboardUpcomingItems(){
  const today = new Date().toISOString().slice(0,10);
  const birthdays = getBirthdayEventFeed()
    .filter(item => item.date >= today)
    .map(item => ({
      title: item.label,
      sub: item.description || 'Birthday',
      date: item.date,
      badge: item.badge || 'bg-amber',
    }));
  const holidays = (DB.holidays||[])
    .filter(h=>h.date>=today)
    .map(h=>({title:h.name, sub:`${h.type} holiday`, date:h.date, badge:'bg-amber'}));
  const events = (Array.isArray(DB.events) ? DB.events : [])
    .map(event => ({
      title: event.title || 'Event',
      sub: event.desc || 'Company event',
      date: String(event.start || event.date || '').slice(0,10),
      badge: event.type === 'holiday' ? 'bg-amber' : event.type === 'meeting' ? 'bg-purple' : 'bg-blue',
    }))
    .filter(item => item.date && item.date >= today);
  const approvedLeaves = (DB.leaves||[])
    .filter(l=>l.status==='Approved' && l.from>=today)
    .map(l=>({title:`${l.empName} â€” ${l.type}`, sub:`Leave ${formatDate(l.from)} to ${formatDate(l.to)}`, date:l.from, badge:'bg-purple'}));
  return [...birthdays, ...holidays, ...events, ...approvedLeaves].sort((a,b)=>a.date.localeCompare(b.date)).slice(0,8);
}

function pageAdminDashboard(){
  try{
    const today=new Date().toISOString().split('T')[0];
    const currentUser = DB.currentUser || {};
    const attendance = Array.isArray(DB.attendance) ? DB.attendance : [];
    const leaves = Array.isArray(DB.leaves) ? DB.leaves : [];
    const employees = Array.isArray(DB.employees) ? DB.employees : [];
    const departments = Array.isArray(DB.departments) ? DB.departments : [];
    const todayAtt=attendance.filter(a=>a.date===today);
    const liveStatus = Array.isArray(DB.liveAttendance) ? DB.liveAttendance : [];
    const present=liveStatus.filter(l=>l.status==='in'||l.status==='break').length || todayAtt.filter(a=>a.status==='Present').length;
    const absent=liveStatus.filter(l=>l.status==='not_checked_in').length || todayAtt.filter(a=>a.status==='Absent').length;
    const onLeave=liveStatus.filter(l=>l.status==='leave').length || leaves.filter(l=>l.status==='Approved'&&l.from<=today&&l.to>=today).length;
    const pendingLeaves=leaves.filter(l=>l.status==='Pending').length;
    const newJoiners=employees.filter(e=>e.doj===today).length;
    const approvedTodayLeaves = leaves.filter(l=>l.status==='Approved'&&l.from<=today&&l.to>=today);
    const sickLeaveCount = approvedTodayLeaves.filter(l=>String(l.type||'').toLowerCase().includes('sick')).length;
    const otherLeaveCount = Math.max(0, approvedTodayLeaves.length - sickLeaveCount);
    const attendanceSegmentsTotal = Math.max(1, absent + present + otherLeaveCount + sickLeaveCount);
    const checkedInNow = liveStatus
      .filter(l=>l.status==='in'||l.status==='break')
      .slice(0,5);
    const notCheckedInNow = liveStatus
      .filter(l=>l.status==='not_checked_in')
      .slice(0,5);
    const deptPalette = ['#BDEFD9', '#6E83D9', '#4058E2', '#D6DBFF', '#A7B2F3', '#C9CCF4'];
    const deptAttendanceItems = departments.map((d, index)=>({
      name: d.name,
      color: deptPalette[index % deptPalette.length],
      totalEmployees: Number(d.count || 0),
      markedAttendance: Number(d.present || 0)
    }));
    const visibleDeptAttendanceItems = deptAttendanceItems.slice(0, 3);
    const pendingLeaveRequests = leaves
      .filter(l => l.status === 'Pending')
      .slice(0, 4)
      .map(l => {
        const emp = employees.find(e => String(e.id) === String(l.empId));
        const parts = String(l.empName || '').trim().split(/\s+/);
        return {
          id: l.id,
          name: l.empName || (emp ? `${emp.fname} ${emp.lname}`.trim() : 'Unknown Employee'),
          avatar: emp?.avatar || initials(parts[0] || 'U', parts.slice(1).join(' ') || 'N'),
          avatarColor: emp?.avatarColor || 'var(--purple)',
          range: `${formatDate(l.from)} - ${formatDate(l.to)}`,
          type: l.type || 'Leave'
        };
      });
    const recentRows = dashboardRecentRows();
    const upcoming = dashboardUpcomingItems().filter(item => !/ birthday$/i.test(String(item.title || '')));

    return `
  <div class="card dash-welcome-card" style="margin-bottom:14px;">
    <div class="dash-welcome-copy">
      <div class="dash-kicker">Operations Overview</div>
      <h2>Hello ${currentUser.fname || 'Team'},</h2>
      <p>Track attendance, approvals, leave coverage, and today’s team movement from one polished workspace.</p>
    </div>
    <div class="dash-welcome-pills">
      <span class="dash-pill soft">${present} present now</span>
      <span class="dash-pill">${pendingLeaves} approvals</span>
      <span class="dash-pill danger">${absent} absent</span>
    </div>
  </div>
    <div class="g4 dash-stat-grid" style="margin-bottom:14px;">
    <div class="stat-card"><div class="stat-label">Total Employees</div><div class="stat-val">${employees.length}</div><div class="stat-sub" style="color:var(--green);">+ ${newJoiners} new today</div></div>
    <div class="stat-card"><div class="stat-label">Present Today</div><div class="stat-val" style="color:var(--green);">${present}</div><div class="stat-sub">Checked in now</div></div>
    <div class="stat-card"><div class="stat-label">On Leave Today</div><div class="stat-val" style="color:var(--purple);">${onLeave}</div><div class="stat-sub">Approved leave</div></div>
    <div class="stat-card"><div class="stat-label">Pending Approvals</div><div class="stat-val" style="color:var(--amber);">${pendingLeaves}</div><div class="stat-sub" onclick="window.showPage('leave')" style="cursor:pointer;color:var(--accent);">View requests -></div></div>
  </div>

    <div class="g3 dashboard-trio-grid" style="margin-bottom:14px;">
      <div class="card">
        <div class="card-hdr"><div class="card-title">Attendance</div><span class="badge bg-blue">Live Today</span></div>
        <div class="attendance-mini-card">
          <div class="attendance-mini-bar">
            <span class="attendance-mini-fill absence" style="width:${(absent/attendanceSegmentsTotal)*100}%;"></span>
            <span class="attendance-mini-fill present" style="width:${(present/attendanceSegmentsTotal)*100}%;"></span>
            <span class="attendance-mini-fill leave" style="width:${(otherLeaveCount/attendanceSegmentsTotal)*100}%;"></span>
            <span class="attendance-mini-fill sick" style="width:${(sickLeaveCount/attendanceSegmentsTotal)*100}%;"></span>
          </div>
          <div class="attendance-mini-stats">
            <div class="attendance-mini-item">
              <div class="attendance-mini-meta"><span class="attendance-mini-dot absence"></span><span>Absence</span></div>
              <strong>${absent.toString().padStart(2,'0')}</strong>
            </div>
            <div class="attendance-mini-item">
              <div class="attendance-mini-meta"><span class="attendance-mini-dot present"></span><span>Present</span></div>
              <strong>${present.toString().padStart(2,'0')}</strong>
            </div>
            <div class="attendance-mini-item">
              <div class="attendance-mini-meta"><span class="attendance-mini-dot leave"></span><span>On leave</span></div>
              <strong>${otherLeaveCount.toString().padStart(2,'0')}</strong>
            </div>
            <div class="attendance-mini-item">
              <div class="attendance-mini-meta"><span class="attendance-mini-dot sick"></span><span>Sick leave</span></div>
              <strong>${sickLeaveCount.toString().padStart(2,'0')}</strong>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-hdr">
          <div class="card-title">Team Attendance</div>
          ${deptAttendanceItems.length > 3 ? `<button class="dashboard-card-link" type="button" onclick="window.showPage('realtime')">Show more</button>` : ''}
        </div>
        <div class="dept-summary-card">
          <div class="dept-donut-wrap">
            <div id="dept-attendance-chart" class="dept-chart-canvas" aria-label="Department attendance breakdown"></div>
          </div>
          <div class="dept-summary-list">
            ${visibleDeptAttendanceItems.map(item=>`
            <div class="dept-summary-item">
              <div class="dept-summary-meta">
                <span class="dept-summary-dot" style="background:${item.color};"></span>
                <div class="dept-summary-copy">
                  <span>${item.name} <strong>${item.markedAttendance}/${item.totalEmployees}</strong></span>
                  <small>${item.markedAttendance} of ${item.totalEmployees} marked</small>
                </div>
              </div>
            </div>`).join('')}
          </div>
        </div>
      </div>
      <div class="card dashboard-leave-card dashboard-leave-card-compact">
        <div class="card-hdr">
          <div class="card-title">Leave Requests</div>
          <button class="dashboard-filter-btn" type="button" onclick="window.showPage('leave')">View All</button>
        </div>
        <div class="dashboard-leave-requests compact">
          ${pendingLeaveRequests.map(item=>`
          <button class="dashboard-leave-request compact" type="button" onclick="window.showPage('leave')">
            <div class="dashboard-leave-request-user">
              <div class="av av-32" style="background:${item.avatarColor}22;color:${item.avatarColor};">${item.avatar}</div>
              <div class="dashboard-leave-request-copy">
                <strong>${item.name}</strong>
                <span>${item.range}</span>
              </div>
            </div>
            <span class="dashboard-leave-request-arrow">›</span>
          </button>`).join('') || `<div class="dashboard-leave-request-empty compact">No pending leave requests right now.</div>`}
        </div>
      </div>
    </div>

    <div class="g2">
      <div class="card">
        <div class="card-hdr dashboard-recent-head">
          <div class="card-title">Recent</div>
          <div class="dashboard-recent-tools">
            <label class="dashboard-recent-search">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="7" cy="7" r="4.8"></circle><path d="M10.8 10.8L14 14"></path></svg>
              <input type="text" id="dashboard-recent-search-input" placeholder="Search for employee" value="${String(window.__dashboardRecentSearch || '').replace(/"/g,'&quot;')}" oninput="window.filterDashboardRecentRows(this.value)">
            </label>
            <button class="dashboard-filter-btn" type="button" onclick="window.showPage('employees')">Filter</button>
          </div>
        </div>
        <div class="dashboard-recent-table">
          <div class="dashboard-recent-thead">
            <span>Date</span>
            <span>Name</span>
            <span>Role</span>
            <span>Status</span>
          </div>
          <div class="dashboard-recent-tbody">
            ${recentRows.map(row=>`
            <div class="dashboard-recent-row" data-dashboard-recent-row data-search="${[row.date,row.empId,row.name,row.role,row.dept,row.status].join(' ')}">
              <span>${formatDate(row.date)}</span>
              <span>
                <div class="dashboard-recent-user">
                  <div class="av av-28" style="background:${row.avatarColor};color:#fff;">${row.avatar}</div>
                  <strong>${row.name}</strong>
                </div>
              </span>
              <span>${row.role}</span>
              <span>${statusBadge(row.status)}</span>
            </div>`).join('')}
            ${recentRows.length ? `<div class="dashboard-recent-empty" data-dashboard-recent-empty-search style="display:none;">No matching employees found.</div>` : `<div class="dashboard-recent-empty">No recent activity available.</div>`}
          </div>
        </div>
      </div>
    <div style="display:grid;gap:14px;">
      <div class="card">
        <div class="card-hdr"><div class="card-title">Upcoming Events</div><button class="btn btn-sm" onclick="window.showPage('calendar')">View All</button></div>
        ${upcoming.map(ev=>`
        <div class="irow">
          <div><strong style="font-size:13px;">${ev.title}</strong><div style="font-size:11px;color:var(--muted);">${ev.sub}</div></div>
          <span class="badge ${ev.badge}">${formatDate(ev.date)}</span>
        </div>`).join('') || `<p style="color:var(--muted);">No upcoming events.</p>`}
      </div>
    </div>
  </div>

  <div class="g2" style="margin-top:14px;">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Checked In Live</div><button class="btn btn-sm" onclick="window.showPage('realtime')">Open Live Monitor</button></div>
      ${checkedInNow.map(emp=>`
      <div class="irow">
        <div>
          <strong style="font-size:13px;">${emp.name}</strong>
          <div style="font-size:11px;color:var(--muted);">${emp.dept || '-'} | ${emp.status==='break' ? 'On Break' : 'Checked In'}</div>
        </div>
        <span class="badge bg-green">${emp.clockIn || emp.since || '-'}</span>
      </div>`).join('') || `<p style="color:var(--muted);">No employees are checked in right now.</p>`}
    </div>
    <div class="card">
      <div class="card-hdr"><div class="card-title">Not Checked In Yet</div><button class="btn btn-sm" onclick="window.wpReload().then(() => window.showPage('dashboard'))">Refresh</button></div>
      ${notCheckedInNow.map(emp=>`
      <div class="irow">
        <div>
          <strong style="font-size:13px;">${emp.name}</strong>
          <div style="font-size:11px;color:var(--muted);">${emp.dept || '-'} | Waiting for check-in</div>
        </div>
        <span class="badge bg-red">Not Checked In</span>
      </div>`).join('') || `<p style="color:var(--muted);">Everyone has already checked in or is on leave.</p>`}
    </div>
  </div>`;
  }catch(e){
    console.error('pageAdminDashboard render error', e);
    return `<div class="card"><div class="card-title">Dashboard</div><p style="margin-top:8px;color:var(--muted);">Could not render dashboard. Please refresh.</p><p style="margin-top:6px;color:var(--red);font-size:12px;">${String(e?.message || e)}</p></div>`;
  }
}

function pageHrDashboard(){
  const today=new Date().toISOString().split('T')[0];
  const pendingLeaves=DB.leaves.filter(l=>l.status==='Pending').length;
  const onLeave=DB.leaves.filter(l=>l.status==='Approved'&&l.from<=today&&l.to>=today).length;
  const activeEmployees=DB.employees.filter(e=>e.status==='Active').length;
  const probationEmployees=DB.employees.filter(e=>e.status==='Probation').length;
  const thisMonthPrefix=today.slice(0,7);
  const newJoiners=DB.employees.filter(e=>(e.doj||'').startsWith(thisMonthPrefix)).length;
  const recentEmployees=DB.employees
    .slice()
    .sort((a,b)=>(b.doj||'').localeCompare(a.doj||''))
    .slice(0,5);
  const pendingRequests=DB.leaves
    .filter(l=>l.status==='Pending')
    .slice(0,5);

  return `
  <div class="alert al-info"><span>â„¹ï¸</span><div><strong>HR Focus:</strong> ${pendingLeaves} pending leave request(s), ${probationEmployees} employee(s) on probation, and ${newJoiners} new joiner(s) this month.</div></div>

  <div class="g4" style="margin-bottom:14px;">
    <div class="stat-card"><div class="stat-label">Active Employees</div><div class="stat-val">${activeEmployees}</div><div class="stat-sub" onclick="window.showPage('employees')" style="cursor:pointer;color:var(--accent);">Open directory</div></div>
    <div class="stat-card"><div class="stat-label">On Leave Today</div><div class="stat-val" style="color:var(--purple);">${onLeave}</div><div class="stat-sub">Approved leave</div></div>
    <div class="stat-card"><div class="stat-label">Pending Approvals</div><div class="stat-val" style="color:var(--amber);">${pendingLeaves}</div><div class="stat-sub" onclick="window.showPage('leave')" style="cursor:pointer;color:var(--accent);">Review requests</div></div>
    <div class="stat-card"><div class="stat-label">On Probation</div><div class="stat-val" style="color:var(--amber);">${probationEmployees}</div><div class="stat-sub">Follow-up due</div></div>
  </div>

  <div class="g2" style="margin-bottom:14px;">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Pending Leave Queue</div><button class="btn btn-sm" onclick="window.showPage('leave')">Open Leave</button></div>
      <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Team</th><th>Type</th><th>Dates</th><th>Status</th></tr></thead>
      <tbody>${pendingRequests.map(l=>`
        <tr>
          <td>${l.empName}</td>
          <td>${l.dept||'-'}</td>
          <td>${l.type}</td>
          <td>${formatDate(l.from)} - ${formatDate(l.to)}</td>
          <td>${statusBadge(l.status)}</td>
        </tr>`).join('') || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px;">No pending requests</td></tr>`}</tbody>
      </table></div>
    </div>
    <div class="card">
      <div class="card-hdr"><div class="card-title">Recent Joiners</div><button class="btn btn-sm" onclick="window.showPage('employees')">View Employees</button></div>
      ${recentEmployees.map(e=>`
        <div class="irow">
          <div>
            <strong style="font-size:13px;">${e.fname} ${e.lname}</strong>
            <div style="font-size:11px;color:var(--muted);">${e.dept||'-'} | ${e.desg||'-'}</div>
          </div>
          <span class="badge bg-blue">${formatDate(e.doj)}</span>
        </div>`).join('') || `<p style="color:var(--muted);">No employee records found.</p>`}
    </div>
  </div>

  <div class="g2">
    <div class="card">
      <div class="card-hdr"><div class="card-title">Team Snapshot</div><button class="btn btn-sm" onclick="window.showPage('departments')">View Teams</button></div>
      ${DB.departments.map(d=>`
      <div style="padding:7px 0;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
          <span>${d.name}</span>
          <strong>${d.count} employees</strong>
        </div>
        <div class="prog-bar"><div class="prog-fill" style="width:${d.count ? Math.round((d.present/d.count)*100) : 0}%;background:${d.color};"></div></div>
      </div>`).join('')}
    </div>
    <div class="card">
      <div class="card-hdr"><div class="card-title">Latest Announcements</div><button class="btn btn-sm" onclick="window.showPage('announcements')">Manage</button></div>
      ${DB.announcements.slice(0,4).map(a=>`
      <div style="padding:9px 0;border-bottom:1px solid var(--border);">
        <div style="font-weight:500;font-size:13px;">${a.title}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;">${formatDate(a.date)} | ${a.author}</div>
      </div>`).join('') || `<p style="color:var(--muted);">No announcements yet.</p>`}
    </div>
  </div>`;
}

function pageAttendance(){
  const ps=DB.punchState;
  const u=DB.currentUser;
  const today=getTodayLocalDate();
  const todayRec=getTodayAttendanceRecord()||{in:null,out:null,breakOut:null,breakIn:null,status:'Not Clocked In',late:false};
  const punchDisplay = getTodayPunchDisplay();
  const workedBreakdown = getTodayWorkedBreakdown();
  const shiftCompleted = isShiftCompletedForDate(today);

  const statusLabel = ps.punchedIn ? (ps.onBreak?'On Break':'In Office') : (shiftCompleted ? 'Shift Completed' : (todayRec.out?'Clocked Out':'Not Started'));
  const statusBadgeHtml = ps.punchedIn ? (ps.onBreak?`<span class="badge bg-amber">On Break</span>`:`<span class="badge bg-green">In Office</span>`) : (shiftCompleted ? `<span class="badge bg-gray">Shift Completed</span>` : `<span class="badge bg-gray">Not Clocked In</span>`);

  let punchButtons = '';
  if(!ps.punchedIn && !shiftCompleted){
    punchButtons = `<button class="punch-btn pb-in" onclick="punchIn()">Clock In</button>`;
  } else if(ps.punchedIn){
    punchButtons = `
      <button class="punch-btn pb-out" onclick="punchOut()">Clock Out</button>
      ${ps.onBreak
        ? `<button class="punch-btn pb-break-in" style="margin-top:6px;" onclick="breakIn()">Break Out â€” End Break</button>`
        : `<button class="punch-btn pb-break" style="margin-top:6px;" onclick="breakOut()">Break In</button>`
      }`;
  } else {
    punchButtons = `<button class="punch-btn" style="background:var(--surface2);color:var(--muted);" disabled>Shift Completed</button>`;
  }

  const logRows = DB.attendance.filter(a=>a.empId===u.id).map(a=>`
    <tr>
      <td>${formatDate(a.date)}</td>
      <td>${new Date(a.date+'T00:00:00').toLocaleDateString('en-GB',{weekday:'short'})}</td>
      <td>${a.in||'-'}</td>
      <td>${a.breakOut||'-'}</td>
      <td>${a.breakIn||'-'}</td>
      <td>${a.out||'-'}</td>
      <td>${calcWorkHours(a)}</td>
      <td>${a.overtime?'+'+a.overtime+'m':'â€”'}</td>
      <td>${statusBadge(a.late?'Late':a.status)}</td>
    </tr>`).join('');

  const regRows = DB.regulations.filter(r=>r.empId===u.id).map(r=>`
    <tr><td>${formatDate(r.date)}</td><td>${escapeHtml(r.type)}</td><td>${escapeHtml(r.orig)}</td><td>${escapeHtml(r.req)}</td><td>${escapeHtml(r.reason)}</td><td>${statusBadge(r.status)}</td>
    <td>${r.status==='Pending'?`<button class="btn btn-sm bg-red" onclick="cancelRegulation('${r.id}')">Cancel</button>`:'â€”'}</td></tr>`).join('');

  const logTimeline = ps.sessionLogs.map(l=>`
    <div class="tl-item"><div class="tl-dot" style="background:var(--accent);"></div><div class="tl-line"></div>
    <div><div style="font-size:13px;font-weight:500;">${l.event}</div><div style="font-size:11px;color:var(--muted);">${l.time}</div></div></div>`).join('')||
    '<div style="color:var(--muted);font-size:13px;">No activity logged yet today.</div>';

  return `
  <div class="admin-att-shell" style="margin-bottom:18px;">
    <div class="admin-att-hero">
      <div class="admin-att-hero-panel">
        <div class="admin-att-eyebrow">Live attendance</div>
        <div class="admin-att-time" id="cw-time-display">${new Date().toLocaleTimeString('en-GB')}</div>
        <div class="admin-att-date">${new Date().toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}</div>
        <div class="admin-att-status">${statusBadgeHtml}</div>
        <div class="admin-att-meta">
          <span class="admin-att-chip">Shift ${u.shiftStart||'11:00'} - ${u.shiftEnd||'20:00'}</span>
          <span class="admin-att-chip">Worked <span data-work-hours-live data-work-hours-format="standard">${getLiveWorkedTimeLabel()}</span></span>
        </div>
        <div class="admin-att-actions">${punchButtons}</div>
      </div>
    </div>
    <div class="admin-att-summary card">
      <div class="att-summary-top">
        <div>
          <div class="card-title">Today's Summary</div>
          <div class="att-summary-subtitle">Live attendance snapshot for today</div>
        </div>
        <span class="att-summary-pill">Attendance</span>
      </div>
      <div class="admin-att-summary-grid">
        <div class="admin-att-stat">
          <span class="admin-att-stat-label">Clock In</span>
          <strong class="admin-att-stat-value">${punchDisplay.clockIn || '-'}</strong>
        </div>
        <div class="admin-att-stat">
          <span class="admin-att-stat-label">Clock Out</span>
          <strong class="admin-att-stat-value">${ps.punchedIn ? '-' : (punchDisplay.clockOut || '-')}</strong>
        </div>
        <div class="admin-att-stat">
          <span class="admin-att-stat-label">Break Time</span>
          <strong class="admin-att-stat-value">${todayRec.breakOut&&todayRec.breakIn?'30 min':'-'}</strong>
        </div>
        <div class="admin-att-stat admin-att-stat-highlight">
          <span class="admin-att-stat-label">Working Hours Today</span>
          <strong class="admin-att-stat-value" data-work-hours-live data-work-hours-format="standard">${getLiveWorkedTimeLabel()}</strong>
        </div>
      </div>
      <div class="admin-att-details">
        <div class="att-summary-detail">
          <span class="att-summary-detail-label">Calculation</span>
          <strong class="att-summary-detail-value">${workedBreakdown.completedLabel} + ${workedBreakdown.currentSessionLabel} = ${workedBreakdown.totalLabel}</strong>
        </div>
        <div class="att-summary-detail">
          <span class="att-summary-detail-label">Status</span>
          <div class="att-summary-detail-badge">${ps.punchedIn?(todayRec.late?statusBadge('Late'):statusBadge('Present')):statusBadge(todayRec.status||'Not Clocked In')}</div>
        </div>
        <div class="att-summary-detail">
          <span class="att-summary-detail-label">Shift Policy</span>
          <strong class="att-summary-detail-value">11:00 - 20:00</strong>
        </div>
      </div>
    </div>
  </div>
  ${buildTabs('att',[
    {id:'log',label:'Daily Log',content:`
      ${buildAttendanceMonthLog('admin', u.id, 'Attendance Log', '<button class="btn btn-sm" onclick="window.openRegulationModal()">+ Regulation</button><button class="btn btn-sm btn-primary" onclick="window.exportAttendanceCSV()">Export CSV</button>')}`},
    {id:'today',label:"Today's Log",content:`
      <div class="card"><div class="card-title" style="margin-bottom:14px;">Session Activity</div>
      <div class="tl">${logTimeline}</div></div>`},
    {id:'regulation',label:'Regulation Requests',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">Regulation Requests</div><button class="btn btn-sm btn-primary" onclick="window.openRegulationModal()">New Request</button></div>
      <div class="table-wrap"><table><thead><tr><th>Date</th><th>Type</th><th>Original</th><th>Requested</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>${regRows||'<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No regulation requests</td></tr>'}</tbody></table></div></div>`},
    {id:'monthly',label:'Monthly',content:`
      <div class="g4" style="margin-bottom:14px;">
        <div class="stat-card"><div class="stat-label">Working Days</div><div class="stat-val">22</div></div>
        <div class="stat-card"><div class="stat-label">Present</div><div class="stat-val" style="color:var(--green);">19</div></div>
        <div class="stat-card"><div class="stat-label">On Leave</div><div class="stat-val" style="color:var(--purple);">2</div></div>
        <div class="stat-card"><div class="stat-label">Absent</div><div class="stat-val" style="color:var(--red);">1</div></div>
      </div>
      <div class="g2">
        <div class="stat-card"><div class="stat-label">Total Hours</div><div class="stat-val">162h 45m</div><div class="stat-sub">Avg 8h 33m/day</div></div>
        <div class="stat-card"><div class="stat-label">Overtime</div><div class="stat-val" style="color:var(--amber);">4h 20m</div></div>
      </div>`},
  ],'log')}`;
}

function cancelRegulation(id){
  wpApi('/api/attendance/regulations/'+encodeURIComponent(id), {method:'DELETE'})
    .then(()=>wpReload())
    .then(()=>{ showToast('Regulation request cancelled','amber'); showPage(DB.currentRole === 'employee' ? 'emp-attendance' : 'attendance'); })
    .catch(e=>showToast('Backend error: '+(e?.message||'Failed'),'red'));
}

function getRealtimeMonitorTeamOptions(selectedTeam=''){
  const teams = [
    ...((Array.isArray(DB.departments) ? DB.departments : []).map(department => department?.name)),
    ...((Array.isArray(DB.liveAttendance) ? DB.liveAttendance : []).map(entry => entry?.dept)),
  ]
    .filter(team => team && team !== '-');

  if(selectedTeam && !teams.includes(selectedTeam)){
    teams.push(selectedTeam);
  }

  return [...new Set(teams)].sort();
}

function pageRealtime(){
  const realtimeFilters = window.__realtimeMonitorFilters || {status:'', team:'', search:''};
  const inCount=DB.liveAttendance.filter(l=>l.status==='in').length;
  const breakCount=DB.liveAttendance.filter(l=>l.status==='break').length;
  const outCount=DB.liveAttendance.filter(l=>l.status==='out').length;
  const leaveCount=DB.liveAttendance.filter(l=>l.status==='leave').length;
  const teamOptions = getRealtimeMonitorTeamOptions(realtimeFilters.team);
  const cards=DB.liveAttendance.map(e=>{
    const dot={in:'md-in',break:'md-break',out:'md-out',leave:'md-leave'}[e.status]||'md-out';
    const lbl={in:'In since '+e.since,break:'On Break â€” '+e.since,out:'Clocked Out '+e.since,leave:'On Leave â€” '+e.since}[e.status];
    return`<div class="mon-card" data-status="${e.status || 'out'}" data-team="${e.dept || ''}"><div class="mon-dot ${dot}"></div>
      <div style="min-width:0;"><div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${e.name}</div>
      <div style="font-size:11px;color:var(--muted);">${e.dept} Â· ${lbl}</div></div></div>`;
  }).join('');

  return `
  <div class="g4" style="margin-bottom:14px;">
    <div class="stat-card"><div class="stat-label"><span class="live-dot" style="margin-right:4px;"></span>In Office</div><div class="stat-val" style="color:var(--green);">${inCount}</div></div>
    <div class="stat-card"><div class="stat-label">On Break</div><div class="stat-val" style="color:var(--amber);">${breakCount}</div></div>
    <div class="stat-card"><div class="stat-label">Clocked Out</div><div class="stat-val" style="color:var(--muted);">${outCount}</div></div>
    <div class="stat-card"><div class="stat-label">On Leave</div><div class="stat-val" style="color:var(--purple);">${leaveCount}</div></div>
  </div>
  <div class="card">
    <div class="card-hdr">
      <div class="card-title"><span class="live-dot" style="margin-right:6px;"></span>Live Employee Status</div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <select class="fi" id="rt-status-filter" onchange="filterMonitor()" style="width:180px;">
          <option value="" ${!realtimeFilters.status ? 'selected' : ''}>All Statuses</option>
          <option value="in" ${realtimeFilters.status === 'in' ? 'selected' : ''}>Clocked In</option>
          <option value="break" ${realtimeFilters.status === 'break' ? 'selected' : ''}>On Break</option>
          <option value="out" ${realtimeFilters.status === 'out' ? 'selected' : ''}>Clocked Out</option>
          <option value="leave" ${realtimeFilters.status === 'leave' ? 'selected' : ''}>On Leave</option>
        </select>
        <select class="fi" id="rt-team-filter" onchange="filterMonitor()" style="width:180px;">
          <option value="" ${!realtimeFilters.team ? 'selected' : ''}>All Teams</option>
          ${teamOptions.map(team => `<option value="${team}" ${realtimeFilters.team === team ? 'selected' : ''}>${team}</option>`).join('')}
        </select>
        <input class="search-input" id="rt-search" placeholder="Search..." value="${realtimeFilters.search || ''}" oninput="filterMonitor()" style="width:200px;">
      </div>
    </div>
    <div class="monitor-grid" id="monitor-grid">${cards}</div>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-in" style="flex-shrink:0;"></div>In Office</div>
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-break" style="flex-shrink:0;"></div>On Break</div>
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-out" style="flex-shrink:0;"></div>Clocked Out</div>
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-leave" style="flex-shrink:0;"></div>On Leave</div>
    </div>
  </div>`;
}

function filterMonitor(){
  const searchText = (document.getElementById('rt-search')?.value || '').toLowerCase();
  const statusFilter = document.getElementById('rt-status-filter')?.value || '';
  const teamFilter = document.getElementById('rt-team-filter')?.value || '';
  window.__realtimeMonitorFilters = {status: statusFilter, team: teamFilter, search: searchText};
  document.querySelectorAll('#monitor-grid .mon-card').forEach(card=>{
    const matchesText = card.textContent.toLowerCase().includes(searchText);
    const matchesStatus = !statusFilter || (card.dataset.status || '') === statusFilter;
    const matchesTeam = !teamFilter || (card.dataset.team || '') === teamFilter;
    card.style.display = matchesText && matchesStatus && matchesTeam ? '' : 'none';
  });
}

function pageLeave(){
  const pending=DB.leaves.filter(l=>l.status==='Pending');
  const all=DB.leaves;
  const employeesById = new Map((DB.employees || []).map(employee => [String(employee.id), employee]));
  const regulations = (Array.isArray(DB.regulations) ? DB.regulations : []).map(regulation => {
    const employee = employeesById.get(String(regulation.empId)) || {};
    return {
      ...regulation,
      empName: `${employee.fname || ''} ${employee.lname || ''}`.trim() || regulation.empId || '-',
      dept: employee.dept || '-',
    };
  });
  const pendingRegulations = regulations.filter(regulation => regulation.status === 'Pending');
  const onLeaveToday = getEmployeesOnLeaveToday();
  const leaveBalanceList = getLeaveBalancesList();
  const leaveBalances = leaveBalanceList.length
    ? leaveBalanceList.map(balance => [balance.name, balance.remaining, balance.allocated || balance.remaining || 1, 'var(--accent)'])
    : [['Annual Leave',18,21,'var(--accent)'],['Sick Leave',7,10,'var(--green)'],['Casual Leave',3,5,'var(--purple)'],['Paternity Leave',5,5,'var(--amber)'],['Maternity Leave',90,90,'var(--teal)'],['Marriage Leave',7,7,'var(--red)'],['Bereavement Leave',3,3,'var(--muted)']];

  const canManageLeaveBalances = isSuperAdminRole();
  const canManageLeaveTypes = isSuperAdminRole();
  const leavePolicies = getLeavePoliciesList();
  const leaveTypes = getLeaveTypesList();
  const employeeOptions = (DB.employees || [])
    .map(employee => `<option value="${employee.id}">${employee.id} - ${employee.fname} ${employee.lname}</option>`)
    .join('');

  const leaveBalanceActions = canManageLeaveBalances
    ? `<div style="display:flex;gap:8px;align-items:center;">
        <select class="fi" id="lv-balance-emp" style="min-width:220px;height:32px;padding:4px 8px;">
          ${employeeOptions}
        </select>
        <button class="btn btn-sm" onclick="window.openEditLeave(document.getElementById('lv-balance-emp')?.value)">Edit Balance</button>
      </div>`
    : `<button class="btn btn-sm btn-apply-leave" onclick="window.openModal('leaveModal')">Apply Leave</button>`;

  const pendingRows=pending.map(l=>`
    <tr>
      <td><div class="ucell"><div class="av av-28" style="background:var(--accent-bg);color:var(--accent);">${l.empName.split(' ').map(x=>x[0]).join('')}</div>
      <div class="ucell-info"><div class="n">${l.empName}</div><div class="s">${l.dept}</div></div></div></td>
      <td>${escapeHtml(l.type)}</td><td>${formatDate(l.from)}</td><td>${formatDate(l.to)}</td><td>${formatLeaveDuration(l)}</td><td>${escapeHtml(l.reason)}</td>
      <td>${statusBadge(l.hrStatus)}</td>
      <td><div style="display:flex;gap:4px;">
        <button class="btn btn-sm bg-green" onclick="openApproval('${l.id}')">Review</button>
      </div></td>
    </tr>`).join('');

  const allRows=all.map(l=>`
    <tr>
      <td>${l.empName}</td><td>${l.type}</td><td>${formatDate(l.from)}</td><td>${formatDate(l.to)}</td>
      <td>${formatLeaveDuration(l)}</td><td>${formatDate(l.applied)}</td><td>${statusBadge(l.hrStatus)}</td><td>${statusBadge(l.status)}</td>
    </tr>`).join('');

  const regulationRows=regulations.map(r=>`
    <tr>
      <td><div class="ucell"><div class="av av-28" style="background:var(--accent-bg);color:var(--accent);">${r.empName.split(' ').map(x=>x[0]).join('').slice(0,2) || 'ER'}</div>
      <div class="ucell-info"><div class="n">${r.empName}</div><div class="s">${r.empId} | ${r.dept}</div></div></div></td>
      <td>${formatDate(r.date)}</td>
      <td>${escapeHtml(r.orig || '-')}</td>
      <td>${escapeHtml(r.req || '-')}</td>
      <td>${escapeHtml(r.reason || '-')}</td>
      <td>${statusBadge(r.status)}</td>
      <td>${r.status === 'Pending' ? `<div style="display:flex;gap:4px;flex-wrap:wrap;">
        <button class="btn btn-sm bg-green" onclick="window.reviewRegulationRequest('${r.id}','Approved')">Approve</button>
        <button class="btn btn-sm bg-red" onclick="window.reviewRegulationRequest('${r.id}','Rejected')">Decline</button>
      </div>` : '-'}</td>
    </tr>`).join('');

  const onLeaveTodayHTML = `
    <div class="card">
      <div class="card-hdr">
        <div>
          <div class="card-title">Employees On Leave Today</div>
          <div style="font-size:12px;color:var(--muted);margin-top:4px;">Filter the company leave view by full company, team, or team manager.</div>
        </div>
        <div class="data-pill-row">
          <span class="data-pill">On leave today <strong id="lv-today-count">${onLeaveToday.length}</strong></span>
        </div>
      </div>
      <div class="toolbar-card" style="margin-bottom:14px;">
        <div class="toolbar-grid">
          <div>
            <label class="fl">Scope</label>
            <select class="fi" id="lv-today-scope" onchange="window.updateLeaveTodayFilters(); window.applyLeaveTodayFilters();">
              <option value="company">Full Company</option>
              <option value="department">Team</option>
              <option value="team">Team Manager</option>
            </select>
          </div>
          <div>
            <label class="fl">Filter</label>
            <select class="fi" id="lv-today-detail" onchange="window.applyLeaveTodayFilters();">
              <option value="">All Employees</option>
            </select>
          </div>
          <div>
            <label class="fl">Date</label>
            <div class="data-pill-row"><span class="data-pill">${formatDate(getTodayLocalDate())}</span></div>
          </div>
          <div style="display:flex;align-items:end;justify-content:flex-end;">
            <button class="btn btn-sm" onclick="window.updateLeaveTodayFilters(); window.applyLeaveTodayFilters();">Refresh View</button>
          </div>
        </div>
      </div>
      <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Team</th><th>Team Manager</th><th>Leave Type</th><th>From</th><th>To</th><th>Duration</th></tr></thead>
      <tbody id="lv-today-tbody">${renderOnLeaveTodayRows(onLeaveToday)}</tbody></table></div>
    </div>`;

  const holidayRows=DB.holidays.map(h=>`
    <tr><td>${formatDate(h.date)}</td><td>${new Date(h.date+'T00:00:00').toLocaleDateString('en-GB',{weekday:'long'})}</td>
    <td>${h.name}</td><td>${statusBadge(h.type)}</td><td>Nationwide</td><td>${canManageLeaveBalances ? `<button class="btn btn-sm btn-danger" onclick="window.deleteHoliday('${h.date}')">Delete</button>` : '-'}</td></tr>`).join('');

  const balanceHTML=`
    <div class="g2">
      <div class="card">
        <div class="card-hdr"><div class="card-title">Leave Balances â€” 2025</div>${leaveBalanceActions}</div>
        ${leaveBalances.map(([n,r,t,c]) => {
          const safeRemaining = Number.isFinite(Number(r)) ? Number(r) : 0;
          const safeTotal = Math.max(1, Number.isFinite(Number(t)) ? Number(t) : 1);
          const percent = Math.max(0, Math.min(100, Math.round((safeRemaining / safeTotal) * 100)));
          return `
        <div class="ltr">
          <div class="ltr-hdr"><span class="ltr-name">${n}</span><span class="ltr-cnt">${formatLeaveBalanceValue(safeRemaining)} / ${formatLeaveBalanceValue(safeTotal)} days remaining</span></div>
          <div class="prog-bar"><div class="prog-fill" style="width:${percent}%;background:${c};"></div></div>
        </div>`;
        }).join('')}
      </div>
      <div class="card">
        <div class="card-hdr">
          <div class="card-title">Leave Policy</div>
          ${canManageLeaveBalances ? '<button class="btn btn-sm" onclick="window.openEditLeavePolicy()">Edit Policy</button>' : ''}
        </div>
        ${leavePolicies.map(policy => `
          <div class="irow"><span class="ikey">${policy.name} Quota</span><span class="ival">${policy.quota_days} days/year</span></div>
        `).join('')}
        <div class="irow"><span class="ikey">Leave Year Basis</span><span class="ival">Janâ€“Dec (Annual)</span></div>
        <div class="irow"><span class="ikey">Pro-Rata Calculation</span><span class="ival"><span class="badge ${leavePolicies.some(policy => policy.pro_rata) ? 'bg-green' : 'bg-red'}">${leavePolicies.some(policy => policy.pro_rata) ? 'Enabled' : 'Disabled'}</span></span></div>
        <div class="irow"><span class="ikey">Carry Forward</span><span class="ival">Configured per leave type</span></div>
        <div class="irow"><span class="ikey">Approval Workflow</span><span class="ival">Employee -> HR</span></div>
      </div>
    </div>`;

  const leaveTypeRows = leaveTypes.map(type=>`
    <tr>
      <td>${type.name}</td>
      <td><code>${type.code}</code></td>
      <td>${type.paid ? statusBadge('Paid') : statusBadge('Unpaid')}</td>
      <td>
        ${canManageLeaveTypes ? `<div style="display:flex;gap:6px;"><button class="btn btn-sm" onclick="window.openEditLeaveType('${type.code}')">Edit</button><button class="btn btn-sm btn-danger" onclick="window.deleteLeaveType('${type.code}')">Delete</button></div>` : '-'}
      </td>
    </tr>`).join('');

  return buildTabs('lv',[
    {id:'all',label:'All Requests',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">All Leave Requests</div><button class="btn btn-sm btn-apply-leave" onclick="window.openModal('leaveModal')">Apply Leave</button></div>
      <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Duration</th><th>Applied</th><th>HR</th><th>Status</th></tr></thead>
      <tbody>${allRows}</tbody></table></div></div>`},
    {id:'today',label:`On Leave Today (${onLeaveToday.length})`,content:onLeaveTodayHTML},
    {id:'pending',label:`Pending Approvals (${pending.length})`,content:`
      <div class="card"><div class="card-hdr"><div class="card-title">Pending Leave Requests</div></div>
      <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Duration</th><th>Reason</th><th>HR</th><th>Action</th></tr></thead>
      <tbody>${pendingRows||'<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No pending requests</td></tr>'}</tbody></table></div></div>`},
    {id:'regulations',label:`Regulation Requests (${pendingRegulations.length})`,content:`
      <div class="card">
        <div class="card-hdr">
          <div>
            <div class="card-title">Attendance Regulation Requests</div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px;">Approve or decline employee corrections for missing or incorrect attendance times.</div>
          </div>
          <span class="data-pill">Pending <strong>${pendingRegulations.length}</strong></span>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Date</th><th>Current Time</th><th>Requested Time</th><th>Remarks</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>${regulationRows||'<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No regulation requests</td></tr>'}</tbody></table></div>
      </div>`},
    {id:'balance',label:'Leave Balances',content:balanceHTML},
    {id:'types',label:'Leave Types',content:`
      <div class="card">
        <div class="card-hdr">
          <div class="card-title">Leave Types</div>
          ${canManageLeaveTypes ? '<button class="btn btn-sm btn-primary" onclick="window.openCreateLeaveType()">+ Add Leave Type</button>' : ''}
        </div>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Code</th><th>Paid</th><th>Action</th></tr></thead>
        <tbody>${leaveTypeRows || '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px;">No leave types configured</td></tr>'}</tbody></table></div>
      </div>`},
    {id:'holidays',label:'National Holidays',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">National Holidays 2025</div><button class="btn btn-sm btn-primary" onclick="window.openModal('holidayModal')">+ Add Holiday</button></div>
      <div class="table-wrap"><table><thead><tr><th>Date</th><th>Day</th><th>Holiday</th><th>Type</th><th>Region</th><th>Action</th></tr></thead>
      <tbody>${holidayRows || '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px;">No holidays added yet</td></tr>'}</tbody></table></div></div>`},
  ],'pending');
}

function pageEmployees(){
  const employees = Array.isArray(DB.employees) ? DB.employees : [];
  const today = new Date().toISOString().slice(0, 10);
  const currentMonthValue = String(new Date().getMonth() + 1).padStart(2, '0');
  const birthdayMonthOptions = [
    { value: '', label: 'All Birthdays' },
    { value: '__current__', label: 'This Month' },
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
  ];
  const getEmployeeLifecycleStage = (employee) => {
    const status = String(employee?.status || '').toLowerCase();
    const lastWorkingDate = String(employee?.lwd || '');
    if(status === 'offboarding') return 'offboarding';
    if(status === 'inactive' || status === 'resigned') return 'ex';
    if(lastWorkingDate){
      return lastWorkingDate < today ? 'ex' : 'offboarding';
    }
    return 'current';
  };
  const currentEmployees = employees.filter(e=>getEmployeeLifecycleStage(e)==='current');
  const offboardingEmployees = employees.filter(e=>getEmployeeLifecycleStage(e)==='offboarding');
  const exEmployees = employees.filter(e=>getEmployeeLifecycleStage(e)==='ex');
  const activeCount = currentEmployees.filter(e=>e.status==='Active').length;
  const probationCount = currentEmployees.filter(e=>e.status==='Probation').length;
  const offboardingCount = offboardingEmployees.length;
  const exCount = exEmployees.length;
  const renderDirectoryRows = (items, emptyLabel, allowRemove = false) => items.map(e=>`
    <tr data-birth-month="${String(e.dob || '').slice(5, 7)}">
      <td>${e.id}</td>
      <td><div class="ucell"><div class="av av-32" style="background:${e.avatarColor}22;color:${e.avatarColor};">${e.avatar}</div>
      <div class="ucell-info"><div class="n">${e.fname} ${e.lname}</div><div class="s">${e.email}</div></div></div></td>
      <td>
        <div style="display:flex;flex-direction:column;gap:4px;">
          <span>${e.desg}</span>
          ${roleBadge(e.role)}
        </div>
      </td>
      <td>${e.dept}</td>
      <td>${e.dept==='Human Resources'?'HQ - People Ops':e.dept+' Hub'}</td>
      <td>${e.manager||'-'}</td>
      <td>${statusBadge(e.status)}</td>
      <td><div style="display:flex;gap:4px;">
        <button class="btn btn-sm" onclick="viewEmpProfile('${e.id}')">Open</button>
        ${canModifyEmployee(e) ? `<button class="btn btn-sm btn-ghost" onclick="window.openEditEmployee('${e.id}')">Edit</button>` : ''}
        ${allowRemove && canModifyEmployee(e) ? `<button class="btn btn-sm btn-danger" onclick="deleteEmployee('${e.id}')">Remove</button>` : ''}
      </div></td>
    </tr>`).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">${emptyLabel}</td></tr>`;

  return `
  <div class="hero-panel" style="margin-bottom:14px;">
    <div class="hero-title">Team Workspace</div>
    <div class="hero-sub">A cleaner employee directory inspired by modern HR suites: searchable records, stronger hierarchy context, team-linked employee codes, and quick access to profile details, reporting lines, and employment status.</div>
    <div class="hero-chip-row">
      <div class="hero-chip"><div class="k">Total Employees</div><div class="v">${DB.employees.length}</div></div>
      <div class="hero-chip"><div class="k">Active</div><div class="v">${activeCount}</div></div>
      <div class="hero-chip"><div class="k">Probation</div><div class="v">${probationCount}</div></div>
      <div class="hero-chip"><div class="k">Offboarding</div><div class="v">${offboardingCount}</div></div>
      <div class="hero-chip"><div class="k">Ex-employee</div><div class="v">${exCount}</div></div>
    </div>
  </div>

  <div class="directory-stats">
    <div class="directory-stat"><div class="label">Active Employees</div><div class="num">${activeCount}</div><div class="hint">Ready for payroll and attendance cycles</div></div>
    <div class="directory-stat"><div class="label">Offboarding</div><div class="num">${offboardingCount}</div><div class="hint">Employees serving notice or in exit process</div></div>
    <div class="directory-stat"><div class="label">Ex-employees</div><div class="num">${exCount}</div><div class="hint">Records after last working date or archive</div></div>
    <div class="directory-stat"><div class="label">Probation Reviews</div><div class="num">${probationCount}</div><div class="hint">Need follow-up from HR</div></div>
    <div class="directory-stat"><div class="label">Managers Listed</div><div class="num">${new Set(DB.employees.map(e=>e.manager).filter(Boolean)).size}</div><div class="hint">Reporting lines visible in profiles</div></div>
  </div>

  <div class="directory-card">
    <div class="directory-top">
      <div>
        <div class="panel-title">Employee Directory</div>
        <div style="font-size:12px;color:var(--muted);margin-top:3px;">PayPeople-style team listing with dedicated tabs for active employees, offboarding cases, and ex-employees after the exit date is completed.</div>
      </div>
      <div class="data-pill-row">
        <span class="data-pill">Current records <strong>${currentEmployees.length}</strong></span>
        <span class="data-pill">Offboarding <strong>${offboardingEmployees.length}</strong></span>
        <span class="data-pill">Ex-employee <strong>${exEmployees.length}</strong></span>
        <span class="data-pill">Team leads <strong>${DB.departments.filter(d=>d.head&&d.head!=='-').length}</strong></span>
      </div>
    </div>
    <div class="toolbar-card" style="margin-bottom:14px;">
      <div class="toolbar-grid">
        <div>
          <label class="fl">Search Employee</label>
          <input class="search-input" id="emp-search" placeholder="Search employee, code, title, team, manager..." oninput="applyEmployeeDirectoryFilters()" style="width:100%;">
        </div>
        <div class="toolbar-field-compact">
          <label class="fl">Team</label>
          <select class="fi" id="emp-team-filter" onchange="applyEmployeeDirectoryFilters()">
            <option value="">All Teams</option>
            ${DB.departments.map(d=>`<option>${d.name}</option>`).join('')}
          </select>
        </div>
        <div class="toolbar-field-compact">
          <label class="fl">Birthday Month</label>
          <select class="fi" id="emp-birthday-filter" onchange="applyEmployeeDirectoryFilters()">
            ${birthdayMonthOptions.map(option => `<option value="${option.value}">${option.label}${option.value === '__current__' ? ` (${birthdayMonthOptions.find(item => item.value === currentMonthValue)?.label || ''})` : ''}</option>`).join('')}
          </select>
        </div>
        <div class="toolbar-field-summary">
          <label class="fl">Directory View</label>
          <div class="data-pill-row">
            <span class="data-pill">Active <strong>${activeCount}</strong></span>
            <span class="data-pill">Probation <strong>${probationCount}</strong></span>
            <span class="data-pill">Offboarding <strong>${offboardingCount}</strong></span>
            <span class="data-pill">Ex-employee <strong>${exCount}</strong></span>
          </div>
        </div>
        <div class="toolbar-field-action" style="display:flex;align-items:end;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
          <button class="btn btn-sm" onclick="window.importEmployeeProfiles()">Import Employees</button>
          <button class="btn btn-sm btn-primary" onclick="window.openModal('addEmpModal')">+ Add Employee</button>
        </div>
      </div>
    </div>
    ${buildTabs('employees-directory',[
      {id:'current',label:`Employees (${currentEmployees.length})`,content:`
        <div class="soft-table"><div class="table-wrap"><table id="emp-table-current">
          <thead><tr><th>Employee Code</th><th>Employee</th><th>Job Title / Role</th><th>Team</th><th>Office Location</th><th>Line Manager</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>${renderDirectoryRows(currentEmployees, 'No active employees found.', true)}</tbody>
        </table></div></div>`},
      {id:'offboarding',label:`Offboarding (${offboardingEmployees.length})`,content:`
        <div class="soft-table"><div class="table-wrap"><table id="emp-table-offboarding">
          <thead><tr><th>Employee Code</th><th>Employee</th><th>Job Title / Role</th><th>Team</th><th>Office Location</th><th>Line Manager</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>${renderDirectoryRows(offboardingEmployees, 'No employees are currently in offboarding.')}</tbody>
        </table></div></div>`},
      {id:'ex',label:`Ex-employee (${exEmployees.length})`,content:`
        <div class="soft-table"><div class="table-wrap"><table id="emp-table-ex">
          <thead><tr><th>Employee Code</th><th>Employee</th><th>Job Title / Role</th><th>Team</th><th>Office Location</th><th>Line Manager</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>${renderDirectoryRows(exEmployees, 'No ex-employee records found.')}</tbody>
        </table></div></div>`},
    ], 'current')}
  </div>`;
}

function applyEmployeeDirectoryFilters(){
  const searchText = (document.getElementById('emp-search')?.value || '').trim().toLowerCase();
  const teamFilter = document.getElementById('emp-team-filter')?.value || '';
  const birthdayFilter = document.getElementById('emp-birthday-filter')?.value || '';
  const selectedBirthdayMonth = birthdayFilter === '__current__'
    ? String(new Date().getMonth() + 1).padStart(2, '0')
    : birthdayFilter;
  const activeTab = (window.__workpulseTabState && window.__workpulseTabState['employees-directory']) || 'current';
  const tableId = {
    current: 'emp-table-current',
    offboarding: 'emp-table-offboarding',
    ex: 'emp-table-ex',
  }[activeTab] || 'emp-table-current';

  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row=>{
    const cells = row.querySelectorAll('td');
    const searchableText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');
    const rowTeam = cells[3]?.textContent?.trim() || '';
    const rowBirthMonth = row.getAttribute('data-birth-month') || '';
    const matchesSearch = !searchText || searchableText.includes(searchText);
    const matchesTeam = !teamFilter || rowTeam === teamFilter;
    const matchesBirthdayMonth = !selectedBirthdayMonth || rowBirthMonth === selectedBirthdayMonth;
    row.style.display = matchesSearch && matchesTeam && matchesBirthdayMonth ? '' : 'none';
  });

  ['emp-table-current', 'emp-table-offboarding', 'emp-table-ex']
    .filter(id => id !== tableId)
    .forEach(otherTableId => {
      document.querySelectorAll(`#${otherTableId} tbody tr`).forEach(row=>{
        row.style.display = '';
      });
    });
}

function filterEmpDept(val){
  const teamSelect = document.getElementById('emp-team-filter');
  if(teamSelect) teamSelect.value = val || '';
  applyEmployeeDirectoryFilters();
}

function openDepartmentView(name=''){
  window.__workpulseSelectedDepartment = name || '';
  showPage('departments');
}

function formatUserRole(role){
  const value = String(role || 'employee').toLowerCase();
  const labels = {
    employee: 'Employee',
    manager: 'Super-Admin',
    super_admin: 'Super-Admin',
    admin: 'Admin',
  };
  return labels[value] || 'Employee';
}

function roleBadge(role){
  const value = String(role || 'employee').toLowerCase();
  const cls = {
    employee: 'bg-blue',
    manager: 'bg-amber',
    super_admin: 'bg-amber',
    admin: 'bg-red',
  }[value] || 'bg-blue';
  return `<span class="badge ${cls}">${formatUserRole(value)}</span>`;
}

function getRoleCounts(){
  const employees = Array.isArray(DB.employees) ? DB.employees : [];
  return {
    employee: employees.filter(e => String(e.role || 'employee').toLowerCase() === 'employee').length,
    manager: employees.filter(e => ['manager','super_admin'].includes(String(e.role || 'employee').toLowerCase())).length,
    admin: employees.filter(e => String(e.role || 'employee').toLowerCase() === 'admin').length,
  };
}

function pageRoles(){
  const counts = getRoleCounts();
  const employees = (Array.isArray(DB.employees) ? DB.employees : []).slice().sort((a,b)=>{
    const roleOrder = {admin: 1, manager: 2, super_admin: 2, employee: 3, hr: 4};
    const roleDiff = (roleOrder[String(a.role || 'employee').toLowerCase()] || 99) - (roleOrder[String(b.role || 'employee').toLowerCase()] || 99);
    if(roleDiff !== 0) return roleDiff;
    return `${a.fname || ''} ${a.lname || ''}`.localeCompare(`${b.fname || ''} ${b.lname || ''}`);
  });

  const rows = employees.map(e => `
    <tr>
      <td><div class="ucell"><div class="av av-32" style="background:${e.avatarColor}22;color:${e.avatarColor};">${e.avatar}</div>
        <div class="ucell-info"><div class="n">${e.fname} ${e.lname}</div><div class="s">${e.email}</div></div></div>
      </td>
      <td>${e.id}</td>
      <td>${roleBadge(e.role)}</td>
      <td>${e.desg || '-'}</td>
      <td>${e.dept || '-'}</td>
      <td>${e.manager || '-'}</td>
      <td>${statusBadge(e.status)}</td>
      <td><div style="display:flex;gap:4px;flex-wrap:wrap;">
        ${!(e.role === 'manager' && DB.currentRole !== 'manager') ? `<button class="btn btn-sm" onclick="window.openEditEmployee('${e.id}')">Edit Role</button>` : ''}
        <button class="btn btn-sm btn-ghost" onclick="window.viewEmpProfile('${e.id}')">Profile</button>
      </div></td>
    </tr>
  `).join('');

  return `
  <div class="hero-panel" style="margin-bottom:14px;">
    <div class="hero-title">Roles & Permissions</div>
    <div class="hero-sub">Manage who is an Admin, Super-Admin, or Employee from one place. Use this page to create higher-access accounts quickly and update role ownership without hunting through the full employee directory.</div>
    <div class="hero-chip-row">
      <div class="hero-chip"><div class="k">Admins</div><div class="v">${counts.admin}</div></div>
      <div class="hero-chip"><div class="k">Super-Admins</div><div class="v">${counts.manager}</div></div>
      <div class="hero-chip"><div class="k">Employees</div><div class="v">${counts.employee}</div></div>
    </div>
  </div>

  <div class="directory-stats">
    <div class="directory-stat"><div class="label">Admin Access</div><div class="num">${counts.admin}</div><div class="hint">Full company control and elevated settings</div></div>
    <div class="directory-stat"><div class="label">Super-Admin Seats</div><div class="num">${counts.manager}</div><div class="hint">Highest recovery and operational access</div></div>
    <div class="directory-stat"><div class="label">Total Assigned</div><div class="num">${employees.length}</div><div class="hint">Employees with visible role assignments</div></div>
  </div>

  <div class="card" style="margin-bottom:14px;">
    <div class="card-hdr">
      <div class="card-title">Quick Role Actions</div>
      <div style="font-size:12px;color:var(--muted);">Create a new elevated account or jump into an existing profile to update access.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      ${DB.currentRole === 'manager' ? `<button class="btn btn-primary" onclick="window.openAddEmployeeWithRole && window.openAddEmployeeWithRole('manager')">+ New Super-Admin</button>` : ''}
      <button class="btn" onclick="window.openAddEmployeeWithRole && window.openAddEmployeeWithRole('admin')">+ New Admin</button>
      <button class="btn btn-ghost" onclick="window.showPage('employees')">Open Employee Directory</button>
    </div>
  </div>

  <div class="directory-card">
    <div class="directory-top">
      <div>
        <div class="panel-title">Role Directory</div>
        <div style="font-size:12px;color:var(--muted);margin-top:3px;">Every employee is listed with their current role, team, reporting manager, and direct edit action.</div>
      </div>
      <div class="data-pill-row">
        <span class="data-pill">Highest access <strong>${counts.admin}</strong></span>
        <span class="data-pill">Approvers <strong>${counts.manager + counts.admin}</strong></span>
      </div>
    </div>
    <div class="toolbar-card" style="margin-bottom:14px;">
      <div class="toolbar-grid">
        <div>
          <label class="fl">Search Role Directory</label>
          <input class="search-input" id="role-search" placeholder="Search name, email, role, team..." oninput="filterTable('role-search','role-table')" style="width:100%;">
        </div>
        <div>
          <label class="fl">Open Creation Flow</label>
          <div class="data-pill-row">
            <span class="data-pill">Super-Admin <strong>${counts.manager}</strong></span>
            <span class="data-pill">Admin <strong>${counts.admin}</strong></span>
          </div>
        </div>
        <div>
          <label class="fl">Need Team Leads?</label>
          <div style="font-size:12px;color:var(--muted);padding-top:10px;">Create Super-Admins here, then assign reporting managers in employee profiles.</div>
        </div>
        <div style="display:flex;align-items:end;justify-content:flex-end;">
          <button class="btn btn-sm btn-primary" onclick="window.openAddEmployeeWithRole && window.openAddEmployeeWithRole('employee')">+ Add Employee</button>
        </div>
      </div>
    </div>
    <div class="soft-table"><div class="table-wrap"><table id="role-table">
      <thead><tr><th>Employee</th><th>Employee Code</th><th>Current Role</th><th>Designation</th><th>Team</th><th>Line Manager</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>${rows || '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No employees found</td></tr>'}</tbody>
    </table></div></div>
  </div>`;
}

async function viewEmpProfile(id){
  window._viewEmpId=id;
  try{
    const data = await wpApi('/api/employees/'+encodeURIComponent(id), {method:'GET'});
    if(data?.employee){
      const existingIndex = (DB.employees || []).findIndex(emp => emp.id === id);
      if(existingIndex >= 0){
        DB.employees[existingIndex] = {...DB.employees[existingIndex], ...data.employee};
      }
    }
  }catch(e){}
  showPage('emp-profile-detail');
}

function pageEmpProfileDetail(){
  const e=DB.employees.find(emp=>emp.id===window._viewEmpId)||DB.employees[0];
  const canSeeConfidential = isSuperAdminRole();
  const gross=(e.basic||0)+(e.house||0)+(e.transport||0);
  const net=gross-(e.tax||0);

  return `
  <div class="profile-hero">
    <div class="av av-64" style="background:${e.avatarColor}33;color:${e.avatarColor};border:3px solid ${e.avatarColor}44;">${e.avatar}</div>
    <div style="flex:1;">
      <div class="ph-name">${e.fname} ${e.lname}</div>
      <div class="ph-role">${e.desg} Â· ${e.id}</div>
      <div class="ph-tags">${statusBadge(e.status)}<span class="badge bg-blue">${e.dept}</span></div>
    </div>
    <button class="btn btn-sm" style="color:#fff;border-color:rgba(255,255,255,.25);" onclick="window.showPage('employees')">â† Back</button>
  </div>
  ${buildTabs('epd',[
    {id:'personal',label:'Personal',content:`
      <div class="g2">
        <div class="card"><div class="card-title" style="margin-bottom:13px;">Personal Details</div>
          <div class="irow"><span class="ikey">Full Name</span><span class="ival">${e.fname} ${e.lname}</span></div>
          <div class="irow"><span class="ikey">Date of Birth</span><span class="ival">${formatDate(e.dob)}</span></div>
          <div class="irow"><span class="ikey">Gender</span><span class="ival">${e.gender||'â€”'}</span></div>
          <div class="irow"><span class="ikey">CNIC</span><span class="ival">${e.cnic||'â€”'}</span></div>
          <div class="irow"><span class="ikey">Personal Phone</span><span class="ival">${e.phone}</span></div>
          <div class="irow"><span class="ikey">Personal Email</span><span class="ival">${e.email}</span></div>
          <div class="irow"><span class="ikey">Address</span><span class="ival">${e.address||'â€”'}</span></div>
          <div class="irow"><span class="ikey">Blood Group</span><span class="ival">${e.blood||'â€”'}</span></div>
        </div>
        <div class="card"><div class="card-title" style="margin-bottom:13px;">Next of Kin</div>
          <div class="irow"><span class="ikey">Name</span><span class="ival">${e.kin||'â€”'}</span></div>
          <div class="irow"><span class="ikey">Relationship</span><span class="ival">${e.kinRel||'â€”'}</span></div>
          <div class="irow"><span class="ikey">Contact</span><span class="ival">${e.kinPhone||'â€”'}</span></div>
        </div>
      </div>`},
    {id:'job',label:'Job & HR',content:`
      <div class="g2">
        <div class="card"><div class="card-title" style="margin-bottom:13px;">Job Details</div>
          <div class="irow"><span class="ikey">Employee ID</span><span class="ival">${e.id}</span></div>
          <div class="irow"><span class="ikey">Date of Joining</span><span class="ival">${formatDate(e.doj)}</span></div>
          <div class="irow"><span class="ikey">Probation Date</span><span class="ival">${formatDate(e.dop)}</span></div>
          <div class="irow"><span class="ikey">Last Working Date</span><span class="ival">${formatDate(e.lwd)}</span></div>
          <div class="irow"><span class="ikey">Team</span><span class="ival">${e.dept}</span></div>
          <div class="irow"><span class="ikey">Designation</span><span class="ival">${e.desg}</span></div>
          <div class="irow"><span class="ikey">User Role</span><span class="ival">${roleBadge(e.role)}</span></div>
          <div class="irow"><span class="ikey">Employment Type</span><span class="ival">${e.type}</span></div>
          <div class="irow"><span class="ikey">Status</span><span class="ival">${statusBadge(e.status)}</span></div>
          <div class="irow"><span class="ikey">Reporting To</span><span class="ival">${e.manager}</span></div>
          <div class="irow"><span class="ikey">Official Email</span><span class="ival">${e.email}</span></div>
          <div class="irow"><span class="ikey">Office Phone</span><span class="ival">${e.phone}</span></div>
        </div>
        <div class="card"><div class="card-title" style="margin-bottom:13px;">HR Details</div>
          <div class="irow"><span class="ikey">Notice Period</span><span class="ival">1 Month</span></div>
          <div class="irow"><span class="ikey">Shift</span><span class="ival">11:00 â€“ 20:00</span></div>
          <div class="irow"><span class="ikey">Working Days</span><span class="ival">Mon â€“ Fri</span></div>
          <div class="irow"><span class="ikey">Weekly Hours</span><span class="ival">45 hrs</span></div>
          <div class="irow"><span class="ikey">Contract Type</span><span class="ival">${e.type}</span></div>
        </div>
      </div>`},
    {id:'salary',label:'Salary & Bank',content:`
      <div class="g2">
        <div class="card"><div class="card-hdr"><div class="card-title">Salary <span class="badge bg-red" style="margin-left:6px;">Confidential</span></div></div>
          <div class="irow"><span class="ikey">Basic Salary</span><span class="ival">${confidentialValue(`PKR ${(e.basic||0).toLocaleString()}`)}</span></div>
          <div class="irow"><span class="ikey">House Allowance</span><span class="ival">${confidentialValue(`PKR ${(e.house||0).toLocaleString()}`)}</span></div>
          <div class="irow"><span class="ikey">Transport</span><span class="ival">${confidentialValue(`PKR ${(e.transport||0).toLocaleString()}`)}</span></div>
          <div class="irow"><span class="ikey">Gross Salary</span><span class="ival" style="font-weight:700;">${confidentialValue(`PKR ${gross.toLocaleString()}`)}</span></div>
          <div class="irow"><span class="ikey">Tax Deduction</span><span class="ival">${confidentialValue(`PKR ${(e.tax||0).toLocaleString()}`)}</span></div>
          <div class="irow"><span class="ikey">Net Salary</span><span class="ival" style="color:var(--green);font-weight:700;">${confidentialValue(`PKR ${net.toLocaleString()}`)}</span></div>
        </div>
        <div class="card"><div class="card-hdr"><div class="card-title">Bank Details <span class="badge bg-red" style="margin-left:6px;">Confidential</span></div></div>
          <div class="irow"><span class="ikey">Bank Name</span><span class="ival">${e.bank||'â€”'}</span></div>
          <div class="irow"><span class="ikey">Account No</span><span class="ival">${e.acct||'â€”'}</span></div>
          <div class="irow"><span class="ikey">IBAN</span><span class="ival">${e.iban||'â€”'}</span></div>
          <div class="irow"><span class="ikey">Payment Method</span><span class="ival">${confidentialValue('Bank Transfer')}</span></div>
        </div>
      </div>`},
    {id:'docs',label:'Documents',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">Documents</div><button class="btn btn-sm btn-primary">Upload</button></div>
        <div class="table-wrap"><table><thead><tr><th>Document</th><th>Type</th><th>Uploaded</th><th>Status</th></tr></thead><tbody>
          <tr><td>CNIC Copy</td><td>Identity</td><td>${formatDate(e.doj)}</td><td>${statusBadge('Approved')}</td></tr>
          <tr><td>Degree Certificate</td><td>Education</td><td>${formatDate(e.doj)}</td><td>${statusBadge('Approved')}</td></tr>
          <tr><td>Offer Letter</td><td>Employment</td><td>${formatDate(e.doj)}</td><td>${statusBadge('Approved')}</td></tr>
          <tr><td>Medical Certificate</td><td>Medical</td><td>â€”</td><td><span class="badge bg-amber">Pending</span></td></tr>
        </tbody></table></div>
      </div>`},
  ],'personal')}`;
}

function pageDepartments(){
  const selectedTeam = window.__workpulseSelectedDepartment || '';
  const focusedDepartment = (DB.departments || []).find(d => d.name === selectedTeam) || null;
  const focusedEmployees = focusedDepartment
    ? (DB.employees || []).filter(emp => emp.dept === focusedDepartment.name)
    : [];

  return `
  ${focusedDepartment ? `
  <div class="card" style="margin-bottom:14px;">
    <div class="card-hdr">
      <div>
        <div class="card-title">${focusedDepartment.name} Team View</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;">Focused team summary with current attendance and assigned employees.</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn btn-sm" onclick="window.__workpulseSelectedDepartment=''; window.showPage('departments')">All Teams</button>
        <button class="btn btn-sm btn-primary" onclick="window.showPage('employees'); setTimeout(() => window.filterEmpDept('${focusedDepartment.name.replace(/'/g,"\\'")}'), 0);">Open Employees</button>
      </div>
    </div>
    <div class="g4" style="margin-bottom:14px;">
      <div class="stat-card"><div class="stat-label">Head</div><div class="stat-val" style="font-size:22px;">${focusedDepartment.head || '-'}</div><div class="stat-sub">Current team lead</div></div>
      <div class="stat-card"><div class="stat-label">Employees</div><div class="stat-val">${focusedDepartment.count || 0}</div><div class="stat-sub">Assigned to this team</div></div>
      <div class="stat-card"><div class="stat-label">Present Today</div><div class="stat-val" style="color:var(--green);">${focusedDepartment.present || 0}</div><div class="stat-sub">Checked in today</div></div>
      <div class="stat-card"><div class="stat-label">On Leave</div><div class="stat-val" style="color:var(--purple);">${focusedDepartment.leave || 0}</div><div class="stat-sub">Approved leave today</div></div>
    </div>
    <div class="soft-table"><div class="table-wrap"><table>
      <thead><tr><th>Employee</th><th>Designation</th><th>Line Manager</th><th>Status</th></tr></thead>
      <tbody>
        ${focusedEmployees.map(emp => `
          <tr>
            <td><div class="ucell"><div class="av av-32" style="background:${emp.avatarColor}22;color:${emp.avatarColor};">${emp.avatar}</div><div class="ucell-info"><div class="n">${emp.fname} ${emp.lname}</div><div class="s">${emp.email}</div></div></div></td>
            <td>${emp.desg || '-'}</td>
            <td>${emp.manager || '-'}</td>
            <td>${statusBadge(emp.status)}</td>
          </tr>
        `).join('') || `<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px;">No employees found for this team.</td></tr>`}
      </tbody>
    </table></div></div>
  </div>` : ''}
  <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">
    <button class="btn btn-sm btn-primary" onclick="window.openCreateDepartment()">+ Add Team</button>
  </div>
  <div class="g3">
      ${DB.departments.map(d=>{
        const attendanceRate = d.count ? Math.round((d.present / d.count) * 100) : 0;
        return `
      <div class="dept-card" style="${focusedDepartment && focusedDepartment.name===d.name ? 'box-shadow:0 0 0 3px rgba(38,134,147,.12);border-color:rgba(38,134,147,.38);' : ''}">
        <div class="dc-bar" style="background:${d.color};"></div>
        <div class="dc-body">
          <div class="dc-head">
          <div><div class="dc-name">${d.name}</div><div class="dc-sub">Head: ${d.head}</div></div>
            <span class="badge bg-blue">${d.count} emp</span>
          </div>
          <div class="irow"><span class="ikey">Present Today</span><span class="ival" style="color:var(--green);">${d.present}</span></div>
          <div class="irow"><span class="ikey">On Leave</span><span class="ival" style="color:var(--purple);">${d.leave}</span></div>
          <div class="irow"><span class="ikey">Absent</span><span class="ival" style="color:var(--red);">${d.absent}</span></div>
          <div class="prog-bar dept-progress"><div class="prog-fill" style="width:${attendanceRate}%;background:${d.color};"></div></div>
          <div class="dc-rate">${attendanceRate}% attendance rate</div>
          <div class="dc-actions">
            <button class="btn btn-sm" style="flex:1;justify-content:center;" onclick="window.openDepartmentView('${d.name.replace(/'/g,"\\'")}')">View Team</button>
            <button class="btn btn-sm" onclick="window.openEditDepartment('${d.name.replace(/'/g,"\\'")}')">Edit</button>
            <button class="btn btn-sm btn-danger" onclick="window.deleteDepartment('${d.name.replace(/'/g,"\\'")}')">Delete</button>
          </div>
        </div>
      </div>`;}).join('')}
    </div>`;
  }

function pageOrgChart(){
  return `
  <div class="card">
    <div class="card-hdr"><div class="card-title">Team Structure</div><button class="btn btn-sm">Export PNG</button></div>
    <div class="org-wrap">
      <div class="org-tree">
        <div class="org-node root"><div class="oname">Zainab Hussain</div><div class="orole">CEO</div></div>
        <div class="org-vline" style="height:24px;"></div>
        <div style="position:relative;display:flex;gap:0;">
          <div style="position:absolute;top:0;left:15%;right:15%;height:1px;background:var(--border);"></div>
          <div style="display:flex;gap:18px;">
            ${DB.departments.map(d=>`
            <div class="org-branch">
              <div class="org-vline" style="height:24px;"></div>
              <div class="org-node" onclick="window.showPage('employees')" style="border-top:3px solid ${d.color};">
                <div class="oname" style="font-size:11px;">${d.head}</div>
                <div class="orole">${d.name} Team</div>
                <span class="badge bg-gray" style="margin-top:4px;font-size:9px;">${d.count} emp</span>
              </div>
            </div>`).join('')}
          </div>
        </div>
      </div>
    </div>
    <div class="alert al-info" style="margin-top:14px;"><span>Info</span><div>Click any node to view team employees. The chart supports up to 6 levels of hierarchy.</div></div>
  </div>`;
}

function getAudienceAnnouncementEvents(expandRange=false){
  return (Array.isArray(DB.announcements) ? DB.announcements : []).flatMap(item => {
    const from = String(item.effectiveFrom || item.date || '').trim();
    const to = String(item.effectiveTo || from).trim();
    const dates = from ? getDateRangeValues(from, to || from) : [];
    const eventDates = expandRange
      ? (dates.length ? dates : [String(item.date || '').trim()].filter(Boolean))
      : [from || String(item.date || '').trim()].filter(Boolean);

    return eventDates.map(date => ({
      date,
      label: item.title,
      description: expandRange || !to || to === from
        ? item.msg
        : `${item.msg}${item.msg ? ' | ' : ''}${formatDate(from)} - ${formatDate(to)}`,
      badge: item.cat === 'Event' ? 'bg-purple' : item.cat === 'Policy' ? 'bg-green' : item.cat === 'Important' ? 'bg-red' : 'bg-blue',
      type: 'announcement',
      effectiveFrom: from || null,
      effectiveTo: to || null,
    }));
  });
}

function getCalendarEventFeed(){
  const birthdayEvents = getBirthdayEventFeed();
  const announcementEvents = getAudienceAnnouncementEvents();
  const companyEvents = (Array.isArray(DB.events) ? DB.events : [])
    .map(event => ({
      date: String(event.start || event.date || '').slice(0, 10),
      label: event.title || 'Event',
      description: event.desc || '',
      badge: event.type === 'holiday' ? 'bg-amber' : event.type === 'meeting' ? 'bg-purple' : 'bg-blue',
      type: 'event',
    }))
    .filter(event => event.date);

  return birthdayEvents
    .concat(companyEvents)
    .concat(announcementEvents)
    .sort((a, b) => String(a.date).localeCompare(String(b.date)));
}

function getBirthdayEventFeed(){
  if(typeof isSuperAdminRole === 'function' && !isSuperAdminRole()){
    return [];
  }

  const employees = Array.isArray(DB.employees) ? DB.employees : [];
  const today = getTodayLocalDate();
  const currentYear = Number(String(today).slice(0, 4));

  return employees
    .filter(employee => /^\d{4}-\d{2}-\d{2}$/.test(String(employee?.dob || '')))
    .map(employee => {
      const dob = String(employee.dob);
      const monthDay = dob.slice(5);
      let eventDate = `${currentYear}-${monthDay}`;
      if(eventDate < today){
        eventDate = `${currentYear + 1}-${monthDay}`;
      }

      const fullName = `${employee.fname || ''} ${employee.lname || ''}`.trim() || employee.id || 'Employee';
      return {
        date: eventDate,
        label: `${fullName} Birthday`,
        description: `${employee.dept || 'Team'}${employee.desg ? ` | ${employee.desg}` : ''}`,
        badge: 'bg-amber',
        type: 'birthday',
        employeeId: employee.id,
      };
    })
    .sort((a, b) => String(a.date).localeCompare(String(b.date)));
}

function getCalendarEventLabelMeta(item){
  const rawType = String(item.type || '').toLowerCase();
  const rawLabel = String(item.label || '').toLowerCase();
  const rawDescription = String(item.description || '').toLowerCase();

  if(rawType === 'announcement'){
    if(rawLabel.includes('policy') || rawDescription.includes('policy')){
      return { tag:'Policy', icon:'✓', helper:'Policy update' };
    }
    if(rawLabel.includes('holiday') || rawDescription.includes('holiday') || rawLabel.includes('eid')){
      return { tag:'Holiday', icon:'🎉', helper:'Holiday notice' };
    }
    return { tag:'Announcement', icon:'📢', helper:'Announcement' };
  }

  if(rawType === 'event'){
    if(rawLabel.includes('meeting') || rawLabel.includes('town hall')){
      return { tag:'Event', icon:'📅', helper:'Company event' };
    }
    return { tag:'Reminder', icon:'⏰', helper:'Reminder' };
  }

  return { tag:'Update', icon:'•', helper:'Update' };
}

function formatEventCardDate(dateValue){
  if(!dateValue) return '—';
  const raw = String(dateValue).trim();
  if(!raw) return '—';
  const normalized = /^\d{4}-\d{2}-\d{2}$/.test(raw)
    ? `${raw}T00:00:00`
    : raw.replace(' ', 'T');
  const parsed = new Date(normalized);
  if(Number.isNaN(parsed.getTime())) return '—';
  return parsed.toLocaleDateString('en-GB', { day:'numeric', month:'short' });
}

function pageCalendar(empView=false){
  const today=new Date();
  const month=today.getMonth(), year=today.getFullYear();
  const firstDay=(new Date(year,month,1).getDay()+6)%7;
  const daysInMonth=new Date(year,month+1,0).getDate();
  const todayDate=today.getDate();
  const events = getCalendarEventFeed();
  const calendarRangeEvents = getAudienceAnnouncementEvents(true);
  const birthdayItems = !empView && typeof isSuperAdminRole === 'function' && isSuperAdminRole()
    ? getBirthdayEventFeed().slice(0, 6).map(item => {
        const employee = (Array.isArray(DB.employees) ? DB.employees : []).find(entry => String(entry.id) === String(item.employeeId)) || {};
        return {
          name: String(item.label || '').replace(/ Birthday$/, ''),
          avatar: employee.avatar,
          avatarColor: employee.avatarColor,
          detail: `${formatDate(item.date)}${item.description ? ` - ${item.description}` : ''}`,
          badge: '<span class="badge bg-amber">Birthday</span>',
        };
      })
    : [];

  let calDays='';
  for(let i=0;i<firstDay;i++) calDays+=`<div class="cal-day"></div>`;
  for(let d=1;d<=daysInMonth;d++){
    const dateStr=`${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const isToday=d===todayDate;
    const isHoliday=DB.holidays.some(h=>h.date===dateStr);
    const hasLeave=DB.leaves.some(l=>l.status==='Approved'&&l.from<=dateStr&&l.to>=dateStr);
    const hasEvent=events.some(ev=>ev.date===dateStr) || calendarRangeEvents.some(ev=>ev.date===dateStr);
    const cls=isToday?'cal-today':isHoliday?'cal-holiday':hasLeave?'cal-leave':hasEvent?'cal-event':'';
    const title=isHoliday
      ? DB.holidays.find(h=>h.date===dateStr).name
      : ([...events.filter(ev=>ev.date===dateStr), ...calendarRangeEvents.filter(ev=>ev.date===dateStr)].map(ev=>ev.label).join(' | ') || '');
    calDays+=`<div class="cal-day ${cls}" title="${title}">${d}</div>`;
  }

  const legacyEvents=[
    {date:'Today',label:'ðŸŽ‚ Sara Ahmed Birthday',badge:'bg-amber'},
    {date:'Apr 20â€“22',label:'ðŸ–ï¸ Eid-ul-Fitr Holiday',badge:'bg-amber'},
    {date:'Apr 22',label:'ðŸ“‹ Q2 Town Hall',badge:'bg-purple'},
    {date:'Apr 25',label:'âš™ï¸ Zara Khan Probation',badge:'bg-blue'},
    {date:'May 1',label:'ðŸŒ Labour Day',badge:'bg-green'},
  ];

  return `
  ${empView ? renderEmployeeWorkspaceTabs('emp-calendar') : ''}
  <div class="g2">
    <div class="card">
      <div class="card-hdr">
        <div class="card-title">${today.toLocaleDateString('en-GB',{month:'long',year:'numeric'})}</div>
        <div style="display:flex;gap:5px;"><button class="btn btn-sm">â€¹</button><button class="btn btn-sm">â€º</button></div>
      </div>
      <div class="cal-grid">
        ${['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(d=>`<div class="cal-dh">${d}</div>`).join('')}
        ${calDays}
      </div>
      <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:5px;font-size:11px;"><div style="width:9px;height:9px;border-radius:2px;background:var(--green-bg);border:1px solid var(--green);"></div>Leave</div>
        <div style="display:flex;align-items:center;gap:5px;font-size:11px;"><div style="width:9px;height:9px;border-radius:2px;background:var(--amber-bg);border:1px solid var(--amber);"></div>Holiday</div>
        <div style="display:flex;align-items:center;gap:5px;font-size:11px;"><div style="width:9px;height:9px;border-radius:2px;background:var(--purple-bg);border:1px solid var(--purple);"></div>Events & Announcements</div>
        <div style="display:flex;align-items:center;gap:5px;font-size:11px;"><div style="width:9px;height:9px;border-radius:2px;background:var(--accent);"></div>Today</div>
      </div>
    </div>
    <div class="card">
      <div class="card-hdr"><div class="card-title">Events & Reminders</div></div>
      ${events.slice(0,6).map(ev=>{
        const meta = getCalendarEventLabelMeta(ev);
        return `
      <div class="event-feed-item">
        <div class="event-feed-main">
          <div class="event-feed-tag"><span class="event-feed-icon">${meta.icon}</span><span>${meta.tag}</span></div>
          <strong class="event-feed-title">${ev.label}</strong>
          <div class="event-feed-desc">${ev.description || meta.helper}</div>
        </div>
        <span class="badge ${ev.badge}">${ev.effectiveFrom && ev.effectiveTo && ev.effectiveTo !== ev.effectiveFrom ? `${formatDate(ev.effectiveFrom)} - ${formatDate(ev.effectiveTo)}` : formatEventCardDate(ev.date)}</span>
      </div>`;
      }).join('') || `<div class="irow"><span style="font-size:13px;color:var(--muted);">No events or announcements yet.</span></div>`}
      <div style="margin-top:14px;">
        <div class="card-title" style="margin-bottom:10px;">National Holidays</div>
        ${DB.holidays.slice(0,5).map(h=>`
        <div class="irow"><span style="font-size:13px;">${h.name}</span><span class="badge bg-amber">${formatDate(h.date)}</span></div>`).join('')}
      </div>
      ${!empView && typeof isSuperAdminRole === 'function' && isSuperAdminRole() ? `
      <div style="margin-top:14px;">
        <div class="card-hdr" style="padding:0 0 10px 0;margin:0;"><div class="card-title">Upcoming Birthdays</div><button class="btn btn-sm" onclick="window.showPage('employees')">View Employees</button></div>
        ${renderEmployeeDashboardStatusList(birthdayItems, 'No upcoming birthdays right now')}
      </div>` : ''}
    </div>
  </div>`;
}

function pageReports(){
  const now = new Date();
  const ym = now.toISOString().slice(0,7);

  return buildTabs('rp',[
    {id:'att',label:'Attendance',content:`
      <div class="card">
        <div class="card-hdr"><div class="card-title">Attendance Report</div>
          <div style="display:flex;gap:6px;">
            <input type="month" class="fi" id="rp-att-month" style="width:140px;padding:6px 10px;" value="${ym}">
            <button class="btn btn-sm btn-primary" onclick="window.loadAttendanceReport()">Refresh</button>
            <button class="btn btn-sm" onclick="window.downloadAttendanceMonthlyCSV()">Export CSV</button>
          </div>
        </div>
        <div class="g4" style="margin-bottom:14px;">
          <div class="stat-card"><div class="stat-label">Present Days (Total)</div><div class="stat-val" id="rp-present">â€”</div></div>
          <div class="stat-card"><div class="stat-label">Late Days (Total)</div><div class="stat-val" id="rp-late" style="color:var(--amber);">â€”</div></div>
          <div class="stat-card"><div class="stat-label">Absent Days (Total)</div><div class="stat-val" id="rp-absent" style="color:var(--red);">â€”</div></div>
          <div class="stat-card"><div class="stat-label">Overtime (Minutes)</div><div class="stat-val" id="rp-ot" style="color:var(--green);">â€”</div></div>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Dept</th><th>Present</th><th>Absent</th><th>Leave</th><th>Late</th><th>Overtime (min)</th></tr></thead>
        <tbody id="rp-att-tbody"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">Loadingâ€¦</td></tr></tbody></table></div>
      </div>`},
    {id:'lv',label:'Leave',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">Leave Report</div><button class="btn btn-sm btn-primary" onclick="window.exportLeaveCSV()">Export</button></div>
        <div class="table-wrap"><table><thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
        <tbody>${(DB.leaves||[]).map(l=>`
          <tr><td>${l.empName}</td><td>${l.type}</td><td>${formatDate(l.from)}</td><td>${formatDate(l.to)}</td><td>${l.days}</td><td>${statusBadge(l.status)}</td></tr>`).join('')}</tbody></table></div>
      </div>`},
    {id:'monthly',label:'Monthly Summary',content:`
      <div class="g3" style="margin-bottom:14px;">
        <div class="stat-card"><div class="stat-label">Month</div><div class="stat-val" id="rp-m-month" style="font-size:18px;">â€”</div></div>
        <div class="stat-card"><div class="stat-label">Employees</div><div class="stat-val" id="rp-m-emps">â€”</div></div>
        <div class="stat-card"><div class="stat-label">Attendance %</div><div class="stat-val" id="rp-m-att">â€”</div></div>
      </div>
      <div class="card"><div class="card-hdr"><div class="card-title">By Team</div><button class="btn btn-sm btn-primary" onclick="window.loadMonthlySummary()">Refresh</button></div>
      <div class="table-wrap"><table><thead><tr><th>Team</th><th>Employees</th><th>Avg Present</th><th>Total Absent</th><th>Total Leave</th><th>OT Hours</th><th>Attendance %</th></tr></thead>
          <tbody id="rp-dept-tbody"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">Loadingâ€¦</td></tr></tbody></table></div>
      </div>`},
    {id:'empdata',label:'Employee Data',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">Employee Records</div>
        <div style="display:flex;gap:6px;">
          <button class="btn btn-sm btn-primary" onclick="window.exportEmployeeRecordsCSV()">Export CSV</button>
        </div>
      </div>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Dept</th><th>Designation</th><th>DOJ</th><th>Type</th><th>Status</th></tr></thead>
        <tbody id="rp-emp-tbody"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">Loadingâ€¦</td></tr></tbody></table></div>
      </div>`},
  ],'att');
}

async function loadAttendanceReport(){
  try{
    const v = document.getElementById('rp-att-month')?.value;
    if(!v) return;
    const parts = v.split('-');
    const year = parseInt(parts[0],10);
    const month = parseInt(parts[1],10);
    const data = await wpApi(`/api/reports/attendance/monthly?year=${year}&month=${month}`, {method:'GET', headers:{}});
    const rows = (data && data.rows) ? data.rows : [];
    const tb = document.getElementById('rp-att-tbody');
    if(!tb) return;

    tb.innerHTML = rows.map(r=>`
      <tr>
        <td>${r.name}</td>
        <td>${r.department||'â€”'}</td>
        <td>${r.present_days}</td>
        <td>${r.absent_days}</td>
        <td>${r.leave_days}</td>
        <td>${r.late_days}</td>
        <td>${r.overtime_minutes}</td>
      </tr>
    `).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No data</td></tr>`;

    const sum = rows.reduce((acc,r)=>({
      present: acc.present + (+r.present_days||0),
      absent: acc.absent + (+r.absent_days||0),
      late: acc.late + (+r.late_days||0),
      ot: acc.ot + (+r.overtime_minutes||0),
    }), {present:0,absent:0,late:0,ot:0});
    const set = (id,val)=>{ const el=document.getElementById(id); if(el) el.textContent = String(val); };
    set('rp-present', sum.present);
    set('rp-absent', sum.absent);
    set('rp-late', sum.late);
    set('rp-ot', sum.ot);
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

window.loadAttendanceReport = loadAttendanceReport;

async function loadMonthlySummary(){
  try{
    const filters = getMonthlyAttendanceFilters();
    if(!filters.employeeValid){
      showToast('Select a valid employee from search results.','red');
      return;
    }
    const fallbackMonth = new Date().toISOString().slice(0,7);
    if(!filters.query){
      const parts = fallbackMonth.split('-');
      filters.query = new URLSearchParams({year: parts[0], month: String(parseInt(parts[1], 10))}).toString();
    }

    const data = await wpApi(`/api/reports/attendance/monthly?${filters.query}`, {method:'GET', headers:{}});
    const rows = ((data && data.rows) ? data.rows : []).filter(row => !filters.selectedDept || (row.department || '') === filters.selectedDept);

    const deptMap = {};
    rows.forEach(r=>{
      const dept = r.department || '-';
      if(!deptMap[dept]) deptMap[dept] = {dept, employees:0, present:0, absent:0, leave:0, late:0, otMin:0};
      deptMap[dept].employees += 1;
      deptMap[dept].present += (+r.present_days||0);
      deptMap[dept].absent += (+r.absent_days||0);
      deptMap[dept].leave += (+r.leave_days||0);
      deptMap[dept].late += (+r.late_days||0);
      deptMap[dept].otMin += (+r.overtime_minutes||0);
    });
    const deptRows = Object.values(deptMap).sort((a,b)=>a.dept.localeCompare(b.dept));

    const tb = document.getElementById('rp-dept-tbody');
    if(tb){
      tb.innerHTML = deptRows.map(d=>{
        const totalDays = d.present + d.absent + d.leave;
        const attPct = totalDays ? Math.round((d.present / totalDays) * 100) : 0;
        const avgPresent = d.employees ? Math.round(d.present / d.employees) : 0;
        const otHours = Math.round((d.otMin||0) / 60);
        return `
          <tr>
            <td>${d.dept}</td>
            <td>${d.employees}</td>
            <td>${avgPresent}</td>
            <td>${d.absent}</td>
            <td>${d.leave}</td>
            <td>${otHours}h</td>
            <td>${attPct}%</td>
          </tr>
        `;
      }).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No data</td></tr>`;
    }

    const totalEmployees = rows.length;
    const totals = rows.reduce((acc,r)=>({
      present: acc.present + (+r.present_days||0),
      absent: acc.absent + (+r.absent_days||0),
      leave: acc.leave + (+r.leave_days||0),
    }), {present:0,absent:0,leave:0});
    const totalDays = totals.present + totals.absent + totals.leave;
    const attPct = totalDays ? Math.round((totals.present / totalDays) * 100) : 0;

    const set = (id,val)=>{ const el=document.getElementById(id); if(el) el.textContent = String(val); };
    set('rp-m-month', formatAttendanceRangeLabel(data?.range));
    set('rp-m-emps', totalEmployees);
    set('rp-m-att', attPct + '%');
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

window.loadMonthlySummary = loadMonthlySummary;

let __employeeRecordsCache = null;
async function loadEmployeeRecords(){
  const data = await wpApi('/api/reports/employees', {method:'GET', headers:{}});
  __employeeRecordsCache = (data && data.employees) ? data.employees : [];
  const tb = document.getElementById('rp-emp-tbody');
  if(!tb) return;
  tb.innerHTML = __employeeRecordsCache.map(r=>{
    return `<tr>
      <td>${r.employee_code||'-'}</td>
      <td>${r.name||'-'}</td>
      <td>${r.department||'-'}</td>
      <td>${r.designation||'-'}</td>
      <td>${formatDate(r.date_of_joining)}</td>
      <td>${r.employment_type||'-'}</td>
      <td>${statusBadge(r.status||'-')}</td>
    </tr>`;
  }).join('') || `<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No employees</td></tr>`;
}

async function exportEmployeeRecordsCSV(){
  try{
    window.location.href = '/api/reports/employees.csv';
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

window.loadEmployeeRecords = loadEmployeeRecords;
window.exportEmployeeRecordsCSV = exportEmployeeRecordsCSV;

function pageReports(){
  const now = new Date();
  const today = now.toISOString().slice(0,10);
  const ym = now.toISOString().slice(0,7);
  const monthRows = DB.attendance.filter(a=>(a.date||'').startsWith(ym));
  const uniqueEmployees = new Set(monthRows.map(a=>a.empId)).size || DB.employees.length;
  const presentCount = monthRows.filter(a=>a.status==='Present').length;
  const lateCount = monthRows.filter(a=>a.late).length;
  const approvedLeave = DB.leaves.filter(l=>l.status==='Approved').length;
  const totalOt = monthRows.reduce((sum,row)=>sum + (+row.overtime||0),0);
  const employeeDirectory = (DB.employees || [])
    .slice()
    .sort((a,b)=>(`${a.id||''} ${a.fname||''} ${a.lname||''}`).localeCompare(`${b.id||''} ${b.fname||''} ${b.lname||''}`))
    .map(employee => ({
      code: employee.id || '',
      name: `${(employee.fname||'').trim()} ${(employee.lname||'').trim()}`.trim(),
    }));
  const employeeOptions = employeeDirectory
    .map(employee => `<option value="${employee.code} - ${employee.name}"></option>`)
    .join('');

  return buildTabs('rp',[
    {id:'daily',label:'Daily Attendance Report',content:`
      <div class="hero-panel" style="margin-bottom:14px;">
        <div class="hero-title">Daily Attendance Report</div>
        <div class="hero-sub">Review each employee's attendance status for a selected day, including punches, worked minutes, overtime, and late arrivals.</div>
        <div class="hero-chip-row">
          <div class="hero-chip"><div class="k">Selected Date</div><div class="v" id="rp-daily-date-label" style="font-size:17px;">${today}</div></div>
          <div class="hero-chip"><div class="k">Present</div><div class="v" id="rp-daily-present">-</div></div>
          <div class="hero-chip"><div class="k">Leave</div><div class="v" id="rp-daily-leave">-</div></div>
          <div class="hero-chip"><div class="k">Absent</div><div class="v" id="rp-daily-absent">-</div></div>
        </div>
      </div>
      <div class="toolbar-card" style="margin-bottom:14px;">
        <div class="toolbar-grid">
          <div><label class="fl">Date</label><input type="date" class="fi" id="rp-daily-date" value="${today}"></div>
          <div><label class="fl">Team</label><select class="fi" id="rp-daily-dept"><option value="">All Teams</option>${DB.departments.map(d=>`<option value="${d.name}">${d.name}</option>`).join('')}</select></div>
          <div><label class="fl">Metrics</label><div class="data-pill-row"><span class="data-pill">Late <strong id="rp-daily-late">-</strong></span><span class="data-pill">OT mins <strong id="rp-daily-ot">-</strong></span></div></div>
          <div style="display:flex;align-items:end;justify-content:flex-end;gap:8px;"><button class="btn btn-sm btn-primary" onclick="window.loadAttendanceReport()">Refresh</button><button class="btn btn-sm" onclick="window.downloadAttendanceDailyCSV()">Export CSV</button></div>
        </div>
      </div>
      <div class="soft-table"><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Team</th><th>Designation</th><th>Status</th><th>Clock In</th><th>Break In</th><th>Break Out</th><th>Clock Out</th><th>Worked</th><th>OT</th><th>Late</th></tr></thead>
      <tbody id="rp-daily-tbody"><tr><td colspan="11" style="text-align:center;color:var(--muted);padding:20px;">Loading...</td></tr></tbody></table></div></div>`},
    {id:'monthly',label:'Monthly Attendance Report',content:`
      <div class="hero-panel" style="margin-bottom:14px;">
        <div class="hero-title">Monthly Attendance Report</div>
        <div class="hero-sub">Track attendance performance by month or a custom date range, then export the same filtered employee data whenever needed.</div>
        <div class="hero-chip-row">
          <div class="hero-chip"><div class="k">Tracked Range</div><div class="v" id="rp-att-range-label" style="font-size:17px;">${ym}</div></div>
          <div class="hero-chip"><div class="k">Employees Seen</div><div class="v" id="rp-att-employees-seen">${uniqueEmployees}</div></div>
          <div class="hero-chip"><div class="k">Late Instances</div><div class="v" id="rp-att-late-instances">${lateCount}</div></div>
          <div class="hero-chip"><div class="k">OT Minutes</div><div class="v" id="rp-att-ot-minutes">${totalOt}</div></div>
        </div>
      </div>
      <div class="toolbar-card" style="margin-bottom:14px;">
        <div class="toolbar-grid">
          <div><label class="fl">Filter Type</label><select class="fi" id="rp-att-filter-mode" onchange="window.syncMonthlyAttendanceFilterMode()"><option value="month" selected>Single Month</option><option value="custom">Custom Date Range</option></select></div>
          <div id="rp-att-month-wrap"><label class="fl">Month</label><input type="month" class="fi" id="rp-att-month" value="${ym}"></div>
          <div id="rp-att-custom-range-row" style="display:none;grid-column:span 2;">
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
              <div><label class="fl">Start Date</label><input type="date" class="fi" id="rp-att-start-date" value="${today}"></div>
              <div><label class="fl">End Date</label><input type="date" class="fi" id="rp-att-end-date" value="${today}"></div>
            </div>
          </div>
          <div>
            <label class="fl">Employee</label>
            <input type="search" class="fi" id="rp-att-employee-search" list="rp-att-employee-list" placeholder="Search by employee code or name">
            <datalist id="rp-att-employee-list">
              <option value="">All Employees</option>
              ${employeeOptions}
            </datalist>
          </div>
          <div><label class="fl">Team</label><select class="fi" id="rp-monthly-dept"><option value="">All Teams</option>${DB.departments.map(d=>`<option value="${d.name}">${d.name}</option>`).join('')}</select></div>
          <div><label class="fl">Insight</label><div class="data-pill-row"><span class="data-pill">Present <strong>${presentCount}</strong></span><span class="data-pill">Leave <strong>${approvedLeave}</strong></span></div></div>
          <div style="display:flex;align-items:end;justify-content:flex-end;gap:8px;"><button class="btn btn-sm btn-primary" onclick="window.loadMonthlyAttendanceReport()">Refresh</button><button class="btn btn-sm" onclick="window.downloadAttendanceMonthlyCSV()">Export CSV</button></div>
        </div>
      </div>
      <div class="metric-strip" style="margin-bottom:14px;">
        <div class="metric-box"><div class="eyebrow">Present Days Total</div><div class="value" id="rp-present">-</div><div class="meta">Monthly attendance volume</div></div>
        <div class="metric-box"><div class="eyebrow">Late Days Total</div><div class="value" id="rp-late" style="color:var(--amber);">-</div><div class="meta">Policy follow-up signal</div></div>
        <div class="metric-box"><div class="eyebrow">Absent Days Total</div><div class="value" id="rp-absent" style="color:var(--red);">-</div><div class="meta">Unavailability pattern</div></div>
        <div class="metric-box"><div class="eyebrow">OT Minutes</div><div class="value" id="rp-ot" style="color:var(--green);">-</div><div class="meta">Extra time logged</div></div>
      </div>
      <div class="soft-table"><div class="table-wrap" id="rp-monthly-matrix-wrap"><table><thead id="rp-att-head"><tr><th>Employee</th><th>Team</th><th>Designation</th><th>Present</th><th>Absent</th><th>Leave</th><th>Late</th><th>Overtime (min)</th></tr></thead>
      <tbody id="rp-att-tbody"><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">Loading...</td></tr></tbody></table></div></div>
      <div style="font-size:12px;color:var(--muted);margin-top:10px;">Codes: <strong>P</strong> Present, <strong>LT</strong> Late, <strong>L</strong> Leave, <strong>A</strong> Absent.</div>`},
    {id:'lv',label:'Leave Overview',content:`
      <div class="split-panel">
        <div class="panel-card" style="margin:0;">
          <div class="panel-head"><div class="panel-title">Leave Requests Snapshot</div><button class="btn btn-sm btn-primary" onclick="window.exportLeaveCSV()">Export</button></div>
          <div class="mini-kpi-grid" style="margin-bottom:12px;">
            <div class="mini-kpi"><div class="label">Total Requests</div><div class="n">${DB.leaves.length}</div></div>
            <div class="mini-kpi"><div class="label">Approved</div><div class="n" style="color:var(--green);">${DB.leaves.filter(l=>l.status==='Approved').length}</div></div>
            <div class="mini-kpi"><div class="label">Pending</div><div class="n" style="color:var(--amber);">${DB.leaves.filter(l=>l.status==='Pending').length}</div></div>
          </div>
          <div class="soft-table"><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
          <tbody>${(DB.leaves||[]).map(l=>`<tr><td>${l.empName}</td><td>${l.type}</td><td>${formatDate(l.from)}</td><td>${formatDate(l.to)}</td><td>${l.days}</td><td>${statusBadge(l.status)}</td></tr>`).join('')}</tbody></table></div></div>
        </div>
        <div class="panel-card" style="margin:0;">
          <div class="panel-head"><div class="panel-title">Policy Notes</div><span class="badge bg-blue">HR</span></div>
          <div class="irow"><span class="ikey">Workflow</span><span class="ival">Employee ???????? HR</span></div>
          <div class="irow"><span class="ikey">Carry Forward</span><span class="ival">Up to 5 days</span></div>
          <div class="irow"><span class="ikey">Policy Year</span><span class="ival">Jan - Dec</span></div>
          <div class="irow"><span class="ikey">Live Approved Leaves</span><span class="ival">${approvedLeave}</span></div>
          <div class="irow"><span class="ikey">Review Queue</span><span class="ival">${DB.leaves.filter(l=>l.status==='Pending').length}</span></div>
        </div>
      </div>`},
    {id:'summary',label:'Monthly Summary',content:`
      <div class="metric-strip" style="margin-bottom:14px;">
        <div class="metric-box"><div class="eyebrow">Month</div><div class="value" id="rp-m-month" style="font-size:20px;">-</div><div class="meta">Current summary period</div></div>
        <div class="metric-box"><div class="eyebrow">Employees</div><div class="value" id="rp-m-emps">-</div><div class="meta">Included in summary</div></div>
        <div class="metric-box"><div class="eyebrow">Attendance %</div><div class="value" id="rp-m-att">-</div><div class="meta">Presence ratio</div></div>
        <div class="metric-box"><div class="eyebrow">Teams</div><div class="value">${DB.departments.length}</div><div class="meta">Organizational coverage</div></div>
      </div>
      <div class="panel-card" style="margin:0;">
        <div class="panel-head"><div class="panel-title">Team Summary</div><button class="btn btn-sm btn-primary" onclick="window.loadMonthlySummary()">Refresh</button></div>
        <div class="soft-table"><div class="table-wrap"><table><thead><tr><th>Team</th><th>Employees</th><th>Avg Present</th><th>Total Absent</th><th>Total Leave</th><th>OT Hours</th><th>Attendance %</th></tr></thead>
        <tbody id="rp-dept-tbody"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">Loading...</td></tr></tbody></table></div></div>
      </div>`},
    {id:'empdata',label:'Employee Data',content:`
      <div class="panel-card" style="margin:0;">
        <div class="panel-head"><div class="panel-title">Employee Records</div><button class="btn btn-sm btn-primary" onclick="window.exportEmployeeRecordsCSV()">Export CSV</button></div>
        <div class="data-pill-row" style="margin-bottom:12px;"><span class="data-pill">Directory size <strong>${DB.employees.length}</strong></span><span class="data-pill">Teams <strong>${DB.departments.length}</strong></span><span class="data-pill">Probation <strong>${DB.employees.filter(e=>e.status==='Probation').length}</strong></span></div>
        <div class="soft-table"><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Team</th><th>Designation</th><th>DOJ</th><th>Type</th><th>Status</th></tr></thead>
        <tbody id="rp-emp-tbody"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">Loading...</td></tr></tbody></table></div></div>
      </div>`},
  ],'daily');
}

async function loadAttendanceReport(){
  try{
    const date = document.getElementById('rp-daily-date')?.value;
    if(!date) return;

    const data = await wpApi(`/api/attendance/daily?date=${encodeURIComponent(date)}`, {method:'GET', headers:{}});
    const selectedDept = document.getElementById('rp-daily-dept')?.value || '';
    const rows = ((data && data.rows) ? data.rows : []).filter(row => !selectedDept || (row.department || '') === selectedDept);
    const tb = document.getElementById('rp-daily-tbody');
    if(!tb) return;

    tb.innerHTML = rows.map(r=>`
      <tr>
        <td>${r.name || '-'}</td>
        <td>${r.department || '-'}</td>
        <td>${r.designation || '-'}</td>
        <td>${statusBadge(r.status || '-')}</td>
        <td>${r.punches?.clock_in || '-'}</td>
        <td>${r.punches?.break_out || '-'}</td>
        <td>${r.punches?.break_in || '-'}</td>
        <td>${r.punches?.clock_out || '-'}</td>
        <td>${formatWorkedMinutesLabel(r.worked_minutes || 0, false)}</td>
        <td>${formatWorkedMinutesLabel(r.overtime_minutes || 0, false)}</td>
        <td>${r.late ? 'Yes' : 'No'}</td>
      </tr>
    `).join('') || `<tr><td colspan="11" style="text-align:center;color:var(--muted);padding:20px;">No data</td></tr>`;

    const summary = rows.reduce((acc, r) => {
      const status = r.status || 'Absent';
      if(status === 'Present') acc.present += 1;
      else if(status === 'Leave') acc.leave += 1;
      else acc.absent += 1;
      if(r.late) acc.late += 1;
      acc.ot += Number(r.overtime_minutes || 0);
      return acc;
    }, {present:0, leave:0, absent:0, late:0, ot:0});

    const set = (id,val)=>{ const el=document.getElementById(id); if(el) el.textContent = String(val); };
    set('rp-daily-date-label', date);
    set('rp-daily-present', summary.present);
    set('rp-daily-leave', summary.leave);
    set('rp-daily-absent', summary.absent);
    set('rp-daily-late', summary.late);
    set('rp-daily-ot', summary.ot);
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

window.loadAttendanceReport = loadAttendanceReport;

function downloadAttendanceDailyCSV(){
  const date = document.getElementById('rp-daily-date')?.value;
  if(!date) return;
  window.location.href = `/api/attendance/daily.csv?date=${encodeURIComponent(date)}`;
}

window.downloadAttendanceDailyCSV = downloadAttendanceDailyCSV;

function getMonthlyAttendanceFilters(){
  const mode = document.getElementById('rp-att-filter-mode')?.value || 'month';
  const employeeSearch = (document.getElementById('rp-att-employee-search')?.value || '').trim();
  const employeeCode = resolveMonthlyAttendanceEmployeeCode(employeeSearch);
  const selectedDept = document.getElementById('rp-monthly-dept')?.value || '';
  const monthValue = document.getElementById('rp-att-month')?.value || '';
  const startDate = document.getElementById('rp-att-start-date')?.value || '';
  const endDate = document.getElementById('rp-att-end-date')?.value || '';
  const params = new URLSearchParams();

  if(mode === 'custom'){
    if(startDate) params.set('start_date', startDate);
    if(endDate) params.set('end_date', endDate);
  }else if(monthValue){
    const parts = monthValue.split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    if(Number.isFinite(year)) params.set('year', String(year));
    if(Number.isFinite(month)) params.set('month', String(month));
  }

  if(employeeCode) params.set('employee_code', employeeCode);

  return {
    mode,
    employeeSearch,
    employeeCode,
    employeeValid: !employeeSearch || !!employeeCode,
    selectedDept,
    monthValue,
    startDate,
    endDate,
    query: params.toString(),
  };
}

function resolveMonthlyAttendanceEmployeeCode(employeeSearch){
  if(!employeeSearch) return '';

  const employees = Array.isArray(DB.employees) ? DB.employees : [];
  const normalizedSearch = employeeSearch.trim().toLowerCase();
  const exactCodeMatch = employees.find(employee => String(employee.id || '').toLowerCase() === normalizedSearch);
  if(exactCodeMatch) return exactCodeMatch.id || '';

  const exactLabelMatch = employees.find(employee => {
    const name = `${(employee.fname || '').trim()} ${(employee.lname || '').trim()}`.trim();
    return `${String(employee.id || '')} - ${name}`.trim().toLowerCase() === normalizedSearch;
  });
  if(exactLabelMatch) return exactLabelMatch.id || '';

  const exactNameMatches = employees.filter(employee => {
    const name = `${(employee.fname || '').trim()} ${(employee.lname || '').trim()}`.trim().toLowerCase();
    return name === normalizedSearch;
  });
  if(exactNameMatches.length === 1) return exactNameMatches[0].id || '';

  const partialMatches = employees.filter(employee => {
    const name = `${(employee.fname || '').trim()} ${(employee.lname || '').trim()}`.trim().toLowerCase();
    const code = String(employee.id || '').toLowerCase();
    return code.includes(normalizedSearch) || name.includes(normalizedSearch);
  });

  return partialMatches.length === 1 ? (partialMatches[0].id || '') : '';
}

function syncMonthlyAttendanceFilterMode(){
  const mode = document.getElementById('rp-att-filter-mode')?.value || 'month';
  const monthField = document.getElementById('rp-att-month-wrap');
  const customFields = document.getElementById('rp-att-custom-range-row');
  if(monthField) monthField.style.display = mode === 'custom' ? 'none' : '';
  if(customFields) customFields.style.display = mode === 'custom' ? '' : 'none';
}

window.syncMonthlyAttendanceFilterMode = syncMonthlyAttendanceFilterMode;

function formatAttendanceRangeLabel(range){
  if(!range || !range.start || !range.end) return '-';
  if(range.start === range.end) return range.start;
  return `${range.start} to ${range.end}`;
}

async function loadMonthlyAttendanceReport(){
  try{
    const filters = getMonthlyAttendanceFilters();
    if(!filters.employeeValid){
      showToast('Select a valid employee from search results.','red');
      return;
    }
    if((filters.mode === 'custom' && (!filters.startDate || !filters.endDate)) || (!filters.query)){
      showToast('Select a valid month or custom date range.','red');
      return;
    }

    const data = await wpApi(`/api/reports/attendance/monthly?${filters.query}`, {method:'GET', headers:{}});
    const selectedDept = filters.selectedDept;
    const rows = ((data && data.rows) ? data.rows : []).filter(row => !selectedDept || (row.department || '') === selectedDept);
    const dates = (data && data.dates) ? data.dates : [];
    const tb = document.getElementById('rp-att-tbody');
    const head = document.getElementById('rp-att-head');
    if(!tb || !head) return;

    head.innerHTML = `<tr>
      <th>Employee</th>
      <th>Team</th>
      <th>Designation</th>
      ${dates.map(date => `<th>${new Date(date+'T00:00:00').getDate()}</th>`).join('')}
      <th>Present</th>
      <th>Absent</th>
      <th>Leave</th>
      <th>Late</th>
      <th>Overtime (min)</th>
    </tr>`;

    tb.innerHTML = rows.map(r=>`
      <tr>
        <td>${r.name}</td>
        <td>${r.department||'-'}</td>
        <td>${r.designation||'-'}</td>
        ${(r.days || []).map(day => `<td title="${day.date} - ${day.status}" style="text-align:center;">${day.code}</td>`).join('')}
        <td>${r.present_days}</td>
        <td>${r.absent_days}</td>
        <td>${r.leave_days}</td>
        <td>${r.late_days}</td>
        <td>${r.overtime_minutes}</td>
      </tr>
    `).join('') || `<tr><td colspan="${dates.length + 8}" style="text-align:center;color:var(--muted);padding:20px;">No data</td></tr>`;

    const sum = rows.reduce((acc,r)=>({
      present: acc.present + (+r.present_days||0),
      absent: acc.absent + (+r.absent_days||0),
      late: acc.late + (+r.late_days||0),
      ot: acc.ot + (+r.overtime_minutes||0),
    }), {present:0,absent:0,late:0,ot:0});
    const set = (id,val)=>{ const el=document.getElementById(id); if(el) el.textContent = String(val); };
    set('rp-present', sum.present);
    set('rp-absent', sum.absent);
    set('rp-late', sum.late);
    set('rp-ot', sum.ot);
    set('rp-att-range-label', formatAttendanceRangeLabel(data?.range));
    set('rp-att-employees-seen', rows.length);
    set('rp-att-late-instances', sum.late);
    set('rp-att-ot-minutes', sum.ot);
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

window.loadMonthlyAttendanceReport = loadMonthlyAttendanceReport;

function downloadAttendanceMonthlyCSV(){
  const filters = getMonthlyAttendanceFilters();
  if(!filters.employeeValid){
    showToast('Select a valid employee from search results.','red');
    return;
  }
  if((filters.mode === 'custom' && (!filters.startDate || !filters.endDate)) || (!filters.query)){
    showToast('Select a valid month or custom date range.','red');
    return;
  }
  window.location.href = `/api/reports/attendance/monthly.csv?${filters.query}`;
}

window.downloadAttendanceMonthlyCSV = downloadAttendanceMonthlyCSV;

function formatAnnouncementDateLabel(announcement){
  const from = String(announcement?.effectiveFrom || announcement?.date || '').trim();
  const to = String(announcement?.effectiveTo || '').trim();
  if(!from) return formatDate(announcement?.publishedOn || announcement?.date || '');
  if(!to || to === from) return formatDate(from);
  return `${formatDate(from)} - ${formatDate(to)}`;
}

function pageAnnouncements(empView=false){
  const items=DB.announcements.map(a=>`
    <div class="ann" style="border-left-color:${a.cat==='Event'?'var(--purple)':a.cat==='Policy'?'var(--green)':a.cat==='Important'?'var(--red)':'var(--accent)'};">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div class="ann-title">${a.title}</div>
        <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;margin-left:8px;">
          <span class="badge bg-blue">${a.cat}</span>
          ${a.hasVote ? `<span class="badge ${a.voteStatus === 'closed' ? 'bg-gray' : 'bg-green'}">Vote ${a.voteStatus || 'open'}</span>` : ''}
          ${empView && a.hasVote ? `<button class="btn btn-sm btn-primary" onclick="window.openAnnouncementVote('${a.id}')">Open Vote</button>` : ''}
          ${!empView && isSuperAdminRole() && a.hasVote ? `<button class="btn btn-sm" onclick="window.openAnnouncementVoteResults('${a.id}')">Results (${a.voteCount || 0})</button>${a.voteStatus === 'open' ? `<button class="btn btn-sm" onclick="window.closeAnnouncementVote('${a.id}')">Close Vote</button>` : ''}<button class="btn btn-sm" onclick="window.exportAnnouncementVoteResults('${a.id}')">Export CSV</button>` : ''}
          ${!empView && isSuperAdminRole() ? `<button class="btn btn-sm" onclick="window.openAnnouncementModal('${a.id}')">Edit</button><button class="btn btn-sm" style="border-color:#f3c1c1;color:#b42318;background:#fff5f5;" onclick="window.deleteAnnouncement('${a.id}')">Delete</button>` : ''}
        </div>
      </div>
      <div style="font-size:13px;margin-top:6px;">${a.msg}</div>
      ${a.hasVote ? `<div style="margin-top:10px;padding:10px;border:1px solid var(--border);border-radius:8px;background:#fff;"><strong>${a.voteQuestion || 'Voting'}</strong><div style="margin-top:6px;color:var(--muted);font-size:12px;">${a.myVoteOptionId ? `Your vote: ${(a.voteOptions || []).find(option => String(option.id) === String(a.myVoteOptionId))?.label || 'Submitted'}` : (a.voteStatus === 'closed' ? 'Voting is closed' : 'Waiting for your vote')}</div>${Array.isArray(a.voteOptions) && a.voteOptions.some(option => option.count !== null) ? `<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">${a.voteOptions.map(option => `<span class="badge bg-blue">${option.label}: ${option.count}</span>`).join('')}</div>` : ''}</div>` : ''}
      ${Array.isArray(a.recipients) && a.recipients.length ? `<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">${a.recipients.map(recipient => `<span class="badge bg-blue">${recipient.name}</span>`).join('')}</div>` : ''}
      <div class="ann-meta">By ${a.author} (${a.role}) | ${formatAnnouncementDateLabel(a)} | Audience: ${a.audience}</div>
    </div>`).join('');

  return `
  ${!empView?`<div style="display:flex;justify-content:flex-end;margin-bottom:14px;"><button class="btn btn-sm btn-primary" onclick="window.openAnnouncementModal()">+ New Announcement</button></div>`:''}
  ${items||'<div class="card"><p style="color:var(--muted);">No announcements yet.</p></div>'}`;
}

function pageAnnouncementVote(){
  const announcement = getAnnouncementById(window.__workpulseVoteDetailAnnouncementId);
  if(!announcement || !announcement.hasVote){
    return `<div class="card"><div class="card-title">Vote not found</div><p style="color:var(--muted);margin-top:8px;">This voting announcement is not available.</p><button class="btn btn-sm" style="margin-top:12px;" onclick="window.showPage('emp-announcements')">Back</button></div>`;
  }

  const closed = announcement.voteStatus === 'closed';
  const selectedId = String(announcement.myVoteOptionId || '');
  const options = (announcement.voteOptions || []).map(option => `
    <label style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px;background:#fff;">
      <span style="display:flex;align-items:center;gap:8px;">
        <input type="radio" name="announcement-vote-option" value="${option.id}" ${String(option.id) === selectedId ? 'checked' : ''} ${closed ? 'disabled' : ''}>
        <span>${option.label}</span>
      </span>
      ${option.count !== null ? `<span class="badge bg-blue">${option.count}</span>` : ''}
    </label>
  `).join('');

  return `<div class="card">
    <div class="card-hdr"><div><div class="card-title">${announcement.title}</div><div style="font-size:12px;color:var(--muted);margin-top:4px;">${closed ? 'Voting closed' : (selectedId ? 'You can update your vote until voting is closed' : 'Choose one option')}</div></div><button class="btn btn-sm" onclick="window.showPage('emp-announcements')">Back</button></div>
    <div style="margin-top:14px;font-size:15px;font-weight:700;">${announcement.voteQuestion || 'Voting'}</div>
    <div style="margin-top:12px;">${options}</div>
    ${closed ? `<div class="alert al-info" style="margin-top:12px;"><span>i</span><div>This vote is closed.</div></div>` : `<button class="btn btn-primary" onclick="window.submitAnnouncementVote('${announcement.id}')">${selectedId ? 'Update Vote' : 'Submit Vote'}</button>`}
  </div>`;
}

function pageAnnouncementVoteResults(){
  const announcementId = window.__workpulseVoteResultsAnnouncementId;
  const announcement = getAnnouncementById(announcementId);
  const results = window.__workpulseVoteResults;
  if(!announcementId || !announcement){
    return `<div class="card"><div class="card-title">Results not found</div><button class="btn btn-sm" style="margin-top:12px;" onclick="window.showPage('announcements')">Back</button></div>`;
  }
  if(!results){
    return `<div class="card"><div class="card-hdr"><div class="card-title">Vote Results</div><button class="btn btn-sm" onclick="window.showPage('announcements')">Back</button></div><p style="color:var(--muted);margin-top:12px;">Loading results...</p></div>`;
  }

  const responses = Array.isArray(results.responses) ? results.responses : [];
  const options = Array.isArray(results.options) ? results.options : [];
  const noResponseCount = responses.filter(response => !response.optionId).length;
  const selectedFilter = window.__workpulseVoteResultsFilter || 'all';
  const filteredResponses = selectedFilter === 'all'
    ? responses
    : selectedFilter === 'no-response'
      ? responses.filter(response => !response.optionId)
      : responses.filter(response => String(response.optionId) === String(selectedFilter));
  const selectedOption = options.find(option => String(option.id) === String(selectedFilter));
  const filterLabel = selectedFilter === 'all'
    ? 'All responses'
    : selectedFilter === 'no-response'
      ? 'No Response'
      : (selectedOption?.label || 'Selected option');
  const optionCards = options.map(option => `<div class="stat-card"><div class="stat-label">${option.label}</div><div class="stat-value">${option.count}</div></div>`).join('');
  const filterOptions = [`<option value="all"${selectedFilter === 'all' ? ' selected' : ''}>All responses (${responses.length})</option>`]
    .concat(options.map(option => `<option value="${option.id}"${String(selectedFilter) === String(option.id) ? ' selected' : ''}>${option.label} (${option.count})</option>`))
    .concat([`<option value="no-response"${selectedFilter === 'no-response' ? ' selected' : ''}>No Response (${noResponseCount})</option>`])
    .join('');
  const rows = filteredResponses.map(response => `<tr><td>${response.employeeCode || '-'}</td><td>${response.name || '-'}</td><td>${response.department || '-'}</td><td><strong>${response.selectedOption || 'No Response'}</strong></td><td>${response.votedAt ? formatDate(response.votedAt) : '-'}</td></tr>`).join('');

  return `<div class="card">
    <div class="card-hdr">
      <div><div class="card-title">${results.title}</div><div style="font-size:12px;color:var(--muted);margin-top:4px;">${results.question || 'Voting'} | ${results.totalVotes || 0}/${results.totalAudience || 0} responses | ${results.status}</div></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
        ${announcement.voteStatus === 'open' ? `<button class="btn btn-sm" onclick="window.closeAnnouncementVote('${announcement.id}')">Close Vote</button>` : ''}
        <button class="btn btn-sm" onclick="window.exportAnnouncementVoteResults('${announcement.id}')">Export CSV</button>
        <button class="btn btn-sm" onclick="window.showPage('announcements')">Back</button>
      </div>
    </div>
    <div class="stats-grid" style="margin-top:14px;">${optionCards}</div>
    <div style="display:flex;align-items:end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;">
      <div>
        <label class="fl" for="vote-results-filter">Filter by answer</label>
        <select class="fi" id="vote-results-filter" onchange="window.filterAnnouncementVoteResults(this.value)" style="min-width:240px;">${filterOptions}</select>
      </div>
      <div class="badge bg-blue" style="font-size:13px;">${filterLabel}: ${filteredResponses.length}</div>
    </div>
    <div class="table-wrap" style="margin-top:14px;"><table><thead><tr><th>Employee ID</th><th>Name</th><th>Department</th><th>Selected Option</th><th>Voted At</th></tr></thead><tbody>${rows || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px;">No employees found for this answer.</td></tr>`}</tbody></table></div>
  </div>`;
}

function formatFileSize(bytes){
  const value = Number(bytes || 0);
  if(!value) return '0 KB';
  if(value >= 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)} MB`;
  return `${Math.max(1, Math.round(value / 1024))} KB`;
}

function pagePolicies(isEmployee=false){
  const policies = Array.isArray(DB.companyPolicies) ? DB.companyPolicies : [];
  const canManage = isSuperAdminRole() && !isEmployee;
  const rows = policies.map(policy => `
    <tr>
      <td>
        <div style="display:flex;flex-direction:column;gap:4px;">
          <strong>${policy.title || 'Policy Document'}</strong>
          <span style="font-size:12px;color:var(--muted);">${policy.fileName || 'PDF file'}</span>
        </div>
      </td>
      <td>${formatFileSize(policy.fileSize)}</td>
      <td>${formatDate(policy.uploadedAt)}</td>
      <td>${policy.uploadedBy || 'Admin'}</td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn btn-sm" onclick="window.open('${policy.fileUrl}','_blank')">Open PDF</button>
          ${canManage ? `<button class="btn btn-sm btn-danger" onclick="window.deleteCompanyPolicy(${policy.id})">Delete</button>` : ''}
        </div>
      </td>
    </tr>
  `).join('') || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px;">No company policies uploaded yet.</td></tr>`;

  return `
  <div class="hero-panel" style="margin-bottom:14px;">
    <div class="hero-title">Company Policies</div>
    <div class="hero-sub">${canManage ? 'Upload and manage official company policy PDFs for the whole organization. Employees can open the latest policy documents directly from this page.' : 'View official company policy PDFs published by admin. Open any policy to read the full document.'}</div>
    <div class="hero-chip-row">
      <div class="hero-chip"><div class="k">Policies</div><div class="v">${policies.length}</div></div>
      <div class="hero-chip"><div class="k">Format</div><div class="v">PDF</div></div>
      <div class="hero-chip"><div class="k">Access</div><div class="v">${canManage ? 'Admin' : 'View Only'}</div></div>
    </div>
  </div>

  ${canManage ? `
    <div class="card" style="margin-bottom:14px;">
      <div class="card-hdr">
        <div class="card-title">Upload Policy</div>
        <div style="font-size:12px;color:var(--muted);">Only PDF files are allowed. Employees will only be able to open and read them.</div>
      </div>
      <div class="toolbar-grid">
        <div>
          <label class="fl">Policy Title</label>
          <input class="fi" id="policy-title" placeholder="e.g. Attendance Policy">
        </div>
        <div>
          <label class="fl">PDF File</label>
          <input type="file" class="fi" id="policy-file" accept=".pdf,application/pdf">
        </div>
        <div style="display:flex;align-items:end;justify-content:flex-end;">
          <button class="btn btn-primary" onclick="window.uploadCompanyPolicy()">Upload Policy</button>
        </div>
      </div>
    </div>
  ` : ''}

  <div class="directory-card">
    <div class="directory-top">
      <div>
        <div class="panel-title">Policy Library</div>
        <div style="font-size:12px;color:var(--muted);margin-top:3px;">Central place for HR, compliance, workplace, and employee handbook PDFs.</div>
      </div>
      <div class="data-pill-row">
        <span class="data-pill">Published <strong>${policies.length}</strong></span>
      </div>
    </div>
    <div class="soft-table"><div class="table-wrap"><table>
      <thead><tr><th>Policy</th><th>Size</th><th>Uploaded</th><th>Uploaded By</th><th>Action</th></tr></thead>
      <tbody>${rows}</tbody>
    </table></div></div>
  </div>`;
}

function pageBackups(){
  const backups = Array.isArray(DB.backups) ? DB.backups : [];
  const deletedBackups = Array.isArray(DB.deletedBackups) ? DB.deletedBackups : [];
  const recoveryItems = Array.isArray(DB.recoveryItems) ? DB.recoveryItems : [];
  const latest = backups[0] || null;
  const todayKey = getTodayLocalDate().replace(/-/g, '');
  const manualBackupsToday = backups.filter(backup => (backup.type || '').toLowerCase() === 'manual' && String(backup.name || backup.id || '').startsWith(`musharp-${todayKey}-`)).length;
  const manualLimitReached = manualBackupsToday >= 5;
  const rows = backups.map((backup, index) => `
    <tr>
      <td>
        <div style="font-weight:800;">${backup.name || backup.id}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;">${backup.typeLabel || ((backup.type || '').toLowerCase() === 'manual' ? 'Manual Backup' : 'Auto Backup')}</div>
      </td>
      <td>${backup.createdAt || '-'}</td>
      <td>${backup.sizeLabel || '-'}</td>
      <td><span class="badge ${(backup.type || '').toLowerCase() === 'manual' ? 'bg-amber' : (index === 0 ? 'bg-green' : 'bg-blue')}">${index === 0 ? 'Latest' : 'Available'}</span></td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn btn-sm" onclick="window.restoreBackup('${String(backup.id || backup.name).replace(/'/g, "\\'")}')">Restore</button>
          <button class="btn btn-sm btn-danger" onclick="window.deleteBackup('${String(backup.id || backup.name).replace(/'/g, "\\'")}')">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
  const deletedRows = deletedBackups.map((backup) => `
    <tr>
      <td>
        <div style="font-weight:800;">${backup.name || backup.id}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;">Deleted backup, manager recovery only</div>
      </td>
      <td>${backup.deletedAt || '-'}</td>
      <td>${backup.recoverableUntil || '-'}</td>
      <td>${backup.sizeLabel || '-'}</td>
      <td><button class="btn btn-sm" onclick="window.restoreBackup('${String(backup.id || backup.name).replace(/'/g, "\\'")}')">Restore</button></td>
    </tr>
  `).join('');
  const dataRecoveryRows = recoveryItems.map((item) => `
    <tr>
      <td><span class="badge bg-blue">${item.type || 'data'}</span></td>
      <td>
        <div style="font-weight:800;">${item.label || '-'}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;">Deleted by ${item.deletedBy || 'Admin'}</div>
      </td>
      <td>${item.deletedAt || '-'}</td>
      <td>${item.recoverableUntil || '-'}</td>
      <td><button class="btn btn-sm" onclick="window.restoreRecoveryItem(${Number(item.id)})">Restore</button></td>
    </tr>
  `).join('');

  return `
  <div class="g4" style="margin-bottom:14px;">
    <div class="stat-card"><div class="stat-label">Saved Backups</div><div class="stat-val">${backups.length}</div><div class="stat-sub">Showing latest 10 days</div></div>
    <div class="stat-card"><div class="stat-label">Schedule</div><div class="stat-val">01:00</div><div class="stat-sub">Runs every night</div></div>
    <div class="stat-card"><div class="stat-label">Latest Backup</div><div class="stat-val" style="font-size:24px;">${latest ? latest.createdAt.split(' ')[0] : '-'}</div><div class="stat-sub">${latest ? latest.sizeLabel : 'No backup yet'}</div></div>
    <div class="stat-card"><div class="stat-label">Manual Today</div><div class="stat-val" style="font-size:24px;">${manualBackupsToday}/5</div><div class="stat-sub">${manualLimitReached ? 'Daily manual limit reached' : 'Manual backups remaining today'}</div></div>
  </div>

  <div class="card">
    <div class="card-hdr">
      <div>
        <div class="card-title">Backups & Restore</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;">Restore employees, admins, reports data, events, announcements, policies, attendance, leave, and uploaded documents from any auto or manual backup.</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn btn-sm" onclick="window.refreshBackups()">Refresh</button>
        <button class="btn btn-sm btn-primary" onclick="window.runBackupNow()" ${manualLimitReached ? 'disabled style="opacity:.55;cursor:not-allowed;"' : ''}>Run Backup Now</button>
      </div>
    </div>
    <div class="alert al-info"><span>Info</span><div><strong>Restore replaces current database data.</strong> Auto backups run nightly at 01:00. Manual backups are separate and limited to 5 per day.</div></div>
    <div class="soft-table" style="margin-top:14px;"><div class="table-wrap"><table>
      <thead><tr><th>Backup</th><th>Created</th><th>Size</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>${rows || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:26px;">No backups found yet. Use Run Backup Now or wait for the 01:00 scheduled backup.</td></tr>`}</tbody>
    </table></div></div>
  </div>

  ${DB.currentRole === 'manager' ? `
  <div class="card" style="margin-top:14px;">
    <div class="card-hdr">
      <div>
        <div class="card-title">Super-Admin Recovery</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;">Backups deleted by an admin remain recoverable here for 4 days. Use Restore to roll the system back even if the backup was removed from the admin list.</div>
      </div>
      <span class="badge bg-amber">4 day window</span>
    </div>
    <div class="soft-table" style="margin-top:14px;"><div class="table-wrap"><table>
      <thead><tr><th>Deleted Backup</th><th>Deleted</th><th>Recoverable Until</th><th>Size</th><th>Action</th></tr></thead>
      <tbody>${deletedRows || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:22px;">No deleted backups are waiting for manager recovery.</td></tr>`}</tbody>
    </table></div></div>
  </div>
  <div class="card" style="margin-top:14px;">
    <div class="card-hdr">
      <div>
        <div class="card-title">Deleted Data Recovery</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;">Employees and admin-deleted records appear here for 4 days, including announcements, holidays, policies, leave types, shifts, departments, and custom notifications.</div>
      </div>
      <button class="btn btn-sm" onclick="window.refreshRecoveryItems()">Refresh</button>
    </div>
    <div class="soft-table" style="margin-top:14px;"><div class="table-wrap"><table>
      <thead><tr><th>Type</th><th>Item</th><th>Deleted</th><th>Recoverable Until</th><th>Action</th></tr></thead>
      <tbody>${dataRecoveryRows || `<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:22px;">No deleted data is waiting for manager recovery.</td></tr>`}</tbody>
    </table></div></div>
  </div>` : ''}`;
}

async function refreshBackups(){
  try{
    const data = await wpApi('/api/backups', {method:'GET', headers:{}});
    DB.backups = data.backups || [];
    DB.deletedBackups = data.deletedBackups || [];
    window.__workpulseBackupsLastSyncedAt = Date.now();
    if(window.__workpulseCurrentPage === 'backups') showPage('backups');
  }catch(e){
    showToast(e.message || 'Unable to load backups','red');
  }
}

async function refreshRecoveryItems(){
  if(DB.currentRole !== 'manager') return;
  try{
    const data = await wpApi('/api/recovery', {method:'GET', headers:{}});
    DB.recoveryItems = data.recoveryItems || [];
    if(window.__workpulseCurrentPage === 'backups') showPage('backups');
  }catch(e){
    showToast(e.message || 'Unable to load recovery items','red');
  }
}

async function restoreRecoveryItem(id){
  if(!confirm('Restore this deleted item?')) return;
  try{
    const data = await wpApi('/api/recovery/'+encodeURIComponent(id)+'/restore', {method:'POST', body: JSON.stringify({})});
    DB.recoveryItems = data.recoveryItems || [];
    await wpReload();
    showToast('Deleted item restored','green');
    showPage('backups');
  }catch(e){
    showToast(e.message || 'Restore failed','red');
  }
}

async function runBackupNow(){
  if(!confirm('Create a full backup now?')) return;
  showToast('Creating backup. This can take a minute...','green');
  try{
    const data = await wpApi('/api/backups', {method:'POST', body: JSON.stringify({})});
    DB.backups = data.backups || [];
    DB.deletedBackups = data.deletedBackups || [];
    showToast('Backup created successfully','green');
    showPage('backups');
  }catch(e){
    showToast(e.message || 'Backup failed','red');
  }
}

async function restoreBackup(id){
  if(!confirm('Restore this backup? Current database data will be replaced. A pre-restore backup will be created first.')) return;
  showToast('Restoring backup. Please keep this page open...','amber');
  try{
    const data = await wpApi('/api/backups/'+encodeURIComponent(id)+'/restore', {method:'POST', body: JSON.stringify({})});
    DB.backups = data.backups || [];
    DB.deletedBackups = data.deletedBackups || [];
    await wpReload();
    showToast('Backup restored successfully','green');
    showPage('backups');
  }catch(e){
    showToast(e.message || 'Restore failed','red');
  }
}

async function deleteBackup(id){
  if(!confirm('Delete this backup file permanently?')) return;
  try{
    const data = await wpApi('/api/backups/'+encodeURIComponent(id), {method:'DELETE'});
    DB.backups = data.backups || [];
    DB.deletedBackups = data.deletedBackups || [];
    showToast('Backup deleted','green');
    showPage('backups');
  }catch(e){
    showToast(e.message || 'Delete failed','red');
  }
}

window.refreshBackups = refreshBackups;
window.runBackupNow = runBackupNow;
window.restoreBackup = restoreBackup;
window.deleteBackup = deleteBackup;
window.refreshRecoveryItems = refreshRecoveryItems;
window.restoreRecoveryItem = restoreRecoveryItem;

function pageCompany(){
  const company = DB.company || {};
  const companyName = company.company_name || 'muSharp';
  const website = company.website_link || 'musharp.com';
  const officialEmail = company.official_email || 'info@musharp.com';
  const officialContact = company.official_contact_no || '+92 42 35761234';
  const officeLocation = company.office_location || '12 Tech City, Arfa Software Park, Lahore';
  const linkedin = company.linkedin_page || 'linkedin.com/company/musharp';
  const latestBackup = Array.isArray(DB.backups) && DB.backups.length ? DB.backups[0] : null;

  return `
  <div class="g2">
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">Company Information</div>
      <div class="irow"><span class="ikey">Company Name</span><span class="ival">${companyName}</span></div>
      <div class="irow"><span class="ikey">Website</span><span class="ival" style="color:var(--accent);">${website}</span></div>
      <div class="irow"><span class="ikey">Official Email</span><span class="ival">${officialEmail}</span></div>
      <div class="irow"><span class="ikey">Contact No</span><span class="ival">${officialContact}</span></div>
      <div class="irow"><span class="ikey">Office Location</span><span class="ival">${officeLocation}</span></div>
      <div class="irow"><span class="ikey">LinkedIn</span><span class="ival" style="color:var(--accent);">${linkedin}</span></div>
      <div class="irow"><span class="ikey">No. of Employees</span><span class="ival">${DB.employees.length}</span></div>
      <div class="irow"><span class="ikey">Industry</span><span class="ival">Software & Technology</span></div>
      <div class="irow"><span class="ikey">Incorporated</span><span class="ival">2018</span></div>
    </div>
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">Backup & Disaster Recovery</div>
      <div class="alert ${latestBackup ? 'al-success' : 'al-info'}"><span>Info</span><div><strong>Last Backup:</strong> ${latestBackup ? `${latestBackup.createdAt} - ${latestBackup.sizeLabel}` : 'No backup found yet'}</div></div>
      <div class="irow"><span class="ikey">Backup Frequency</span><span class="ival">Daily at 01:00 AM</span></div>
      <div class="irow"><span class="ikey">Backup Type</span><span class="ival">Full database + local files</span></div>
      <div class="irow"><span class="ikey">Retention</span><span class="ival">10 days</span></div>
      <div class="irow"><span class="ikey">Storage</span><span class="ival">Local server storage/app/backups/muSharp</span></div>
      <div class="irow"><span class="ikey">RTO</span><span class="ival">&lt; 4 hours</span></div>
      <div class="irow"><span class="ikey">RPO</span><span class="ival">&lt; 24 hours</span></div>
      <div style="margin-top:14px;display:flex;gap:8px;">
        <button class="btn btn-sm btn-primary" onclick="window.runBackupNow()">Run Backup Now</button>
        <button class="btn btn-sm" onclick="window.showPage('backups')">Open Backups</button>
      </div>
    </div>
  </div>
  <div class="card" style="margin-top:14px;">
    <div class="card-hdr">
      <div>
        <div class="card-title">Transfer Data</div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;">Move company data safely by downloading a full transfer package or importing/exporting complete employee profiles and company details.</div>
      </div>
      <span class="badge bg-blue">Admin Tool</span>
    </div>
    <div class="g4" style="margin-bottom:14px;">
      <div class="stat-card"><div class="stat-label">Employees</div><div class="stat-val">${DB.employees.length}</div><div class="stat-sub">Ready for transfer</div></div>
      <div class="stat-card"><div class="stat-label">Attendance Rows</div><div class="stat-val">${DB.attendance.length}</div><div class="stat-sub">Daily records included</div></div>
      <div class="stat-card"><div class="stat-label">Leave Records</div><div class="stat-val">${DB.leaves.length}</div><div class="stat-sub">Requests and approvals</div></div>
      <div class="stat-card"><div class="stat-label">Shifts</div><div class="stat-val">${(DB.shifts||[]).length}</div><div class="stat-sub">Standard schedules available</div></div>
    </div>
  <div class="alert al-info"><span>â‡„</span><div><strong>Transfer package:</strong> includes company details, employees, teams, attendance, leave, holidays, announcements, and other operational data in one JSON file.</div></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
      <button class="btn btn-primary" onclick="window.exportTransferData()">Download Full Transfer Data</button>
      <button class="btn" onclick="window.exportEmployeeProfilesJson()">Export Full Employee Profiles JSON</button>
      <button class="btn" onclick="window.importEmployeeProfiles()">Import Employee Profiles</button>
      <button class="btn" onclick="window.exportCompanyDetailsJson()">Export Company Details JSON</button>
      <button class="btn" onclick="window.importCompanyDetails()">Import Company Details JSON</button>
      <button class="btn" onclick="window.exportEmployeeCSV()">Employees CSV</button>
      <button class="btn" onclick="window.exportAttendanceCSV()">Attendance CSV</button>
      <button class="btn" onclick="window.exportLeaveCSV()">Leave CSV</button>
    </div>
    <div class="panel-card" style="margin-top:14px;">
      <div class="panel-head"><div class="panel-title">Standard Shifts</div><button class="btn btn-sm btn-primary" onclick="window.openCreateShift()">+ Add Shift</button></div>
      <div class="soft-table"><div class="table-wrap"><table><thead><tr><th>Name</th><th>Code</th><th>Time</th><th>Grace</th><th>Working Days</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>${(DB.shifts||[]).map(shift => `<tr><td>${shift.name}</td><td>${shift.code}</td><td>${shift.start} - ${shift.end}</td><td>${shift.grace} min</td><td>${shift.break || 60} min</td><td>${shift.workingDays||'-'}</td><td>${shift.active ? statusBadge('Active') : statusBadge('Inactive')}</td><td><div style="display:flex;gap:6px;"><button class="btn btn-sm" onclick="window.openEditShift(${shift.id})">Edit</button><button class="btn btn-sm btn-danger" onclick="window.deleteShift(${shift.id})">Delete</button></div></td></tr>`).join('') || `<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px;">No shifts configured.</td></tr>`}</tbody></table></div></div>
    </div>
  </div>`;
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  â–ˆâ–ˆâ–ˆâ–ˆâ–ˆâ–ˆ EMPLOYEE PAGES â–ˆâ–ˆâ–ˆâ–ˆâ–ˆâ–ˆ
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
function renderEmployeeWorkspaceTabs(activePage){
  const tabs = [
    {label:'Dashboard', page:'emp-dashboard'},
    {label:'My Profile', page:'emp-profile'},
    {label:'Team Profile', page:'emp-team'},
    {label:'Attendance', page:'emp-attendance'},
    {label:'Calendar', page:'emp-calendar'},
  ];

  return `<div class="emp-pp-tabs">
    ${tabs.map(tab => `<button class="${tab.page===activePage ? 'active' : ''}" ${tab.page===activePage ? '' : `onclick="window.showPage('${tab.page}')"`}>${tab.label}</button>`).join('')}
  </div>`;
}

function getEmployeeDashboardTeamPeople(user){
  const employees = Array.isArray(DB.employees) ? DB.employees : [];
  const userId = String(user?.id || '');
  const userDept = String(user?.dept || '');

  return employees.filter(employee => {
    if(!employee || String(employee.id || '') === userId) return false;
    const role = String(employee.role || '').toLowerCase();
    const dept = String(employee.dept || '');
    const designation = String(employee.desg || '').toLowerCase();
    const isHr = role === 'hr' || dept.toLowerCase() === 'human resources' || designation.includes('hr');
    const isTeam = userDept && dept === userDept;
    return isHr || isTeam;
  });
}

function getEmployeeDashboardInitials(name){
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
  if(!parts.length) return '--';
  return `${parts[0][0] || ''}${(parts[1] || parts[0])[0] || ''}`.toUpperCase();
}

function renderEmployeeDashboardStatusList(items, emptyText){
  if(!items.length){
    return `<div class="emp-pp-empty">${emptyText}</div>`;
  }

  return `<div class="team-mini-list">${items.slice(0, 5).map(item => `
    <div class="team-mini-item">
      <div class="av av-32" style="background:${item.avatarColor || 'var(--accent)'}22;color:${item.avatarColor || 'var(--accent)'};">${item.avatar || getEmployeeDashboardInitials(item.name || item.empName || '')}</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.name || item.empName}</div>
        <div style="font-size:11px;color:var(--muted);">${item.detail || item.dept || '-'}</div>
      </div>
      ${item.badge || ''}
    </div>
  `).join('')}</div>`;
}

function pageEmpDashboard(){
  try{
    const u=DB.currentUser || {};
    const ps=DB.punchState || {punchedIn:false,onBreak:false};
    const attendance = Array.isArray(DB.attendance) ? DB.attendance : [];
    const leaves = Array.isArray(DB.leaves) ? DB.leaves : [];
    const employees = Array.isArray(DB.employees) ? DB.employees : [];
    const today=getTodayLocalDate();
    const todayRec=getTodayAttendanceRecord()||{in:null,out:null,status:'Not Started',late:false};
    const punchDisplay = getTodayPunchDisplay();
    const workedBreakdown = getTodayWorkedBreakdown();
    const clockInLabel = formatPunchMoment(
      punchDisplay.clockIn,
      today,
      '--:--',
      true
    );
    const clockOutLabel = ps.punchedIn
      ? '--:--'
      : formatPunchMoment(punchDisplay.clockOut, today, '--:--', false);
    const workedHeroLabel = formatWorkedHoursClockLabel(workedBreakdown.totalMinutes);
    const shiftCompleted = isShiftCompletedForDate(today);
    const myLeaves=leaves.filter(l=>l.empId===u.id);
    const leaveCards = [
      findLeaveBalance('sick') || {name:'Sick Leaves', allocated:0, used:0, remaining:0},
      findLeaveBalance('annual') || {name:'Annual Leaves', allocated:0, used:0, remaining:0},
      findLeaveBalance('bereavement') || {name:'Bereavement Leaves', allocated:0, used:0, remaining:0},
      findLeaveBalance('marriage') || {name:'Marriage Leaves', allocated:0, used:0, remaining:0},
    ].map(balance => ({
      name: balance.name || 'Leave',
      quota: Number(balance.allocated ?? 0),
      used: Number(balance.used ?? Math.max(0, Number(balance.allocated ?? 0) - Number(balance.remaining ?? 0))),
      left: Number(balance.remaining ?? 0),
    }));
    const teamPeople = getEmployeeDashboardTeamPeople(u);
    const teamPeopleById = new Map(teamPeople.map(employee => [String(employee.id), employee]));
    const sameTeamIds = new Set(teamPeople
      .filter(employee => String(employee.dept || '') === String(u.dept || ''))
      .map(employee => String(employee.id)));
    const peopleOfInterestIds = new Set(teamPeople.map(employee => String(employee.id)));
    const teamOnLeave = leaves
      .filter(l=>l.status==='Approved' && l.from<=today && l.to>=today && peopleOfInterestIds.has(String(l.empId)))
      .map(leave => {
        const employee = teamPeopleById.get(String(leave.empId)) || {};
        return {
          name: leave.empName,
          empName: leave.empName,
          avatar: employee.avatar,
          avatarColor: employee.avatarColor,
          detail: `${leave.type || 'Leave'} - ${leave.dept || employee.dept || '-'}`,
          badge: `<span class="badge bg-purple">${formatLeaveDuration(leave)}</span>`,
        };
      });
    const liveByEmployeeId = new Map((Array.isArray(DB.liveAttendance) ? DB.liveAttendance : []).map(entry => [String(entry.empId || ''), entry]));
    const teamNotClockedIn = teamPeople
      .filter(employee => sameTeamIds.has(String(employee.id)))
      .map(employee => ({employee, live: liveByEmployeeId.get(String(employee.id))}))
      .filter(({live}) => !live || live.status === 'not_checked_in')
      .map(({employee}) => ({
        name: `${employee.fname || ''} ${employee.lname || ''}`.trim(),
        avatar: employee.avatar,
        avatarColor: employee.avatarColor,
        detail: `${employee.desg || 'Employee'} - ${employee.dept || '-'}`,
        badge: '<span class="badge bg-red">Not In</span>',
      }));
    const whoOff = teamOnLeave.length;
    const myLogs = attendance.filter(a=>a.empId===u.id).slice(0,5);
    const latestAnnouncements = (Array.isArray(DB.announcements) ? DB.announcements : []).slice(0,3);
    const upcomingItems = dashboardUpcomingItems();
    const birthdayItems = getBirthdayEventFeed()
      .filter(item => item.date >= today)
      .slice(0, 5)
      .map(item => {
        const employee = employees.find(entry => String(entry.id) === String(item.employeeId)) || {};
        return {
          name: String(item.label || '').replace(/ Birthday$/, ''),
          avatar: employee.avatar,
          avatarColor: employee.avatarColor,
          detail: `${formatDate(item.date)}${item.description ? ` - ${item.description}` : ''}`,
          badge: '<span class="badge bg-amber">Birthday</span>',
        };
      });
    const statusLabel=ps.punchedIn?(ps.onBreak?'On Break':'In Office'):(shiftCompleted?'Completed':(todayRec.out?'Clocked Out':'Not Started'));

    return `
  ${renderEmployeeWorkspaceTabs('emp-dashboard')}

  <div class="emp-pp-layout">
    <div class="emp-pp-left">
      <div class="emp-pp-card emp-pp-clock">
        <div class="emp-pp-clock-main">
          <div class="emp-pp-title">Welcome To ${u.fname}</div>
          <div class="emp-pp-kicker">CLOCK IN / Clock Out</div>
          <div class="emp-pp-clock-lines">
            <div class="emp-pp-clock-line">
              <span class="emp-pp-line-dot dot-in"></span>
              <span class="emp-pp-line-label">CLOCK IN</span>
              <span class="emp-pp-line-value">${clockInLabel}</span>
            </div>
            <div class="emp-pp-clock-line">
              <span class="emp-pp-line-dot dot-out"></span>
              <span class="emp-pp-line-label">Clock Out</span>
              <span class="emp-pp-line-value">${clockOutLabel}</span>
            </div>
          </div>
          <div class="emp-pp-hours-wrap">
            <div class="emp-pp-hours" data-work-hours-live data-work-hours-format="compact">${workedHeroLabel}</div>
            <div class="emp-pp-hours-note">Today's Hours</div>
          </div>
          <div class="emp-pp-actions">
            ${!ps.punchedIn && !shiftCompleted
              ? `<button class="punch-btn punch-btn-inline pb-in" onclick="punchIn()">Clock In</button>`
              : ps.punchedIn
                ? `<button class="punch-btn punch-btn-inline pb-out" onclick="punchOut()">Clock Out</button>
                   ${ps.onBreak
                     ? `<button class="punch-btn punch-btn-inline pb-break-in" onclick="breakIn()">Break Out</button>`
                     : `<button class="punch-btn punch-btn-inline pb-break" onclick="breakOut()">Break In</button>`}`
                : `<button class="punch-btn punch-btn-inline punch-btn-muted" disabled>Shift Completed</button>`
            }
          </div>
          <div class="emp-pp-break">Break: ${punchDisplay.breakDuration}</div>
          <div class="emp-pp-policy">${getCurrentShiftPolicy()}</div>
        </div>
        <div class="emp-pp-illus-card">
          <div class="emp-pp-illus-blob blob-a"></div>
          <div class="emp-pp-illus-blob blob-b"></div>
          <div class="emp-pp-illus-blob blob-c"></div>
          <div class="emp-pp-illus-board"></div>
          <div class="emp-pp-illus-clock"></div>
          <div class="emp-pp-illus" aria-hidden="true"></div>
        </div>
      </div>

      <div class="emp-pp-card">
        <div class="emp-pp-title" style="margin-bottom:10px;">Time Off</div>
        <div class="emp-pp-leaves">
          ${leaveCards.map(card => `
            <div class="emp-pp-leaf">
              <div>
                <span>${card.name}</span>
                <div class="emp-pp-leaf-meta">Quota: ${formatLeaveBalanceValue(card.quota)} Days Â· Used: ${formatLeaveBalanceValue(card.used)} Days</div>
              </div>
              <strong>${formatLeaveBalanceValue(card.left)} Left</strong>
            </div>
          `).join('')}
        </div>
      </div>

      <div class="emp-pp-card">
        <div class="card-hdr"><div class="emp-pp-title">My Attendance</div><button class="btn btn-sm" onclick="window.showPage('emp-attendance')">View All</button></div>
        <div class="table-wrap"><table>
          <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead>
          <tbody>${myLogs.map(a=>`
            <tr><td>${formatDate(a.date)}</td><td>${a.in||'--'}</td><td>${a.out||'--'}</td><td>${statusBadge(a.late?'Late':a.status)}</td></tr>
          `).join('') || `<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:16px;">No attendance records</td></tr>`}</tbody>
        </table></div>
      </div>
    </div>

    <div class="emp-pp-right">
      <div class="emp-pp-card">
        <div class="card-hdr"><div class="emp-pp-title">Who is Off</div><span class="badge bg-purple">${whoOff}</span></div>
        ${renderEmployeeDashboardStatusList(teamOnLeave, 'No HR or team member is on leave today')}
      </div>
      <div class="emp-pp-card">
        <div class="card-hdr"><div class="emp-pp-title">Not Clocked In Yet</div><span class="badge bg-red">${teamNotClockedIn.length}</span></div>
        ${renderEmployeeDashboardStatusList(teamNotClockedIn, 'Everyone from your team has clocked in or is on leave')}
      </div>
      <div class="emp-pp-card">
        <div class="card-hdr"><div class="emp-pp-title">Latest Announcements</div><button class="btn btn-sm" onclick="window.showPage('emp-announcements')">View All</button></div>
        ${latestAnnouncements.map(item=>`
          <div class="irow" style="align-items:flex-start;">
            <div>
              <div style="font-size:13px;font-weight:600;">${item.title}</div>
              <div style="font-size:11px;color:var(--muted);margin-top:3px;">${item.msg}</div>
            </div>
            <span class="badge ${item.cat === 'Event' ? 'bg-purple' : item.cat === 'Policy' ? 'bg-green' : 'bg-blue'}">${formatAnnouncementDateLabel(item)}</span>
          </div>
        `).join('') || `<div class="emp-pp-empty">No announcements for your audience right now</div>`}
      </div>
    </div>
  </div>`;
  }catch(e){
    console.error('pageEmpDashboard render error', e);
    return `<div class="card"><div class="card-title">Employee Dashboard</div><p style="margin-top:8px;color:var(--muted);">Could not render dashboard. Please refresh.</p></div>`;
  }
}

function pageEmpAttendance(){
  const u=DB.currentUser;
  const ps=DB.punchState;
  const today=getTodayLocalDate();
  const todayRec=getTodayAttendanceRecord()||{in:null,out:null,breakOut:null,breakIn:null,status:'Not Started',late:false};
  const punchDisplay = getTodayPunchDisplay();
  const workedBreakdown = getTodayWorkedBreakdown();
  const shiftCompleted = isShiftCompletedForDate(today);
  const liveClock = new Date().toLocaleTimeString('en-GB');
  const liveDate = new Date().toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

  const statusBadgeHtml=ps.punchedIn?(ps.onBreak?`<span class="badge bg-amber">On Break</span>`:`<span class="badge bg-green">In Office</span>`):(shiftCompleted?`<span class="badge bg-gray">Completed</span>`:`<span class="badge bg-gray">Not Clocked In</span>`);

  let employeePunchButtons = '';
  if(!ps.punchedIn && !shiftCompleted){
    employeePunchButtons = `<button class="punch-btn pb-in" onclick="punchIn()">Clock In</button>`;
  } else if(ps.punchedIn){
    employeePunchButtons = `<button class="punch-btn pb-out" onclick="punchOut()">Clock Out</button>
      ${ps.onBreak
        ? `<button class="punch-btn pb-break-in" onclick="breakIn()">Break Out</button>`
        : `<button class="punch-btn pb-break" onclick="breakOut()">Break In</button>`}`;
  } else {
    employeePunchButtons = `<button class="punch-btn punch-btn-muted" disabled>Shift Completed</button>`;
  }

  const logRows=DB.attendance.filter(a=>a.empId===u.id).map(a=>`
    <tr><td>${formatDate(a.date)}</td>
    <td>${new Date(a.date+'T00:00:00').toLocaleDateString('en-GB',{weekday:'short'})}</td>
    <td>${a.in||'-'}</td><td>${a.breakOut||'-'}</td><td>${a.breakIn||'-'}</td><td>${a.out||'-'}</td>
    <td>${calcWorkHours(a)}</td><td>${a.overtime?'+'+a.overtime+'m':'-'}</td>
    <td>${statusBadge(a.late?'Late':a.status)}</td>
    <td>${(a.status==='Present'&&!a.out&&!a.in)?`<button class="btn btn-sm" onclick="window.openRegulationModal()">Regulate</button>`:'-'}</td>
    </tr>`).join('');

  const regRows=DB.regulations.filter(r=>r.empId===u.id).map(r=>`
    <tr><td>${formatDate(r.date)}</td><td>${r.type}</td><td>${r.orig}</td><td>${r.req}</td><td>${r.reason}</td><td>${statusBadge(r.status)}</td></tr>`).join('');

  return `
  ${renderEmployeeWorkspaceTabs('emp-attendance')}
  <div class="admin-att-shell" style="margin-bottom:18px;">
    <div class="admin-att-hero">
      <div class="admin-att-hero-panel">
        <div class="admin-att-eyebrow">My Attendance</div>
        <div class="admin-att-time" id="cw-time-display">${liveClock}</div>
        <div class="admin-att-date">${liveDate}</div>
        <div class="admin-att-status">${statusBadgeHtml}</div>
        <div class="admin-att-meta">
          <span class="admin-att-chip">Shift ${u.shiftStart||'11:00'} - ${u.shiftEnd||'20:00'}</span>
          <span class="admin-att-chip">Worked ${getLiveWorkedTimeLabel()}</span>
        </div>
        <div class="admin-att-actions">
          ${employeePunchButtons}
        </div>
        <div style="margin-top:12px;font-size:11px;color:rgba(255,255,255,.58);">Policy: ${getCurrentShiftPolicy()}</div>
      </div>
    </div>
    <div class="admin-att-summary card">
      <div class="att-summary-top">
        <div>
          <div class="card-title">Today's Summary</div>
          <div class="att-summary-subtitle">Your live attendance overview for today</div>
        </div>
        <span class="att-summary-pill">Employee View</span>
      </div>
      <div class="admin-att-summary-grid">
        <div class="admin-att-stat">
          <span class="admin-att-stat-label">Clock In</span>
          <strong class="admin-att-stat-value">${punchDisplay.clockIn || '-'}</strong>
        </div>
        <div class="admin-att-stat">
          <span class="admin-att-stat-label">Clock Out</span>
          <strong class="admin-att-stat-value">${ps.punchedIn ? '-' : (punchDisplay.clockOut || '-')}</strong>
        </div>
        <div class="admin-att-stat">
          <span class="admin-att-stat-label">Break Time</span>
          <strong class="admin-att-stat-value">${todayRec.breakOut&&todayRec.breakIn?'30 min':'-'}</strong>
        </div>
        <div class="admin-att-stat admin-att-stat-highlight">
          <span class="admin-att-stat-label">Working Hours Today</span>
          <strong class="admin-att-stat-value" data-work-hours-live data-work-hours-format="standard">${getLiveWorkedTimeLabel()}</strong>
        </div>
      </div>
      <div class="admin-att-details">
        <div class="att-summary-detail">
          <span class="att-summary-detail-label">Calculation</span>
          <strong class="att-summary-detail-value">${workedBreakdown.completedLabel} + ${workedBreakdown.currentSessionLabel} = ${workedBreakdown.totalLabel}</strong>
        </div>
        <div class="att-summary-detail">
          <span class="att-summary-detail-label">Status</span>
          <div class="att-summary-detail-badge">${todayRec.late?statusBadge('Late'):statusBadge(todayRec.status||'Not Started')}</div>
        </div>
        <div class="att-summary-detail">
          <span class="att-summary-detail-label">Shift Policy</span>
          <strong class="att-summary-detail-value">${u.shiftStart||'11:00'} - ${u.shiftEnd||'20:00'}</strong>
        </div>
      </div>
    </div>
  </div>
  ${buildTabs('ema',[
    {id:'log',label:'My Log',content:`
      ${buildAttendanceMonthLog('employee', u.id, 'My Attendance Log', '<button class="btn btn-sm" onclick="window.openRegulationModal()">+ Regulation</button>')}`},
    {id:'reg',label:'Regulation Requests',content:`
      <div class="card"><div class="card-hdr"><div class="card-title">My Regulation Requests</div><button class="btn btn-sm btn-primary" onclick="window.openRegulationModal()">+ New</button></div>
      <div class="table-wrap"><table><thead><tr><th>Date</th><th>Type</th><th>Original</th><th>Requested</th><th>Reason</th><th>Status</th></tr></thead>
      <tbody>${regRows||'<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px;">No requests</td></tr>'}</tbody></table></div>
    </div>`},
  ],'log')}`;
}
function pageEmpLeaves(){
  const u=DB.currentUser;
  const myLeaves=DB.leaves.filter(l=>l.empId===u.id);
  const leaveBalanceList = getLeaveBalancesList();
  const leaveBalances = leaveBalanceList.length
    ? leaveBalanceList.map(balance => [balance.name, balance.remaining, balance.allocated || balance.remaining || 1, 'var(--accent)'])
    : [['Annual Leave',18,21,'var(--accent)'],['Sick Leave',7,10,'var(--green)'],['Casual Leave',3,5,'var(--purple)'],['Paternity Leave',5,5,'var(--amber)'],['Marriage Leave',7,7,'var(--red)'],['Bereavement Leave',3,3,'var(--muted)']];
  const rows=myLeaves.map(l=>`
    <tr><td>${l.type}</td><td>${formatDate(l.from)}</td><td>${formatDate(l.to)}</td><td>${formatLeaveDuration(l)}</td>
    <td>${formatDate(l.applied)}</td><td>${statusBadge(l.hrStatus)}</td><td>${statusBadge(l.status)}</td></tr>`).join('');

  return `
  <div class="g2" style="margin-bottom:14px;">
    <div class="card">
      <div class="card-hdr"><div class="card-title">My Leave Balance 2025</div><button class="btn btn-sm btn-apply-leave" onclick="window.openModal('leaveModal')">Apply Leave</button></div>
      ${leaveBalances.map(([n,r,t,c])=>`
      <div class="ltr">
        <div class="ltr-hdr"><span class="ltr-name">${n}</span><span class="ltr-cnt">${formatLeaveBalanceValue(r)}/${formatLeaveBalanceValue(t)} days remaining</span></div>
        <div class="prog-bar"><div class="prog-fill" style="width:${Math.round(r/t*100)}%;background:${c};"></div></div>
      </div>`).join('')}
      <div style="margin-top:12px;font-size:12px;color:var(--muted);">Pro-rata accrual active. Leave year: Janâ€“Dec. Carry forward: max 5 days.</div>
    </div>
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Approval Workflow</div>
      <div class="tl">
        <div class="tl-item"><div class="tl-dot" style="background:var(--accent);"></div><div class="tl-line"></div><div><div style="font-weight:500;font-size:13px;">You apply</div><div style="font-size:11px;color:var(--muted);">Submit via portal with reason</div></div></div>
        <div class="tl-item"><div class="tl-dot" style="background:var(--green);"></div><div><div style="font-weight:500;font-size:13px;">HR reviews</div><div style="font-size:11px;color:var(--muted);">Approve or reject after balance check</div></div></div>
        
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><div class="card-title">My Leave History</div><button class="btn btn-sm btn-apply-leave" onclick="window.openModal('leaveModal')">+ Apply Leave</button></div>
    <div class="table-wrap"><table><thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Applied</th><th>HR</th><th>Status</th></tr></thead>
    <tbody>${rows||'<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No leave requests yet</td></tr>'}</tbody></table></div>
  </div>`;
}

function pageNotifications(employeeView=false){
  const notifications = Array.isArray(DB.notifications) ? DB.notifications : [];
  const customNotifications = Array.isArray(DB.customNotifications) ? DB.customNotifications : [];
  const unreadCount = Number(DB.notificationCount || 0);
  const refreshPage = employeeView ? 'emp-notifications' : 'notifications';
  const heroSub = employeeView
    ? 'Approval updates for your leave requests and attendance regulation requests appear here automatically.'
    : 'New employee requests and review-related updates appear here for admins and HR.';
  const emptyText = employeeView
    ? 'No notifications yet. Once admin reviews your leave or attendance requests, updates will appear here.'
    : 'No notifications yet. New leave and attendance regulation requests will appear here.';

  const items = notifications.map(notification => {
    const toneClass = notification.isRead ? '' : ' unread';
    const badgeMap = {
      leave_review: 'Leave',
      regulation_review: 'Attendance',
      regulation_request_submitted: 'Request',
      leave_request_submitted: 'Request',
      admin_custom_notification: 'Admin',
    };
    const badge = badgeMap[notification.type] || 'Update';
    const message = notification.message || 'You have a new update.';
    const target = getNotificationTarget(notification);
    const clickAttr = target ? ` onclick="window.openNotificationTarget(${Number(notification.id)})" title="Open linked page"` : '';

    const readActionLabel = notification.isRead ? 'Mark Unread' : 'Mark Read';
    const readActionValue = notification.isRead ? 'false' : 'true';

    return `<div class="notif-card${toneClass}" style="${target ? 'cursor:pointer;' : ''}"${clickAttr}>
      <div class="notif-card-head">
        <div>
          <div class="notif-card-title">${escapeHtml(notification.title || 'Notification')}</div>
          <div class="notif-card-meta">${formatDateTime(notification.createdAt)}</div>
        </div>
        <span class="badge ${notification.isRead ? 'bg-gray' : 'bg-blue'}">${notification.isRead ? 'Read' : badge}</span>
      </div>
      <div class="notif-card-body">${escapeHtml(message)}</div>
      ${notification.referenceCode ? `<div class="notif-card-ref">Reference: ${escapeHtml(notification.referenceCode)}</div>` : ''}
      <div style="display:flex;justify-content:flex-end;margin-top:10px;">
        <button class="btn btn-sm" onclick="window.setNotificationReadState(${Number(notification.id)}, ${readActionValue}, event).catch(e=>showToast(e?.message||'Failed','red'))">${readActionLabel}</button>
      </div>
    </div>`;
  }).join('');

  const managementPanel = !employeeView && isSuperAdminRole() ? `
  <div class="card" style="margin-bottom:14px;">
    <div class="card-hdr">
      <div class="card-title">Admin Notification Manager</div>
      <button class="btn btn-sm btn-primary" onclick="window.openNotificationModal()">+ New Notification</button>
    </div>
    <div class="notif-list">
      ${customNotifications.map(notification => `
        <div class="notif-card">
          <div class="notif-card-head">
            <div>
              <div class="notif-card-title">${escapeHtml(notification.title || 'Notification')}</div>
              <div class="notif-card-meta">Audience: ${formatNotificationAudienceLabel(notification.audience)} • Recipients: ${notification.recipientCount || 0} • ${formatDateTime(notification.updatedAt || notification.createdAt)}</div>
            </div>
            <span class="badge bg-teal">${escapeHtml(notification.referenceCode)}</span>
          </div>
          <div class="notif-card-body">${escapeHtml(notification.message || '')}</div>
          <div class="notif-card-ref">Created by ${escapeHtml(notification.authorName || 'Admin')}</div>
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
            <button class="btn btn-sm" onclick="window.openNotificationModal('${notification.referenceCode}')">Edit</button>
            <button class="btn btn-sm" style="border-color:#f3c1c1;color:#b42318;background:#fff5f5;" onclick="window.deleteNotification('${notification.referenceCode}')">Delete</button>
          </div>
        </div>
      `).join('') || `<div class="notif-empty">No custom notifications sent yet. Use New Notification to push a message from admin.</div>`}
    </div>
  </div>` : '';

  return `
  <div class="hero-panel" style="margin-bottom:14px;">
    <div class="hero-title">Notifications</div>
    <div class="hero-sub">${heroSub}</div>
    <div class="hero-chip-row">
      <div class="hero-chip"><div class="k">Unread</div><div class="v">${unreadCount}</div></div>
      <div class="hero-chip"><div class="k">Total</div><div class="v">${notifications.length}</div></div>
    </div>
  </div>
  ${managementPanel}
  <div class="card">
    <div class="card-hdr">
      <div class="card-title">Recent Updates</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <button class="btn btn-sm" onclick="window.wpReload().then(() => window.showPage('${refreshPage}'))">Refresh</button>
        <button class="btn btn-sm btn-primary" onclick="window.markAllNotificationsRead().catch(e=>showToast(e?.message||'Failed','red'))" ${notifications.length ? '' : 'disabled'}>Mark All Read</button>
      </div>
    </div>
    <div class="notif-list">${items || `<div class="notif-empty">${emptyText}</div>`}</div>
  </div>`;
}

function pageEmpNotifications(){
  return pageNotifications(true);
}

function pageEmpProfile(){
  const u=DB.currentUser;
  const emp=DB.employees.find(e=>e.id===u.id)||DB.employees.find(e=>e.email===u.email)||DB.employees[0]||{};
  return `
  <div class="profile-hero">
    <div class="av av-64" style="background:${u.avatarColor}33;color:${u.avatarColor};border:3px solid ${u.avatarColor}55;">${u.avatar}</div>
    <div>
      <div class="ph-name">${u.fname} ${u.lname}</div>
      <div class="ph-role">${u.desg} Â· ${u.id||emp?.id}</div>
      <div class="ph-tags"><span class="badge bg-green">Active</span><span class="badge bg-blue">${u.dept}</span></div>
    </div>
  </div>
  <div class="g2">
    <div class="card">
      <div class="card-title" style="margin-bottom:13px;">My Personal Details</div>
      <div class="irow"><span class="ikey">Full Name</span><span class="ival">${u.fname} ${u.lname}</span></div>
      <div class="irow"><span class="ikey">Employee ID</span><span class="ival">${u.id||emp?.id}</span></div>
          <div class="irow"><span class="ikey">Team</span><span class="ival">${u.dept}</span></div>
      <div class="irow"><span class="ikey">Designation</span><span class="ival">${u.desg}</span></div>
      <div class="irow"><span class="ikey">Date of Joining</span><span class="ival">${formatDate(u.doj)}</span></div>
      <div class="irow"><span class="ikey">Reporting To</span><span class="ival">${u.manager}</span></div>
      <div class="irow"><span class="ikey">Office Email</span><span class="ival">${u.email}</span></div>
      <div class="irow"><span class="ikey">Personal Phone</span><span class="ival">${u.phone}</span></div>
      <div class="irow"><span class="ikey">Date of Birth</span><span class="ival">${formatDate(emp?.dob)||'â€”'}</span></div>
      <div class="irow"><span class="ikey">CNIC</span><span class="ival">${emp?.cnic||'â€”'}</span></div>
      <div class="irow"><span class="ikey">Address</span><span class="ival">${emp?.address||'â€”'}</span></div>
    </div>
    <div class="card">
      <div class="card-title" style="margin-bottom:13px;">Employment Details</div>
      <div class="irow"><span class="ikey">Employment Type</span><span class="ival">${emp?.type||'Permanent'}</span></div>
      <div class="irow"><span class="ikey">Shift</span><span class="ival">11:00 â€“ 20:00</span></div>
      <div class="irow"><span class="ikey">Working Days</span><span class="ival">Mon â€“ Fri</span></div>
      <div class="irow"><span class="ikey">Status</span><span class="ival">${statusBadge(emp?.status||'Active')}</span></div>
      <div class="irow"><span class="ikey">Notice Period</span><span class="ival">1 Month</span></div>
      <div class="irow"><span class="ikey">Blood Group</span><span class="ival">${emp?.blood||'â€”'}</span></div>
      <div class="card-title" style="margin:16px 0 10px;">Next of Kin</div>
      <div class="irow"><span class="ikey">Name</span><span class="ival">${emp?.kin||'â€”'}</span></div>
      <div class="irow"><span class="ikey">Relationship</span><span class="ival">${emp?.kinRel||'â€”'}</span></div>
      <div class="irow"><span class="ikey">Contact</span><span class="ival">${emp?.kinPhone||'â€”'}</span></div>
    </div>
  </div>`;
}

function profileValue(value, fallback='-'){
  return escapeHtml(value===undefined || value===null || value==='' ? fallback : value);
}

function renderAvatarDisplay(person, sizeClass='av av-64', style=''){
  const finalStyle = style ? `${style}${style.trim().endsWith(';') ? '' : ';'}` : '';
  if(person?.profilePhotoUrl){
    return `<div class="${sizeClass}" style="${finalStyle}overflow:hidden;padding:0;"><img src="${person.profilePhotoUrl}" alt="Profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"></div>`;
  }

  return `<div class="${sizeClass}" style="${finalStyle}">${person?.avatar || ''}</div>`;
}

function profileInfoRow(label, value, formatter=null){
  const display = formatter ? formatter(value) : profileValue(value);
  return `<div class="pp-info-row"><div class="label">${escapeHtml(label)}</div><div class="value">${display}</div></div>`;
}

function profileMoney(amount){
  return `PKR ${Number(amount||0).toLocaleString()}`;
}

function confidentialValue(value){
  return `<span class="confidential-value" tabindex="0">${value}</span>`;
}

function profileLeaveCardsForEmployee(employee){
  const employeeLeaves = (DB.leaves||[]).filter(l => l.empId===employee.id);
  const liveBalances = employee.id===DB.currentUser?.id ? getLeaveBalancesList() : [];
  if(liveBalances.length){
    return liveBalances.slice(0,4).map(item => ({
      name: item.name,
      allocated: Number(item.allocated||0),
      used: Number(item.used||0),
      remaining: Number(item.remaining||0),
    }));
  }

  return (DB.leavePolicies||[])
    .slice(0,4)
    .map(policy => {
      const used = employeeLeaves
        .filter(leave => leave.status==='Approved' && leave.type===policy.name)
        .reduce((sum, leave) => sum + Number(leave.days||0), 0);
      const allocated = Number(policy.quota_days||0);
      return {
        name: policy.name,
        allocated,
        used,
        remaining: Math.max(allocated-used, 0),
      };
    });
}

function profileLeaveHistory(employee){
  return (DB.leaves||[])
    .filter(l => l.empId===employee.id)
    .sort((a,b)=>String(b.applied||b.from).localeCompare(String(a.applied||a.from)))
    .slice(0,8);
}

function profileAttendanceRows(employee){
  return (DB.attendance||[])
    .filter(a => a.empId===employee.id)
    .sort((a,b)=>String(b.date).localeCompare(String(a.date)))
    .slice(0,8);
}

function profileTimeline(employee){
  const items = [
    employee.doj ? {title:'Joined company', meta:`Started as ${profileValue(employee.desg)}`, date:employee.doj} : null,
    employee.confirmationDate ? {title:'Confirmation date', meta:'Moved to confirmed employment status', date:employee.confirmationDate} : null,
    employee.dop ? {title:'Probation review date', meta:'Probation milestone tracked by HR', date:employee.dop} : null,
    employee.lwd ? {title:'Last working date', meta:'Exit date recorded', date:employee.lwd} : null,
  ].filter(Boolean);

  const latestLeave = profileLeaveHistory(employee)[0];
  if(latestLeave){
    items.push({
      title:`${latestLeave.type} request ${String(latestLeave.status||'Pending').toLowerCase()}`,
      meta:`${formatDate(latestLeave.from)} to ${formatDate(latestLeave.to)} â€¢ ${formatLeaveDuration(latestLeave)}`,
      date: latestLeave.applied || latestLeave.from,
    });
  }

  return items
    .sort((a,b)=>String(b.date||'').localeCompare(String(a.date||'')))
    .slice(0,6);
}

function profileDocumentsCard(employee, canManageEmployeeDocs=false, selfLabel='Open Document'){
  const cnicDocument = employee.cnicDocumentUrl
    ? `<div class="pp-doc-actions"><a class="btn btn-sm btn-primary" href="${employee.cnicDocumentUrl}" target="_blank" rel="noopener">${selfLabel}</a>${canManageEmployeeDocs ? `<button class="btn btn-sm btn-danger" onclick="window.deleteEmployeeCnicDocument('${employee.id}')">Delete Document</button>` : ''}</div>`
    : `<div class="pp-mini-empty">No profile document has been uploaded yet.</div>`;
  const offboardingDocuments = Array.isArray(employee.offboardingDocuments) ? employee.offboardingDocuments : [];
  const offboardingRows = offboardingDocuments.length
    ? offboardingDocuments.map(doc => `<div class="pp-doc-actions" style="justify-content:space-between;">
        <div><strong>${escapeHtml(doc.title || doc.fileName || 'Offboarding document')}</strong><div class="meta">${escapeHtml(doc.fileName || '')}</div></div>
        <a class="btn btn-sm" href="${doc.url}" target="_blank" rel="noopener">Open</a>
      </div>`).join('')
    : `<div class="pp-mini-empty">No offboarding documents have been uploaded yet.</div>`;

  return `<div class="pp-doc-grid">
    <div class="pp-doc-card">
      <div class="panel-title">Profile Document</div>
      <div class="meta">${employee.cnicDocumentName || 'Employee document record'}</div>
      <div style="margin-top:10px;">${profileInfoRow('National ID', employee.cnic)}</div>
      ${cnicDocument}
    </div>
    <div class="pp-doc-card">
      <div class="panel-title">Passport</div>
      <div class="meta">Travel document information</div>
      <div style="margin-top:10px;">
        ${profileInfoRow('Passport No', employee.passportNo)}
        ${profileInfoRow('Personal Email', employee.personalEmail || employee.email)}
      </div>
    </div>
    <div class="pp-doc-card">
      <div class="panel-title">Offboarding Documents</div>
      <div class="meta">Exit documents and clearance records</div>
      <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px;">${offboardingRows}</div>
      ${canManageEmployeeDocs ? `<div style="margin-top:10px;"><button class="btn btn-sm btn-primary" onclick="window.openEditEmployee('${employee.id}')">Manage Documents</button></div>` : ''}
    </div>
  </div>`;
}

function profileTabsMarkup(employee, opts={}){
  const leaveCards = profileLeaveCardsForEmployee(employee);
  const leaveRows = profileLeaveHistory(employee);
  const attendanceRows = profileAttendanceRows(employee);
  const timelineRows = profileTimeline(employee);
  const customFields = Array.isArray(employee.customFields) ? employee.customFields.filter(field => field && field.label) : [];
  const customFieldTab = customFields.length ? [{
    id:'custom-fields',
    label:'Imported Fields',
    content:`<div class="pp-main-card" style="padding:0;border:none;box-shadow:none;background:transparent;"><div class="pp-info-grid">${customFields.map(field => profileInfoRow(field.label, field.value)).join('')}</div></div>`
  }] : [];

  return `<div class="pp-tab-shell">${buildTabs(opts.group || 'profileTabs',[
    {id:'leave',label:'Leave',content: leaveCards.length ? `<div class="pp-leave-grid">${leaveCards.map(card=>`<div class="pp-leave-card"><h4>${card.name}</h4><div class="pp-leave-stat"><span>Remaining</span><strong>${formatLeaveBalanceValue(card.remaining)}</strong></div><div class="pp-leave-stat"><span>Entitled</span><strong>${formatLeaveBalanceValue(card.allocated)}</strong></div><div class="pp-leave-stat"><span>Used</span><strong>${formatLeaveBalanceValue(card.used)}</strong></div></div>`).join('')}</div>` : `<div class="pp-mini-empty">No leave balances available yet.</div>`},
    {id:'leave-history',label:'Leave History',content: leaveRows.length ? `<div class="soft-table"><div class="table-wrap"><table><thead><tr><th>Applied</th><th>Type</th><th>From</th><th>To</th><th>Duration</th><th>Status</th></tr></thead><tbody>${leaveRows.map(row=>`<tr><td>${formatDate(row.applied || row.from)}</td><td>${row.type}</td><td>${formatDate(row.from)}</td><td>${formatDate(row.to)}</td><td>${formatLeaveDuration(row)}</td><td>${statusBadge(row.status)}</td></tr>`).join('')}</tbody></table></div></div>` : `<div class="pp-mini-empty">No leave history available.</div>`},
    {id:'attendance',label:'Attendance',content: attendanceRows.length ? `<div class="soft-table"><div class="table-wrap"><table><thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Status</th></tr></thead><tbody>${attendanceRows.map(a=>`<tr><td>${formatDate(a.date)}</td><td>${a.in||'-'}</td><td>${a.out||'-'}</td><td>${calcWorkHours(a)}</td><td>${statusBadge(a.late?'Late':a.status)}</td></tr>`).join('')}</tbody></table></div></div>` : `<div class="pp-mini-empty">No attendance records found.</div>`},
    {id:'dependent',label:'Dependent',content:`<div class="pp-main-card" style="padding:0;border:none;box-shadow:none;background:transparent;"><div class="pp-info-grid">${profileInfoRow('Next of Kin', employee.kin)}${profileInfoRow('Relationship', employee.kinRel)}${profileInfoRow('Contact Number', employee.kinPhone)}${profileInfoRow('Marital Status', employee.maritalStatus)}</div></div>`},
    {id:'timeline',label:'Timeline',content: timelineRows.length ? `<div class="pp-timeline">${timelineRows.map(item=>`<div class="pp-timeline-item"><div class="pp-timeline-dot"></div><div class="pp-timeline-copy"><div style="font-size:13px;font-weight:700;">${item.title}</div><div style="font-size:12px;color:var(--muted);margin-top:4px;">${item.meta}</div><div style="font-size:11px;color:var(--muted);margin-top:6px;">${formatDate(item.date)}</div></div></div>`).join('')}</div>` : `<div class="pp-mini-empty">Timeline items will appear here as HR updates are recorded.</div>`},
    {id:'documents',label:'Documents',content: profileDocumentsCard(employee, !!opts.canManageEmployeeDocs, opts.documentButtonLabel || 'Open Document')},
    ...customFieldTab,
    {id:'assets',label:'Assets',content:`<div class="pp-mini-empty">No company assets have been assigned yet.</div>`},
  ], opts.defaultTab || 'leave')}</div>`;
}

function renderProfileWorkspace(employee, options={}){
  const isSelf = !!options.isSelf;
  const canSeeSalary = !!options.canSeeSalary;
  const reportingTeam = (DB.employees||[]).filter(emp=>emp.manager===`${employee.fname} ${employee.lname}` && emp.id!==employee.id).slice(0,6);
  const gross = Number(employee.basic||0) + Number(employee.house||0) + Number(employee.transport||0);
  const totalDeductions = Number(employee.contribution||0) + Number(employee.otherDeductions||0) + Number(employee.tax||0);
  const net = gross - totalDeductions;

  const salaryCard = canSeeSalary ? `<div class="pp-main-card">
    <div class="pp-card-title"><h3>Salary</h3><span class="badge bg-red">${isSelf ? 'Personal' : 'Confidential'}</span></div>
    <div class="pp-info-grid">
      ${profileInfoRow('Pay Period', employee.payPeriod, value => confidentialValue(profileValue(value)))}
      ${profileInfoRow('Salary Start Date', employee.salaryStartDate, value => confidentialValue(formatDate(value)))}
      ${profileInfoRow('Base Salary', employee.basic, value => confidentialValue(profileMoney(value)))}
      ${profileInfoRow('Allowances', Number(employee.house||0) + Number(employee.transport||0), value => confidentialValue(profileMoney(value)))}
      ${profileInfoRow('Contributions', employee.contribution, value => confidentialValue(profileMoney(value)))}
      ${profileInfoRow('Other Deductions', employee.otherDeductions, value => confidentialValue(profileMoney(value)))}
      ${profileInfoRow('Tax', employee.tax, value => confidentialValue(profileMoney(value)))}
      ${profileInfoRow('Net Salary', net, amount => confidentialValue(`<span style="color:var(--green);">${profileMoney(amount)}</span>`))}
    </div>
  </div>` : '';

  const methodCard = canSeeSalary ? `<div class="pp-main-card">
    <div class="pp-card-title"><h3>Method</h3><span class="badge bg-blue">Bank</span></div>
    <div class="pp-info-grid">
      ${profileInfoRow('Bank Name', employee.bank, value => confidentialValue(profileValue(value)))}
      ${profileInfoRow('Account No', employee.acct, value => confidentialValue(profileValue(value)))}
      ${profileInfoRow('IBAN', employee.iban, value => confidentialValue(profileValue(value)))}
      ${profileInfoRow('Gross Salary', gross, value => confidentialValue(profileMoney(value)))}
    </div>
  </div>` : '';

  return `
  <div class="hero-panel" style="margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
      <div>
        <div class="hero-title">${escapeHtml(options.heroTitle || `${employee.fname} ${employee.lname}`)}</div>
        <div class="hero-sub">${escapeHtml(options.heroSub || 'A richer employee workspace with official records, profile identity, salary overview, reporting view, documents, and history in one place.')}</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        ${options.headerAction || ''}
      </div>
    </div>
  </div>
  <div class="pp-profile-shell">
    <div class="pp-summary-card">
      <div class="pp-cover"></div>
      <div class="pp-summary-body">
        <div class="pp-avatar-stage">${renderAvatarDisplay(employee, 'av av-64', `background:${employee.avatarColor}22;color:${employee.avatarColor};border:4px solid #fff;`)}</div>
        <div class="pp-name">${escapeHtml(employee.id || '-')} : ${escapeHtml(employee.fname)} ${escapeHtml(employee.lname)}</div>
        <div class="pp-role">${profileValue(employee.desg)}${employee.workLocation ? ` â€¢ ${employee.workLocation}` : ''}</div>
        <div class="pp-chipbar">${statusBadge(employee.status || 'Active')}<span class="badge bg-blue">${profileValue(employee.dept)}</span></div>
        <div class="pp-meta-list">
          <div class="pp-meta-row"><div class="pp-meta-key">Login ID</div><div class="pp-meta-val">${profileValue(employee.id)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Address</div><div class="pp-meta-val">${profileValue(employee.address)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Personal Email</div><div class="pp-meta-val">${profileValue(employee.personalEmail || employee.email)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Official Email</div><div class="pp-meta-val">${profileValue(employee.email)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">DOB</div><div class="pp-meta-val">${formatDate(employee.dob)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Marital Status</div><div class="pp-meta-val">${profileValue(employee.maritalStatus)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">National ID</div><div class="pp-meta-val">${profileValue(employee.cnic)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Passport No</div><div class="pp-meta-val">${profileValue(employee.passportNo)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Employee Code</div><div class="pp-meta-val">${profileValue(employee.id)}</div></div>
          <div class="pp-meta-row"><div class="pp-meta-key">Phone</div><div class="pp-meta-val">${profileValue(employee.phone)}</div></div>
        </div>
      </div>
    </div>
    <div class="pp-main-stack">
      <div class="pp-main-card">
        <div class="pp-card-title"><h3>Official</h3><span class="badge bg-green">Live Profile</span></div>
        <div class="pp-info-grid">
          ${profileInfoRow('Status', employee.status || 'Active', statusBadge)}
          ${profileInfoRow('Employment', employee.type)}
          ${profileInfoRow('Shift', employee.shiftName ? `${employee.shiftName} (${employee.shiftStart||'-'} - ${employee.shiftEnd||'-'})` : 'Not Assigned')}
          ${profileInfoRow('Hire Date', employee.doj, formatDate)}
          ${profileInfoRow('Joining Date', employee.doj, formatDate)}
          ${profileInfoRow('Confirmation Date', employee.confirmationDate, formatDate)}
          ${profileInfoRow('Work Location', employee.workLocation)}
          ${profileInfoRow('Designation', employee.desg)}
${profileInfoRow('Team', employee.dept)}
          ${profileInfoRow('Line Manager', employee.manager)}
        </div>
      </div>
      ${salaryCard}
      ${methodCard}
    </div>
    <div class="pp-side-stack">
      <div class="pp-side-card">
        <div class="pp-card-title"><h3>Reporting Team (${reportingTeam.length})</h3>${options.sideAction || ''}</div>
        ${reportingTeam.length ? `<div class="team-mini-list">${reportingTeam.map(member=>`<div class="team-mini-item"><div class="av av-32" style="background:${member.avatarColor}22;color:${member.avatarColor};">${member.avatar}</div><div style="flex:1;"><div style="font-size:13px;font-weight:700;">${member.fname} ${member.lname}</div><div style="font-size:11px;color:var(--muted);">${member.desg}</div></div><span class="badge bg-blue">${member.dept}</span></div>`).join('')}</div>` : `<div class="pp-reporting-empty">No team member</div>`}
      </div>
      ${profileTabsMarkup(employee, {
        group: options.tabGroup || 'profileTabs',
        canManageEmployeeDocs: options.canManageEmployeeDocs,
        documentButtonLabel: options.documentButtonLabel,
        defaultTab: 'leave',
      })}
    </div>
  </div>`;
}

function pageEmpProfileDetail(){
  const e=DB.employees.find(emp=>emp.id===window._viewEmpId)||DB.employees[0];
  return renderProfileWorkspace(e, {
    heroTitle: `${e.fname} ${e.lname}`,
    heroSub: `${profileValue(e.desg)} in ${profileValue(e.dept)} with a complete profile workspace for official records, salary visibility, reporting structure, leave history, attendance, documents, and lifecycle details.`,
    canSeeSalary: isSuperAdminRole(),
    canManageEmployeeDocs: isSuperAdminRole(),
    documentButtonLabel: 'Open Document',
    tabGroup: 'epdPlus',
    headerAction: `<button class="btn btn-sm" style="color:#fff;border-color:rgba(255,255,255,.25);background:rgba(255,255,255,.08);" onclick="window.showPage('employees')">Back to Directory</button>`,
    sideAction: isSuperAdminRole() && canModifyEmployee(e) ? `<button class="btn btn-sm" onclick="window.openEditEmployee('${e.id}')">Edit Profile</button>` : '',
  });
}

function pageEmpProfile(){
  const u=DB.currentUser;
  const emp=DB.employees.find(e=>e.id===u.id)||DB.employees.find(e=>e.email===u.email)||DB.employees[0]||{};
  const profile = Object.assign({}, emp, u, { id: u.id || emp.id, fname: u.fname || emp.fname, lname: u.lname || emp.lname });
  return `${renderEmployeeWorkspaceTabs('emp-profile')}${renderProfileWorkspace(profile, {
    heroTitle: 'My Profile',
    heroSub: 'A PayPeople-inspired profile workspace with all major employee identity, employment, salary, bank, leave, history, document, and lifecycle sections in one view.',
    canSeeSalary: true,
    canManageEmployeeDocs: false,
    documentButtonLabel: 'Open My Document',
    tabGroup: 'mePlus',
    headerAction: `<button class="btn btn-sm btn-primary" onclick="window.openAccountSettings()">Account Settings</button>`,
  })}`;
}

function pageEmpTeam(){
  const u=DB.currentUser;
  const teamMembers=DB.employees.filter(e=>e.dept===u.dept&&e.email!==u.email);
  const liveMap={};
  const teamTabs = renderEmployeeWorkspaceTabs('emp-team');
  DB.liveAttendance.forEach(l=>{ liveMap[l.name]={status:l.status,since:l.since}; });

  const cards=teamMembers.map(e=>{
    const lv=liveMap[e.fname+' '+e.lname]||{status:'not_checked_in',since:'-'};
    const dot={in:'md-in',break:'md-break',out:'md-out',leave:'md-leave',not_checked_in:'md-out'}[lv.status]||'md-out';
    const lbl={
      in:'Checked In at '+(lv.since||'-'),
      break:'On Break',
      out:'Clocked Out',
      leave:'On Leave',
      not_checked_in:'Not Checked In Today'
    }[lv.status] || 'Status unavailable';
    return`<div class="card" style="display:flex;align-items:center;gap:12px;">
      <div class="mon-dot ${dot}" style="width:10px;height:10px;"></div>
      <div class="av av-40" style="background:${e.avatarColor}22;color:${e.avatarColor};">${e.avatar}</div>
      <div style="flex:1;">
        <div style="font-weight:500;font-size:13px;">${e.fname} ${e.lname}</div>
        <div style="font-size:11px;color:var(--muted);">${e.desg}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;">${lbl}</div>
      </div>
    </div>`;
  }).join('');

  return `
  ${teamTabs}
  <div class="alert al-info"><span>â„¹ï¸</span><div>Showing basic team info only. Salary, bank, and confidential HR data is not visible here.</div></div>
  <div class="card" style="margin-bottom:14px;">
    <div class="card-hdr"><div class="card-title">My Team â€” ${u.dept}</div></div>
    <div class="irow"><span class="ikey">Team Lead</span><span class="ival">${DB.departments.find(d=>d.name===u.dept)?.head||'â€”'}</span></div>
    <div class="irow"><span class="ikey">Total Members</span><span class="ival">${DB.employees.filter(e=>e.dept===u.dept).length}</span></div>
    <div class="irow"><span class="ikey">Present Today</span><span class="ival" style="color:var(--green);">${DB.departments.find(d=>d.name===u.dept)?.present||'â€”'}</span></div>
  </div>
  <div class="g2">${cards||'<div class="card"><p style="color:var(--muted);">No other team members found.</p></div>'}</div>`;
}

let __liveAttendanceRefreshTimer = null;
let __employeeLeaveSyncTimer = null;

async function refreshLiveAttendanceSnapshot(pageId){
  try{
    const data = await wpApi('/api/attendance/live', {method:'GET', headers:{}});
    DB.liveAttendance = data.liveAttendance || [];
    if(window.__workpulseCurrentPage === pageId){
      if(pageId === 'dashboard'){
        if(document.activeElement && document.activeElement.id === 'dashboard-recent-search-input'){
          return;
        }
        return;
      }
      showPage(pageId);
    }
  }catch(e){}
}

function setupLiveAttendanceRefresh(pageId){
  if(__liveAttendanceRefreshTimer){
    clearInterval(__liveAttendanceRefreshTimer);
    __liveAttendanceRefreshTimer = null;
  }

  if(__employeeLeaveSyncTimer){
    clearInterval(__employeeLeaveSyncTimer);
    __employeeLeaveSyncTimer = null;
  }

  if(['dashboard','realtime','hr-dashboard'].includes(pageId)){
    __liveAttendanceRefreshTimer = setInterval(()=>{
      refreshLiveAttendanceSnapshot(pageId);
    }, 3000);
  }

  if(DB.currentRole === 'employee' && ['emp-dashboard','emp-leaves','emp-profile','emp-calendar','emp-announcements'].includes(pageId)){
    __employeeLeaveSyncTimer = setInterval(()=>{
      refreshEmployeeWorkspaceSnapshot(pageId);
    }, 10000);
  }
}

function pageRealtimeLive(){
  const realtimeFilters = window.__realtimeMonitorFilters || {status:'', team:'', search:''};
  const liveAttendance = Array.isArray(DB.liveAttendance) ? DB.liveAttendance : [];
  const inCount = liveAttendance.filter(l => l.status === 'in').length;
  const breakCount = liveAttendance.filter(l => l.status === 'break').length;
  const notCheckedInCount = liveAttendance.filter(l => l.status === 'not_checked_in').length;
  const leaveCount = liveAttendance.filter(l => l.status === 'leave').length;
  const teamOptions = getRealtimeMonitorTeamOptions(realtimeFilters.team);
  const cards = liveAttendance.map(e=>{
    const dot = {in:'md-in',break:'md-break',out:'md-out',leave:'md-leave',not_checked_in:'md-out'}[e.status] || 'md-out';
    const lbl = {
      in:`Checked In at ${e.clockIn || e.since || '-'}`,
      break:`On Break - Checked In at ${e.clockIn || e.since || '-'}`,
      out:`Clocked Out at ${e.clockOut || e.since || '-'}`,
      leave:`On Leave - ${e.since || 'Approved Leave'}`,
      not_checked_in:'Not Checked In Today'
    }[e.status] || 'Status unavailable';

    return `<div class="mon-card" data-status="${e.status || 'out'}" data-team="${e.dept || ''}"><div class="mon-dot ${dot}"></div>
      <div style="min-width:0;"><div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${e.name}</div>
      <div style="font-size:11px;color:var(--muted);">${e.dept} - ${lbl}</div></div></div>`;
  }).join('');

  return `
  <div class="g4" style="margin-bottom:14px;">
    <div class="stat-card"><div class="stat-label"><span class="live-dot" style="margin-right:4px;"></span>In Office</div><div class="stat-val" style="color:var(--green);">${inCount}</div></div>
    <div class="stat-card"><div class="stat-label">On Break</div><div class="stat-val" style="color:var(--amber);">${breakCount}</div></div>
    <div class="stat-card"><div class="stat-label">Not Checked In</div><div class="stat-val" style="color:var(--red);">${notCheckedInCount}</div></div>
    <div class="stat-card"><div class="stat-label">On Leave</div><div class="stat-val" style="color:var(--purple);">${leaveCount}</div></div>
  </div>
  <div class="card">
    <div class="card-hdr">
      <div class="card-title"><span class="live-dot" style="margin-right:6px;"></span>Live Employee Status</div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <select class="fi" id="rt-status-filter" onchange="filterMonitor()" style="width:180px;">
          <option value="" ${!realtimeFilters.status ? 'selected' : ''}>All Statuses</option>
          <option value="in" ${realtimeFilters.status === 'in' ? 'selected' : ''}>Clocked In</option>
          <option value="break" ${realtimeFilters.status === 'break' ? 'selected' : ''}>On Break</option>
          <option value="out" ${realtimeFilters.status === 'out' ? 'selected' : ''}>Clocked Out</option>
          <option value="not_checked_in" ${realtimeFilters.status === 'not_checked_in' ? 'selected' : ''}>Not Checked In</option>
          <option value="leave" ${realtimeFilters.status === 'leave' ? 'selected' : ''}>On Leave</option>
        </select>
        <select class="fi" id="rt-team-filter" onchange="filterMonitor()" style="width:180px;">
          <option value="" ${!realtimeFilters.team ? 'selected' : ''}>All Teams</option>
          ${teamOptions.map(team => `<option value="${team}" ${realtimeFilters.team === team ? 'selected' : ''}>${team}</option>`).join('')}
        </select>
        <input class="search-input" id="rt-search" placeholder="Search..." value="${realtimeFilters.search || ''}" oninput="filterMonitor()" style="width:200px;">
        <button class="btn btn-sm" onclick="window.wpReload().then(() => window.showPage('realtime'))">Refresh</button>
      </div>
    </div>
    <div class="monitor-grid" id="monitor-grid">${cards || '<div style="color:var(--muted);font-size:13px;">No live attendance data available.</div>'}</div>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-in" style="flex-shrink:0;"></div>In Office</div>
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-break" style="flex-shrink:0;"></div>On Break</div>
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-out" style="flex-shrink:0;"></div>Not Checked In / Out</div>
      <div style="display:flex;align-items:center;gap:5px;font-size:12px;"><div class="mon-dot md-leave" style="flex-shrink:0;"></div>On Leave</div>
    </div>
  </div>`;
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
