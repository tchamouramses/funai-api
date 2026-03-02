<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\FileAttachment;
use Illuminate\Http\JsonResponse;

class DestroyAttachmentController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $attachment = FileAttachment::find($id);

        if (! $attachment) {
            throw new ResourceNotFoundException('Attachment not found');
        }

        if ($attachment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attachment->delete();

        return response()->json([
            'data' => null,
            'message' => 'Attachment deleted successfully',
        ], 200);
    }
}
