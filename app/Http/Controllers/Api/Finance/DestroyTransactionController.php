<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\FileAttachment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class DestroyTransactionController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            throw new ResourceNotFoundException('Transaction not found');
        }

        if ($transaction->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated file attachments
        FileAttachment::forEntity('transaction', $id)->delete();

        $transaction->delete();

        return response()->json([
            'data' => null,
            'message' => 'Transaction deleted successfully',
        ], 200);
    }
}
