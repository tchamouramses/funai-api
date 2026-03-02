<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Models\FileAttachment;
use App\Models\ListModel;
use App\Models\Transaction;
use App\Services\BudgetAlertService;
use Illuminate\Http\JsonResponse;

class StoreTransactionController extends Controller
{
    public function __invoke(StoreTransactionRequest $request, BudgetAlertService $alertService): JsonResponse
    {
        $validated = $request->validated();

        $list = ListModel::find($validated['list_id']);

        if (! $list) {
            throw new ResourceNotFoundException('Finance flow not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attachmentsData = $validated['attachments'] ?? [];
        unset($validated['attachments']);

        $validated['user_id'] = auth()->id();

        if (! isset($validated['status'])) {
            $validated['status'] = $validated['type'] === 'income' ? 'received' : 'paid';
        }

        $transaction = Transaction::create($validated);

        // Save file attachments
        if (! empty($attachmentsData)) {
            foreach ($attachmentsData as $attachment) {
                FileAttachment::create([
                    'user_id' => auth()->id(),
                    'entity_type' => 'transaction',
                    'entity_id' => (string) $transaction->_id,
                    'filename' => $attachment['filename'],
                    'mime_type' => $attachment['mime_type'],
                    'size' => strlen(base64_decode($attachment['data'])),
                    'data' => $attachment['data'],
                    'description' => $attachment['description'] ?? null,
                ]);
            }
        }

        // Check budget alerts after expense creation
        if ($validated['type'] === 'expense' && $validated['status'] !== 'cancelled') {
            $alertService->checkBudgetsAfterTransaction(auth()->id(), $validated['list_id']);
        }

        return response()->json([
            'data' => $transaction,
            'message' => 'Transaction created successfully',
        ], 201);
    }
}
