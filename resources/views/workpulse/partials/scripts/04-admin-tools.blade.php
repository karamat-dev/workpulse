//  EDIT EMPLOYEE
// ══════════════════════════════════════════════════
let _editEmpId = null;
let _editTab = 'personal';

async function openEditEmployee(id){
  _editEmpId = id;
  _editTab = 'personal';
  try{
    const data = await wpApi('/api/employees/'+encodeURIComponent(id), {method:'GET'});
    const e = data.employee;
    if(!e) return;
  // Personal
    document.getElementById('ee-fname').value = e.fname||'';
    document.getElementById('ee-lname').value = e.lname||'';
    document.getElementById('ee-dob').value = e.dob||'';
    document.getElementById('ee-gender').value = e.gender||'Male';
    document.getElementById('ee-cnic').value = e.cnic||'';
    document.getElementById('ee-phone').value = e.phone||'';
    document.getElementById('ee-address').value = e.address||'';
    document.getElementById('ee-blood').value = e.blood||'O+';
    document.getElementById('ee-kin').value = e.kin||'';
    document.getElementById('ee-kinRel').value = e.kinRel||'';
    document.getElementById('ee-kinPhone').value = e.kinPhone||'';
  // Job
    document.getElementById('ee-dept').value = e.dept||'Engineering';
    document.getElementById('ee-desg').value = e.desg||'';
    document.getElementById('ee-doj').value = e.doj||'';
    document.getElementById('ee-dop').value = e.dop||'';
    document.getElementById('ee-lwd').value = e.lwd||'';
    document.getElementById('ee-type').value = e.type||'Permanent';
    document.getElementById('ee-status').value = e.status||'Active';
    document.getElementById('ee-manager').value = e.manager==='-' ? '' : (e.manager||'');
    document.getElementById('ee-email').value = e.email||'';
  // Salary
    document.getElementById('ee-basic').value = e.basic||0;
    document.getElementById('ee-house').value = e.house||0;
    document.getElementById('ee-transport').value = e.transport||0;
    document.getElementById('ee-tax').value = e.tax||0;
    document.getElementById('ee-bank').value = e.bank||'';
    document.getElementById('ee-acct').value = e.acct||'';
    document.getElementById('ee-iban').value = e.iban||'';
    calcGross();
    switchEditTab('personal');
    openModal('editEmpModal');
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

function switchEditTab(tab){
  _editTab = tab;
  ['personal','job','salary'].forEach(t=>{
    const el = document.getElementById('edit-tab-'+t);
    if(el) el.style.display = t===tab?'block':'none';
  });
  document.querySelectorAll('#edit-emp-tabs .tab').forEach((el,i)=>{
    const tabs = ['personal','job','salary'];
    el.classList.toggle('active', tabs[i]===tab);
  });
}

function calcGross(){
  const b = parseInt(document.getElementById('ee-basic')?.value)||0;
  const h = parseInt(document.getElementById('ee-house')?.value)||0;
  const t = parseInt(document.getElementById('ee-transport')?.value)||0;
  const tx = parseInt(document.getElementById('ee-tax')?.value)||0;
  const gross = b+h+t;
  const net = gross-tx;
  const gd = document.getElementById('ee-gross-display');
  const nd = document.getElementById('ee-net-display');
  if(gd) gd.textContent = 'PKR '+gross.toLocaleString();
  if(nd) nd.textContent = 'PKR '+net.toLocaleString();
}

async function saveEditEmployee(){
  if(!_editEmpId) return;
  const payload = {
    fname: document.getElementById('ee-fname').value.trim(),
    lname: document.getElementById('ee-lname').value.trim(),
    dob: document.getElementById('ee-dob').value || null,
    gender: document.getElementById('ee-gender').value,
    cnic: document.getElementById('ee-cnic').value.trim(),
    phone: document.getElementById('ee-phone').value.trim(),
    address: document.getElementById('ee-address').value.trim(),
    blood: document.getElementById('ee-blood').value,
    kin: document.getElementById('ee-kin').value.trim(),
    kinRel: document.getElementById('ee-kinRel').value.trim(),
    kinPhone: document.getElementById('ee-kinPhone').value.trim(),
    dept: document.getElementById('ee-dept').value,
    desg: document.getElementById('ee-desg').value.trim(),
    doj: document.getElementById('ee-doj').value,
    dop: document.getElementById('ee-dop').value || null,
    lwd: document.getElementById('ee-lwd').value || null,
    type: document.getElementById('ee-type').value,
    status: document.getElementById('ee-status').value,
    manager: document.getElementById('ee-manager').value.trim(),
    email: document.getElementById('ee-email').value.trim(),
    basic: parseInt(document.getElementById('ee-basic').value)||0,
    house: parseInt(document.getElementById('ee-house').value)||0,
    transport: parseInt(document.getElementById('ee-transport').value)||0,
    tax: parseInt(document.getElementById('ee-tax').value)||0,
    bank: document.getElementById('ee-bank').value.trim(),
    acct: document.getElementById('ee-acct').value.trim(),
    iban: document.getElementById('ee-iban').value.trim(),
  };

  try{
    await wpApi('/api/employees/'+encodeURIComponent(_editEmpId), {
      method:'PATCH',
      body: JSON.stringify(payload)
    });
    await wpReload();
    closeModal('editEmpModal');
    showToast('Employee profile updated!','green');
    const title = document.getElementById('page-title').textContent;
    if(title==='Employees') showPage('employees');
    else if(title==='Employee Profile') showPage('emp-profile-detail');
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

// ══════════════════════════════════════════════════
//  EDIT LEAVE BALANCE (Admin)
// ══════════════════════════════════════════════════
let _editLeaveEmpId = null;
let _editLeaveBalancesSnapshot = {};

function renderLeaveBalanceEditorRows(balances){
  const container = document.getElementById('edit-leave-balance-grid');
  if(!container) return;

  const rows = Object.entries(balances);
  container.innerHTML = rows.length
    ? rows.map(([code, item]) => `
      <div class="fg">
        <label class="fl">${item.name || code}</label>
        <input
          type="number"
          step="0.5"
          class="fi"
          id="el-${code}"
          data-leave-code="${code}"
          value="${item.value ?? 0}"
          min="-365"
          max="365"
        >
      </div>
    `).join('')
    : '<div style="font-size:12px;color:var(--muted);">No leave types configured.</div>';
}

async function openEditLeave(empId){
  if(!empId){
    showToast('Please select an employee first.','amber');
    return;
  }
  _editLeaveEmpId = empId;
  const e = DB.employees.find(emp=>emp.id===empId);
  const types = (typeof getLeaveTypesList === 'function') ? getLeaveTypesList() : [];
  const defaults = {};
  types.forEach(type => {
    defaults[type.code] = {name: type.name, value: 0};
  });

  try{
    const data = await wpApi('/api/leave/balances/'+encodeURIComponent(empId), {method:'GET'});
    const balances = {...defaults};
    (data.balances || []).forEach(balance => {
      balances[balance.code] = {
        name: balance.name || balance.code,
        value: Number(balance.allocated ?? 0),
      };
    });
    _editLeaveBalancesSnapshot = balances;
    const nameEl = document.getElementById('edit-leave-employee-name');
    if(nameEl && e) nameEl.textContent = 'Editing leave balance for: '+e.fname+' '+e.lname;
    const modeEl = document.getElementById('el-mode');
    if(modeEl) modeEl.value = 'absolute';
    renderLeaveBalanceEditorRows(balances);
    openModal('editLeaveModal');
  }catch(err){
    showToast('Backend error: '+(err?.message||'Failed'),'red');
  }
}

async function saveLeaveBalance(){
  if(!_editLeaveEmpId) return;
  const mode = document.getElementById('el-mode')?.value || 'absolute';
  const inputs = Array.from(document.querySelectorAll('#edit-leave-balance-grid input[data-leave-code]'));
  const balances = {};

  inputs.forEach(input => {
    const code = input.getAttribute('data-leave-code');
    const rawValue = parseFloat(input.value);
    const value = Number.isFinite(rawValue) ? rawValue : 0;

    if(mode === 'adjust'){
      const base = Number(_editLeaveBalancesSnapshot?.[code]?.value ?? 0);
      balances[code] = value - base;
      return;
    }

    balances[code] = Math.max(0, value);
  });

  const payload = {mode, balances};

  try{
    await wpApi('/api/leave/balances/'+encodeURIComponent(_editLeaveEmpId), {
      method:'PUT',
      body: JSON.stringify(payload)
    });
    await wpReload();
    closeModal('editLeaveModal');
    showToast('Leave balance updated!','green');
    if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

function renderLeavePolicyEditorRows(policies){
  const container = document.getElementById('edit-leave-policy-grid');
  if(!container) return;

  const rows = policies.map(policy => `
    <div class="fg">
      <label class="fl">${policy.name}</label>
      <input type="hidden" data-policy-code="${policy.code}">
    </div>
    <div class="fg">
      <label class="fl">Quota</label>
      <input type="number" step="0.5" class="fi" id="lp-quota-${policy.code}" min="0" value="${Number(policy.quota_days ?? 0)}">
    </div>
    <div class="fg">
      <label class="fl">Carry Forward</label>
      <input type="number" step="0.5" class="fi" id="lp-carry-${policy.code}" min="0" value="${Number(policy.carry_forward_days ?? 0)}">
    </div>
    <div class="fg">
      <label class="fl">Pro-Rata</label>
      <select class="fi" id="lp-prorata-${policy.code}">
        <option value="1" ${policy.pro_rata ? 'selected' : ''}>Enabled</option>
        <option value="0" ${policy.pro_rata ? '' : 'selected'}>Disabled</option>
      </select>
    </div>
  `).join('');

  container.innerHTML = rows || '<div style="font-size:12px;color:var(--muted);">No leave policies available.</div>';
}

async function openEditLeavePolicy(){
  try{
    const year = new Date().getFullYear();
    const inputYear = document.getElementById('lp-year');
    if(inputYear) inputYear.value = year;

    const policies = (typeof getLeavePoliciesList === 'function') ? getLeavePoliciesList() : [];
    renderLeavePolicyEditorRows(policies);
    openModal('editLeavePolicyModal');
  }catch(err){
    showToast('Backend error: '+(err?.message||'Failed'),'red');
  }
}

async function saveLeavePolicy(){
  const year = parseInt(document.getElementById('lp-year')?.value, 10) || new Date().getFullYear();
  const policies = {};
  const codes = (typeof getLeavePoliciesList === 'function' ? getLeavePoliciesList() : []).map(policy => policy.code);

  codes.forEach(code => {
    const quota = parseFloat(document.getElementById('lp-quota-'+code)?.value);
    const carry = parseFloat(document.getElementById('lp-carry-'+code)?.value);
    const prorata = document.getElementById('lp-prorata-'+code)?.value === '1';

    policies[code] = {
      quota_days: Number.isFinite(quota) ? quota : 0,
      carry_forward_days: Number.isFinite(carry) ? carry : 0,
      pro_rata: prorata,
    };
  });

  try{
    await wpApi('/api/leave/policies', {
      method:'PUT',
      body: JSON.stringify({year, policies})
    });
    await wpReload();
    closeModal('editLeavePolicyModal');
    showToast('Leave policy updated!','green');
    if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
  }catch(err){
    showToast('Backend error: '+(err?.message||'Failed'),'red');
  }
}

// ══════════════════════════════════════════════════
//  CHANGE PASSWORD
// ══════════════════════════════════════════════════
function submitChangePassword(){
  const current  = document.getElementById('cp-current').value;
  const nw       = document.getElementById('cp-new').value;
  const confirm  = document.getElementById('cp-confirm').value;
  const errEl    = document.getElementById('cp-err');
  errEl.style.display='none';
  if(!DB.currentUser){ return; }
  if(nw.length < 8){ errEl.textContent='New password must be at least 8 characters.'; errEl.style.display='block'; return; }
  if(nw !== confirm){ errEl.textContent='Passwords do not match.'; errEl.style.display='block'; return; }

  wpApi('/password', {
    method: 'PUT',
    body: JSON.stringify({
      current_password: current,
      password: nw,
      password_confirmation: confirm
    })
  }).then(()=>{
    ['cp-current','cp-new','cp-confirm'].forEach(id=>{ const el=document.getElementById(id); if(el) el.value=''; });
    closeModal('changePassModal');
    showToast('Password changed successfully!','green');
  }).catch(e=>{
    errEl.textContent = 'Password update failed. Check current password.';
    errEl.style.display='block';
  });
}

// ══════════════════════════════════════════════════
//  CONFIRM DIALOG
// ══════════════════════════════════════════════════
function showConfirm(title, msg, icon, onConfirm){
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-msg').textContent = msg;
  document.getElementById('confirm-icon').textContent = icon||'⚠️';
  const btn = document.getElementById('confirm-ok-btn');
  btn.onclick = function(){ closeModal('confirmModal'); onConfirm(); };
  openModal('confirmModal');
}

// ══════════════════════════════════════════════════
//  EXPORT CSV (real working download)
// ══════════════════════════════════════════════════
function exportCSV(filename, rows, headers){
  const lines = [headers.join(',')];
  rows.forEach(row=>{
    lines.push(row.map(cell=>{
      const s = String(cell===null||cell===undefined?'':cell).replace(/"/g,'""');
      return s.includes(',')||s.includes('"')||s.includes('\n') ? '"'+s+'"' : s;
    }).join(','));
  });
  const blob = new Blob([lines.join('\n')], {type:'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href=url; a.download=filename; a.style.display='none';
  document.body.appendChild(a); a.click();
  document.body.removeChild(a); URL.revokeObjectURL(url);
  showToast('CSV downloaded: '+filename,'green');
}

function exportAttendanceCSV(){
  const headers = ['Employee ID','Name','Department','Date','Day','Clock In','Break Out','Break In','Clock Out','Hours','Overtime','Status'];
  const rows = DB.attendance.map(a=>{
    const emp = DB.employees.find(e=>e.id===a.empId)||{fname:'',lname:'',dept:''};
    return [a.empId, emp.fname+' '+emp.lname, emp.dept, a.date,
      new Date(a.date+'T00:00:00').toLocaleDateString('en-GB',{weekday:'short'}),
      a.in||'', a.breakOut||'', a.breakIn||'', a.out||'',
      calcWorkHours(a), a.overtime?'+'+a.overtime+'m':'', a.status];
  });
  exportCSV('attendance_report.csv', rows, headers);
}

function exportLeaveCSV(){
  const headers = ['Leave ID','Employee','Department','Type','From','To','Days','Reason','Applied','Manager Status','HR Status','Final Status'];
  const rows = DB.leaves.map(l=>[l.id,l.empName,l.dept,l.type,l.from,l.to,l.days,l.reason,l.applied,l.managerStatus,l.hrStatus,l.status]);
  exportCSV('leave_report.csv', rows, headers);
}

function exportEmployeeCSV(){
  const headers = ['ID','First Name','Last Name','Department','Designation','DOJ','Employment Type','Status','Email','Phone','Manager'];
  const rows = DB.employees.map(e=>[e.id,e.fname,e.lname,e.dept,e.desg,e.doj,e.type,e.status,e.email,e.phone,e.manager]);
  exportCSV('employee_data.csv', rows, headers);
}

function printPage(){
  window.print();
}

// ══════════════════════════════════════════════════
