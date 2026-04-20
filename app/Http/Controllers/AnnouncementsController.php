<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'audience' => ['nullable', 'string', 'max:80'], // all / role:employee / department:<name> / specific
            'message' => ['required', 'string', 'max:10000'],
            'recipient_employee_codes' => ['nullable', 'array'],
            'recipient_employee_codes.*' => ['string', 'max:30'],
        ]);

        $audience = $validated['audience'] ?: 'all';
        $recipientCodes = collect($validated['recipient_employee_codes'] ?? [])
            ->filter()
            ->values();

        if ($audience === 'specific' && $recipientCodes->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Please select at least one employee for a specific announcement.',
            ], 422);
        }

        $id = DB::transaction(function () use ($validated, $request, $audience, $recipientCodes) {
            $id = DB::table('announcements')->insertGetId([
                'title' => $validated['title'],
                'category' => $validated['category'] ?? null,
                'audience' => $audience,
                'message' => $validated['message'],
                'author_user_id' => $request->user()->id,
                'published_on' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($audience === 'specific') {
                $recipientRows = DB::table('users')
                    ->whereIn('employee_code', $recipientCodes->all())
                    ->get(['id'])
                    ->map(fn ($user) => [
                        'announcement_id' => $id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->all();

                if ($recipientRows !== []) {
                    DB::table('announcement_recipients')->insert($recipientRows);
                }
            }

            return $id;
        });

        return response()->json(['ok' => true, 'id' => $id], 201);
    }
}
