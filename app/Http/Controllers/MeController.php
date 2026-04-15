<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSeeConfidential = $user->hasPermission('employees.view_confidential');

        $select = [
            'users.employee_code',
            'users.name',
            'users.email',
            'users.role',
            'departments.name as dept',
            'employee_profiles.designation as desg',
            'employee_profiles.date_of_joining as doj',
            'employee_profiles.probation_end_date as dop',
            'employee_profiles.manager_user_id',
            'mgr.name as manager',
            'employee_profiles.employment_type as type',
            'employee_profiles.status',
            // personal
            'employee_profiles.date_of_birth as dob',
            'employee_profiles.gender',
            'employee_profiles.cnic',
            'employee_profiles.personal_phone as phone',
            'employee_profiles.personal_email',
            'employee_profiles.address',
            'employee_profiles.blood_group as blood',
            // next of kin
            'employee_profiles.next_of_kin_name as kin',
            'employee_profiles.next_of_kin_relationship as kinRel',
            'employee_profiles.next_of_kin_phone as kinPhone',
        ];

        if ($canSeeConfidential) {
            $select = array_merge($select, [
                'employee_profiles.basic_salary as basic',
                'employee_profiles.house_allowance as house',
                'employee_profiles.transport_allowance as transport',
                'employee_profiles.tax_deduction as tax',
                'employee_profiles.bank_name as bank',
                'employee_profiles.bank_account_no as acct',
                'employee_profiles.bank_iban as iban',
            ]);
        }

        $row = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->where('users.id', $user->id)
            ->select($select)
            ->first();

        return response()->json(['ok' => true, 'profile' => $row]);
    }
}

