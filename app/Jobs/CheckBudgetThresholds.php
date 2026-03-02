<?php

namespace App\Jobs;

use App\Services\BudgetAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckBudgetThresholds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $userId,
        public string $listId
    ) {}

    public function handle(BudgetAlertService $service): void
    {
        $service->checkBudgetsAfterTransaction($this->userId, $this->listId);
    }
}
