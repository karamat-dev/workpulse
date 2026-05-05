//  EDIT EMPLOYEE
// ══════════════════════════════════════════════════
let _editEmpId = null;
let _editTab = 'personal';
let _editDepartmentName = null;
let _editEmpRecord = null;

function getManagerDirectoryOptions(excludeEmployeeCode=''){
  return (Array.isArray(DB.employees) ? DB.employees : [])
    .filter(employee => employee && employee.id !== excludeEmployeeCode)
    .slice()
    .sort((a,b) => `${a.fname || ''} ${a.lname || ''}`.localeCompare(`${b.fname || ''} ${b.lname || ''}`))
    .map(employee => {
      const name = `${employee.fname || ''} ${employee.lname || ''}`.trim();
      return {
        value: name,
        search: `${name} ${employee.id || ''} ${employee.dept || ''} ${employee.desg || ''} ${employee.email || ''}`.toLowerCase(),
        label: `${name} (${employee.id || '-'}) - ${employee.dept || '-'} - ${employee.desg || 'Employee'}`,
        meta: `${employee.id || '-'} - ${employee.dept || '-'} - ${employee.desg || 'Employee'}`
      };
    });
}

function escapeManagerPickerHtml(value=''){
  return String(value)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

function getManagerPickerElements(prefix){
  return {
    field: document.getElementById(`${prefix}-manager-field`),
    hidden: document.getElementById(`${prefix}-manager`),
    trigger: document.getElementById(`${prefix}-manager-trigger`),
    value: document.getElementById(`${prefix}-manager-value`),
    arrow: document.getElementById(`${prefix}-manager-arrow`),
    dropdown: document.getElementById(`${prefix}-manager-dropdown`),
    search: document.getElementById(`${prefix}-manager-search`),
    options: document.getElementById(`${prefix}-manager-options`),
    empty: document.getElementById(`${prefix}-manager-empty`),
  };
}

function setManagerPickerValue(prefix, selectedName=''){
  const els = getManagerPickerElements(prefix);
  if(!els.hidden || !els.value) return;

  const value = selectedName || '';
  els.hidden.value = value;
  els.value.textContent = value || 'Select reporting manager';
  els.value.classList.toggle('manager-placeholder', !value);
}

function buildManagerPickerOptions(options, selectedName=''){
  return options.map(option => `
    <button
      type="button"
      class="manager-option ${option.value === selectedName ? 'selected' : ''}"
      onclick="window.selectManagerPickerOption && window.selectManagerPickerOption('${escapeManagerPickerHtml(option.prefix)}', '${escapeManagerPickerHtml(option.value)}')"
    >
      <span class="manager-option-copy">
        <span class="manager-option-name">${escapeManagerPickerHtml(option.value || 'Unknown employee')}</span>
        <span class="manager-option-meta">${escapeManagerPickerHtml(option.meta || option.label || '')}</span>
      </span>
      <span class="manager-option-check">&#10003;</span>
    </button>
  `).join('');
}

function renderManagerPickerOptions(prefix, options, selectedName=''){
  const els = getManagerPickerElements(prefix);
  if(!els.options || !els.empty) return;

  if(!options.length){
    els.options.innerHTML = '';
    els.empty.style.display = 'block';
    return;
  }

  els.options.innerHTML = buildManagerPickerOptions(
    options.map(option => ({...option, prefix})),
    selectedName
  );
  els.empty.style.display = 'none';
}

function syncManagerPicker(prefix, selectedName='', excludeEmployeeCode=''){
  const els = getManagerPickerElements(prefix);
  if(!els.field || !els.hidden) return;

  const currentValue = selectedName || els.hidden.value || '';
  const excludeCode = excludeEmployeeCode || '';
  const term = (els.search?.value || '').trim().toLowerCase();
  const options = getManagerDirectoryOptions(excludeCode);
  const filtered = term ? options.filter(option => option.search.includes(term)) : options.slice();
  const selectedExists = currentValue && options.some(option => option.value === currentValue);

  if(currentValue && !selectedExists && (!term || currentValue.toLowerCase().includes(term))){
    filtered.unshift({
      value: currentValue,
      search: currentValue.toLowerCase(),
      label: currentValue,
      meta: 'Current selection'
    });
  }

  els.field.dataset.excludeEmployeeCode = excludeCode;
  setManagerPickerValue(prefix, currentValue);
  renderManagerPickerOptions(prefix, filtered, currentValue);
}

function syncNewEmployeeManagerOptions(selectedName=''){
  syncManagerPicker('ne', selectedName, '');
}

function syncEditEmployeeManagerOptions(selectedName='', excludeEmployeeCode=''){
  syncManagerPicker('ee', selectedName, excludeEmployeeCode);
}

function closeManagerPicker(prefix, keepSearchValue=false){
  const els = getManagerPickerElements(prefix);
  if(!els.dropdown || !els.trigger || !els.arrow) return;

  els.dropdown.classList.remove('open');
  els.trigger.classList.remove('open');
  els.arrow.classList.remove('up');

  if(!keepSearchValue && els.search){
    els.search.value = '';
  }

  syncManagerPicker(prefix, els.hidden?.value || '', els.field?.dataset.excludeEmployeeCode || '');
}

function openManagerPicker(prefix){
  ['ne','ee'].forEach(key => {
    if(key !== prefix) closeManagerPicker(key);
  });

  const els = getManagerPickerElements(prefix);
  if(!els.dropdown || !els.trigger || !els.arrow) return;

  syncManagerPicker(prefix, els.hidden?.value || '', els.field?.dataset.excludeEmployeeCode || '');
  els.dropdown.classList.add('open');
  els.trigger.classList.add('open');
  els.arrow.classList.add('up');

  window.setTimeout(() => {
    if(els.search) els.search.focus();
  }, 10);
}

function toggleManagerPicker(prefix){
  const els = getManagerPickerElements(prefix);
  if(!els.dropdown) return;
  if(els.dropdown.classList.contains('open')){
    closeManagerPicker(prefix);
    return;
  }
  openManagerPicker(prefix);
}

function filterManagerPickerOptions(prefix){
  const els = getManagerPickerElements(prefix);
  if(!els.field) return;
  syncManagerPicker(prefix, els.hidden?.value || '', els.field.dataset.excludeEmployeeCode || '');
}

function selectManagerPickerOption(prefix, value){
  setManagerPickerValue(prefix, value || '');
  closeManagerPicker(prefix);
}

if(!window.__managerPickerOutsideClickBound){
  document.addEventListener('click', function(event){
    ['ne','ee'].forEach(prefix => {
      const els = getManagerPickerElements(prefix);
      if(!els.field || els.field.contains(event.target)) return;
      closeManagerPicker(prefix);
    });
  });
  window.__managerPickerOutsideClickBound = true;
}

function syncEmployeeRoleOptions(selectId, selected='employee'){
  const select = document.getElementById(selectId);
  if(!select) return;

  const roles = [
    {value:'employee', label:'Employee'},
    ...(DB.currentRole === 'manager' ? [{value:'manager', label:'Super-Admin'}] : []),
    {value:'admin', label:'Admin'},
  ];
  const safeSelected = roles.some(role => role.value === selected) ? selected : 'employee';
  select.innerHTML = roles.map(role => `<option value="${role.value}">${role.label}</option>`).join('');
  select.value = safeSelected;
}

function openAddEmployeeWithRole(role='employee'){
  if(role === 'manager' && DB.currentRole !== 'manager'){
    showToast('Only a Super-Admin can create Super-Admin accounts','red');
    return;
  }

  openModal('addEmpModal');
  setTimeout(() => syncEmployeeRoleOptions('ne-role', role), 0);
}

async function openEditEmployee(id){
  const target = (DB.employees || []).find(employee => employee.id === id);
  if(target?.role === 'manager' && DB.currentRole !== 'manager'){
    showToast('Only a Super-Admin can change Super-Admin accounts','red');
    return;
  }
  _editEmpId = id;
  _editTab = 'personal';
  try{
    const data = await wpApi('/api/employees/'+encodeURIComponent(id), {method:'GET'});
    const e = data.employee;
    if(!e) return;
    _editEmpRecord = e;
  // Personal
    document.getElementById('ee-fname').value = e.fname||'';
    document.getElementById('ee-lname').value = e.lname||'';
    document.getElementById('ee-dob').value = e.dob||'';
    document.getElementById('ee-gender').value = e.gender||'Male';
    document.getElementById('ee-cnic').value = e.cnic||'';
    document.getElementById('ee-passport-no').value = e.passportNo||'';
    document.getElementById('ee-phone').value = e.phone||'';
    document.getElementById('ee-personal-email').value = e.personalEmail||'';
    document.getElementById('ee-address').value = e.address||'';
    document.getElementById('ee-blood').value = e.blood||'O+';
    document.getElementById('ee-marital-status').value = e.maritalStatus||'';
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
    document.getElementById('ee-confirmation-date').value = e.confirmationDate||'';
    document.getElementById('ee-type').value = e.type||'Permanent';
    syncEmployeeRoleOptions('ee-role', e.role||'employee');
    document.getElementById('ee-work-location').value = e.workLocation||'';
    const managerValue = e.manager==='-' ? '' : (e.manager||'');
    const editManagerSearch = document.getElementById('ee-manager-search');
    if(editManagerSearch) editManagerSearch.value = '';
    syncEditEmployeeManagerOptions(managerValue, e.id||'');
    document.getElementById('ee-shift').value = e.shiftId||'';
    document.getElementById('ee-email').value = e.email||'';
    document.getElementById('ee-password').value = '';
    document.getElementById('ee-cnic-document').value = '';
    const offboardingTitle = document.getElementById('ee-offboarding-doc-title');
    const offboardingFile = document.getElementById('ee-offboarding-doc-file');
    if(offboardingTitle) offboardingTitle.value = '';
    if(offboardingFile) offboardingFile.value = '';
    renderOffboardingDocuments(e);
  // Salary
    document.getElementById('ee-basic').value = e.basic||0;
    document.getElementById('ee-house').value = e.house||0;
    document.getElementById('ee-transport').value = e.transport||0;
    document.getElementById('ee-pay-period').value = e.payPeriod||'';
    document.getElementById('ee-salary-start-date').value = e.salaryStartDate||'';
    document.getElementById('ee-contribution').value = e.contribution||0;
    document.getElementById('ee-other-deductions').value = e.otherDeductions||0;
    document.getElementById('ee-tax').value = e.tax||0;
    document.getElementById('ee-bank').value = e.bank||'';
    document.getElementById('ee-acct').value = e.acct||'';
    document.getElementById('ee-iban').value = e.iban||'';
    calcGross();
    switchEditTab('personal');
    openModal('editEmpModal');
  }catch(e){
    showRequestError(e);
  }
}

function renderOffboardingDocuments(employee=null){
  const e = employee || _editEmpRecord || {};
  const docs = Array.isArray(e.offboardingDocuments) ? e.offboardingDocuments : [];
  const list = document.getElementById('ee-offboarding-documents');
  const completeBtn = document.getElementById('ee-offboarding-complete');
  if(!list) return;

  if(completeBtn){
    completeBtn.style.display = String(e.status || '') === 'Offboarding' ? '' : 'none';
    completeBtn.disabled = !docs.length;
  }

  list.innerHTML = docs.length ? docs.map(doc => `
    <div style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;border:1px solid var(--border);border-radius:8px;background:var(--surface);padding:8px;">
      <div style="min-width:0;">
        <input type="text" class="fi" id="offboarding-doc-title-${doc.id}" value="${escapeHtmlAttr(doc.title || '')}" placeholder="Document title" style="margin-bottom:6px;">
        <div style="font-size:12px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(doc.fileName || 'Uploaded document')}</div>
        <input type="file" class="fi" id="offboarding-doc-file-${doc.id}" style="margin-top:6px;">
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
        <a class="btn btn-sm" href="${doc.url}" target="_blank" rel="noopener">Open</a>
        <button type="button" class="btn btn-sm btn-primary" onclick="window.saveOffboardingDocument('${doc.id}')">Save</button>
        <button type="button" class="btn btn-sm btn-danger" onclick="window.deleteOffboardingDocument('${doc.id}')">Delete</button>
      </div>
    </div>
  `).join('') : `<div style="font-size:12px;color:var(--muted);">No offboarding documents uploaded yet.</div>`;
}

async function refreshEditedEmployee(){
  if(!_editEmpId) return null;
  const data = await wpApi('/api/employees/'+encodeURIComponent(_editEmpId), {method:'GET'});
  _editEmpRecord = data.employee || _editEmpRecord;
  renderOffboardingDocuments(_editEmpRecord);
  return _editEmpRecord;
}

async function uploadOffboardingDocument(){
  if(!_editEmpId) return;
  const fileInput = document.getElementById('ee-offboarding-doc-file');
  const titleInput = document.getElementById('ee-offboarding-doc-title');
  const file = fileInput?.files?.[0];
  if(!file){
    showToast('Choose an offboarding document first.','red');
    return;
  }

  const formData = new FormData();
  formData.append('document', file);
  formData.append('title', titleInput?.value?.trim() || '');

  try{
    await wpApi('/api/employees/'+encodeURIComponent(_editEmpId)+'/offboarding-documents', {
      method:'POST',
      body: formData
    });
    if(fileInput) fileInput.value = '';
    if(titleInput) titleInput.value = '';
    await wpReload();
    await refreshEditedEmployee();
    showToast('Offboarding document uploaded.','green');
  }catch(e){
    showToast(e?.message || 'Unable to upload offboarding document.','red');
  }
}

async function saveOffboardingDocument(documentId){
  if(!_editEmpId || !documentId) return;
  const titleInput = document.getElementById('offboarding-doc-title-'+documentId);
  const fileInput = document.getElementById('offboarding-doc-file-'+documentId);
  const formData = new FormData();
  formData.append('title', titleInput?.value?.trim() || '');
  if(fileInput?.files?.[0]) formData.append('document', fileInput.files[0]);
  formData.append('_method', 'PATCH');

  try{
    await wpApi('/api/employees/'+encodeURIComponent(_editEmpId)+'/offboarding-documents/'+encodeURIComponent(documentId), {
      method:'POST',
      body: formData
    });
    await wpReload();
    await refreshEditedEmployee();
    showToast('Offboarding document updated.','green');
  }catch(e){
    showToast(e?.message || 'Unable to update offboarding document.','red');
  }
}

function deleteOffboardingDocument(documentId){
  if(!_editEmpId || !documentId) return;
  showConfirm('Delete Offboarding Document', 'This will remove the selected offboarding document from the employee record.', '!', async function(){
    try{
      await wpApi('/api/employees/'+encodeURIComponent(_editEmpId)+'/offboarding-documents/'+encodeURIComponent(documentId), {method:'DELETE'});
      await wpReload();
      await refreshEditedEmployee();
      showToast('Offboarding document deleted.','green');
    }catch(e){
      showToast(e?.message || 'Unable to delete offboarding document.','red');
    }
  });
}

function completeEmployeeOffboarding(){
  if(!_editEmpId) return;
  showConfirm('Complete Offboarding', 'This will end the offboarding process and move the employee to ex-employee records.', '!', async function(){
    try{
      await wpApi('/api/employees/'+encodeURIComponent(_editEmpId)+'/offboarding-complete', {method:'POST'});
      await wpReload();
      closeModal('editEmpModal');
      showToast('Offboarding completed. Employee moved to Ex-employee.','green');
      const title = document.getElementById('page-title').textContent;
      if(title==='Employees') showPage('employees');
      else if(title==='Employee Profile') showPage('emp-profile-detail');
    }catch(e){
      showToast(e?.message || 'Unable to complete offboarding.','red');
    }
  });
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

function syncDepartmentHeadOptions(selectedCode=''){
  const select = document.getElementById('dept-head');
  if(!select) return;
  const employees = Array.isArray(DB.employees) ? DB.employees : [];
  select.innerHTML = ['<option value="">No Head Assigned</option>'].concat(
    employees.map(employee => `<option value="${employee.id}">${employee.fname} ${employee.lname} (${employee.id})</option>`)
  ).join('');
  if(selectedCode) select.value = selectedCode;
}

function openCreateDepartment(){
  _editDepartmentName = null;
  document.getElementById('dept-modal-title').textContent = 'Add Team';
  document.getElementById('dept-original-name').value = '';
  document.getElementById('dept-name').value = '';
  document.getElementById('dept-color').value = '#2447D0';
  syncDepartmentHeadOptions('');
  openModal('departmentModal');
}

syncNewEmployeeManagerOptions();

function openEditDepartment(name){
  const department = (DB.departments||[]).find(item => item.name === name);
  if(!department){
    showToast('Team not found.','red');
    return;
  }

  _editDepartmentName = name;
  document.getElementById('dept-modal-title').textContent = 'Edit Team';
  document.getElementById('dept-original-name').value = name;
  document.getElementById('dept-name').value = department.name || '';
  document.getElementById('dept-color').value = department.color || '#2447D0';
  const headEmployee = (DB.employees||[]).find(employee => `${employee.fname} ${employee.lname}` === department.head);
  syncDepartmentHeadOptions(headEmployee?.id || '');
  openModal('departmentModal');
}

async function saveDepartment(){
  const originalName = document.getElementById('dept-original-name').value || '';
  const payload = {
    name: document.getElementById('dept-name').value.trim(),
    color: document.getElementById('dept-color').value || '#2447D0',
    head_employee_code: document.getElementById('dept-head').value || null,
  };

  if(!payload.name){
    showToast('Team name is required.','red');
    return;
  }

  try{
    await wpApi(originalName ? `/api/departments/${encodeURIComponent(originalName)}` : '/api/departments', {
      method: originalName ? 'PATCH' : 'POST',
      body: JSON.stringify(payload)
    });
    await wpReload();
    closeModal('departmentModal');
    showToast(`Team ${originalName ? 'updated' : 'created'} successfully.`,'green');
    if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
  }catch(e){
    showRequestError(e);
  }
}

function deleteDepartment(name){
  showConfirm('Delete Team', 'This will remove the team and unassign it from employees currently linked to it.', '⚠️', async function(){
    try{
      await wpApi(`/api/departments/${encodeURIComponent(name)}`, {method:'DELETE'});
      await wpReload();
      showToast('Team deleted.','green');
      if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
    }catch(e){
      showRequestError(e);
    }
  });
}

function calcGross(){
  const b = parseInt(document.getElementById('ee-basic')?.value)||0;
  const h = parseInt(document.getElementById('ee-house')?.value)||0;
  const t = parseInt(document.getElementById('ee-transport')?.value)||0;
  const c = parseInt(document.getElementById('ee-contribution')?.value)||0;
  const od = parseInt(document.getElementById('ee-other-deductions')?.value)||0;
  const tx = parseInt(document.getElementById('ee-tax')?.value)||0;
  const gross = b+h+t;
  const net = gross-c-od-tx;
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
  if(document.getElementById('ee-passport-no').value.trim()) formData.append('passport_no', document.getElementById('ee-passport-no').value.trim());
  if(document.getElementById('ee-phone').value.trim()) formData.append('phone', document.getElementById('ee-phone').value.trim());
  const personalEmail = document.getElementById('ee-personal-email').value.trim();
  if(!personalEmail){
    showToast('Personal email is required','red');
    return;
  }
  const officialEmail = document.getElementById('ee-email').value.trim();
  if(officialEmail.toLowerCase() === personalEmail.toLowerCase()){
    showToast('Official email and personal email must be different','red');
    return;
  }
  formData.append('personal_email', personalEmail);
  if(document.getElementById('ee-address').value.trim()) formData.append('address', document.getElementById('ee-address').value.trim());
  formData.append('blood', document.getElementById('ee-blood').value);
  if(document.getElementById('ee-marital-status').value) formData.append('marital_status', document.getElementById('ee-marital-status').value);
  if(document.getElementById('ee-kin').value.trim()) formData.append('kin', document.getElementById('ee-kin').value.trim());
  if(document.getElementById('ee-kinRel').value.trim()) formData.append('kinRel', document.getElementById('ee-kinRel').value.trim());
  if(document.getElementById('ee-kinPhone').value.trim()) formData.append('kinPhone', document.getElementById('ee-kinPhone').value.trim());
  formData.append('dept', document.getElementById('ee-dept').value);
  formData.append('desg', document.getElementById('ee-desg').value.trim());
  formData.append('doj', document.getElementById('ee-doj').value);
  if(document.getElementById('ee-dop').value) formData.append('dop', document.getElementById('ee-dop').value);
  formData.append('lwd', document.getElementById('ee-lwd').value || '');
  if(document.getElementById('ee-confirmation-date').value) formData.append('confirmation_date', document.getElementById('ee-confirmation-date').value);
  formData.append('type', document.getElementById('ee-type').value);
  formData.append('role', document.getElementById('ee-role').value);
  if(document.getElementById('ee-work-location').value.trim()) formData.append('work_location', document.getElementById('ee-work-location').value.trim());
  if(document.getElementById('ee-manager').value.trim()) formData.append('manager', document.getElementById('ee-manager').value.trim());
  formData.append('shift_id', document.getElementById('ee-shift').value || '');
  formData.append('email', officialEmail);
  if(document.getElementById('ee-password').value) formData.append('password', document.getElementById('ee-password').value);
  formData.append('basic', parseInt(document.getElementById('ee-basic').value)||0);
  formData.append('house', parseInt(document.getElementById('ee-house').value)||0);
  formData.append('transport', parseInt(document.getElementById('ee-transport').value)||0);
  formData.append('pay_period', document.getElementById('ee-pay-period').value.trim());
  if(document.getElementById('ee-salary-start-date').value) formData.append('salary_start_date', document.getElementById('ee-salary-start-date').value);
  formData.append('contribution', parseInt(document.getElementById('ee-contribution').value)||0);
  formData.append('other_deductions', parseInt(document.getElementById('ee-other-deductions').value)||0);
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
    showRequestError(e);
  }
}

// ══════════════════════════════════════════════════
//  EDIT LEAVE BALANCE (Admin)
// ══════════════════════════════════════════════════
function openAccountSettings(){
  const forced = typeof isPasswordChangeRequired === 'function' && isPasswordChangeRequired();
  const errEl = document.getElementById('acc-err');
  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  const profilePhotoEl = document.getElementById('acc-profile-photo');
  if(profilePhotoEl) profilePhotoEl.value = '';
  updateAccountProfilePhotoUI();

  ['acc-current-password','acc-new-password','acc-confirm-password'].forEach(id=>{
    const el = document.getElementById(id);
    if(el) el.value = '';
  });

  configureAccountSettingsModal(forced);
  openModal('accountSettingsModal');
  if(forced){
    setTimeout(()=>document.getElementById('acc-current-password')?.focus(), 0);
  }
}

function configureAccountSettingsModal(forced=false){
  const modal = document.getElementById('accountSettingsModal');
  if(!modal) return;

  const title = modal.querySelector('.modal-title');
  const closeButton = modal.querySelector('.modal-close');
  const actions = modal.querySelector('.modal-actions');
  const cancelButton = actions ? actions.querySelector('.btn:not(.btn-primary)') : null;
  const profileRow = document.getElementById('acc-profile-photo')?.closest('.fg');
  const note = document.getElementById('acc-err')?.nextElementSibling;
  const profilePhoto = document.getElementById('acc-profile-photo');
  const saveButton = actions ? actions.querySelector('.btn-primary') : null;
  let logoutButton = document.getElementById('account-settings-logout');

  modal.classList.toggle('force-password-change', forced);
  if(title) title.textContent = forced ? 'Set New Password' : 'Account Settings';
  if(closeButton) closeButton.style.display = forced ? 'none' : '';
  if(cancelButton) cancelButton.style.display = forced ? 'none' : '';
  if(profileRow) profileRow.style.display = forced ? 'none' : '';
  if(profilePhoto) profilePhoto.disabled = forced;
  if(note){
    note.textContent = forced
      ? 'For security, set a new password before opening your workspace.'
      : 'Employees can update only their own password and profile picture here. Profile, team, type, salary, and other HR details stay managed by admin.';
  }

  if(forced && actions && !logoutButton){
    logoutButton = document.createElement('button');
    logoutButton.type = 'button';
    logoutButton.className = 'btn';
    logoutButton.id = 'account-settings-logout';
    logoutButton.textContent = 'Logout';
    logoutButton.addEventListener('click', () => {
      if(typeof doLogout === 'function') doLogout();
    });
    actions.insertBefore(logoutButton, saveButton || null);
  }
  if(logoutButton) logoutButton.style.display = forced ? '' : 'none';

  ['acc-current-password','acc-new-password','acc-confirm-password'].forEach(id=>{
    const input = document.getElementById(id);
    const label = input?.closest('.fg')?.querySelector('.fl');
    if(!label) return;
    const base = label.textContent.replace('*', '').trim();
    label.innerHTML = forced ? `${base} <span class="req-star">*</span>` : base;
  });
}

function showPasswordChangeRequired(){
  window.__passwordChangeRequired = true;
  if(DB.currentUser) DB.currentUser.mustChangePassword = true;

  const title = document.getElementById('page-title');
  if(title) title.textContent = 'Set New Password';

  const main = document.getElementById('main-content');
  if(main){
    main.innerHTML = `<div class="card"><div class="card-title">Set a new password</div><p style="margin-top:8px;color:var(--muted);">Your account is active. Please set a new password to continue.</p></div>`;
  }

  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.querySelectorAll('.mobile-nav-item').forEach(n=>n.classList.remove('active'));
  configureAccountSettingsModal(true);
  openAccountSettings();
}

function updateAccountProfilePhotoUI(file=null){
  const user = DB.currentUser || {};
  const preview = document.getElementById('acc-profile-photo-preview');
  const title = document.getElementById('acc-profile-photo-title');
  const status = document.getElementById('acc-profile-photo-status');
  if(!preview || !title || !status) return;

  const fallbackInitials = String((user.fname || user.name || 'U')).charAt(0) + String(user.lname || '').charAt(0);
  const initialsText = user.avatar || fallbackInitials.toUpperCase() || 'U';
  preview.style.background = user.avatarColor || 'var(--accent)';
  preview.style.color = '#fff';

  if(file){
    preview.innerHTML = '';
    const img = document.createElement('img');
    img.alt = 'Selected profile photo';
    img.src = URL.createObjectURL(file);
    img.onload = () => URL.revokeObjectURL(img.src);
    preview.appendChild(img);
    title.textContent = file.name;
    status.textContent = 'New photo selected. Save account to update it.';
    return;
  }

  if(user.profilePhotoUrl){
    preview.innerHTML = `<img src="${user.profilePhotoUrl}" alt="Profile photo">`;
    title.textContent = user.profilePhotoName || 'Profile picture uploaded';
    status.textContent = 'Use Edit to replace your current photo.';
    return;
  }

  preview.textContent = initialsText;
  title.textContent = 'No profile picture uploaded';
  status.textContent = 'Use Edit to choose a JPG, PNG, or WebP image.';
}

document.getElementById('acc-profile-photo')?.addEventListener('change', function(){
  updateAccountProfilePhotoUI(this.files?.[0] || null);
});

async function submitAccountSettings(){
  const forced = typeof isPasswordChangeRequired === 'function' && isPasswordChangeRequired();
  const profilePhoto = forced ? null : (document.getElementById('acc-profile-photo')?.files?.[0] || null);
  const currentPassword = document.getElementById('acc-current-password')?.value || '';
  const newPassword = document.getElementById('acc-new-password')?.value || '';
  const confirmPassword = document.getElementById('acc-confirm-password')?.value || '';
  const errEl = document.getElementById('acc-err');

  if(errEl){
    errEl.textContent = '';
    errEl.style.display = 'none';
  }

  if(forced && (!currentPassword || !newPassword || !confirmPassword)){
    if(errEl){
      errEl.textContent = 'Current password, new password, and confirmation are required.';
      errEl.style.display = 'block';
    }
    return;
  }

  if(!forced && !profilePhoto && !newPassword){
    if(errEl){
      errEl.textContent = 'Choose a profile picture or enter a new password.';
      errEl.style.display = 'block';
    }
    return;
  }

  if(newPassword && !currentPassword){
    if(errEl){
      errEl.textContent = 'Current password is required to change password.';
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

  if(newPassword && newPassword === currentPassword){
    if(errEl){
      errEl.textContent = 'New password must be different from your current password.';
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
    const formData = new FormData();
    if(profilePhoto) formData.append('profile_photo', profilePhoto);
    if(currentPassword) formData.append('current_password', currentPassword);
    if(newPassword){
      formData.append('password', newPassword);
      formData.append('password_confirmation', confirmPassword || '');
    }

    const data = await wpApi('/api/me/account', {
      method:'PATCH',
      body: formData
    });

    if(forced || data?.passwordChangeRequired === false){
      window.__passwordChangeRequired = false;
      if(DB.currentUser) DB.currentUser.mustChangePassword = false;
      configureAccountSettingsModal(false);
    }

    if(typeof window.bootWorkpulse === 'function'){
      await window.bootWorkpulse();
    }else{
      await wpReload();
    }
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
    showRequestError(err);
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
    showRequestError(e);
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
  showConfirm('Delete Profile Document', 'This will remove the uploaded document from the employee profile. You can upload a new one later.', '⚠️', async function(){
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
    showRequestError(err);
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
    showRequestError(err);
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
  const headers = ['Employee ID','Name','Team','Date','Day','Clock In','Break In','Break Out','Clock Out','Hours','Overtime','Status'];
  const rows = DB.attendance.map(a=>{
    const emp = DB.employees.find(e=>e.id===a.empId)||{fname:'',lname:'',dept:''};
    return [a.empId, emp.fname+' '+emp.lname, emp.dept, a.date,
      new Date(a.date+'T00:00:00').toLocaleDateString('en-GB',{weekday:'short'}),
      a.in||'', a.breakIn||'', a.breakOut||'', a.out||'',
      calcWorkHours(a), a.overtime?'+'+a.overtime+'m':'', a.status];
  });
  exportCSV('attendance_report.csv', rows, headers);
}

function exportLeaveCSV(){
  const headers = ['Leave ID','Employee','Team','Type','From','To','Days','Reason','Applied','Manager Status','HR Status','Final Status'];
  const rows = DB.leaves.map(l=>[l.id,l.empName,l.dept,l.type,l.from,l.to,l.days,l.reason,l.applied,l.managerStatus,l.hrStatus,l.status]);
  exportCSV('leave_report.csv', rows, headers);
}

function exportEmployeeCSV(){
  const headers = ['ID','First Name','Last Name','Team','Designation','DOJ','Employment Type','Status','Email','Phone','Manager'];
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

function exportCompanyDetailsJson(){
  window.location.href = '/api/transfer/company/export';
  showToast('Company details export started.','green');
}

function importTransferJson(inputId, endpoint, successMessage){
  const input = document.getElementById(inputId);
  if(!input) return;
  input.value = '';
  input.onchange = async function(){
    const file = input.files?.[0];
    if(!file) return;
    const formData = new FormData();
    formData.append('file', file);
    try{
      const data = await wpApi(endpoint, {method:'POST', body: formData});
      await wpReload();
      showToast(successMessage(data),'green');
      if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
    }catch(e){
      showRequestError(e);
    }
  };
  input.click();
}

let employeeImportMappingState = null;

function renderEmployeeImportMappingModal(preview){
  const body = document.getElementById('employee-import-map-body');
  const summary = document.getElementById('employee-import-map-summary');
  if(!body || !summary) return;

  const columns = Array.isArray(preview.columns) ? preview.columns : [];
  const sample = Array.isArray(preview.sample) ? preview.sample : [];
  const fields = Array.isArray(preview.fields) ? preview.fields : [];
  const suggested = preview.suggested_mapping || {};
  const fieldOptions = [
    {value:'__skip', label:'Skip this column'},
    {value:'__custom', label:'Imported custom field'},
    ...fields
  ];

  summary.textContent = `${columns.length} source column(s) detected. Map each column, then start the import.`;
  body.innerHTML = columns.map(column=>{
    const sampleValues = sample
      .map(row => row && row[column] !== undefined && row[column] !== null ? String(row[column]).trim() : '')
      .filter(Boolean)
      .slice(0, 2)
      .join(' / ');
    const selected = suggested[column] || '__custom';
    return `
      <div class="import-map-row">
        <div class="import-map-source">
          <strong>${escapeHtml(column)}</strong>
          <span>${escapeHtml(sampleValues || 'No sample value')}</span>
        </div>
        <select class="fi import-map-select" data-source="${escapeHtmlAttr(column)}">
          ${fieldOptions.map(option=>`<option value="${escapeHtmlAttr(option.value)}" ${option.value===selected?'selected':''}>${escapeHtml(option.label)}</option>`).join('')}
        </select>
      </div>`;
  }).join('') || `<div class="empty">No columns detected.</div>`;
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
    formData.append('preview', '1');

    try{
      const preview = await wpApi('/api/transfer/employees/import', {method:'POST', body: formData});
      employeeImportMappingState = {file, preview};
      renderEmployeeImportMappingModal(preview);
      openModal('employeeImportMapModal');
    }catch(e){
      employeeImportMappingState = null;
      showRequestError(e);
    }
  };
  input.click();
}

async function confirmEmployeeImportMapping(){
  if(!employeeImportMappingState?.file){
    showToast('Choose an import file first.','red');
    return;
  }

  const mapping = {};
  document.querySelectorAll('#employee-import-map-body .import-map-select').forEach(select=>{
    mapping[select.dataset.source || ''] = select.value || '__skip';
  });

  const mappedValues = Object.values(mapping);
  const hasEmail = mappedValues.includes('email');
  const hasName = mappedValues.includes('name') || (mappedValues.includes('fname') && mappedValues.includes('lname'));
  if(!hasEmail || !hasName){
    showToast('Map Official Email and either Full Name or First Name + Last Name before importing.','red');
    return;
  }

  const button = document.getElementById('employee-import-map-confirm');
  if(button) button.disabled = true;

  const formData = new FormData();
  formData.append('file', employeeImportMappingState.file);
  formData.append('column_mapping', JSON.stringify(mapping));

  try{
    const data = await wpApi('/api/transfer/employees/import', {method:'POST', body: formData});
    await wpReload();
    closeModal('employeeImportMapModal');
    employeeImportMappingState = null;
    const created = Array.isArray(data.created_fields) && data.created_fields.length
      ? ` Created ${data.created_fields.length} new field(s).`
      : '';
    const skipped = data.skipped ? ` Skipped ${data.skipped}.` : '';
    showToast(`Imported ${data.imported || 0} employee profile(s).${created}${skipped}`,'green');
    if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
  }catch(e){
    showRequestError(e);
  }finally{
    if(button) button.disabled = false;
  }
}

function importCompanyDetails(){
  importTransferJson(
    'company-import-file',
    '/api/transfer/company/import',
    ()=> 'Company details imported successfully.'
  );
}

function openCreateShift(){
  document.getElementById('shift-modal-title').textContent = 'Add Standard Shift';
  document.getElementById('shift-id').value = '';
  document.getElementById('shift-name').value = '';
  document.getElementById('shift-code').value = '';
  document.getElementById('shift-start').value = '11:00';
  document.getElementById('shift-end').value = '20:00';
  document.getElementById('shift-grace').value = '10';
  document.getElementById('shift-break').value = '60';
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
  document.getElementById('shift-break').value = shift.break ?? 60;
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
    break: parseInt(document.getElementById('shift-break').value, 10) || 0,
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
    showRequestError(e);
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
      showRequestError(e);
    }
  });
}

async function uploadCompanyPolicy(){
  const title = document.getElementById('policy-title')?.value?.trim() || '';
  const file = document.getElementById('policy-file')?.files?.[0] || null;

  if(!title){
    showToast('Policy title is required.','red');
    return;
  }

  if(!file){
    showToast('Please choose a PDF file.','red');
    return;
  }

  try{
    const formData = new FormData();
    formData.append('title', title);
    formData.append('policy_file', file);

    await wpApi('/api/policies', {
      method:'POST',
      body: formData
    });
    await wpReload();
    const titleEl = document.getElementById('policy-title');
    const fileEl = document.getElementById('policy-file');
    if(titleEl) titleEl.value = '';
    if(fileEl) fileEl.value = '';
    showToast('Policy uploaded successfully.','green');
    if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
  }catch(e){
    showRequestError(e);
  }
}

function deleteCompanyPolicy(policyId){
  showConfirm('Delete Policy', 'This will permanently remove the policy PDF from the company library.', 'Warning', async function(){
    try{
      await wpApi(`/api/policies/${encodeURIComponent(policyId)}`, {method:'DELETE'});
      await wpReload();
      showToast('Policy deleted.','green');
      if(window.__workpulseCurrentPage) showPage(window.__workpulseCurrentPage);
    }catch(e){
      showRequestError(e);
    }
  });
}

function printPage(){
  window.print();
}

// ══════════════════════════════════════════════════
