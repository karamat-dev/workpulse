// ══════════════════════════════════════════════════
//  DATA STORE
// ══════════════════════════════════════════════════
const DB = {
  currentUser: null,
  currentRole: null,

  users: [
    {id:'EMP-001',fname:'Ali',lname:'Raza',email:'employee1@workpulse.com',pass:'emp123',role:'employee',dept:'Engineering',desg:'Software Engineer',doj:'2024-01-15',dop:'2024-04-15',manager:'Zainab Hussain',phone:'+92 300 1234567',avatar:'AR',avatarColor:'#2447D0',status:'Active',type:'Permanent'},
    {id:'EMP-002',fname:'Sara',lname:'Ahmed',email:'hr@workpulse.com',pass:'hr123',role:'hr',dept:'Human Resources',desg:'HR Manager',doj:'2021-03-01',manager:'Zainab Hussain',phone:'+92 301 2345678',avatar:'SA',avatarColor:'#1B7A42',status:'Active',type:'Permanent'},
    {id:'ADM-001',fname:'Zainab',lname:'Hussain',email:'admin@workpulse.com',pass:'admin123',role:'admin',dept:'Management',desg:'CEO',doj:'2018-01-01',manager:'—',phone:'+92 321 9876543',avatar:'ZH',avatarColor:'#6B3FA0',status:'Active',type:'Permanent'},
  ],

  employees: [
    {id:'EMP-001',fname:'Ahmed',lname:'Karim',dept:'Engineering',desg:'Senior Engineer',doj:'2022-01-15',dop:'2022-04-15',manager:'Hassan Ali',phone:'+92 300 1234567',email:'ahmed.k@workpulse.com',avatar:'AK',avatarColor:'#2447D0',status:'Active',type:'Permanent',dob:'1990-03-15',gender:'Male',cnic:'42301-1234567-8',address:'123 Gulberg III, Lahore',blood:'O+',kin:'Fatima Karim',kinRel:'Spouse',kinPhone:'+92 301 7654321',basic:150000,house:40000,transport:10000,tax:8500,bank:'HBL',acct:'****-1234',iban:'PK36HBL...'},
    {id:'EMP-002',fname:'Sara',lname:'Ahmed',dept:'Human Resources',desg:'HR Manager',doj:'2021-03-01',manager:'Zainab Hussain',phone:'+92 301 2345678',email:'sara.a@workpulse.com',avatar:'SA',avatarColor:'#1B7A42',status:'Active',type:'Permanent',dob:'1996-04-10',gender:'Female',cnic:'42301-2345678-9',address:'45 DHA Phase 5, Lahore',blood:'B+',kin:'Ali Ahmed',kinRel:'Father',kinPhone:'+92 333 4567890',basic:120000,house:30000,transport:8000,tax:5500,bank:'MCB',acct:'****-5678',iban:'PK36MCB...'},
    {id:'EMP-003',fname:'Hassan',lname:'Ali',dept:'Engineering',desg:'Engineering Director',doj:'2019-09-10',manager:'Zainab Hussain',phone:'+92 302 3456789',email:'hassan.a@workpulse.com',avatar:'HA',avatarColor:'#0D7373',status:'Active',type:'Permanent',dob:'1985-07-22',gender:'Male',cnic:'42301-3456789-0',address:'78 Model Town, Lahore',blood:'A+',kin:'Aisha Hassan',kinRel:'Spouse',kinPhone:'+92 344 5678901',basic:200000,house:60000,transport:15000,tax:14000,bank:'HBL',acct:'****-9012',iban:'PK36HBL...'},
    {id:'EMP-004',fname:'Maria',lname:'Santos',dept:'Product',desg:'UI Designer',doj:'2025-04-10',dop:'2025-07-10',manager:'Khalid PM',phone:'+92 303 4567890',email:'maria.s@workpulse.com',avatar:'MS',avatarColor:'#A05C00',status:'Probation',type:'Probation',dob:'1998-11-05',gender:'Female',cnic:'42301-4567890-1',address:'22 Johar Town, Lahore',blood:'AB+',kin:'Jose Santos',kinRel:'Father',kinPhone:'+92 355 6789012',basic:80000,house:20000,transport:5000,tax:0,bank:'UBL',acct:'****-3456',iban:'PK36UBL...'},
    {id:'EMP-005',fname:'Omar',lname:'Farooq',dept:'Engineering',desg:'Software Engineer',doj:'2023-06-01',manager:'Hassan Ali',phone:'+92 304 5678901',email:'omar.f@workpulse.com',avatar:'OF',avatarColor:'#C0392B',status:'Active',type:'Permanent',dob:'1994-02-18',gender:'Male',cnic:'42301-5678901-2',address:'15 Wapda Town, Lahore',blood:'O-',kin:'Noor Farooq',kinRel:'Spouse',kinPhone:'+92 366 7890123',basic:100000,house:25000,transport:7000,tax:3500,bank:'Meezan',acct:'****-7890',iban:'PK36MEZ...'},
  ],

  attendance: [
    {empId:'EMP-001',date:'2025-04-10',in:'08:58',out:null,breakOut:null,breakIn:null,status:'Present',late:false,overtime:0},
    {empId:'EMP-001',date:'2025-04-09',in:'09:15',out:'18:00',breakOut:'13:00',breakIn:'13:30',status:'Present',late:true,overtime:0},
    {empId:'EMP-001',date:'2025-04-08',in:null,out:null,breakOut:null,breakIn:null,status:'Leave',late:false,overtime:0},
    {empId:'EMP-001',date:'2025-04-07',in:'08:55',out:'18:33',breakOut:'13:00',breakIn:'13:30',status:'Present',late:false,overtime:33},
    {empId:'EMP-001',date:'2025-04-04',in:'09:00',out:'17:00',breakOut:'13:00',breakIn:'13:30',status:'Present',late:false,overtime:0},
    {empId:'EMP-001',date:'2025-04-03',in:null,out:null,breakOut:null,breakIn:null,status:'Absent',late:false,overtime:0},
    {empId:'EMP-002',date:'2025-04-10',in:'08:45',out:null,breakOut:null,breakIn:null,status:'Present',late:false,overtime:0},
    {empId:'EMP-003',date:'2025-04-10',in:'09:02',out:null,breakOut:null,breakIn:null,status:'Present',late:false,overtime:0},
    {empId:'EMP-005',date:'2025-04-10',in:null,out:null,breakOut:null,breakIn:null,status:'Absent',late:false,overtime:0},
  ],

  leaves: [
    {id:'LV-001',empId:'EMP-001',empName:'Ahmed Karim',dept:'Engineering',type:'Annual Leave',from:'2025-04-08',to:'2025-04-08',days:1,reason:'Personal work',handover:'Omar Farooq',applied:'2025-04-01',managerStatus:'Approved',hrStatus:'Approved',status:'Approved'},
    {id:'LV-002',empId:'EMP-005',empName:'Omar Farooq',dept:'Engineering',type:'Sick Leave',from:'2025-04-11',to:'2025-04-11',days:1,reason:'Fever',handover:'Hassan Ali',applied:'2025-04-10',managerStatus:'Pending',hrStatus:'Waiting',status:'Pending'},
    {id:'LV-003',empId:'EMP-002',empName:'Sara Ahmed',dept:'HR',type:'Annual Leave',from:'2025-04-18',to:'2025-04-20',days:3,reason:'Family event',handover:'Nadia Iqbal',applied:'2025-04-08',managerStatus:'Approved',hrStatus:'Pending',status:'Pending'},
  ],

  regulations: [
    {id:'REG-001',empId:'EMP-001',date:'2025-04-03',type:'Missing Clock In',orig:'—',req:'11:00',reason:'Biometric device issue',status:'Pending'},
    {id:'REG-002',empId:'EMP-001',date:'2025-03-28',type:'Wrong Clock Out Time',orig:'17:00',req:'18:30',reason:'Client call overrun',status:'Approved'},
    {id:'REG-003',empId:'EMP-001',date:'2025-03-15',type:'Break Adjustment',orig:'60 min',req:'30 min',reason:'Urgent delivery',status:'Rejected'},
  ],

  announcements: [
    {id:'AN-001',title:'🎉 Eid Mubarak! Office Closure Notice',cat:'Holiday',audience:'All Employees',msg:'The office will be closed from April 20–22 for Eid-ul-Fitr. Wishing everyone a blessed Eid!',author:'Sara Ahmed',role:'HR Manager',date:'2025-04-10'},
    {id:'AN-002',title:'📋 Q2 Town Hall — Save the Date',cat:'Event',audience:'All Employees',msg:'Q2 Town Hall will be held on April 22, 2025 at 3:00 PM in the Main Conference Room. Attendance is mandatory for all department heads.',author:'Zainab Hussain',role:'CEO',date:'2025-04-08'},
    {id:'AN-003',title:'✅ New Attendance Policy — Effective May 1',cat:'Policy',audience:'All Employees',msg:'Starting May 1, the shift starts at 11:00 AM with a 10-minute grace period. Late arrivals after 11:10 AM will be marked "Late". Repeated late arrivals (3+) will trigger an HR review.',author:'Sara Ahmed',role:'HR Manager',date:'2025-04-05'},
  ],

  company: {
    company_name:'WorkPulse Technologies Pvt. Ltd.',
    website_link:'www.workpulse.com',
    official_email:'info@workpulse.com',
    official_contact_no:'+92 42 35761234',
    office_location:'12 Tech City, Arfa Software Park, Lahore',
    linkedin_page:'linkedin.com/company/workpulse',
  },

  companyPolicies: [],
  backups: [],
  deletedBackups: [],
  recoveryItems: [],

  events: [],

  holidays: [
    {date:'2025-01-01',name:"New Year's Day",type:'National'},
    {date:'2025-02-05',name:'Kashmir Solidarity Day',type:'National'},
    {date:'2025-03-23',name:'Pakistan Day',type:'National'},
    {date:'2025-04-20',name:'Eid-ul-Fitr',type:'Religious'},
    {date:'2025-04-21',name:'Eid-ul-Fitr (2nd Day)',type:'Religious'},
    {date:'2025-05-01',name:'Labour Day',type:'National'},
    {date:'2025-08-14',name:'Independence Day',type:'National'},
    {date:'2025-11-09',name:'Iqbal Day',type:'National'},
    {date:'2025-12-25',name:'Quaid Day / Christmas',type:'National'},
  ],

  departments: [
    {name:'Engineering',head:'Hassan Ali',color:'#2447D0',count:42,present:39,leave:2,absent:1},
    {name:'Human Resources',head:'Sara Ahmed',color:'#1B7A42',count:8,present:7,leave:1,absent:0},
    {name:'Finance',head:'Khalid Rehman',color:'#6B3FA0',count:15,present:14,leave:1,absent:0},
    {name:'Marketing',head:'Sana Khan',color:'#A05C00',count:19,present:14,leave:3,absent:2},
    {name:'Product',head:'Tariq Mahmood',color:'#C0392B',count:21,present:18,leave:2,absent:1},
    {name:'Operations',head:'Bilal Ahmed',color:'#6E6C63',count:43,present:38,leave:3,absent:2},
  ],

  leaveBalances: {
    'EMP-001':{annual:18,sick:7,casual:3,paternity:5,maternity:90,marriage:7,bereavement:3},
  },

  leaveTypes: [
    {name:'Annual Leave', code:'annual', paid:true},
    {name:'Sick Leave', code:'sick', paid:true},
    {name:'Casual Leave', code:'casual', paid:true},
    {name:'Paternity Leave', code:'paternity', paid:true},
    {name:'Maternity Leave', code:'maternity', paid:true},
    {name:'Marriage Leave', code:'marriage', paid:true},
    {name:'Bereavement Leave', code:'bereavement', paid:true},
    {name:'Unpaid Leave', code:'unpaid', paid:false},
  ],

  leavePolicies: [
    {name:'Annual Leave', code:'annual', paid:true, quota_days:21, pro_rata:true, carry_forward_days:5},
    {name:'Sick Leave', code:'sick', paid:true, quota_days:10, pro_rata:false, carry_forward_days:0},
    {name:'Casual Leave', code:'casual', paid:true, quota_days:5, pro_rata:false, carry_forward_days:0},
    {name:'Paternity Leave', code:'paternity', paid:true, quota_days:5, pro_rata:false, carry_forward_days:0},
    {name:'Maternity Leave', code:'maternity', paid:true, quota_days:90, pro_rata:false, carry_forward_days:0},
    {name:'Marriage Leave', code:'marriage', paid:true, quota_days:7, pro_rata:false, carry_forward_days:0},
    {name:'Bereavement Leave', code:'bereavement', paid:true, quota_days:3, pro_rata:false, carry_forward_days:0},
    {name:'Unpaid Leave', code:'unpaid', paid:false, quota_days:30, pro_rata:false, carry_forward_days:0},
  ],

  liveAttendance: [
    {name:'Ahmed Karim',dept:'Engineering',status:'in',since:'08:55'},
    {name:'Sara Ahmed',dept:'HR',status:'break',since:'13:02'},
    {name:'Hassan Ali',dept:'Engineering',status:'in',since:'09:02'},
    {name:'Nadia Iqbal',dept:'HR',status:'leave',since:'Annual'},
    {name:'Omar Farooq',dept:'Engineering',status:'out',since:'17:30'},
    {name:'Zara Khan',dept:'Engineering',status:'in',since:'09:45'},
    {name:'Fatima Malik',dept:'Finance',status:'in',since:'08:50'},
    {name:'Khalid Rehman',dept:'Finance',status:'break',since:'13:10'},
    {name:'Aisha Siddiqui',dept:'Marketing',status:'in',since:'09:00'},
    {name:'Usman Tariq',dept:'Product',status:'in',since:'09:00'},
    {name:'Sana Butt',dept:'Marketing',status:'leave',since:'Sick'},
    {name:'Bilal Ahmed',dept:'Operations',status:'out',since:'17:00'},
  ],

  notifications: [],
  notificationCount: 0,
  customNotifications: [],
  browserNotifications: {
    initialized: false,
    permission: 'default',
    sentIds: [],
    promptRequested: false,
  },

  // Punch state per user session
  punchState: {
    punchedIn: false,
    onBreak: false,
    clockInTime: null,
    clockOutTime: null,
    breakOutTime: null,
    breakInTime: null,
    totalBreakMs: 0,
    currentSessionBreakMs: 0,
    sessionLogs: [],
  },

  currentApprovalId: null,
};

// ══════════════════════════════════════════════════
//  CLOCK
// ══════════════════════════════════════════════════
let clockInterval = null;
function startClock(){
  function tick(){
    const now = new Date();
    const t = now.toLocaleTimeString('en-GB');
    const el = document.getElementById('tb-clock');
    const mobileEl = document.getElementById('tb-clock-mobile');
    const ecwEl = document.getElementById('ecw-time-display');
    const cwEl = document.getElementById('cw-time-display');
    if(el) el.textContent = t;
    if(mobileEl) mobileEl.textContent = t;
    if(ecwEl) ecwEl.textContent = t;
    if(cwEl) cwEl.textContent = t;

    const workHourNodes = Array.from(document.querySelectorAll('[data-work-hours-live]'));
    workHourNodes.forEach((node) => {
      const format = node.getAttribute('data-work-hours-format') || 'standard';
      if(format === 'compact' && typeof getTodayWorkedBreakdown === 'function' && typeof formatWorkedHoursClockLabel === 'function'){
        node.textContent = formatWorkedHoursClockLabel(getTodayWorkedBreakdown(now).totalMinutes);
        return;
      }
      if(typeof getLiveWorkedTimeLabel === 'function'){
        node.textContent = getLiveWorkedTimeLabel(now);
      }
    });
  }
  tick();
  if(clockInterval) clearInterval(clockInterval);
  clockInterval = setInterval(tick, 1000);
}

// ══════════════════════════════════════════════════
