<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class DestroyController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $list = ListModel::find($id);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        ListItem::where('list_id', $id)->delete();

        foreach ($list->children as $child) {
            $this->deleteListRecursive($child->_id);
        }

        if ($list->parent_list_id) {
            $parent = ListModel::find($list->parent_list_id);
            if ($parent) {
                $parent->decrement('children_count');
            }
        }

        $list->delete();

        return response()->json(['message' => 'List deleted successfully'], 200);
    }

    private function deleteListRecursive(string $listId): void
    {
        $list = ListModel::find($listId);
        if (! $list) {
            return;
        }

        ListItem::where('list_id', $listId)->delete();

        foreach ($list->children as $child) {
            $this->deleteListRecursive($child->_id);
        }

        $list->delete();
    }
}
