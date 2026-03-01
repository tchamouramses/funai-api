<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateProgramRequest;
use App\Models\ListItem;
use App\Models\ListModel;
use App\Services\FitnessAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GenerateProgramController extends Controller
{
    public function __invoke(GenerateProgramRequest $request, FitnessAIService $fitnessAIService): JsonResponse
    {
        $list = ListModel::find($request->input('list_id'));

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($list->type !== 'fitness') {
            return response()->json(['message' => 'This list is not a fitness list'], 422);
        }

        $profile = $request->input('profile');
        $locale = auth()->user()->locale ?? 'fr';

        try {
            // Generate the program via AI
            $program = $fitnessAIService->generateProgram($profile, $locale);

            // Save fitness profile to list metadata
            $metadata = $list->metadata ?? [];
            $metadata['fitnessProfile'] = $profile;
            $metadata['setupCompleted'] = true;
            $metadata['programName'] = $program['programName'];
            $metadata['programDescription'] = $program['description'];
            $metadata['durationWeeks'] = $program['durationWeeks'];
            $metadata['sessionsPerWeek'] = $program['sessionsPerWeek'];
            $metadata['tips'] = $program['tips'];
            $metadata['challenges'] = [];

            // Save schedule to workoutSchedule
            if (! empty($program['schedule']['daysOfWeek'])) {
                $metadata['workoutSchedule'] = [
                    'daysOfWeek' => $program['schedule']['daysOfWeek'],
                    'weeksCount' => $program['durationWeeks'],
                    'startDate' => now()->toISOString(),
                ];
            }

            $list->update(['metadata' => $metadata]);

            // Create sub-lists and exercises
            $createdSubLists = [];

            foreach ($program['subLists'] as $index => $subListData) {
                $subList = ListModel::create([
                    'user_id' => auth()->id(),
                    'title' => $subListData['title'],
                    'type' => $list->type,
                    'description' => $subListData['description'] ?? null,
                    'metadata' => [
                        'flowType' => 'fitness',
                    ],
                    'parent_list_id' => (string) $list->_id,
                    'depth' => ($list->depth ?? 0) + 1,
                    'children_count' => 0,
                    'total_item_count' => 0,
                    'total_completed_count' => 0,
                ]);

                $list->increment('children_count');

                // Create exercises for this sub-list
                foreach ($subListData['exercises'] as $exerciseIndex => $exercise) {
                    $itemMetadata = [];

                    if ($exercise['sets'] !== null) {
                        $itemMetadata['sets'] = $exercise['sets'];
                    }
                    if ($exercise['reps'] !== null) {
                        $itemMetadata['reps'] = $exercise['reps'];
                    }
                    if ($exercise['weight'] !== null) {
                        $itemMetadata['weight'] = $exercise['weight'];
                    }
                    if ($exercise['duration'] !== null) {
                        $itemMetadata['duration'] = $exercise['duration'];
                    }
                    if ($exercise['notes'] !== null) {
                        $itemMetadata['notes'] = $exercise['notes'];
                    }

                    $item = ListItem::create([
                        'list_id' => (string) $subList->_id,
                        'content' => $exercise['content'],
                        'completed' => false,
                        'order' => $exerciseIndex,
                        'metadata' => $itemMetadata,
                    ]);

                    $item->series_id = (string) $item->id;
                    $item->save();

                    $subList->increment('total_item_count');
                }

                $createdSubLists[] = $subList->load('items');
            }

            return response()->json([
                'data' => [
                    'list' => $list->fresh(),
                    'program' => $program,
                    'subLists' => $createdSubLists,
                ],
                'message' => 'Program generated successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error generating fitness program', [
                'error' => $e->getMessage(),
                'list_id' => $list->_id,
            ]);

            return response()->json([
                'message' => 'Failed to generate program: '.$e->getMessage(),
            ], 500);
        }
    }
}
