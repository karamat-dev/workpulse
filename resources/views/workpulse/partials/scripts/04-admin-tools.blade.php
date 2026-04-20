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
    if(typeof syncDepartmentOptions === 'function') syncDepartmentOptions('ee-dept', e.dept||'Engineering');
    if(typeof syncShiftOptions === 'function') syncShiftOptions('ee-shift', e.shiftId||'');
    document.getElementById('ee-dept').value = e.dept||'Engineering';
    document.getElementById('ee-desg').value = e.desg||'';
    document.getElementById('ee-doj').value = e.doj||'';
    document.getElementById('ee-dop').value = e.dop||'';
    document.getElementById('ee-lwd').value = e.lwd||'';
    document.getElementById('ee-type').value = e.type||'Permanent';
    document.getElementById('ee-status').value = e.status||'Active';
    document.getElementById('ee-manager').value = e.manager==='-' ? '' : (e.manager||'');
    document.getElementById('ee-shift').value = e.shiftId||'';
    document.getElementById('ee-email').value = e.email||'';
    document.getElementById('ee-password').value = '';
    document.getElementById('ee-cnic-document').value = '';
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
  const formData = new FormData();
  formData.append('fname', document.getElementById('ee-fname').value.trim());
  formData.append('lname', document.getElementById('ee-lname').value.trim());
  if(document.getElementById('ee-dob').value) formData.append('dob', document.getElementById('ee-dob').value);
  formData.append('gender', document.getElementById('ee-gender').value);
  if(document.getElementById('ee-cnic').value.trim()) formData.append('cnic', document.getElementById('ee-cnic').value.trim());
  if(document.getElementById('ee-phone').value.trim()) formData.append('phone', document.getElementById('ee-phone').value.trim());
  if(document.getElementById('ee-address').value.trim()) formData.append('address', document.getElementById('ee-address').value.trim());
  formData.append('blood', document.getElementById('ee-blood').value);
  if(document.getElementById('ee-kin').value.trim()) formData.append('kin', document.getElementById('ee-kin').value.trim());
  if(document.getElementById('ee-kinRel').value.trim()) formData.append('kinRel', document.getElementById('ee-kinRel').value.trim());
  if(document.getElementById('ee-kinPhone').value.trim()) formData.append('kinPhone', document.getElementById('ee-kinPhone').value.trim());
  formData.append('dept', document.getElementById('ee-dept').value);
  formData.append('desg', document.getElementById('ee-desg').value.trim());
  formData.append('doj', document.getElementById('ee-doj').value);
  if(document.getElementById('ee-dop').value) formData.append('dop', document.getElementById('ee-dop').value);
  if(document.getElementById('ee-lwd').value) formData.append('lwd', document.getElementById('ee-lwd').value);
  formData.append('type', document.getElementById('ee-type').value);
  formData.append('status', document.getElementById('ee-status').value);
  if(document.getElementById('ee-manager').value.trim()) formData.append('manager', document.getElementById('ee-manager').value.trim());
  formData.append('shift_id', document.getElementById('ee-shift').value || '');
  formData.append('email', document.getElementById('ee-email').value.trim());
  if(document.getElementById('ee-password').value) formData.append('password', document.getElementById('ee-password').value);
  formData.append('basic', parseInt(document.getElementById('ee-basic').value)||0);
  formData.append('house', parseInt(document.getElementById('ee-house').value)||0);
  formData.append('transport', parseInt(document.getElementById('ee-transport').value)||0);
  formData.append('tax', parseInt(document.getElementById('ee-tax').value)||0);
  if(document.getElementById('ee-bank').value.trim()) formData.append('bank', document.getElementById('ee-bank').value.trim());
  if(document.getElementById('ee-acct').value.trim()) formData.append('acct', document.getElementById('ee-acct').value.trim());
  if(document.getElementById('ee-iban').value.trim()) formData.append('iban', document.getElementById('ee-iban').value.trim());
  const cnicDocument = document.getElementById('ee-cnic-document').files?.[0];
  if(cnicDocument) formData.append('cnic_document', cnicDocument);
  formData.append('_method', 'PATCH');

  try{
    await wpApi('/api/employees/'+encodeURIComponent(_editEmpId), {
      method:'POST',
      body: formData
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
function openAccountSettings(){
  const errEl = document.getElementById('acc-err');
  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  const emailEl = document.getElementById('acc-email');
  if(emailEl) emailEl.value = DB.currentUser?.email || '';

  ['acc-current-password','acc-new-password','acc-confirm-password'].forEach(id=>{
    const el = document.getElementById(id);
    if(el) el.value = '';
  });

  openModal('accountSettingsModal');
}

async function submitAccountSettings(){
  const email = document.getElementById('acc-email')?.value?.trim() || '';
  const currentPassword = document.getElementById('acc-current-password')?.value || '';
  const newPassword = document.getElementById('acc-new-password')?.value || '';
  const confirmPassword = document.getElementById('acc-confirm-password')?.value || '';
  const errEl = document.getElementById('acc-err');

  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  if(!email || !currentPassword){
    if(errEl){
      errEl.textContent = 'Email and current password are required.';
      errEl.style.display = 'block';
    }
    return;
  }

  if(newPassword && newPassword.length < 8){
    if(errEl){
      errEl.textContent = 'New password must be at least 8 characters.';
      errEl.style.display = 'block';
    }
    return;
  }

  if(newPassword !== confirmPassword){
    if(errEl){
      errEl.textContent = 'New password and confirmation do not match.';
      errEl.style.display = 'block';
    }
    return;
  }

  try{
    await wpApi('/api/me/account', {
      method:'PATCH',
      body: JSON.stringify({
        email,
        current_password: currentPassword,
        password: newPassword || null,
        password_confirmation: confirmPassword || null
      })
    });
    await wpReload();
    closeModal('accountSettingsModal');
    showToast('Account settings updated.','green');
    if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
  }catch(e){
    if(errEl){
      errEl.textContent = e?.message || 'Account update failed.';
      errEl.style.display = 'block';
    }
  }
}

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

function openCreateLeaveType(){
  const errEl = document.getElementById('lt-err');
  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  document.getElementById('lt-modal-title').textContent = 'Add Leave Type';
  document.getElementById('lt-original-code').value = '';
  document.getElementById('lt-name').value = '';
  document.getElementById('lt-code').value = '';
  document.getElementById('lt-paid').value = '1';
  openModal('leaveTypeModal');
}

function openEditLeaveType(code){
  const type = getLeaveTypesList().find(item => item.code === code);
  if(!type){
    showToast('Leave type not found.','red');
    return;
  }

  const errEl = document.getElementById('lt-err');
  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  document.getElementById('lt-modal-title').textContent = 'Edit Leave Type';
  document.getElementById('lt-original-code').value = type.code || '';
  document.getElementById('lt-name').value = type.name || '';
  document.getElementById('lt-code').value = type.code || '';
  document.getElementById('lt-paid').value = type.paid ? '1' : '0';
  openModal('leaveTypeModal');
}

async function saveLeaveType(){
  const originalCode = document.getElementById('lt-original-code')?.value || '';
  const name = document.getElementById('lt-name')?.value?.trim() || '';
  const code = document.getElementById('lt-code')?.value?.trim() || '';
  const paid = document.getElementById('lt-paid')?.value === '1';
  const errEl = document.getElementById('lt-err');

  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  if(!name){
    if(errEl){
      errEl.textContent = 'Leave type name is required.';
      errEl.style.display = 'block';
    }
    return;
  }

  const payload = {
    name,
    code: code || null,
    paid,
  };

  try{
    await wpApi(originalCode ? '/api/leave/types/'+encodeURIComponent(originalCode) : '/api/leave/types', {
      method: originalCode ? 'PATCH' : 'POST',
      body: JSON.stringify(payload)
    });
    await wpReload();
    closeModal('leaveTypeModal');
    showToast(`Leave type ${originalCode ? 'updated' : 'created'} successfully!`,'green');
    if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
  }catch(e){
    if(errEl){
      errEl.textContent = e?.message || 'Unable to save leave type.';
      errEl.style.display = 'block';
    }
  }
}

function deleteLeaveType(code){
  showConfirm('Delete Leave Type', 'This will remove the leave type only if it has never been used in requests, balances, or policies.', '⚠️', async function(){
    try{
      await wpApi('/api/leave/types/'+encodeURIComponent(code), {method:'DELETE'});
      await wpReload();
      showToast('Leave type deleted.','green');
      if(document.getElementById('page-title').textContent==='Leave Management') showPage('leave');
    }catch(e){
      showToast(e?.message || 'Unable to delete leave type.','red');
    }
  });
}

function deleteEmployeeCnicDocument(employeeCode){
  showConfirm('Delete CNIC Document', 'This will remove the uploaded CNIC document from the employee profile. You can upload a new one later.', '⚠️', async function(){
    try{
      await wpApi('/api/employees/'+encodeURIComponent(employeeCode)+'/cnic-document', {method:'DELETE'});
      await wpReload();
      showToast('CNIC document deleted.','green');
      const title = document.getElementById('page-title').textContent;
      if(title==='Employee Profile') showPage('emp-profile-detail');
      if(title==='Employees') showPage('employees');
    }catch(e){
      showToast(e?.message || 'Unable to delete CNIC document.','red');
    }
  });
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

function exportTransferData(){
  window.location.href = '/api/transfer/export';
  showToast('Transfer data package downloaded.','green');
}

function exportEmployeeProfilesJson(){
  window.location.href = '/api/transfer/employees/export';
  showToast('Employee profile export started.','green');
}

function importEmployeeProfiles(){
  const input = document.getElementById('transfer-import-file');
  if(!input) return;
  input.value = '';
  input.onchange = async function(){
    const file = input.files?.[0];
    if(!file) return;
    const formData = new FormData();
    formData.append('file', file);
    try{
      const data = await wpApi('/api/transfer/employees/import', {method:'POST', body: formData});
      await wpReload();
      showToast(`Imported ${data.imported || 0} employee profile(s).`,'green');
      if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
    }catch(e){
      showToast('Backend error: '+(e?.message||'Failed'),'red');
    }
  };
  input.click();
}

function openCreateShift(){
  document.getElementById('shift-modal-title').textContent = 'Add Standard Shift';
  document.getElementById('shift-id').value = '';
  document.getElementById('shift-name').value = '';
  document.getElementById('shift-code').value = '';
  document.getElementById('shift-start').value = '11:00';
  document.getElementById('shift-end').value = '20:00';
  document.getElementById('shift-grace').value = '10';
  document.getElementById('shift-days').value = 'Mon-Fri';
  document.getElementById('shift-active').value = '1';
  openModal('shiftModal');
}

function openEditShift(shiftId){
  const shift = (DB.shifts||[]).find(item => String(item.id) === String(shiftId));
  if(!shift){
    showToast('Shift not found.','red');
    return;
  }

  document.getElementById('shift-modal-title').textContent = 'Edit Standard Shift';
  document.getElementById('shift-id').value = shift.id;
  document.getElementById('shift-name').value = shift.name || '';
  document.getElementById('shift-code').value = shift.code || '';
  document.getElementById('shift-start').value = shift.start || '11:00';
  document.getElementById('shift-end').value = shift.end || '20:00';
  document.getElementById('shift-grace').value = shift.grace ?? 10;
  document.getElementById('shift-days').value = shift.workingDays || 'Mon-Fri';
  document.getElementById('shift-active').value = shift.active ? '1' : '0';
  openModal('shiftModal');
}

async function saveShift(){
  const shiftId = document.getElementById('shift-id').value;
  const payload = {
    name: document.getElementById('shift-name').value.trim(),
    code: document.getElementById('shift-code').value.trim() || null,
    start: document.getElementById('shift-start').value,
    end: document.getElementById('shift-end').value,
    grace: parseInt(document.getElementById('shift-grace').value, 10) || 0,
    workingDays: document.getElementById('shift-days').value.trim(),
    active: document.getElementById('shift-active').value === '1',
  };

  if(!payload.name || !payload.start || !payload.end){
    showToast('Shift name, start time, and end time are required.','red');
    return;
  }

  try{
    await wpApi(shiftId ? `/api/shifts/${encodeURIComponent(shiftId)}` : '/api/shifts', {
      method: shiftId ? 'PATCH' : 'POST',
      body: JSON.stringify(payload)
    });
    await wpReload();
    closeModal('shiftModal');
    showToast(`Shift ${shiftId ? 'updated' : 'created'} successfully.`,'green');
    if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
  }catch(e){
    showToast('Backend error: '+(e?.message||'Failed'),'red');
  }
}

function deleteShift(shiftId){
  showConfirm('Delete Shift', 'This will remove the standard shift and unassign it from employees currently using it.', '⚠️', async function(){
    try{
      await wpApi(`/api/shifts/${encodeURIComponent(shiftId)}`, {method:'DELETE'});
      await wpReload();
      showToast('Shift deleted.','green');
      if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
    }catch(e){
      showToast('Backend error: '+(e?.message||'Failed'),'red');
    }
  });
}

function printPage(){
  window.print();
}

// ══════════════════════════════════════════════════
