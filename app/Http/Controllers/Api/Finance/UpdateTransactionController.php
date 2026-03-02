<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Models\FileAttachment;
use App\Models\Transaction;
use App\Services\BudgetAlertService;
use Illuminate\Http\JsonResponse;

class UpdateTransactionController extends Controller
{
    public function __invoke(UpdateTransactionRequest $request, string $id, BudgetAlertService $alertService): JsonResponse
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            throw new ResourceNotFoundException('Transaction not found');
        }

        if ($transaction->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $attachmentsData = $validated['attachments'] ?? null;
        unset($validated['attachments']);

        $transaction->update($validated);

        // Handle new attachments
        if ($attachmentsData !== null) {
            foreach ($attachmentsData as $attachment) {
                FileAttachment::create([
                    'user_id' => auth()->id(),
                    'entity_type' => 'transaction',
                    'entity_id' => $id,
                    'filename' => $attachment['filename'],
                    'mime_type' => $attachment['mime_type'],
                    'size' => strlen(base64_decode($attachment['data'])),
                    'data' => $attachment['data'],
                    'description' => $attachment['description'] ?? null,
                ]);
            }
        }

        // Recheck budget alerts if expense amount or status changed
        if ($transaction->type === 'expense') {
            $alertService->checkBudgetsAfterTransaction(auth()->id(), $transaction->list_id);
        }

        return response()->json([
            'data' => $transaction->fresh(),
            'message' => 'Transaction updated successfully',
        ], 200);
    }
}
