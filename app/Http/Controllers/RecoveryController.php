<?php

namespace App\Http\Controllers;

use App\Services\DeletionRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RecoveryController extends Controller
{
    private const GENERIC_ERROR = 'Unable to restore this item right now. Please try again.';

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

            return response()->json(['ok' => false, 'message' => self::GENERIC_ERROR], 500);
        }
    }
}
