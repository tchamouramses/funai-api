<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexTransactionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', auth()->id())
            ->orderBy('date', 'desc');

        if ($request->has('list_id')) {
            $query->where('list_id', $request->input('list_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateBetween($request->input('start_date'), $request->input('end_date'));
        }


        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate($request->input('per_page', 20));

        return response()->json(['data' => $transactions], 200);
    }
}
