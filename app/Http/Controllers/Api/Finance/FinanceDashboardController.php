<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use App\Services\FinanceDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    public function __invoke(Request $request, string $listId, FinanceDashboardService $dashboardService): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('Finance flow not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $period = $request->input('period', 'monthly');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        $dashboard = $dashboardService->getDashboard(
            $listId,
            $period,
            $customStart,
            $customEnd
        );

        return response()->json(['data' => $dashboard], 200);
    }
}
