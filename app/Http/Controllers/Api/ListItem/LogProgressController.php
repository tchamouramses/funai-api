<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LogProgressRequest;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class LogProgressController extends Controller
{
    public function __invoke(string $id, LogProgressRequest $request): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        $entry = $item->progressEntries()->create([
            'date' => now(),
            'value' => $request->validated()['value'],
            'notes' => $request->validated()['notes'] ?? null,
        ]);

        return response()->json([
            'data' => $entry,
            'message' => 'Progress logged successfully',
        ], 201);
    }
}
