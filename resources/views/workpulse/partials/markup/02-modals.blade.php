<!-- ═══════════ MODALS ═══════════ -->

<!-- Leave Modal -->
<div class="modal-overlay" id="leaveModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Apply for Leave</div><button class="modal-close" onclick="window.closeModal('leaveModal')">×</button></div>
    <div class="fg"><label class="fl">Leave Type</label>
      <select class="fi" id="lv-type"><option value="annual">Annual Leave</option><option value="sick">Sick Leave</option><option value="casual">Casual Leave</option><option value="paternity">Paternity Leave</option><option value="maternity">Maternity Leave</option><option value="marriage">Marriage Leave</option><option value="bereavement">Bereavement Leave</option><option value="unpaid">Unpaid Leave</option></select></div>
    <div class="g2">
      <div class="fg"><label class="fl">From Date</label><input type="date" class="fi" id="lv-from"></div>
      <div class="fg"><label class="fl">To Date</label><input type="date" class="fi" id="lv-to"></div>
    </div>
    <div class="fg"><label class="fl">Reason</label><textarea class="fi" id="lv-reason" rows="3" placeholder="Brief reason..."></textarea></div>
    <div class="fg"><label class="fl">Handover To</label><input type="text" class="fi" id="lv-handover" placeholder="Colleague name"></div>
    <div id="lv-calc" style="background:var(--accent-bg);border-radius:7px;padding:10px 12px;font-size:13px;color:var(--accent-dark);margin-bottom:12px;display:none;">
      📅 <strong id="lv-days">0</strong> working day(s) requested
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('leaveModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitLeave()"">Submit Request</button>
    </div>
  </div>
</div>

<!-- Regulation Modal -->
<div class="modal-overlay" id="regulationModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Attendance Regulation Request</div><button class="modal-close" onclick="window.closeModal('regulationModal')">×</button></div>
    <div class="fg"><label class="fl">Date</label><input type="date" class="fi" id="reg-date"></div>
    <div class="fg"><label class="fl">Regulation Type</label>
      <select class="fi" id="reg-type"><option>Missing Clock In</option><option>Missing Clock Out</option><option>Wrong Clock In Time</option><option>Wrong Clock Out Time</option><option>Break Adjustment</option></select></div>
    <div class="g2">
      <div class="fg"><label class="fl">Original Time</label><input type="time" class="fi" id="reg-orig"></div>
      <div class="fg"><label class="fl">Requested Time</label><input type="time" class="fi" id="reg-req"></div>
    </div>
    <div class="fg"><label class="fl">Reason</label><textarea class="fi" id="reg-reason" rows="3" placeholder="Explain the reason..."></textarea></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('regulationModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitRegulation()"">Submit</button>
    </div>
  </div>
</div>

<!-- Announcement Modal -->
<div class="modal-overlay" id="announcementModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Post Announcement</div><button class="modal-close" onclick="window.closeModal('announcementModal')">×</button></div>
    <div class="fg"><label class="fl">Title</label><input type="text" class="fi" id="ann-title" placeholder="Announcement title..."></div>
    <div class="g2">
      <div class="fg"><label class="fl">Category</label>
        <select class="fi" id="ann-cat"><option>General</option><option>Policy</option><option>Event</option><option>Important</option><option>Holiday</option></select></div>
      <div class="fg"><label class="fl">Audience</label>
        <select class="fi" id="ann-aud"><option>All Employees</option><option>Engineering</option><option>HR</option><option>Finance</option><option>Marketing</option><option>Management</option></select></div>
    </div>
    <div class="fg"><label class="fl">Message</label><textarea class="fi" id="ann-msg" rows="4" placeholder="Type your announcement..."></textarea></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('announcementModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitAnnouncement()"">Publish</button>
    </div>
  </div>
</div>

<!-- Add Employee Modal -->
<div class="modal-overlay" id="addEmpModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Add New Employee</div><button class="modal-close" onclick="window.closeModal('addEmpModal')">×</button></div>
    <div class="g2">
      <div class="fg"><label class="fl">First Name</label><input type="text" class="fi" id="ne-fname" placeholder="First name"></div>
      <div class="fg"><label class="fl">Last Name</label><input type="text" class="fi" id="ne-lname" placeholder="Last name"></div>
    </div>
    <div class="fg"><label class="fl">Official Email</label><input type="email" class="fi" id="ne-email" placeholder="name@company.com"></div>
    <div class="fg"><label class="fl">Personal Phone</label><input type="text" class="fi" id="ne-phone" placeholder="+92 3XX XXXXXXX"></div>
    <div class="g2">
      <div class="fg"><label class="fl">Department</label>
        <select class="fi" id="ne-dept"><option>Engineering</option><option>HR</option><option>Finance</option><option>Marketing</option><option>Product</option><option>Operations</option></select></div>
      <div class="fg"><label class="fl">Designation</label><input type="text" class="fi" id="ne-desg" placeholder="Job title"></div>
    </div>
    <div class="g2">
      <div class="fg"><label class="fl">Date of Joining</label><input type="date" class="fi" id="ne-doj"></div>
      <div class="fg"><label class="fl">Employment Type</label>
        <select class="fi" id="ne-type"><option>Permanent</option><option>Probation</option><option>Contract</option><option>Intern</option></select></div>
    </div>
    <div class="fg"><label class="fl">Reporting Manager</label><input type="text" class="fi" id="ne-manager" placeholder="Manager name"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('addEmpModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitAddEmployee()"">Add Employee</button>
    </div>
  </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal-overlay" id="holidayModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Add National Holiday</div><button class="modal-close" onclick="window.closeModal('holidayModal')">×</button></div>
    <div class="fg"><label class="fl">Holiday Name</label><input type="text" class="fi" id="hol-name" placeholder="e.g. Independence Day"></div>
    <div class="fg"><label class="fl">Date</label><input type="date" class="fi" id="hol-date"></div>
    <div class="fg"><label class="fl">Type</label>
      <select class="fi" id="hol-type"><option>National</option><option>Religious</option><option>Optional</option></select></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('holidayModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitHoliday()"">Add Holiday</button>
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
  <div class="modal" style="width:600px;">
    <div class="modal-hdr"><div class="modal-title">Edit Employee</div><button class="modal-close" onclick="window.closeModal('editEmpModal')">×</button></div>
    <div class="tabs" style="margin-bottom:16px;" id="edit-emp-tabs">
      <div class="tab active" onclick="window.switchEditTab('personal')">Personal</div>
      <div class="tab" onclick="window.switchEditTab('job')">Job & HR</div>
      <div class="tab" onclick="window.switchEditTab('salary')">Salary & Bank</div>
    </div>
    <!-- Personal Tab -->
    <div id="edit-tab-personal">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">First Name</label><input type="text" class="fi" id="ee-fname"></div>
        <div class="fg"><label class="fl">Last Name</label><input type="text" class="fi" id="ee-lname"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Date of Birth</label><input type="date" class="fi" id="ee-dob"></div>
        <div class="fg"><label class="fl">Gender</label><select class="fi" id="ee-gender"><option>Male</option><option>Female</option><option>Other</option></select></div>
      </div>
      <div class="fg"><label class="fl">CNIC</label><input type="text" class="fi" id="ee-cnic" placeholder="XXXXX-XXXXXXX-X"></div>
      <div class="fg"><label class="fl">Personal Phone</label><input type="text" class="fi" id="ee-phone"></div>
      <div class="fg"><label class="fl">Address</label><input type="text" class="fi" id="ee-address"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Blood Group</label><select class="fi" id="ee-blood"><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option></select></div>
        <div class="fg"><label class="fl">Next of Kin Name</label><input type="text" class="fi" id="ee-kin"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Kin Relationship</label><input type="text" class="fi" id="ee-kinRel"></div>
        <div class="fg"><label class="fl">Kin Phone</label><input type="text" class="fi" id="ee-kinPhone"></div>
      </div>
    </div>
    <!-- Job Tab -->
    <div id="edit-tab-job" style="display:none;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Department</label><select class="fi" id="ee-dept"><option>Engineering</option><option>HR</option><option>Finance</option><option>Marketing</option><option>Product</option><option>Operations</option></select></div>
        <div class="fg"><label class="fl">Designation</label><input type="text" class="fi" id="ee-desg"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Date of Joining</label><input type="date" class="fi" id="ee-doj"></div>
        <div class="fg"><label class="fl">Probation End Date</label><input type="date" class="fi" id="ee-dop"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Employment Type</label><select class="fi" id="ee-type"><option>Permanent</option><option>Probation</option><option>Contract</option><option>Intern</option></select></div>
        <div class="fg"><label class="fl">Status</label><select class="fi" id="ee-status"><option>Active</option><option>Probation</option><option>Inactive</option><option>Resigned</option></select></div>
      </div>
      <div class="fg"><label class="fl">Reporting Manager</label><input type="text" class="fi" id="ee-manager"></div>
      <div class="fg"><label class="fl">Official Email</label><input type="email" class="fi" id="ee-email"></div>
    </div>
    <!-- Salary Tab -->
    <div id="edit-tab-salary" style="display:none;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Basic Salary (PKR)</label><input type="number" class="fi" id="ee-basic" oninput="window.calcGross()"></div>
        <div class="fg"><label class="fl">House Allowance (PKR)</label><input type="number" class="fi" id="ee-house" oninput="window.calcGross()"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="fg"><label class="fl">Transport Allowance (PKR)</label><input type="number" class="fi" id="ee-transport" oninput="window.calcGross()"></div>
        <div class="fg"><label class="fl">Tax Deduction (PKR)</label><input type="number" class="fi" id="ee-tax" oninput="window.calcGross()"></div>
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
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
      <button class="btn" onclick="window.closeModal('editEmpModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveEditEmployee()">Save Changes</button>
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
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('editLeaveModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveLeaveBalance()">Save Balance</button>
    </div>
  </div>
</div>

<!-- Edit Leave Policy Modal (Admin/HR) -->
<div class="modal-overlay" id="editLeavePolicyModal">
  <div class="modal" style="max-width:760px;">
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
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
      <button class="btn" onclick="window.closeModal('editLeavePolicyModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.saveLeavePolicy()">Save Policy</button>
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="changePassModal">
  <div class="modal">
    <div class="modal-hdr"><div class="modal-title">Change Password</div><button class="modal-close" onclick="window.closeModal('changePassModal')">×</button></div>
    <div class="fg"><label class="fl">Current Password</label><input type="password" class="fi" id="cp-current" placeholder="Enter current password"></div>
    <div class="fg"><label class="fl">New Password</label><input type="password" class="fi" id="cp-new" placeholder="Min 6 characters"></div>
    <div class="fg"><label class="fl">Confirm New Password</label><input type="password" class="fi" id="cp-confirm" placeholder="Re-enter new password"></div>
    <div id="cp-err" style="color:var(--red);font-size:12px;margin-bottom:10px;display:none;"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button class="btn" onclick="window.closeModal('changePassModal')">Cancel</button>
      <button class="btn btn-primary" onclick="window.submitChangePassword()">Update Password</button>
    </div>
  </div>
</div>

<!-- Confirm Dialog -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal" style="width:360px;text-align:center;">
    <div style="font-size:32px;margin-bottom:12px;" id="confirm-icon">⚠️</div>
    <div class="modal-title" id="confirm-title" style="margin-bottom:8px;">Are you sure?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:20px;" id="confirm-msg"></div>
    <div style="display:flex;gap:8px;justify-content:center;">
      <button class="btn" onclick="window.closeModal('confirmModal')">Cancel</button>
      <button class="btn btn-danger" id="confirm-ok-btn">Confirm</button>
    </div>
  </div>
</div>
