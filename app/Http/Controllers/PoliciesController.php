<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PoliciesController extends Controller
{
    public function index(): JsonResponse
    {
        $policies = $this->policiesQuery()
            ->orderByDesc('company_policies.created_at')
            ->get()
            ->map(fn ($policy) => $this->formatPolicy($policy))
            ->values();

        return response()->json([
            'ok' => true,
            'policies' => $policies,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'policy_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $request->file('policy_file');
        $directory = public_path('uploads/company-policies');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = sprintf(
            'policy-%s-%s.pdf',
            now()->format('YmdHis'),
            strtolower(bin2hex(random_bytes(4)))
        );

        $fileSize = (int) $file->getSize();
        $file->move($directory, $filename);

        $id = DB::table('company_policies')->insertGetId([
            'title' => $validated['title'],
            'file_path' => 'uploads/company-policies/'.$filename,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $fileSize,
            'uploaded_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $policy = $this->policiesQuery()
            ->where('company_policies.id', $id)
            ->first();

        return response()->json([
            'ok' => true,
            'policy' => $policy ? $this->formatPolicy($policy) : null,
        ], 201);
    }

    public function destroy(Request $request, int $policyId): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        $policy = DB::table('company_policies')->where('id', $policyId)->first();
        if (! $policy) {
            return response()->json([
                'ok' => false,
                'message' => 'Policy not found.',
            ], 404);
        }

        if ($policy->file_path) {
            $absolutePath = public_path((string) $policy->file_path);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        DB::table('company_policies')->where('id', $policyId)->delete();

        return response()->json(['ok' => true]);
    }

    private function policiesQuery()
    {
        return DB::table('company_policies')
            ->leftJoin('users', 'users.id', '=', 'company_policies.uploaded_by')
            ->select([
                'company_policies.id',
                'company_policies.title',
                'company_policies.file_path',
                'company_policies.file_name',
                'company_policies.file_size',
                'company_policies.created_at',
                'users.name as uploaded_by_name',
            ]);
    }

    private function formatPolicy(object $policy): array
    {
        return [
            'id' => (int) $policy->id,
            'title' => $policy->title,
            'fileName' => $policy->file_name,
            'fileSize' => (int) ($policy->file_size ?? 0),
            'fileUrl' => $policy->file_path ? asset($policy->file_path) : null,
            'uploadedBy' => $policy->uploaded_by_name ?: 'Admin',
            'uploadedAt' => $policy->created_at ? (string) $policy->created_at : null,
        ];
    }
}
