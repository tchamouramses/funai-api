<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\FileAttachment;
use Illuminate\Http\JsonResponse;

class ShowAttachmentController extends Controller
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

        return response()->json([
            'data' => [
                'id' => (string) $attachment->_id,
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'data' => $attachment->data,
                'description' => $attachment->description,
                'created_at' => $attachment->created_at,
            ],
        ], 200);
    }
}
