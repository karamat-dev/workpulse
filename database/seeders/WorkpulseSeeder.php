<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WorkpulseSeeder extends Seeder
{
    public function run(): void
    {
        // Note: MySQL `TRUNCATE` performs an implicit commit, so we intentionally
        // do not wrap the whole seeding process in a transaction.
        $this->truncateForReseed();
        $this->seedUsers();
        $this->seedDepartments();
        $this->seedEmployeeProfilesAndReportingLines();
        $this->seedHolidays();
        $this->seedAnnouncements();
        $this->seedAttendance();
        $this->seedLeave();
        $this->seedPermissionsAndPolicies();
        $this->seedCompanySettings();
    }

    private function truncateForReseed(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'role_permissions',
            'permissions',
            'module_policies',
            'leave_approvals',
            'leave_requests',
            'leave_balances',
            'leave_policies',
            'leave_types',
            'attendance_regulation_requests',
            'attendance_punches',
            'attendance_days',
            'announcements',
            'events',
            'holidays',
            'reporting_lines',
            'employee_profiles',
            'departments',
            'company_settings',
        ] as $table) {
            DB::table($table)->truncate();
        }

        // Keep users table (Breeze auth) but wipe non-test users for clean demo.
        DB::table('users')->whereNot('email', 'test@example.com')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'employee_code' => 'EMP-001',
                'name' => 'Ali Raza',
                'email' => 'employee1@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'HR-001',
                'name' => 'Sara Ahmed',
                'email' => 'hr@workpulse.com',
                'password' => 'hr123',
                'role' => 'hr',
            ],
            [
                'employee_code' => 'EMP-002',
                'name' => 'Ayesha Khan',
                'email' => 'employee2@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-003',
                'name' => 'Hamza Siddiqui',
                'email' => 'employee3@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-004',
                'name' => 'Fatima Noor',
                'email' => 'employee4@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-005',
                'name' => 'Usman Tariq',
                'email' => 'employee5@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-006',
                'name' => 'Hira Malik',
                'email' => 'employee6@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-007',
                'name' => 'Bilal Javed',
                'email' => 'employee7@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-008',
                'name' => 'Maham Iqbal',
                'email' => 'employee8@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-009',
                'name' => 'Danish Saleem',
                'email' => 'employee9@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'EMP-010',
                'name' => 'Zoya Ahmed',
                'email' => 'employee10@workpulse.com',
                'password' => 'emp123',
                'role' => 'employee',
            ],
            [
                'employee_code' => 'ADM-001',
                'name' => 'Zainab Hussain',
                'email' => 'admin@workpulse.com',
                'password' => 'admin123',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $u) {
            DB::table('users')->updateOrInsert(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'employee_code' => $u['employee_code'],
                    'role' => $u['role'],
                    'password' => Hash::make($u['password']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedEmployeeProfilesAndReportingLines(): void
    {
        // Seed employee directory data (from prototype's DB.employees)
        $employees = [
            [
                'employee_code' => 'EMP-001',
                'department' => 'Engineering',
                'designation' => 'Software Engineer',
                'date_of_joining' => '2024-01-15',
                'probation_end_date' => '2024-04-15',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 300 1234567',
                'personal_email' => 'employee1@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1995-03-15',
                'gender' => 'Male',
                'cnic' => '42301-1234567-8',
                'address' => '123 Gulberg III, Lahore',
                'blood_group' => 'O+',
                'next_of_kin_name' => 'Nadia Raza',
                'next_of_kin_relationship' => 'Sister',
                'next_of_kin_phone' => '+92 301 7654321',
                'basic_salary' => 120000,
                'house_allowance' => 30000,
                'transport_allowance' => 10000,
                'tax_deduction' => 6000,
                'bank_name' => 'HBL',
                'bank_account_no' => '****-1234',
                'bank_iban' => 'PK36HBL...',
            ],
            [
                'employee_code' => 'EMP-002',
                'department' => 'Engineering',
                'designation' => 'QA Engineer',
                'date_of_joining' => '2024-02-01',
                'probation_end_date' => '2024-05-01',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 301 2345678',
                'personal_email' => 'employee2@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1996-04-10',
                'gender' => 'Female',
                'cnic' => '42301-2345678-9',
                'address' => '45 DHA Phase 5, Lahore',
                'blood_group' => 'B+',
                'next_of_kin_name' => 'Irfan Khan',
                'next_of_kin_relationship' => 'Father',
                'next_of_kin_phone' => '+92 333 4567890',
                'basic_salary' => 105000,
                'house_allowance' => 26000,
                'transport_allowance' => 8000,
                'tax_deduction' => 5500,
                'bank_name' => 'MCB',
                'bank_account_no' => '****-5678',
                'bank_iban' => 'PK36MCB...',
            ],
            [
                'employee_code' => 'EMP-003',
                'department' => 'Product',
                'designation' => 'UI Designer',
                'date_of_joining' => '2024-03-10',
                'probation_end_date' => null,
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 302 3456789',
                'personal_email' => 'employee3@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1997-07-22',
                'gender' => 'Male',
                'cnic' => '42301-3456789-0',
                'address' => '78 Model Town, Lahore',
                'blood_group' => 'A+',
                'next_of_kin_name' => 'Amina Siddiqui',
                'next_of_kin_relationship' => 'Mother',
                'next_of_kin_phone' => '+92 344 5678901',
                'basic_salary' => 98000,
                'house_allowance' => 22000,
                'transport_allowance' => 7000,
                'tax_deduction' => 4200,
                'bank_name' => 'HBL',
                'bank_account_no' => '****-9012',
                'bank_iban' => 'PK36HBL...',
            ],
            [
                'employee_code' => 'EMP-004',
                'department' => 'Human Resources',
                'designation' => 'HR Executive',
                'date_of_joining' => '2024-04-10',
                'probation_end_date' => '2024-07-10',
                'last_working_date' => null,
                'manager_name' => 'Sara Ahmed',
                'personal_phone' => '+92 303 4567890',
                'personal_email' => 'employee4@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1998-11-05',
                'gender' => 'Female',
                'cnic' => '42301-4567890-1',
                'address' => '22 Johar Town, Lahore',
                'blood_group' => 'AB+',
                'next_of_kin_name' => 'Saba Noor',
                'next_of_kin_relationship' => 'Father',
                'next_of_kin_phone' => '+92 355 6789012',
                'basic_salary' => 90000,
                'house_allowance' => 20000,
                'transport_allowance' => 5000,
                'tax_deduction' => 2500,
                'bank_name' => 'UBL',
                'bank_account_no' => '****-3456',
                'bank_iban' => 'PK36UBL...',
            ],
            [
                'employee_code' => 'EMP-005',
                'department' => 'Engineering',
                'designation' => 'Software Engineer',
                'date_of_joining' => '2023-06-01',
                'probation_end_date' => null,
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 304 5678901',
                'personal_email' => 'employee5@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1994-02-18',
                'gender' => 'Male',
                'cnic' => '42301-5678901-2',
                'address' => '15 Wapda Town, Lahore',
                'blood_group' => 'O-',
                'next_of_kin_name' => 'Maham Tariq',
                'next_of_kin_relationship' => 'Spouse',
                'next_of_kin_phone' => '+92 366 7890123',
                'basic_salary' => 100000,
                'house_allowance' => 25000,
                'transport_allowance' => 7000,
                'tax_deduction' => 3500,
                'bank_name' => 'Meezan',
                'bank_account_no' => '****-7890',
                'bank_iban' => 'PK36MEZ...',
            ],
            [
                'employee_code' => 'EMP-006',
                'department' => 'Finance',
                'designation' => 'Accountant',
                'date_of_joining' => '2024-05-05',
                'probation_end_date' => '2024-08-05',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 305 6789012',
                'personal_email' => 'employee6@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1993-06-14',
                'gender' => 'Female',
                'cnic' => '42301-6789012-3',
                'address' => '90 Faisal Town, Lahore',
                'blood_group' => 'B-',
                'next_of_kin_name' => 'Naveed Malik',
                'next_of_kin_relationship' => 'Brother',
                'next_of_kin_phone' => '+92 377 8901234',
                'basic_salary' => 93000,
                'house_allowance' => 21000,
                'transport_allowance' => 6000,
                'tax_deduction' => 2800,
                'bank_name' => 'HBL',
                'bank_account_no' => '****-2468',
                'bank_iban' => 'PK36HBL...',
            ],
            [
                'employee_code' => 'EMP-007',
                'department' => 'Marketing',
                'designation' => 'Content Specialist',
                'date_of_joining' => '2024-05-20',
                'probation_end_date' => '2024-08-20',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 306 7890123',
                'personal_email' => 'employee7@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1992-09-11',
                'gender' => 'Male',
                'cnic' => '42301-7890123-4',
                'address' => '14 Bahria Town, Lahore',
                'blood_group' => 'A-',
                'next_of_kin_name' => 'Hadia Javed',
                'next_of_kin_relationship' => 'Spouse',
                'next_of_kin_phone' => '+92 388 9012345',
                'basic_salary' => 88000,
                'house_allowance' => 20000,
                'transport_allowance' => 5500,
                'tax_deduction' => 2400,
                'bank_name' => 'MCB',
                'bank_account_no' => '****-3579',
                'bank_iban' => 'PK36MCB...',
            ],
            [
                'employee_code' => 'EMP-008',
                'department' => 'Operations',
                'designation' => 'Operations Coordinator',
                'date_of_joining' => '2024-06-15',
                'probation_end_date' => '2024-09-15',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 307 8901234',
                'personal_email' => 'employee8@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1995-10-09',
                'gender' => 'Female',
                'cnic' => '42301-8901234-5',
                'address' => '38 Cantt, Lahore',
                'blood_group' => 'O+',
                'next_of_kin_name' => 'Rafay Iqbal',
                'next_of_kin_relationship' => 'Brother',
                'next_of_kin_phone' => '+92 399 0123456',
                'basic_salary' => 86000,
                'house_allowance' => 18000,
                'transport_allowance' => 5000,
                'tax_deduction' => 2200,
                'bank_name' => 'UBL',
                'bank_account_no' => '****-4680',
                'bank_iban' => 'PK36UBL...',
            ],
            [
                'employee_code' => 'EMP-009',
                'department' => 'Engineering',
                'designation' => 'Backend Developer',
                'date_of_joining' => '2024-07-01',
                'probation_end_date' => '2024-10-01',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 308 9012345',
                'personal_email' => 'employee9@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1991-12-20',
                'gender' => 'Male',
                'cnic' => '42301-9012345-6',
                'address' => '61 Valencia, Lahore',
                'blood_group' => 'AB-',
                'next_of_kin_name' => 'Sana Saleem',
                'next_of_kin_relationship' => 'Sister',
                'next_of_kin_phone' => '+92 311 0123456',
                'basic_salary' => 125000,
                'house_allowance' => 32000,
                'transport_allowance' => 9000,
                'tax_deduction' => 6800,
                'bank_name' => 'Meezan',
                'bank_account_no' => '****-5791',
                'bank_iban' => 'PK36MEZ...',
            ],
            [
                'employee_code' => 'EMP-010',
                'department' => 'Product',
                'designation' => 'Product Analyst',
                'date_of_joining' => '2024-07-18',
                'probation_end_date' => '2024-10-18',
                'last_working_date' => null,
                'manager_name' => 'Zainab Hussain',
                'personal_phone' => '+92 309 0123456',
                'personal_email' => 'employee10@workpulse.com',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'date_of_birth' => '1996-01-28',
                'gender' => 'Female',
                'cnic' => '42301-0123456-7',
                'address' => '12 Township, Lahore',
                'blood_group' => 'A+',
                'next_of_kin_name' => 'Hammad Ahmed',
                'next_of_kin_relationship' => 'Brother',
                'next_of_kin_phone' => '+92 322 1234567',
                'basic_salary' => 95000,
                'house_allowance' => 24000,
                'transport_allowance' => 6500,
                'tax_deduction' => 3000,
                'bank_name' => 'HBL',
                'bank_account_no' => '****-6802',
                'bank_iban' => 'PK36HBL...',
            ],
        ];

        foreach ($employees as $e) {
            $userId = DB::table('users')->where('employee_code', $e['employee_code'])->value('id');
            if (!$userId) {
                continue;
            }

            $departmentId = DB::table('departments')->where('name', $e['department'])->value('id');
            $managerUserId = DB::table('users')->where('name', $e['manager_name'])->value('id');

            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'department_id' => $departmentId,
                    'manager_user_id' => $managerUserId,
                    'designation' => $e['designation'],
                    'date_of_joining' => $e['date_of_joining'],
                    'probation_end_date' => $e['probation_end_date'],
                    'last_working_date' => $e['last_working_date'],
                    'employment_type' => $e['employment_type'],
                    'status' => $e['status'],
                    'date_of_birth' => $e['date_of_birth'],
                    'gender' => $e['gender'],
                    'cnic' => $e['cnic'],
                    'personal_phone' => $e['personal_phone'],
                    'personal_email' => $e['personal_email'],
                    'address' => $e['address'],
                    'blood_group' => $e['blood_group'],
                    'next_of_kin_name' => $e['next_of_kin_name'],
                    'next_of_kin_relationship' => $e['next_of_kin_relationship'],
                    'next_of_kin_phone' => $e['next_of_kin_phone'],
                    'basic_salary' => $e['basic_salary'],
                    'house_allowance' => $e['house_allowance'],
                    'transport_allowance' => $e['transport_allowance'],
                    'tax_deduction' => $e['tax_deduction'],
                    'bank_name' => $e['bank_name'],
                    'bank_account_no' => $e['bank_account_no'],
                    'bank_iban' => $e['bank_iban'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('reporting_lines')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'manager_user_id' => $managerUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedDepartments(): void
    {
        $departments = [
            ['name' => 'Engineering', 'head' => 'Hassan Ali', 'color' => '#2447D0'],
            ['name' => 'Human Resources', 'head' => 'Sara Ahmed', 'color' => '#1B7A42'],
            ['name' => 'Finance', 'head' => 'Khalid Rehman', 'color' => '#6B3FA0'],
            ['name' => 'Marketing', 'head' => 'Sana Khan', 'color' => '#A05C00'],
            ['name' => 'Product', 'head' => 'Tariq Mahmood', 'color' => '#C0392B'],
            ['name' => 'Operations', 'head' => 'Bilal Ahmed', 'color' => '#6E6C63'],
            ['name' => 'Management', 'head' => 'Zainab Hussain', 'color' => '#6B3FA0'],
        ];

        foreach ($departments as $d) {
            $headUserId = DB::table('users')->where('name', $d['head'])->value('id');

            DB::table('departments')->updateOrInsert(
                ['name' => $d['name']],
                [
                    'color' => $d['color'],
                    'head_user_id' => $headUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedHolidays(): void
    {
        $holidays = [
            ['date' => '2025-01-01', 'name' => "New Year's Day", 'type' => 'National'],
            ['date' => '2025-02-05', 'name' => 'Kashmir Solidarity Day', 'type' => 'National'],
            ['date' => '2025-03-23', 'name' => 'Pakistan Day', 'type' => 'National'],
            ['date' => '2025-04-20', 'name' => 'Eid-ul-Fitr', 'type' => 'Religious'],
            ['date' => '2025-04-21', 'name' => 'Eid-ul-Fitr (2nd Day)', 'type' => 'Religious'],
            ['date' => '2025-05-01', 'name' => 'Labour Day', 'type' => 'National'],
            ['date' => '2025-08-14', 'name' => 'Independence Day', 'type' => 'National'],
            ['date' => '2025-11-09', 'name' => 'Iqbal Day', 'type' => 'National'],
            ['date' => '2025-12-25', 'name' => 'Quaid Day / Christmas', 'type' => 'National'],
        ];

        foreach ($holidays as $h) {
            DB::table('holidays')->updateOrInsert(
                ['date' => $h['date']],
                [
                    'name' => $h['name'],
                    'type' => $h['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedAnnouncements(): void
    {
        $authorId = DB::table('users')->where('email', 'hr@workpulse.com')->value('id')
            ?? DB::table('users')->where('role', 'admin')->value('id');

        $announcements = [
            [
                'title' => '🎉 Eid Mubarak! Office Closure Notice',
                'category' => 'Holiday',
                'audience' => 'all',
                'message' => 'The office will be closed from April 20–22 for Eid-ul-Fitr. Wishing everyone a blessed Eid!',
                'published_on' => '2025-04-10',
            ],
            [
                'title' => '📋 Q2 Town Hall — Save the Date',
                'category' => 'Event',
                'audience' => 'all',
                'message' => 'Q2 Town Hall will be held on April 22, 2025 at 3:00 PM in the Main Conference Room. Attendance is mandatory for all department heads.',
                'published_on' => '2025-04-08',
            ],
            [
                'title' => '✅ New Attendance Policy — Effective May 1',
                'category' => 'Policy',
                'audience' => 'all',
                'message' => 'Starting May 1, the shift starts at 11:00 AM with a 10-minute grace period. Late arrivals after 11:10 AM will be marked \"Late\". Repeated late arrivals (3+) will trigger an HR review.',
                'published_on' => '2025-04-05',
            ],
        ];

        foreach ($announcements as $a) {
            DB::table('announcements')->updateOrInsert(
                ['title' => $a['title'], 'published_on' => $a['published_on']],
                [
                    'category' => $a['category'],
                    'audience' => $a['audience'],
                    'message' => $a['message'],
                    'author_user_id' => $authorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedAttendance(): void
    {
        $rows = [
            ['emp' => 'EMP-001', 'date' => '2025-04-10', 'in' => '08:58', 'out' => null, 'breakOut' => null, 'breakIn' => null, 'status' => 'Present', 'late' => false, 'overtime' => 0],
            ['emp' => 'EMP-001', 'date' => '2025-04-09', 'in' => '09:15', 'out' => '18:00', 'breakOut' => '13:00', 'breakIn' => '13:30', 'status' => 'Present', 'late' => true, 'overtime' => 0],
            ['emp' => 'EMP-001', 'date' => '2025-04-08', 'in' => null, 'out' => null, 'breakOut' => null, 'breakIn' => null, 'status' => 'Leave', 'late' => false, 'overtime' => 0],
            ['emp' => 'EMP-001', 'date' => '2025-04-07', 'in' => '08:55', 'out' => '18:33', 'breakOut' => '13:00', 'breakIn' => '13:30', 'status' => 'Present', 'late' => false, 'overtime' => 33],
            ['emp' => 'EMP-001', 'date' => '2025-04-04', 'in' => '09:00', 'out' => '17:00', 'breakOut' => '13:00', 'breakIn' => '13:30', 'status' => 'Present', 'late' => false, 'overtime' => 0],
            ['emp' => 'EMP-001', 'date' => '2025-04-03', 'in' => null, 'out' => null, 'breakOut' => null, 'breakIn' => null, 'status' => 'Absent', 'late' => false, 'overtime' => 0],
            ['emp' => 'EMP-002', 'date' => '2025-04-10', 'in' => '08:45', 'out' => null, 'breakOut' => null, 'breakIn' => null, 'status' => 'Present', 'late' => false, 'overtime' => 0],
            ['emp' => 'EMP-003', 'date' => '2025-04-10', 'in' => '09:02', 'out' => null, 'breakOut' => null, 'breakIn' => null, 'status' => 'Present', 'late' => false, 'overtime' => 0],
            ['emp' => 'EMP-005', 'date' => '2025-04-10', 'in' => null, 'out' => null, 'breakOut' => null, 'breakIn' => null, 'status' => 'Absent', 'late' => false, 'overtime' => 0],
        ];

        foreach ($rows as $r) {
            $userId = DB::table('users')->where('employee_code', $r['emp'])->value('id');
            if (!$userId) {
                continue;
            }

            DB::table('attendance_days')->updateOrInsert(
                ['user_id' => $userId, 'date' => $r['date']],
                [
                    'status' => $r['status'],
                    'late' => (bool) $r['late'],
                    'overtime_minutes' => (int) $r['overtime'],
                    'worked_minutes' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $this->maybePunch($userId, $r['date'], 'clock_in', $r['in']);
            $this->maybePunch($userId, $r['date'], 'break_out', $r['breakOut']);
            $this->maybePunch($userId, $r['date'], 'break_in', $r['breakIn']);
            $this->maybePunch($userId, $r['date'], 'clock_out', $r['out']);
        }

        $regs = [
            ['code' => 'REG-001', 'emp' => 'EMP-001', 'date' => '2025-04-03', 'type' => 'Missing Clock In', 'orig' => '—', 'req' => '11:00', 'reason' => 'Biometric device issue', 'status' => 'Pending'],
            ['code' => 'REG-002', 'emp' => 'EMP-001', 'date' => '2025-03-28', 'type' => 'Wrong Clock Out Time', 'orig' => '17:00', 'req' => '18:30', 'reason' => 'Client call overrun', 'status' => 'Approved'],
            ['code' => 'REG-003', 'emp' => 'EMP-001', 'date' => '2025-03-15', 'type' => 'Break Adjustment', 'orig' => '60 min', 'req' => '30 min', 'reason' => 'Urgent delivery', 'status' => 'Rejected'],
        ];

        foreach ($regs as $reg) {
            $userId = DB::table('users')->where('employee_code', $reg['emp'])->value('id');
            if (!$userId) {
                continue;
            }

            DB::table('attendance_regulation_requests')->updateOrInsert(
                ['code' => $reg['code']],
                [
                    'user_id' => $userId,
                    'date' => $reg['date'],
                    'type' => $reg['type'],
                    'original_value' => $reg['orig'],
                    'requested_value' => $reg['req'],
                    'reason' => $reg['reason'],
                    'status' => $reg['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function maybePunch(int $userId, string $date, string $type, ?string $time): void
    {
        if (!$time) {
            return;
        }

        $dt = $date.' '.$time.':00';

        DB::table('attendance_punches')->updateOrInsert(
            ['user_id' => $userId, 'date' => $date, 'type' => $type, 'punched_at' => $dt],
            ['created_at' => now(), 'updated_at' => now()],
        );
    }

    private function seedLeave(): void
    {
        $types = [
            ['name' => 'Annual Leave', 'code' => 'annual', 'paid' => true],
            ['name' => 'Sick Leave', 'code' => 'sick', 'paid' => true],
            ['name' => 'Unpaid Leave', 'code' => 'unpaid', 'paid' => false],
            ['name' => 'Paternity Leave', 'code' => 'paternity', 'paid' => true],
            ['name' => 'Maternity Leave', 'code' => 'maternity', 'paid' => true],
            ['name' => 'Marriage Leave', 'code' => 'marriage', 'paid' => true],
            ['name' => 'Bereavement Leave', 'code' => 'bereavement', 'paid' => true],
        ];

        foreach ($types as $t) {
            DB::table('leave_types')->updateOrInsert(
                ['code' => $t['code']],
                ['name' => $t['name'], 'paid' => $t['paid'], 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $year = 2025;
        $policies = [
            ['code' => 'annual', 'quota' => 18],
            ['code' => 'sick', 'quota' => 7],
            ['code' => 'paternity', 'quota' => 5],
            ['code' => 'maternity', 'quota' => 90],
            ['code' => 'marriage', 'quota' => 7],
            ['code' => 'bereavement', 'quota' => 3],
        ];

        foreach ($policies as $p) {
            $typeId = DB::table('leave_types')->where('code', $p['code'])->value('id');
            if (!$typeId) {
                continue;
            }

            DB::table('leave_policies')->updateOrInsert(
                ['year' => $year, 'leave_type_id' => $typeId],
                [
                    'quota_days' => $p['quota'],
                    'pro_rata' => true,
                    'carry_forward_days' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        // Seed leave requests from prototype
        $reqs = [
            ['code' => 'LV-001', 'emp' => 'EMP-001', 'type' => 'annual', 'from' => '2025-04-08', 'to' => '2025-04-08', 'days' => 1, 'reason' => 'Personal work', 'handover' => 'Omar Farooq', 'status' => 'Approved', 'mgr' => 'Approved', 'hr' => 'Approved'],
            ['code' => 'LV-002', 'emp' => 'EMP-005', 'type' => 'sick', 'from' => '2025-04-11', 'to' => '2025-04-11', 'days' => 1, 'reason' => 'Fever', 'handover' => 'Hassan Ali', 'status' => 'Pending', 'mgr' => 'Pending', 'hr' => 'Waiting'],
            ['code' => 'LV-003', 'emp' => 'EMP-002', 'type' => 'annual', 'from' => '2025-04-18', 'to' => '2025-04-20', 'days' => 3, 'reason' => 'Family event', 'handover' => 'Nadia Iqbal', 'status' => 'Pending', 'mgr' => 'Approved', 'hr' => 'Pending'],
        ];

        foreach ($reqs as $r) {
            $userId = DB::table('users')->where('employee_code', $r['emp'])->value('id');
            $typeId = DB::table('leave_types')->where('code', $r['type'])->value('id');
            if (!$userId || !$typeId) {
                continue;
            }

            $leaveRequestId = DB::table('leave_requests')->insertGetId([
                'code' => $r['code'],
                'user_id' => $userId,
                'leave_type_id' => $typeId,
                'from_date' => $r['from'],
                'to_date' => $r['to'],
                'days' => $r['days'],
                'reason' => $r['reason'],
                'handover_to' => $r['handover'],
                'status' => $r['status'] === 'Approved' ? 'Approved' : 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('leave_approvals')->insert([
                [
                    'leave_request_id' => $leaveRequestId,
                    'step' => 'manager',
                    'reviewer_user_id' => null,
                    'status' => $r['mgr'],
                    'reviewed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'leave_request_id' => $leaveRequestId,
                    'step' => 'hr',
                    'reviewer_user_id' => null,
                    'status' => $r['hr'],
                    'reviewed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // Seed a sample balance (from prototype leaveBalances)
        $emp1 = DB::table('users')->where('employee_code', 'EMP-001')->value('id');
        if ($emp1) {
            $balances = [
                ['code' => 'annual', 'allocated' => 18, 'used' => 0],
                ['code' => 'sick', 'allocated' => 7, 'used' => 0],
                ['code' => 'paternity', 'allocated' => 5, 'used' => 0],
                ['code' => 'maternity', 'allocated' => 90, 'used' => 0],
                ['code' => 'marriage', 'allocated' => 7, 'used' => 0],
                ['code' => 'bereavement', 'allocated' => 3, 'used' => 0],
            ];

            foreach ($balances as $b) {
                $typeId = DB::table('leave_types')->where('code', $b['code'])->value('id');
                if (!$typeId) {
                    continue;
                }
                $remaining = max(0, $b['allocated'] - $b['used']);

                DB::table('leave_balances')->updateOrInsert(
                    ['user_id' => $emp1, 'year' => $year, 'leave_type_id' => $typeId],
                    [
                        'allocated_days' => $b['allocated'],
                        'used_days' => $b['used'],
                        'remaining_days' => $remaining,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    private function seedPermissionsAndPolicies(): void
    {
        $permissions = [
            ['key' => 'employees.view', 'label' => 'View employees'],
            ['key' => 'employees.view_confidential', 'label' => 'View salary/bank'],
            ['key' => 'employees.manage', 'label' => 'Manage employees'],
            ['key' => 'attendance.punch', 'label' => 'Clock in/out and breaks'],
            ['key' => 'attendance.view', 'label' => 'View attendance'],
            ['key' => 'attendance.manage', 'label' => 'Manage attendance/policies'],
            ['key' => 'leave.apply', 'label' => 'Apply for leave'],
            ['key' => 'leave.approve_manager', 'label' => 'Approve leave (manager)'],
            ['key' => 'leave.approve_hr', 'label' => 'Approve leave (HR)'],
            ['key' => 'leave.manage', 'label' => 'Manage leave types/quotas'],
            ['key' => 'announcements.manage', 'label' => 'Create announcements'],
            ['key' => 'reports.view', 'label' => 'View reports'],
            ['key' => 'company.manage', 'label' => 'Manage company settings'],
        ];

        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $p['key']],
                ['label' => $p['label'], 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $permIds = DB::table('permissions')->pluck('id', 'key');

        $roleMatrix = [
            'admin' => array_fill_keys(array_keys($permissions), true),
            'manager' => [
                'employees.view' => true,
                'employees.view_confidential' => false,
                'employees.manage' => false,
                'attendance.punch' => true,
                'attendance.view' => true,
                'attendance.manage' => false,
                'leave.apply' => true,
                'leave.approve_manager' => true,
                'leave.approve_hr' => false,
                'leave.manage' => false,
                'announcements.manage' => false,
                'reports.view' => true,
                'company.manage' => false,
            ],
            'hr' => [
                'employees.view' => true,
                'employees.view_confidential' => true,
                'employees.manage' => true,
                'attendance.punch' => true,
                'attendance.view' => true,
                'attendance.manage' => true,
                'leave.apply' => true,
                'leave.approve_manager' => false,
                'leave.approve_hr' => true,
                'leave.manage' => true,
                'announcements.manage' => true,
                'reports.view' => true,
                'company.manage' => true,
            ],
            'employee' => [
                'employees.view' => false,
                'employees.view_confidential' => false,
                'employees.manage' => false,
                'attendance.punch' => true,
                'attendance.view' => true,
                'attendance.manage' => false,
                'leave.apply' => true,
                'leave.approve_manager' => false,
                'leave.approve_hr' => false,
                'leave.manage' => false,
                'announcements.manage' => false,
                'reports.view' => false,
                'company.manage' => false,
            ],
        ];

        foreach ($roleMatrix as $role => $allowedMap) {
            foreach ($allowedMap as $key => $allowed) {
                $permId = $permIds[$key] ?? null;
                if (!$permId) {
                    continue;
                }
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'permission_id' => $permId],
                    ['allowed' => (bool) $allowed, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        $policies = [
            ['module' => 'attendance', 'key' => 'shift_start', 'value' => '11:00', 'value_type' => 'string'],
            ['module' => 'attendance', 'key' => 'grace_minutes', 'value' => '10', 'value_type' => 'int'],
            ['module' => 'attendance', 'key' => 'late_trigger_count', 'value' => '3', 'value_type' => 'int'],
            ['module' => 'leave', 'key' => 'approval_flow', 'value' => json_encode(['manager', 'hr']), 'value_type' => 'json'],
        ];

        foreach ($policies as $p) {
            DB::table('module_policies')->updateOrInsert(
                ['module' => $p['module'], 'key' => $p['key']],
                ['value' => $p['value'], 'value_type' => $p['value_type'], 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function seedCompanySettings(): void
    {
        DB::table('company_settings')->updateOrInsert(
            ['id' => 1],
            [
                'company_name' => 'WorkPulse',
                'website_link' => null,
                'official_email' => null,
                'official_contact_no' => null,
                'office_location' => null,
                'linkedin_page' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
