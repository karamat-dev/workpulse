<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\DeletionRecoveryService;

class AnnouncementsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        [$validated, $audience, $recipientCodes] = $this->validateAnnouncementPayload($request);

        $id = DB::transaction(function () use ($validated, $request, $audience, $recipientCodes) {
            return $this->persistAnnouncement(
                announcementId: null,
                authorUserId: $request->user()->id,
                validated: $validated,
                audience: $audience,
                recipientCodes: $recipientCodes,
            );
        });

        return response()->json(['ok' => true, 'id' => $id], 201);
    }

    public function update(Request $request, int $announcementId): JsonResponse
    {
        if (! DB::table('announcements')->where('id', $announcementId)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Announcement not found.',
            ], 404);
        }

        [$validated, $audience, $recipientCodes] = $this->validateAnnouncementPayload($request);

        DB::transaction(function () use ($announcementId, $request, $validated, $audience, $recipientCodes) {
            $this->persistAnnouncement(
                announcementId: $announcementId,
                authorUserId: $request->user()->id,
                validated: $validated,
                audience: $audience,
                recipientCodes: $recipientCodes,
            );
        });

        return response()->json(['ok' => true, 'id' => $announcementId]);
    }

    public function destroy(Request $request, int $announcementId): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request, $announcementId) {
            $announcement = DB::table('announcements')->where('id', $announcementId)->first();
            if (!$announcement) {
                return 0;
            }

            $recipients = DB::table('announcement_recipients')
                ->where('announcement_id', $announcementId)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            app(DeletionRecoveryService::class)->record('announcement', (string) $announcement->title, [
                'table' => 'announcements',
                'keyColumn' => 'id',
                'row' => (array) $announcement,
                'recipients' => $recipients,
            ], (int) $request->user()->id);

            DB::table('announcement_recipients')
                ->where('announcement_id', $announcementId)
                ->delete();

            return DB::table('announcements')
                ->where('id', $announcementId)
                ->delete();
        });

        if (! $deleted) {
            return response()->json([
                'ok' => false,
                'message' => 'Announcement not found.',
            ], 404);
        }

        return response()->json(['ok' => true]);
    }

    private function validateAnnouncementPayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'audience' => ['nullable', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:10000'],
            'recipient_employee_codes' => ['nullable', 'array'],
            'recipient_employee_codes.*' => ['string', 'max:30'],
        ]);

        $audience = $validated['audience'] ?: 'all';
        $recipientCodes = collect($validated['recipient_employee_codes'] ?? [])
            ->filter()
            ->values();

        if ($audience === 'specific' && $recipientCodes->isEmpty()) {
            throw ValidationException::withMessages([
                'recipient_employee_codes' => ['Please select at least one employee for a specific announcement.'],
            ]);
        }

        return [$validated, $audience, $recipientCodes];
    }

    private function persistAnnouncement(?int $announcementId, int $authorUserId, array $validated, string $audience, $recipientCodes): int
    {
        if ($announcementId) {
            DB::table('announcements')
                ->where('id', $announcementId)
                ->update([
                    'title' => $validated['title'],
                    'category' => $validated['category'] ?? null,
                    'audience' => $audience,
                    'message' => $validated['message'],
                    'author_user_id' => $authorUserId,
                    'published_on' => now()->toDateString(),
                    'updated_at' => now(),
                ]);

            DB::table('announcement_recipients')
                ->where('announcement_id', $announcementId)
                ->delete();
        } else {
            $announcementId = DB::table('announcements')->insertGetId([
                'title' => $validated['title'],
                'category' => $validated['category'] ?? null,
                'audience' => $audience,
                'message' => $validated['message'],
                'author_user_id' => $authorUserId,
                'published_on' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($audience === 'specific') {
            $recipientRows = DB::table('users')
                ->whereIn('employee_code', $recipientCodes->all())
                ->get(['id'])
                ->map(fn ($user) => [
                    'announcement_id' => $announcementId,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            if ($recipientRows !== []) {
                DB::table('announcement_recipients')->insert($recipientRows);
            }
        }

        return $announcementId;
    }
}
