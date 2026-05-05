//  EXPOSE ALL FUNCTIONS TO WINDOW (fixes onclick scope)
// ══════════════════════════════════════════════════
window.doLogin = doLogin;
window.doLogout = doLogout;
window.showPage = showPage;
window.toggleMobileNav = toggleMobileNav;
window.toggleTopbarQuickActions = toggleTopbarQuickActions;
window.sendForgotPassword = sendForgotPassword;
window.setNewEmployeePasswordMode = setNewEmployeePasswordMode;
window.generateNewEmployeePassword = generateNewEmployeePassword;
window.initializeNewEmployeePassword = initializeNewEmployeePassword;
window.syncEmployeeRoleOptions = syncEmployeeRoleOptions;
window.openAddEmployeeWithRole = openAddEmployeeWithRole;
window.switchTab = switchTab;
window.openModal = openModal;
window.closeModal = closeModal;
window.punchIn = punchIn;
window.punchOut = punchOut;
window.breakOut = breakOut;
window.breakIn = breakIn;
window.submitLeave = submitLeave;
window.submitRegulation = submitRegulation;
window.submitAnnouncement = submitAnnouncement;
window.toggleAnnouncementRecipients = toggleAnnouncementRecipients;
window.toggleAnnouncementVoteFields = toggleAnnouncementVoteFields;
window.addAnnouncementVoteOption = addAnnouncementVoteOption;
window.removeAnnouncementVoteOption = removeAnnouncementVoteOption;
window.openAnnouncementVote = openAnnouncementVote;
window.submitAnnouncementVote = submitAnnouncementVote;
window.closeAnnouncementVote = closeAnnouncementVote;
window.openAnnouncementVoteResults = openAnnouncementVoteResults;
window.exportAnnouncementVoteResults = exportAnnouncementVoteResults;
window.filterAnnouncementVoteResults = filterAnnouncementVoteResults;
window.submitAddEmployee = submitAddEmployee;
window.submitHoliday = submitHoliday;
window.deleteHoliday = deleteHoliday;
window.approveLeave = approveLeave;
window.openApproval = openApproval;
window.reviewRegulationRequest = reviewRegulationRequest;
window.openNotificationModal = openNotificationModal;
window.deleteNotification = deleteNotification;
window.markAllNotificationsRead = markAllNotificationsRead;
window.openNotificationTarget = openNotificationTarget;
window.deleteEmployee = deleteEmployee;
window.cancelRegulation = cancelRegulation;
window.filterTable = filterTable;
window.filterMonitor = filterMonitor;
window.updateLeaveTodayFilters = updateLeaveTodayFilters;
window.applyLeaveTodayFilters = applyLeaveTodayFilters;
window.filterEmpDept = filterEmpDept;
window.viewEmpProfile = viewEmpProfile;
window.canModifyEmployee = canModifyEmployee;
window.calcLeaveDays = calcLeaveDays;
window.openEditEmployee = openEditEmployee;
window.switchEditTab = switchEditTab;
window.openCreateDepartment = openCreateDepartment;
window.openEditDepartment = openEditDepartment;
window.saveDepartment = saveDepartment;
window.deleteDepartment = deleteDepartment;
window.calcGross = calcGross;
window.saveEditEmployee = saveEditEmployee;
window.uploadOffboardingDocument = uploadOffboardingDocument;
window.saveOffboardingDocument = saveOffboardingDocument;
window.deleteOffboardingDocument = deleteOffboardingDocument;
window.completeEmployeeOffboarding = completeEmployeeOffboarding;
window.openAccountSettings = openAccountSettings;
window.submitAccountSettings = submitAccountSettings;
window.openEditLeave = openEditLeave;
window.saveLeaveBalance = saveLeaveBalance;
window.openCreateLeaveType = openCreateLeaveType;
window.openEditLeaveType = openEditLeaveType;
window.saveLeaveType = saveLeaveType;
window.deleteLeaveType = deleteLeaveType;
window.deleteEmployeeCnicDocument = deleteEmployeeCnicDocument;
window.openCreateShift = openCreateShift;
window.openEditShift = openEditShift;
window.saveShift = saveShift;
window.deleteShift = deleteShift;
window.openEditLeavePolicy = openEditLeavePolicy;
window.saveLeavePolicy = saveLeavePolicy;
window.submitChangePassword = submitChangePassword;
window.showConfirm = showConfirm;
window.exportAttendanceCSV = exportAttendanceCSV;
window.exportLeaveCSV = exportLeaveCSV;
window.exportEmployeeCSV = exportEmployeeCSV;
window.exportTransferData = exportTransferData;
window.exportEmployeeProfilesJson = exportEmployeeProfilesJson;
window.importEmployeeProfiles = importEmployeeProfiles;
window.confirmEmployeeImportMapping = confirmEmployeeImportMapping;
window.exportCompanyDetailsJson = exportCompanyDetailsJson;
window.importCompanyDetails = importCompanyDetails;
window.uploadCompanyPolicy = uploadCompanyPolicy;
window.deleteCompanyPolicy = deleteCompanyPolicy;
window.printPage = printPage;
window.wpReload = wpReload;

const passwordEyeIcon = '<svg data-password-eye viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.1 12s3.6-7 9.9-7 9.9 7 9.9 7-3.6 7-9.9 7-9.9-7-9.9-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
const passwordEyeOffIcon = '<svg data-password-eye-off hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.2A10.7 10.7 0 0 1 12 4c6.3 0 9.9 8 9.9 8a18.4 18.4 0 0 1-2.8 3.9"></path><path d="M14.1 14.1A3 3 0 0 1 9.9 9.9"></path><path d="M6.6 6.6A18.5 18.5 0 0 0 2.1 12s3.6 7 9.9 7a10.8 10.8 0 0 0 5.4-1.5"></path><path d="M2 2l20 20"></path></svg>';

function syncPasswordToggleIcon(btn, revealed) {
  if (!btn.querySelector('[data-password-eye]')) {
    btn.innerHTML = passwordEyeIcon + passwordEyeOffIcon;
  }
  const eye = btn.querySelector('[data-password-eye]');
  const eyeOff = btn.querySelector('[data-password-eye-off]');
  if (eye) eye.hidden = revealed;
  if (eyeOff) eyeOff.hidden = !revealed;
  btn.setAttribute('aria-label', revealed ? 'Hide password' : 'Show password');
}

document.querySelectorAll('[data-password-toggle]').forEach(function(btn) {
  const input = btn.closest('.password-input-wrap')?.querySelector('input');
  syncPasswordToggleIcon(btn, input?.type === 'text');
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-password-toggle]');
  if (!btn) return;

  const wrap = btn.closest('.password-input-wrap');
  const input = wrap ? wrap.querySelector('input') : null;
  if (!input) return;

  const showing = input.type === 'text';
  input.type = showing ? 'password' : 'text';
  syncPasswordToggleIcon(btn, input.type === 'text');
  input.focus();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(function(m) {
      m.classList.remove('open');
    });
  }
  if (e.key === 'Enter') {
    var ls = document.getElementById('login-screen');
    if (ls && ls.style.display !== 'none') { window.doLogin(); }
  }
});
