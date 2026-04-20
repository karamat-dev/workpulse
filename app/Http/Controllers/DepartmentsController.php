<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentsController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'color' => ['nullable', 'string', 'max:16'],
            'head_employee_code' => ['nullable', 'string', 'max:30'],
        ]);

        $headUserId = null;
        if (!empty($validated['head_employee_code'])) {
            $headUserId = DB::table('users')->where('employee_code', $validated['head_employee_code'])->value('id');
        }

        $id = DB::table('departments')->insertGetId([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#2447D0',
            'head_user_id' => $headUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id], 201);
    }

    public function update(Request $request, string $name): JsonResponse
    {
        $this->ensureAdmin($request);

        $department = DB::table('departments')->where('name', $name)->first();
        if (!$department) {
            return response()->json(['ok' => false, 'message' => 'Department not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,'.$department->id],
            'color' => ['nullable', 'string', 'max:16'],
            'head_employee_code' => ['nullable', 'string', 'max:30'],
        ]);

        $headUserId = null;
        if (!empty($validated['head_employee_code'])) {
            $headUserId = DB::table('users')->where('employee_code', $validated['head_employee_code'])->value('id');
        }

        DB::table('departments')
            ->where('id', $department->id)
            ->update([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? ($department->color ?: '#2447D0'),
                'head_user_id' => $headUserId,
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $name): JsonResponse
    {
        $this->ensureAdmin($request);

        $department = DB::table('departments')->where('name', $name)->first();
        if (!$department) {
            return response()->json(['ok' => false, 'message' => 'Department not found'], 404);
        }

        DB::table('employee_profiles')
            ->where('department_id', $department->id)
            ->update([
                'department_id' => null,
                'updated_at' => now(),
            ]);

        DB::table('departments')->where('id', $department->id)->delete();

        return response()->json(['ok' => true]);
    }
}
