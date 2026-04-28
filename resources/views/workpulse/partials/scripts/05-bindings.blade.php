//  EXPOSE ALL FUNCTIONS TO WINDOW (fixes onclick scope)
// ══════════════════════════════════════════════════
window.doLogin = doLogin;
window.doLogout = doLogout;
window.showPage = showPage;
window.sendForgotPassword = sendForgotPassword;
window.setNewEmployeePasswordMode = setNewEmployeePasswordMode;
window.generateNewEmployeePassword = generateNewEmployeePassword;
window.initializeNewEmployeePassword = initializeNewEmployeePassword;
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
window.submitAddEmployee = submitAddEmployee;
window.submitHoliday = submitHoliday;
window.deleteHoliday = deleteHoliday;
window.approveLeave = approveLeave;
window.openApproval = openApproval;
window.deleteEmployee = deleteEmployee;
window.cancelRegulation = cancelRegulation;
window.filterTable = filterTable;
window.filterMonitor = filterMonitor;
window.updateLeaveTodayFilters = updateLeaveTodayFilters;
window.applyLeaveTodayFilters = applyLeaveTodayFilters;
window.filterEmpDept = filterEmpDept;
window.viewEmpProfile = viewEmpProfile;
window.calcLeaveDays = calcLeaveDays;
window.openEditEmployee = openEditEmployee;
window.switchEditTab = switchEditTab;
window.openCreateDepartment = openCreateDepartment;
window.openEditDepartment = openEditDepartment;
window.saveDepartment = saveDepartment;
window.deleteDepartment = deleteDepartment;
window.calcGross = calcGross;
window.saveEditEmployee = saveEditEmployee;
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
window.exportCompanyDetailsJson = exportCompanyDetailsJson;
window.importCompanyDetails = importCompanyDetails;
window.uploadCompanyPolicy = uploadCompanyPolicy;
window.deleteCompanyPolicy = deleteCompanyPolicy;
window.printPage = printPage;
window.wpReload = wpReload;


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
