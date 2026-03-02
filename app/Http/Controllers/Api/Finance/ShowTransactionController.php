<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\FileAttachment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class ShowTransactionController extends Controller
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

        $attachments = FileAttachment::forEntity('transaction', $id)
            ->get()
            ->map(fn ($a) => [
                'id' => (string) $a->_id,
                'filename' => $a->filename,
                'mime_type' => $a->mime_type,
                'size' => $a->size,
                'description' => $a->description,
                'created_at' => $a->created_at,
            ]);

        $data = $transaction->toArray();
        $data['file_attachments'] = $attachments;

        return response()->json(['data' => $data], 200);
    }
}
