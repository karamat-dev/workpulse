<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\DeletionRecoveryService;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

            $voteOptions = DB::table('announcement_vote_options')
                ->where('announcement_id', $announcementId)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $votes = DB::table('announcement_votes')
                ->where('announcement_id', $announcementId)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

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
                'voteOptions' => $voteOptions,
                'votes' => $votes,
            ], (int) $request->user()->id);

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

    public function vote(Request $request, int $announcementId): JsonResponse
    {
        $announcement = $this->visibleAnnouncementForUser($request->user(), $announcementId);
        if (!$announcement || !(bool) $announcement->has_vote) {
            return response()->json(['ok' => false, 'message' => 'Voting announcement not found.'], 404);
        }

        if ($announcement->vote_status !== 'open') {
            return response()->json(['ok' => false, 'message' => 'This vote is closed.'], 422);
        }

        $validated = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        $optionExists = DB::table('announcement_vote_options')
            ->where('announcement_id', $announcementId)
            ->where('id', $validated['option_id'])
            ->exists();

        if (!$optionExists) {
            return response()->json(['ok' => false, 'message' => 'Please select a valid option.'], 422);
        }

        DB::table('announcement_votes')->updateOrInsert(
            ['announcement_id' => $announcementId, 'user_id' => $request->user()->id],
            [
                'option_id' => $validated['option_id'],
                'voted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function closeVote(Request $request, int $announcementId): JsonResponse
    {
        $updated = DB::table('announcements')
            ->where('id', $announcementId)
            ->where('has_vote', true)
            ->update([
                'vote_status' => 'closed',
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['ok' => false, 'message' => 'Voting announcement not found.'], 404);
        }

        return response()->json(['ok' => true]);
    }

    public function voteResults(Request $request, int $announcementId): JsonResponse
    {
        $announcement = DB::table('announcements')->where('id', $announcementId)->first();
        if (!$announcement || !(bool) $announcement->has_vote) {
            return response()->json(['ok' => false, 'message' => 'Voting announcement not found.'], 404);
        }

        return response()->json([
            'ok' => true,
            'results' => $this->buildVoteResults($announcement),
        ]);
    }

    public function voteResultsCsv(Request $request, int $announcementId): StreamedResponse|JsonResponse
    {
        $announcement = DB::table('announcements')->where('id', $announcementId)->first();
        if (!$announcement || !(bool) $announcement->has_vote) {
            return response()->json(['ok' => false, 'message' => 'Voting announcement not found.'], 404);
        }

        $results = $this->buildVoteResults($announcement);
        $filename = 'announcement-vote-'.$announcementId.'.csv';

        return response()->streamDownload(function () use ($results) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Announcement ID', 'Announcement Title', 'Question', 'Vote Status', 'Employee ID', 'Employee Name', 'Department', 'Selected Option', 'Voted At']);

            foreach ($results['responses'] as $response) {
                fputcsv($out, [
                    $results['announcementId'],
                    $results['title'],
                    $results['question'],
                    $results['status'],
                    $response['employeeCode'],
                    $response['name'],
                    $response['department'],
                    $response['selectedOption'],
                    $response['votedAt'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
            'has_vote' => ['nullable', 'boolean'],
            'vote_question' => ['nullable', 'string', 'max:255'],
            'vote_options' => ['nullable', 'array'],
            'vote_options.*.id' => ['nullable', 'integer'],
            'vote_options.*.label' => ['nullable', 'string', 'max:255'],
            'show_results_to_employees_after_close' => ['nullable', 'boolean'],
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

        if (!empty($validated['has_vote'])) {
            $options = collect($validated['vote_options'] ?? [])
                ->map(fn ($option) => [
                    'id' => $option['id'] ?? null,
                    'label' => trim((string) ($option['label'] ?? '')),
                ])
                ->filter(fn ($option) => $option['label'] !== '')
                ->values();

            if (trim((string) ($validated['vote_question'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'vote_question' => ['Voting question is required.'],
                ]);
            }

            if ($options->count() < 2) {
                throw ValidationException::withMessages([
                    'vote_options' => ['Add at least two voting choices.'],
                ]);
            }

            $validated['vote_options'] = $options->all();
        } else {
            $validated['has_vote'] = false;
            $validated['vote_question'] = null;
            $validated['vote_options'] = [];
            $validated['show_results_to_employees_after_close'] = false;
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
                    'has_vote' => (bool) ($validated['has_vote'] ?? false),
                    'vote_question' => $validated['has_vote'] ? $validated['vote_question'] : null,
                    'vote_status' => $validated['has_vote'] ? DB::raw('vote_status') : 'open',
                    'show_results_to_employees_after_close' => (bool) ($validated['show_results_to_employees_after_close'] ?? false),
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
                'has_vote' => (bool) ($validated['has_vote'] ?? false),
                'vote_question' => $validated['has_vote'] ? $validated['vote_question'] : null,
                'vote_status' => 'open',
                'show_results_to_employees_after_close' => (bool) ($validated['show_results_to_employees_after_close'] ?? false),
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

        $this->persistVoteOptions($announcementId, $validated);

        return $announcementId;
    }

    private function persistVoteOptions(int $announcementId, array $validated): void
    {
        if (empty($validated['has_vote'])) {
            if (DB::table('announcement_votes')->where('announcement_id', $announcementId)->exists()) {
                throw ValidationException::withMessages([
                    'has_vote' => ['This announcement already has votes and voting cannot be removed.'],
                ]);
            }

            DB::table('announcement_vote_options')->where('announcement_id', $announcementId)->delete();
            return;
        }

        $incoming = collect($validated['vote_options'] ?? []);
        $existingOptions = DB::table('announcement_vote_options')
            ->where('announcement_id', $announcementId)
            ->get()
            ->keyBy('id');

        $incomingIds = $incoming->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        foreach ($existingOptions as $option) {
            $hasVotes = DB::table('announcement_votes')->where('option_id', $option->id)->exists();
            if (!$hasVotes) {
                continue;
            }

            $incomingOption = $incoming->first(fn ($item) => (int) ($item['id'] ?? 0) === (int) $option->id);
            if (!$incomingOption || trim((string) $incomingOption['label']) !== (string) $option->label) {
                throw ValidationException::withMessages([
                    'vote_options' => ['Choices with existing votes cannot be renamed or removed.'],
                ]);
            }
        }

        foreach ($existingOptions as $option) {
            if (!$incomingIds->contains((int) $option->id)) {
                DB::table('announcement_vote_options')->where('id', $option->id)->delete();
            }
        }

        foreach ($incoming as $index => $option) {
            $row = [
                'label' => trim((string) $option['label']),
                'sort_order' => $index,
                'updated_at' => now(),
            ];

            if (!empty($option['id']) && $existingOptions->has((int) $option['id'])) {
                DB::table('announcement_vote_options')
                    ->where('id', (int) $option['id'])
                    ->where('announcement_id', $announcementId)
                    ->update($row);
            } else {
                DB::table('announcement_vote_options')->insert([
                    'announcement_id' => $announcementId,
                    ...$row,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function visibleAnnouncementForUser(object $user, int $announcementId): ?object
    {
        $profile = DB::table('employee_profiles')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->where('employee_profiles.user_id', $user->id)
            ->first(['departments.name as dept_name']);

        return DB::table('announcements')
            ->leftJoin('announcement_recipients', 'announcement_recipients.announcement_id', '=', 'announcements.id')
            ->where('announcements.id', $announcementId)
            ->when(!$user->isSuperAdmin(), function ($query) use ($user, $profile) {
                $query->where(function ($audienceQuery) use ($user, $profile) {
                    $audienceQuery
                        ->where('announcements.audience', 'all')
                        ->orWhere('announcements.audience', 'role:'.$user->role)
                        ->orWhere('announcements.audience', 'department:'.($profile?->dept_name ?? ''))
                        ->orWhere(function ($specificQuery) use ($user) {
                            $specificQuery
                                ->where('announcements.audience', 'specific')
                                ->where('announcement_recipients.user_id', $user->id);
                        });
                });
            })
            ->select('announcements.*')
            ->first();
    }

    private function buildVoteResults(object $announcement): array
    {
        $options = DB::table('announcement_vote_options')
            ->where('announcement_id', $announcement->id)
            ->orderBy('sort_order')
            ->get();

        $votes = DB::table('announcement_votes')
            ->join('announcement_vote_options', 'announcement_vote_options.id', '=', 'announcement_votes.option_id')
            ->where('announcement_votes.announcement_id', $announcement->id)
            ->select([
                'announcement_votes.user_id',
                'announcement_votes.option_id',
                'announcement_votes.voted_at',
                'announcement_vote_options.label as option_label',
            ])
            ->get()
            ->keyBy('user_id');

        $targetUsers = $this->targetUsersForAnnouncement($announcement);

        $responses = $targetUsers->map(function ($user) use ($votes) {
            $vote = $votes->get($user->id);

            return [
                'employeeCode' => $user->employee_code,
                'name' => $user->name,
                'department' => $user->department ?: '-',
                'selectedOption' => $vote?->option_label ?: 'No Response',
                'optionId' => $vote?->option_id ? (int) $vote->option_id : null,
                'votedAt' => $vote?->voted_at ? (string) $vote->voted_at : '',
            ];
        })->values();

        $optionCounts = $options->map(fn ($option) => [
            'id' => (int) $option->id,
            'label' => $option->label,
            'count' => $responses->where('optionId', (int) $option->id)->count(),
        ])->values();

        return [
            'announcementId' => 'AN-'.$announcement->id,
            'title' => $announcement->title,
            'question' => $announcement->vote_question,
            'status' => $announcement->vote_status,
            'totalAudience' => $targetUsers->count(),
            'totalVotes' => $votes->count(),
            'options' => $optionCounts,
            'responses' => $responses,
        ];
    }

    private function targetUsersForAnnouncement(object $announcement)
    {
        $query = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->whereNotNull('users.employee_code')
            ->select([
                'users.id',
                'users.employee_code',
                'users.name',
                'users.role',
                'departments.name as department',
            ])
            ->orderBy('users.employee_code');

        $audience = (string) $announcement->audience;
        if ($audience === 'specific') {
            $query->join('announcement_recipients', function ($join) use ($announcement) {
                $join->on('announcement_recipients.user_id', '=', 'users.id')
                    ->where('announcement_recipients.announcement_id', '=', $announcement->id);
            });
        } elseif (str_starts_with($audience, 'role:')) {
            $query->where('users.role', substr($audience, 5));
        } elseif (str_starts_with($audience, 'department:')) {
            $query->where('departments.name', substr($audience, 11));
        }

        return $query->get();
    }
}
