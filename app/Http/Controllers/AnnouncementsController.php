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
            'audience' => ['nullable', 'string', 'max:50'], // all / role:employee / department:<id>
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $id = DB::table('announcements')->insertGetId([
            'title' => $validated['title'],
            'category' => $validated['category'] ?? null,
            'audience' => $validated['audience'] ?: 'all',
            'message' => $validated['message'],
            'author_user_id' => $request->user()->id,
            'published_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id], 201);
    }
}

