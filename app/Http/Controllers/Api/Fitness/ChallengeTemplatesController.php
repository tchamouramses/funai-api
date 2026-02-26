<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ChallengeTemplatesController extends Controller
{
    public function __invoke(): JsonResponse
    {

        return response()->json(['data' => \App\Constants\FitnessChallengeTemplate::TEMPLATES], 200);
    }
}
