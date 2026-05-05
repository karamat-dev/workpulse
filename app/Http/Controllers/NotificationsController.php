<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\DeletionRecoveryService;
use Illuminate\Support\Str;

class NotificationsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
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
            return response()->json([
                'ok' => false,
                'message' => 'Please select at least one employee for a specific notification.',
            ], 422);
        }

        $recipients = $this->resolveRecipients($audience, $recipientCodes);
        if ($recipients->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No recipients matched the selected audience.',
            ], 422);
        }

        $batchCode = 'NTF-'.Str::upper(Str::random(8));
        $author = $request->user();
        $meta = [
            'author_user_id' => $author->id,
            'author_name' => $author->name,
            'audience' => $audience,
            'recipient_employee_codes' => $recipientCodes->values()->all(),
            'is_custom' => true,
        ];

        DB::table('employee_notifications')->insert(
            $recipients->map(fn ($recipient) => [
                'user_id' => $recipient->id,
                'type' => 'admin_custom_notification',
                'title' => $validated['title'],
                'message' => $validated['message'],
                'reference_type' => 'admin_custom_notification',
                'reference_code' => $batchCode,
                'meta' => json_encode($meta),
                'is_read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        return response()->json([
            'ok' => true,
            'referenceCode' => $batchCode,
        ], 201);
    }

    public function update(Request $request, string $referenceCode): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:10000'],
            'recipient_employee_codes' => ['nullable', 'array'],
            'recipient_employee_codes.*' => ['string', 'max:30'],
        ]);

        $existing = DB::table('employee_notifications')
            ->where('reference_type', 'admin_custom_notification')
            ->where('reference_code', $referenceCode)
            ->exists();

        if (! $existing) {
            return response()->json([
                'ok' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $audience = $validated['audience'] ?: 'all';
        $recipientCodes = collect($validated['recipient_employee_codes'] ?? [])
            ->filter()
            ->values();

        if ($audience === 'specific' && $recipientCodes->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Please select at least one employee for a specific notification.',
            ], 422);
        }

        $recipients = $this->resolveRecipients($audience, $recipientCodes);
        if ($recipients->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No recipients matched the selected audience.',
            ], 422);
        }

        $author = $request->user();
        $meta = [
            'author_user_id' => $author->id,
            'author_name' => $author->name,
            'audience' => $audience,
            'recipient_employee_codes' => $recipientCodes->values()->all(),
            'is_custom' => true,
        ];

        DB::transaction(function () use ($referenceCode, $validated, $recipients, $meta) {
            DB::table('employee_notifications')
                ->where('reference_type', 'admin_custom_notification')
                ->where('reference_code', $referenceCode)
                ->delete();

            DB::table('employee_notifications')->insert(
                $recipients->map(fn ($recipient) => [
                    'user_id' => $recipient->id,
                    'type' => 'admin_custom_notification',
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                    'reference_type' => 'admin_custom_notification',
                    'reference_code' => $referenceCode,
                    'meta' => json_encode($meta),
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        });

        return response()->json([
            'ok' => true,
            'referenceCode' => $referenceCode,
        ]);
    }

    public function destroy(Request $request, string $referenceCode): JsonResponse
    {
        $rows = DB::table('employee_notifications')
            ->where('reference_type', 'admin_custom_notification')
            ->where('reference_code', $referenceCode)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        if ($rows !== []) {
            app(DeletionRecoveryService::class)->record('notification', 'Notification '.$referenceCode, [
                'rows' => $rows,
            ], (int) $request->user()->id);
        }

        $deleted = DB::table('employee_notifications')
            ->where('reference_type', 'admin_custom_notification')
            ->where('reference_code', $referenceCode)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'ok' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
        ]);
    }

    public static function groupedCustomNotifications(): Collection
    {
        return DB::table('employee_notifications')
            ->where('reference_type', 'admin_custom_notification')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('reference_code')
            ->map(function (Collection $rows, string $referenceCode) {
                $first = $rows->first();
                $meta = $first->meta ? json_decode((string) $first->meta, true) : [];

                return [
                    'referenceCode' => $referenceCode,
                    'title' => $first->title,
                    'message' => $first->message,
                    'audience' => $meta['audience'] ?? 'all',
                    'recipientEmployeeCodes' => array_values($meta['recipient_employee_codes'] ?? []),
                    'recipientCount' => $rows->count(),
                    'authorName' => $meta['author_name'] ?? 'Admin',
                    'createdAt' => $first->created_at,
                    'updatedAt' => $first->updated_at,
                ];
            })
            ->values();
    }

    private function resolveRecipients(string $audience, Collection $recipientCodes): Collection
    {
        $query = DB::table('users')
            ->select('id', 'employee_code', 'role')
            ->whereNotNull('email');

        if ($audience === 'all') {
            return $query->get();
        }

        if (str_starts_with($audience, 'role:')) {
            return $query
                ->where('role', substr($audience, 5))
                ->get();
        }

        if (str_starts_with($audience, 'department:')) {
            $department = substr($audience, 11);

            return $query
                ->join('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
                ->join('departments', 'departments.id', '=', 'employee_profiles.department_id')
                ->where('departments.name', $department)
                ->get(['users.id', 'users.employee_code', 'users.role']);
        }

        if ($audience === 'specific') {
            return $query
                ->whereIn('employee_code', $recipientCodes->all())
                ->get();
        }

        return collect();
    }
}
