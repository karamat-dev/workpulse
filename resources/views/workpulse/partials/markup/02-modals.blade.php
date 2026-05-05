<!-- ═══════════ MODALS ═══════════ -->

<!-- Leave Modal -->
<div class="modal-overlay" id="leaveModal">
  <div class="modal modal-xl">
    <div class="modal-hdr"><div class="modal-title">Apply for Leave</div><button class="modal-close" onclick="window.closeModal('leaveModal')">×</button></div>
    <div class="fg"><label class="fl">Leave Type <span class="req-star">*</span></label>
      <select class="fi" id="lv-type"><option value="annual">Annual Leave</option><option value="sick">Sick Leave</option><option value="casual">Casual Leave</option><option value="paternity">Paternity Leave</option><option value="maternity">Maternity Leave</option><option value="marriage">Marriage Leave</option><option value="bereavement">Bereavement Leave</option><option value="unpaid">Unpaid Leave</option></select></div>
    <div class="g2">
      <div class="fg"><label class="fl">From Date <span class="req-star">*</span></label><input type="date" class="fi" id="lv-from"></div>
      <div class="fg"><label class="fl">To Date <span class="req-star">*</span></label><input type="date" class="fi" id="lv-to"></div>
    </div>
    <div id="lv-breakdown-wrap" style="display:none;margin-bottom:12px;">
      <div class="card" style="padding:14px;">
        <div class="card-hdr" style="margin-bottom:10px;">
          <div class="card-title">Leave Plan</div>
          <div style="font-size:12px;color:var(--muted);">Choose full day or half day for each selected date.</div>
        </div>
        <div class="table-wrap" style="border:1px solid var(--border);border-radius:12px;overflow:hidden;">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Duration</th>
              </tr>
            </thead>
            <tbody id="lv-breakdown-rows">
              <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px;">Choose leave dates to build the leave plan.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="fg"><label class="fl">Reason <span class="req-star">*</span></label><textarea class="fi" id="lv-reason" rows="3" placeholder="Brief reason..."></textarea></div>
    <div class="fg"><label class="fl">Handover To</label><input type="text" class="fi" id="lv-handover" placeholder="Colleague name"></div>
    <div id="lv-calc" style="background:var(--accent-bg);border-radius:7px;padding:10px 12px;font-size:13px;color:var(--accent-dark);margin-bottom:12px;display:none;">
      <strong id="lv-days">0</strong> working day(s) requested
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('leaveModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitLeave()"">Submit Request</button>
    </div>
  </div>
</div>

<!-- Regulation Modal -->
<div class="modal-overlay" id="regulationModal">
  <div class="modal modal-xl regulation-modal">
    <div class="modal-hdr"><div class="modal-title">Attendance Regulation Request</div><button class="modal-close" onclick="window.closeModal('regulationModal')">×</button></div>
    <div class="g3" style="grid-template-columns:1.2fr 1fr 1fr;align-items:end;">
      <div class="fg"><label class="fl">Employee</label><input type="text" class="fi" id="reg-employee" readonly></div>
      <div class="fg"><label class="fl">From Date <span class="req-star">*</span></label><input type="date" class="fi" id="reg-from"></div>
      <div class="fg"><label class="fl">To Date <span class="req-star">*</span></label><input type="date" class="fi" id="reg-to"></div>
    </div>
    <div class="alert al-info" style="margin-bottom:14px;"><span>ℹ</span><div>Select the checkbox for each day you want to edit, update the time slots, then submit the selected rows.</div></div>
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
      <button class="btn btn-sm" onclick="window.loadRegulationRows()">Fetch Attendance</button>
    </div>
    <div class="table-wrap" style="max-height:420px;overflow:auto;border:1px solid var(--border);border-radius:12px;">
      <table>
        <thead>
          <tr>
            <th style="width:70px;">Edit</th>
            <th>Date</th>
            <th>Old Time In - Old Time Out</th>
            <th>In Date</th>
            <th>In Time</th>
            <th>Out Date</th>
            <th>Out Time</th>
            <th>Remarks</th>
            <th style="width:70px;">Remove</th>
          </tr>
        </thead>
        <tbody id="reg-rows">
          <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px;">Choose a date range and click Fetch Attendance.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('regulationModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitRegulation()">Submit Selected</button>
    </div>
  </div>
</div>

<!-- Notification Modal -->
<div class="modal-overlay" id="notificationModal">
  <div class="modal employee-modal">
    <div class="modal-hdr"><div class="modal-title">Manage Notification</div><button class="modal-close" onclick="window.closeModal('notificationModal')">Ã—</button></div>
    <input type="hidden" id="ntf-reference-code" value="">
    <div class="fg"><label class="fl">Title <span class="req-star">*</span></label><input type="text" class="fi" id="ntf-title" placeholder="Notification title..."></div>
    <div class="g2">
      <div class="fg"><label class="fl">Audience</label>
        <select class="fi" id="ntf-aud" onchange="window.toggleNotificationRecipients()"></select></div>
      <div class="fg" id="ntf-recipient-wrap" style="display:none;"><label class="fl">Specific Employees</label><select class="fi" id="ntf-targets" multiple size="6"></select></div>
    </div>
    <div class="fg"><label class="fl">Message <span class="req-star">*</span></label><textarea class="fi" id="ntf-msg" rows="4" placeholder="Type your notification..."></textarea></div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('notificationModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitNotification()">Save Notification</button>
    </div>
  </div>
</div>

<!-- Announcement Modal -->
<div class="modal-overlay" id="announcementModal">
  <div class="modal employee-modal">
    <div class="modal-hdr"><div class="modal-title" id="announcement-modal-title">Post Announcement</div><button class="modal-close" onclick="window.closeModal('announcementModal')">×</button></div>
    <div class="fg"><label class="fl">Title <span class="req-star">*</span></label><input type="text" class="fi" id="ann-title" placeholder="Announcement title..."></div>
    <div class="g2">
      <div class="fg"><label class="fl">Category</label>
        <select class="fi" id="ann-cat"><option>General</option><option>Policy</option><option>Event</option><option>Important</option><option>Holiday</option></select></div>
      <div class="fg"><label class="fl">Audience</label>
        <select class="fi" id="ann-aud" onchange="window.toggleAnnouncementRecipients()"></select></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Effective From</label><input type="date" class="fi" id="ann-effective-from"></div>
      <div class="fg"><label class="fl">Effective To</label><input type="date" class="fi" id="ann-effective-to"></div>
    </div>
    <div class="fg" id="ann-recipient-wrap" style="display:none;"><label class="fl">Specific Employees</label><select class="fi" id="ann-targets" multiple size="6"></select></div>
    <div class="fg"><label class="fl">Message <span class="req-star">*</span></label><textarea class="fi" id="ann-msg" rows="4" placeholder="Type your announcement..."></textarea></div>
    <div class="fg">
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--text);">
        <input type="checkbox" id="ann-has-vote" onchange="window.toggleAnnouncementVoteFields()">
        Enable voting
      </label>
    </div>
    <div id="ann-vote-fields" style="display:none;">
      <div class="fg"><label class="fl">Voting Question <span class="req-star">*</span></label><input type="text" class="fi" id="ann-vote-question" placeholder="Who wants to attend this dinner?"></div>
      <div class="fg">
        <label class="fl">Choices <span class="req-star">*</span></label>
        <div id="ann-vote-options" style="display:flex;flex-direction:column;gap:8px;"></div>
        <button type="button" class="btn btn-sm" style="margin-top:8px;" onclick="window.addAnnouncementVoteOption()">+ Add Choice</button>
      </div>
      <div class="fg">
        <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);">
          <input type="checkbox" id="ann-vote-show-results">
          Show option counts to employees after voting is closed
        </label>
      </div>
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('announcementModal')">Cancel</button>
      <button class="btn btn-primary" id="announcement-submit-btn" onclick="window.submitAnnouncement()">Publish</button>
    </div>
  </div>
</div>

<!-- Add Employee Modal -->
<div class="modal-overlay" id="addEmpModal">
  <div class="modal employee-modal">
    <div class="modal-hdr"><div class="modal-title">Add New Employee</div><button class="modal-close" onclick="window.closeModal('addEmpModal')">×</button></div>
    <div class="g2">
      <div class="fg"><label class="fl">First Name <span class="req-star">*</span></label><input type="text" class="fi" id="ne-fname" placeholder="First name"></div>
      <div class="fg"><label class="fl">Last Name <span class="req-star">*</span></label><input type="text" class="fi" id="ne-lname" placeholder="Last name"></div>
    </div>
    <div class="fg"><label class="fl">Official Email <span class="req-star">*</span></label><input type="email" class="fi" id="ne-email" placeholder="name@company.com"></div>
    <div class="fg">
      <label class="fl">Login Password <span class="req-star">*</span></label>
      <div style="display:flex;gap:8px;align-items:center;">
        <div class="password-input-wrap" style="flex:1;">
          <input type="text" class="fi" id="ne-password" placeholder="Auto generated password">
          <button type="button" class="password-view-btn" data-password-toggle aria-label="Hide password"></button>
        </div>
        <button type="button" class="btn btn-sm" onclick="window.generateNewEmployeePassword && window.generateNewEmployeePassword()">Regenerate</button>
      </div>
      <div style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap;">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);"><input type="radio" name="ne-password-mode" value="auto" id="ne-password-auto" checked onchange="window.setNewEmployeePasswordMode && window.setNewEmployeePasswordMode('auto')"> Use recommended auto password</label>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);"><input type="radio" name="ne-password-mode" value="manual" id="ne-password-manual" onchange="window.setNewEmployeePasswordMode && window.setNewEmployeePasswordMode('manual')"> Manually create password</label>
      </div>
    </div>
    <div class="fg"><label class="fl">Profile Document <span class="req-star">*</span></label><input type="file" class="fi" id="ne-cnic-document"></div>
    <div class="fg"><label class="fl">Personal Phone</label><input type="text" class="fi" id="ne-phone" placeholder="+92 3XX XXXXXXX"></div>
    <div class="g2">
      <div class="fg"><label class="fl">Personal Email <span class="req-star">*</span></label><input type="email" class="fi" id="ne-personal-email" placeholder="personal@email.com"></div>
      <div class="fg"><label class="fl">Work Location</label><input type="text" class="fi" id="ne-work-location" placeholder="Main Office"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Team <span class="req-star">*</span></label>
        <select class="fi" id="ne-dept"><option>Engineering</option><option>HR</option><option>Finance</option><option>Marketing</option><option>Product</option><option>Operations</option></select></div>
      <div class="fg"><label class="fl">Designation <span class="req-star">*</span></label><input type="text" class="fi" id="ne-desg" placeholder="Job title"></div>
    </div>
    <div class="modal-note" style="margin-top:-4px;margin-bottom:12px;">Employee code is generated automatically from the selected team, for example <code>Eng-emp001</code> or <code>Dev-emp001</code>.</div>
    <div class="fg"><label class="fl">User Role</label>
      <select class="fi" id="ne-role">
        <option value="employee">Employee</option>
        <option value="manager">Super-Admin</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Date of Joining <span class="req-star">*</span></label><input type="date" class="fi" id="ne-doj"></div>
      <div class="fg"><label class="fl">Employment Type</label>
        <select class="fi" id="ne-type"><option>Permanent</option><option>Probation</option><option>Contract</option><option>Intern</option></select></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Probation Date</label><input type="date" class="fi" id="ne-dop"></div>
      <div class="fg"><label class="fl">Last Working Date</label><input type="date" class="fi" id="ne-lwd"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Confirmation Date</label><input type="date" class="fi" id="ne-confirmation-date"></div>
      <div class="fg"><label class="fl">Marital Status</label><select class="fi" id="ne-marital-status"><option value="">Select status</option><option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option></select></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">National ID / CNIC</label><input type="text" class="fi" id="ne-cnic" placeholder="XXXXX-XXXXXXX-X"></div>
      <div class="fg"><label class="fl">Passport No</label><input type="text" class="fi" id="ne-passport-no" placeholder="Passport number"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Date of Birth</label><input type="date" class="fi" id="ne-dob"></div>
      <div class="fg"><label class="fl">Gender</label><select class="fi" id="ne-gender"><option value="">Select gender</option><option>Male</option><option>Female</option><option>Other</option></select></div>
    </div>
    <div class="fg"><label class="fl">Address</label><input type="text" class="fi" id="ne-address" placeholder="City, country"></div>
    <div class="g2">
      <div class="fg"><label class="fl">Blood Group</label><select class="fi" id="ne-blood"><option value="">Select blood group</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option></select></div>
      <div class="fg"><label class="fl">Next of Kin Name</label><input type="text" class="fi" id="ne-kin"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Kin Relationship</label><input type="text" class="fi" id="ne-kinRel"></div>
      <div class="fg"><label class="fl">Kin Phone</label><input type="text" class="fi" id="ne-kinPhone"></div>
    </div>
    <div class="fg">
      <label class="fl">Reporting Manager</label>
      <div class="manager-field" id="ne-manager-field">
        <input type="hidden" id="ne-manager">
        <button type="button" class="manager-trigger" id="ne-manager-trigger" onclick="window.toggleManagerPicker && window.toggleManagerPicker('ne')">
          <span class="manager-trigger-value manager-placeholder" id="ne-manager-value">Select reporting manager</span>
          <span class="manager-trigger-arrow" id="ne-manager-arrow">▼</span>
        </button>
        <div class="manager-dropdown" id="ne-manager-dropdown">
          <div class="manager-search-wrap">
            <input type="text" class="manager-search-input" id="ne-manager-search" placeholder="Search employee..." oninput="window.filterManagerPickerOptions && window.filterManagerPickerOptions('ne')" autocomplete="off">
          </div>
          <div class="manager-options-list" id="ne-manager-options"></div>
          <div class="manager-no-results" id="ne-manager-empty">No results found</div>
        </div>
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:6px;">Search and select from all employees in the dropdown.</div>
    </div>
    <div class="fg"><label class="fl">Standard Shift</label><select class="fi" id="ne-shift"></select></div>
    <div class="employee-salary-grid" style="margin:14px 0;">
      <div class="fg"><label class="fl">Pay Period</label><input type="text" class="fi" id="ne-pay-period" placeholder="Monthly - Payroll"></div>
      <div class="fg"><label class="fl">Salary Start Date</label><input type="date" class="fi" id="ne-salary-start-date"></div>
      <div class="fg"><label class="fl">Basic Salary (PKR)</label><input type="number" class="fi" id="ne-basic" min="0"></div>
      <div class="fg"><label class="fl">House Allowance (PKR)</label><input type="number" class="fi" id="ne-house" min="0"></div>
      <div class="fg"><label class="fl">Transport Allowance (PKR)</label><input type="number" class="fi" id="ne-transport" min="0"></div>
      <div class="fg"><label class="fl">Contributions (PKR)</label><input type="number" class="fi" id="ne-contribution" min="0"></div>
      <div class="fg"><label class="fl">Other Deductions (PKR)</label><input type="number" class="fi" id="ne-other-deductions" min="0"></div>
      <div class="fg"><label class="fl">Tax Deduction (PKR)</label><input type="number" class="fi" id="ne-tax" min="0"></div>
      <div class="fg"><label class="fl">Bank Name</label><input type="text" class="fi" id="ne-bank"></div>
      <div class="fg"><label class="fl">Account No</label><input type="text" class="fi" id="ne-acct"></div>
      <div class="fg" style="grid-column:1 / -1;"><label class="fl">IBAN</label><input type="text" class="fi" id="ne-iban"></div>
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('addEmpModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitAddEmployee()">Add Employee</button>
    </div>
  </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal-overlay" id="holidayModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Add National Holiday</div><button class="modal-close" onclick="window.closeModal('holidayModal')">×</button></div>
    <div class="fg"><label class="fl">Holiday Name <span class="req-star">*</span></label><input type="text" class="fi" id="hol-name" placeholder="e.g. Independence Day"></div>
    <div class="fg"><label class="fl">Date <span class="req-star">*</span></label><input type="date" class="fi" id="hol-date"></div>
    <div class="fg"><label class="fl">Type</label>
      <select class="fi" id="hol-type"><option>National</option><option>Religious</option><option>Optional</option></select></div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('holidayModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitHoliday()"">Add Holiday</button>
    </div>
  </div>
</div>

<!-- Team Modal -->
<div class="modal-overlay" id="departmentModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title" id="dept-modal-title">Add Team</div><button class="modal-close" onclick="window.closeModal('departmentModal')">×</button></div>
    <input type="hidden" id="dept-original-name">
    <div class="fg"><label class="fl">Team Name <span class="req-star">*</span></label><input type="text" class="fi" id="dept-name" placeholder="Engineering"></div>
    <div class="g2">
      <div class="fg"><label class="fl">Color</label><input type="color" class="fi" id="dept-color" value="#2447D0" style="height:44px;padding:6px;"></div>
      <div class="fg"><label class="fl">Team Lead</label><select class="fi" id="dept-head"></select></div>
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('departmentModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveDepartment()">Save Team</button>
    </div>
  </div>
</div>

<!-- Leave Approval Modal -->
<div class="modal-overlay" id="approvalModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Review Leave Request</div><button class="modal-close" onclick="window.closeModal('approvalModal')">×</button></div>
    <div id="approval-details"></div>
    <div class="fg" style="margin-top:12px;"><label class="fl">Comment (optional)</label><textarea class="fi" id="approval-comment" rows="2" placeholder="Add a comment..."></textarea></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('approvalModal')">Cancel</button>
      <button class="btn btn-danger" onclick="window.approveLeave('Rejected')">Reject</button>
      <button class="btn btn-green" onclick="window.approveLeave('Approved')">Approve</button>
    </div>
  </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal-overlay" id="editEmpModal">
  <div class="modal modal-wide">
    <div class="modal-hdr"><div class="modal-title">Edit Employee</div><button class="modal-close" onclick="window.closeModal('editEmpModal')">×</button></div>
    <div class="tabs" style="margin-bottom:16px;" id="edit-emp-tabs">
      <div class="tab active" onclick="window.switchEditTab('personal')">Personal</div>
      <div class="tab" onclick="window.switchEditTab('job')">Job & HR</div>
      <div class="tab" onclick="window.switchEditTab('salary')">Salary & Bank</div>
    </div>
    <!-- Personal Tab -->
    <div id="edit-tab-personal">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">First Name <span class="req-star">*</span></label><input type="text" class="fi" id="ee-fname"></div>
        <div class="fg"><label class="fl">Last Name <span class="req-star">*</span></label><input type="text" class="fi" id="ee-lname"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Date of Birth</label><input type="date" class="fi" id="ee-dob"></div>
        <div class="fg"><label class="fl">Gender</label><select class="fi" id="ee-gender"><option>Male</option><option>Female</option><option>Other</option></select></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">CNIC</label><input type="text" class="fi" id="ee-cnic" placeholder="XXXXX-XXXXXXX-X"></div>
        <div class="fg"><label class="fl">Passport No</label><input type="text" class="fi" id="ee-passport-no"></div>
      </div>
      <div class="fg"><label class="fl">Personal Phone</label><input type="text" class="fi" id="ee-phone"></div>
      <div class="fg"><label class="fl">Personal Email <span class="req-star">*</span></label><input type="email" class="fi" id="ee-personal-email"></div>
      <div class="fg"><label class="fl">Address</label><input type="text" class="fi" id="ee-address"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Blood Group</label><select class="fi" id="ee-blood"><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option></select></div>
        <div class="fg"><label class="fl">Marital Status</label><select class="fi" id="ee-marital-status"><option value="">Select status</option><option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option></select></div>
      </div>
      <div class="fg"><label class="fl">Next of Kin Name</label><input type="text" class="fi" id="ee-kin"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Kin Relationship</label><input type="text" class="fi" id="ee-kinRel"></div>
        <div class="fg"><label class="fl">Kin Phone</label><input type="text" class="fi" id="ee-kinPhone"></div>
      </div>
    </div>
    <!-- Job Tab -->
    <div id="edit-tab-job" style="display:none;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Team <span class="req-star">*</span></label><select class="fi" id="ee-dept"><option>Engineering</option><option>HR</option><option>Finance</option><option>Marketing</option><option>Product</option><option>Operations</option></select></div>
        <div class="fg"><label class="fl">Designation <span class="req-star">*</span></label><input type="text" class="fi" id="ee-desg"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Date of Joining <span class="req-star">*</span></label><input type="date" class="fi" id="ee-doj"></div>
        <div class="fg"><label class="fl">Probation Date</label><input type="date" class="fi" id="ee-dop"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Last Working Date</label><input type="date" class="fi" id="ee-lwd"></div>
        <div class="fg"><label class="fl">Official Email <span class="req-star">*</span></label><input type="email" class="fi" id="ee-email"></div>
      </div>
      <div class="fg" id="ee-offboarding-panel">
        <label class="fl">Offboarding Documents</label>
        <div style="border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--surface2);">
          <div id="ee-offboarding-documents" style="display:flex;flex-direction:column;gap:8px;"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;margin-top:10px;">
            <input type="text" class="fi" id="ee-offboarding-doc-title" placeholder="Document title">
            <input type="file" class="fi" id="ee-offboarding-doc-file">
            <button type="button" class="btn btn-sm btn-primary" onclick="window.uploadOffboardingDocument()">Upload</button>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:10px;">
            <button type="button" class="btn btn-sm btn-green" id="ee-offboarding-complete" onclick="window.completeEmployeeOffboarding()">Offboarding Complete</button>
          </div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Confirmation Date</label><input type="date" class="fi" id="ee-confirmation-date"></div>
        <div class="fg"><label class="fl">Work Location</label><input type="text" class="fi" id="ee-work-location"></div>
      </div>
      <div class="fg"><label class="fl">Replace Profile Document</label><input type="file" class="fi" id="ee-cnic-document"></div>
      <div class="fg"><label class="fl">Reset Login Password</label><div class="password-input-wrap"><input type="password" class="fi" id="ee-password" placeholder="Leave blank to keep current password"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
      <div class="fg"><label class="fl">Employment Type</label><select class="fi" id="ee-type"><option>Permanent</option><option>Probation</option><option>Contract</option><option>Intern</option></select></div>
      <div class="fg"><label class="fl">User Role</label><select class="fi" id="ee-role"><option value="employee">Employee</option><option value="manager">Super-Admin</option><option value="admin">Admin</option></select></div>
      <div class="fg">
        <label class="fl">Reporting Manager</label>
        <div class="manager-field" id="ee-manager-field">
          <input type="hidden" id="ee-manager">
          <button type="button" class="manager-trigger" id="ee-manager-trigger" onclick="window.toggleManagerPicker && window.toggleManagerPicker('ee')">
            <span class="manager-trigger-value manager-placeholder" id="ee-manager-value">Select reporting manager</span>
            <span class="manager-trigger-arrow" id="ee-manager-arrow">▼</span>
          </button>
          <div class="manager-dropdown" id="ee-manager-dropdown">
            <div class="manager-search-wrap">
              <input type="text" class="manager-search-input" id="ee-manager-search" placeholder="Search employee..." oninput="window.filterManagerPickerOptions && window.filterManagerPickerOptions('ee')" autocomplete="off">
            </div>
            <div class="manager-options-list" id="ee-manager-options"></div>
            <div class="manager-no-results" id="ee-manager-empty">No results found</div>
          </div>
        </div>
      </div>
      <div class="fg"><label class="fl">Standard Shift</label><select class="fi" id="ee-shift"></select></div>
    </div>
    <!-- Salary Tab -->
    <div id="edit-tab-salary" style="display:none;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Basic Salary (PKR)</label><input type="number" class="fi" id="ee-basic" oninput="window.calcGross()"></div>
        <div class="fg"><label class="fl">House Allowance (PKR)</label><input type="number" class="fi" id="ee-house" oninput="window.calcGross()"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Transport Allowance (PKR)</label><input type="number" class="fi" id="ee-transport" oninput="window.calcGross()"></div>
        <div class="fg"><label class="fl">Contributions (PKR)</label><input type="number" class="fi" id="ee-contribution" oninput="window.calcGross()"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Other Deductions (PKR)</label><input type="number" class="fi" id="ee-other-deductions" oninput="window.calcGross()"></div>
        <div class="fg"><label class="fl">Tax Deduction (PKR)</label><input type="number" class="fi" id="ee-tax" oninput="window.calcGross()"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Pay Period</label><input type="text" class="fi" id="ee-pay-period"></div>
        <div class="fg"><label class="fl">Salary Start Date</label><input type="date" class="fi" id="ee-salary-start-date"></div>
      </div>
      <div style="background:var(--surface2);border-radius:8px;padding:12px;margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
        <div><span style="color:var(--muted);">Gross Salary:</span> <strong id="ee-gross-display">PKR 0</strong></div>
        <div><span style="color:var(--muted);">Net Salary:</span> <strong id="ee-net-display" style="color:var(--green);">PKR 0</strong></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Bank Name</label><input type="text" class="fi" id="ee-bank"></div>
        <div class="fg"><label class="fl">Account No</label><input type="text" class="fi" id="ee-acct"></div>
      </div>
      <div class="fg"><label class="fl">IBAN</label><input type="text" class="fi" id="ee-iban"></div>
    </div>
    <div class="modal-actions" style="margin-top:16px;">
      <button class="btn" onclick="window.closeModal('editEmpModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveEditEmployee()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Shift Modal -->
<div class="modal-overlay" id="shiftModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title" id="shift-modal-title">Add Standard Shift</div><button class="modal-close" onclick="window.closeModal('shiftModal')">×</button></div>
    <input type="hidden" id="shift-id">
    <div class="fg"><label class="fl">Shift Name <span class="req-star">*</span></label><input type="text" class="fi" id="shift-name" placeholder="Morning Shift"></div>
    <div class="fg"><label class="fl">Shift Code</label><input type="text" class="fi" id="shift-code" placeholder="morning_shift"></div>
    <div class="g2">
      <div class="fg"><label class="fl">Start Time <span class="req-star">*</span></label><input type="time" class="fi" id="shift-start" value="11:00"></div>
      <div class="fg"><label class="fl">End Time <span class="req-star">*</span></label><input type="time" class="fi" id="shift-end" value="20:00"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Grace Minutes</label><input type="number" class="fi" id="shift-grace" min="0" max="240" value="10"></div>
      <div class="fg"><label class="fl">Break Minutes</label><input type="number" class="fi" id="shift-break" min="0" max="480" value="60"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Working Days</label><input type="text" class="fi" id="shift-days" value="Mon-Fri"></div>
      <div class="fg"><label class="fl">Status</label><select class="fi" id="shift-active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('shiftModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveShift()">Save Shift</button>
    </div>
  </div>
</div>

<input type="file" id="transfer-import-file" accept=".json,.csv,.xlsx,application/json,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display:none;">
<input type="file" id="company-import-file" accept=".json,application/json" style="display:none;">

<div class="modal-overlay" id="employeeImportMapModal">
  <div class="modal modal-wide">
    <div class="modal-hdr">
      <div>
        <div class="modal-title">Map Employee Import Columns</div>
        <div class="modal-subtitle" id="employee-import-map-summary" style="margin-top:6px;color:var(--muted);font-size:13px;">Map each source column before importing.</div>
      </div>
      <button class="modal-close" onclick="window.closeModal('employeeImportMapModal')">Ã—</button>
    </div>
    <div class="import-map-head">
      <span>Source column</span>
      <span>Import as</span>
    </div>
    <div id="employee-import-map-body" class="import-map-body"></div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('employeeImportMapModal')">Cancel</button>
      <button class="btn btn-primary" id="employee-import-map-confirm" onclick="window.confirmEmployeeImportMapping()">Start Import</button>
    </div>
  </div>
</div>

<!-- Edit Leave Balance Modal (Admin) -->
<div class="modal-overlay" id="editLeaveModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Edit Leave Balance</div><button class="modal-close" onclick="window.closeModal('editLeaveModal')">×</button></div>
    <div id="edit-leave-employee-name" style="font-size:13px;color:var(--muted);margin-bottom:14px;"></div>
    <div class="fg">
      <label class="fl">Update Mode</label>
      <select class="fi" id="el-mode">
        <option value="absolute">Set exact balance</option>
        <option value="adjust">Adjust balance (+/-)</option>
      </select>
    </div>
    <div id="edit-leave-balance-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;"></div>
    <div style="font-size:12px;color:var(--muted);margin-top:8px;">
      Use "Set exact balance" to replace values directly, or "Adjust balance (+/-)" to increase/decrease from current values.
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('editLeaveModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveLeaveBalance()">Save Balance</button>
    </div>
  </div>
</div>

<!-- Edit Leave Policy Modal (Admin/HR) -->
<div class="modal-overlay" id="editLeavePolicyModal">
  <div class="modal modal-wide">
    <div class="modal-hdr"><div class="modal-title">Edit Leave Policy</div><button class="modal-close" onclick="window.closeModal('editLeavePolicyModal')">×</button></div>
    <div class="g2">
      <div class="fg">
        <label class="fl">Policy Year</label>
        <input type="number" class="fi" id="lp-year" min="2000" max="2100">
      </div>
      <div class="fg">
        <label class="fl">Note</label>
        <div style="font-size:12px;color:var(--muted);padding-top:10px;">Update quota, carry forward, and pro-rata per leave type.</div>
      </div>
    </div>
    <div id="edit-leave-policy-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:10px;"></div>
    <div class="modal-actions" style="margin-top:16px;">
      <button class="btn" onclick="window.closeModal('editLeavePolicyModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveLeavePolicy()">Save Policy</button>
    </div>
  </div>
</div>

<!-- Account Settings Modal -->
<div class="modal-overlay" id="accountSettingsModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Account Settings</div><button class="modal-close" onclick="window.closeModal('accountSettingsModal')">Ã—</button></div>
    <div class="fg"><label class="fl">Profile Picture</label><input type="file" class="fi" id="acc-profile-photo" accept=".jpg,.jpeg,.png,.webp"></div>
    <div class="fg"><label class="fl">Current Password</label><div class="password-input-wrap"><input type="password" class="fi" id="acc-current-password" placeholder="Required only when changing password"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
    <div class="fg"><label class="fl">New Password</label><div class="password-input-wrap"><input type="password" class="fi" id="acc-new-password" placeholder="Leave blank to keep current password"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
    <div class="fg"><label class="fl">Confirm New Password</label><div class="password-input-wrap"><input type="password" class="fi" id="acc-confirm-password" placeholder="Re-enter new password"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
    <div id="acc-err" style="color:var(--red);font-size:12px;margin-bottom:10px;display:none;"></div>
    <div style="font-size:12px;color:var(--muted);margin-bottom:14px;">
      Employees can update only their own password and profile picture here. Profile, team, type, salary, and other HR details stay managed by admin.
    </div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('accountSettingsModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitAccountSettings()">Save Account</button>
    </div>
  </div>
</div>

<!-- Leave Type Modal -->
<div class="modal-overlay" id="leaveTypeModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title" id="lt-modal-title">Add Leave Type</div><button class="modal-close" onclick="window.closeModal('leaveTypeModal')">Ã—</button></div>
    <input type="hidden" id="lt-original-code">
    <div class="fg"><label class="fl">Leave Type Name <span class="req-star">*</span></label><input type="text" class="fi" id="lt-name" placeholder="e.g. Study Leave"></div>
    <div class="fg"><label class="fl">Leave Type Code</label><input type="text" class="fi" id="lt-code" placeholder="study_leave"></div>
    <div class="fg">
      <label class="fl">Paid Leave</label>
      <select class="fi" id="lt-paid">
        <option value="1">Paid</option>
        <option value="0">Unpaid</option>
      </select>
    </div>
    <div id="lt-err" style="color:var(--red);font-size:12px;margin-bottom:10px;display:none;"></div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('leaveTypeModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveLeaveType()">Save Leave Type</button>
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="changePassModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Change Password</div><button class="modal-close" onclick="window.closeModal('changePassModal')">×</button></div>
    <div class="fg"><label class="fl">Current Password <span class="req-star">*</span></label><div class="password-input-wrap"><input type="password" class="fi" id="cp-current" placeholder="Enter current password"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
    <div class="fg"><label class="fl">New Password <span class="req-star">*</span></label><div class="password-input-wrap"><input type="password" class="fi" id="cp-new" placeholder="Min 6 characters"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
    <div class="fg"><label class="fl">Confirm New Password <span class="req-star">*</span></label><div class="password-input-wrap"><input type="password" class="fi" id="cp-confirm" placeholder="Re-enter new password"><button type="button" class="password-view-btn" data-password-toggle aria-label="Show password"></button></div></div>
    <div id="cp-err" style="color:var(--red);font-size:12px;margin-bottom:10px;display:none;"></div>
    <div class="modal-actions">
      <button class="btn" onclick="window.closeModal('changePassModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitChangePassword()">Update Password</button>
    </div>
  </div>
</div>

<!-- Attendance Detail Dialog -->
<div class="modal-overlay" id="attendanceDetailModal">
  <div class="modal modal-wide attendance-detail-modal">
    <div class="modal-hdr">
      <div>
        <div class="modal-title">Attendance Details</div>
        <div class="modal-subtitle" id="attendance-detail-subtitle" style="margin-top:6px;color:var(--muted);font-size:13px;">Select a date from the attendance log.</div>
      </div>
      <button class="modal-close" onclick="window.closeModal('attendanceDetailModal')">×</button>
    </div>
    <div id="attendance-detail-body">
      <div style="color:var(--muted);font-size:13px;">Attendance details will appear here.</div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary" onclick="window.closeModal('attendanceDetailModal')">Close</button>
    </div>
  </div>
</div>

<!-- Confirm Dialog -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal" style="width:360px;text-align:center;">
    <div style="font-size:32px;margin-bottom:12px;" id="confirm-icon">⚠️</div>
    <div class="modal-title" id="confirm-title" style="margin-bottom:8px;">Are you sure?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:20px;" id="confirm-msg"></div>
    <div class="modal-actions" style="justify-content:center;">
      <button class="btn" onclick="window.closeModal('confirmModal')">Cancel</button>
      <button class="btn btn-danger" id="confirm-ok-btn">Confirm</button>
    </div>
  </div>
</div>
