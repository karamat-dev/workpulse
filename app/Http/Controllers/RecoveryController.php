<?php

namespace App\Http\Controllers;

use App\Services\DeletionRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RecoveryController extends Controller
{
    public function index(DeletionRecoveryService $recovery): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'recoveryItems' => $recovery->list(4),
        ]);
    }

    public function restore(Request $request, int $item, DeletionRecoveryService $recovery): JsonResponse
    {
        try {
            $recovery->restore($item, (int) $request->user()->id);

            return response()->json([
                'ok' => true,
                'recoveryItems' => $recovery->list(4),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
