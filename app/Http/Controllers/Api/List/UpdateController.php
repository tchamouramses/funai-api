<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateListRequest;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;
use App\Models\ListItem;
use Illuminate\Support\Carbon;

class UpdateController extends Controller
{
    public function __invoke(string $id, UpdateListRequest $request): JsonResponse
    {
        $list = ListModel::find($id);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        $list->update($request->validated());

        $list->refresh();

        //set schedule for items without schedule if list has recurrence pattern
        if(isset($list->recurrence_pattern)){
            $listItems = ListItem::where('list_id', $list->id)
                ->whereNull('metadata->schedule')
                ->get();

            foreach ($listItems as $item) {
                $item->update([
                    'metadata->schedule' => $item->calculateSchedule(Carbon::parse($list->recurrence_start_date), $list->recurrence_pattern),
                ]);
            }
        }

        return response()->json([
            'data' => $list,
            'message' => 'List updated successfully',
        ], 200);
    }
}
