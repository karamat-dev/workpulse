<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HolidaysController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
        ]);

        DB::table('holidays')->updateOrInsert(
            ['date' => $validated['date']],
            [
                'name' => $validated['name'],
                'type' => $validated['type'] ?? 'National',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return response()->json(['ok' => true], 201);
    }
}

