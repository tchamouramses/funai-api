<?php

namespace App\Http\Controllers\Api\Finance;

use App\Constants\FinanceDefaults;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DefaultsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'incomeCategories' => FinanceDefaults::INCOME_CATEGORIES,
                'expenseCategories' => FinanceDefaults::EXPENSE_CATEGORIES,
                'currencies' => FinanceDefaults::CURRENCIES,
                'paymentMethods' => FinanceDefaults::PAYMENT_METHODS,
                'transactionStatuses' => FinanceDefaults::TRANSACTION_STATUSES,
                'budgetPeriods' => FinanceDefaults::BUDGET_PERIODS,
                'defaultCurrency' => FinanceDefaults::DEFAULT_CURRENCY,
                'defaultAlertThresholds' => FinanceDefaults::DEFAULT_ALERT_THRESHOLDS,
            ],
        ], 200);
    }
}
