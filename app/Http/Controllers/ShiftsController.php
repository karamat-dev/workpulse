<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShiftsController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $shifts = DB::table('shifts')
            ->orderBy('name')
            ->get()
            ->map(fn ($shift) => [
                'id' => $shift->id,
                'code' => $shift->code,
                'name' => $shift->name,
                'start' => substr((string) $shift->start_time, 0, 5),
                'end' => substr((string) $shift->end_time, 0, 5),
                'grace' => (int) $shift->grace_minutes,
                'break' => (int) ($shift->break_minutes ?? 60),
                'workingDays' => $shift->working_days,
                'active' => (bool) $shift->is_active,
            ])
            ->values();

        return response()->json(['ok' => true, 'shifts' => $shifts]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'grace' => ['nullable', 'integer', 'min:0', 'max:240'],
            'break' => ['nullable', 'integer', 'min:0', 'max:480'],
            'workingDays' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $code = Str::of($validated['code'] ?: $validated['name'])->lower()->slug('_')->value();
        $code = $code !== '' ? $code : 'shift';
        $baseCode = $code;
        $suffix = 1;
        while (DB::table('shifts')->where('code', $code)->exists()) {
            $code = $baseCode.'_'.$suffix;
            $suffix++;
        }

        $id = DB::table('shifts')->insertGetId([
            'code' => $code,
            'name' => $validated['name'],
            'start_time' => $validated['start'].':00',
            'end_time' => $validated['end'].':00',
            'grace_minutes' => $validated['grace'] ?? 10,
            'break_minutes' => $validated['break'] ?? 60,
            'working_days' => $validated['workingDays'] ?? 'Mon-Fri',
            'is_active' => (bool) ($validated['active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id], 201);
    }

    public function update(Request $request, int $shiftId): JsonResponse
    {
        $this->ensureAdmin($request);

        $shift = DB::table('shifts')->where('id', $shiftId)->first();
        if (!$shift) {
            return response()->json(['ok' => false, 'message' => 'Shift not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'grace' => ['nullable', 'integer', 'min:0', 'max:240'],
            'break' => ['nullable', 'integer', 'min:0', 'max:480'],
            'workingDays' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $code = Str::of($validated['code'] ?: $validated['name'])->lower()->slug('_')->value();
        $code = $code !== '' ? $code : $shift->code;

        $duplicate = DB::table('shifts')
            ->where('code', $code)
            ->where('id', '!=', $shiftId)
            ->exists();

        if ($duplicate) {
            return response()->json(['ok' => false, 'message' => 'Shift code already exists.'], 422);
        }

        DB::table('shifts')
            ->where('id', $shiftId)
            ->update([
                'code' => $code,
                'name' => $validated['name'],
                'start_time' => $validated['start'].':00',
                'end_time' => $validated['end'].':00',
                'grace_minutes' => $validated['grace'] ?? 10,
                'break_minutes' => $validated['break'] ?? 60,
                'working_days' => $validated['workingDays'] ?? 'Mon-Fri',
                'is_active' => (bool) ($validated['active'] ?? true),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, int $shiftId): JsonResponse
    {
        $this->ensureAdmin($request);

        $shift = DB::table('shifts')->where('id', $shiftId)->first();
        if (!$shift) {
            return response()->json(['ok' => false, 'message' => 'Shift not found'], 404);
        }

        DB::table('employee_profiles')
            ->where('shift_id', $shiftId)
            ->update([
                'shift_id' => null,
                'updated_at' => now(),
            ]);

        DB::table('shifts')->where('id', $shiftId)->delete();

        return response()->json(['ok' => true]);
    }
}
