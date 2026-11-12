<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\LmsGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsGradeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['status' => 'sometimes|in:pending_review,published', 'page' => 'sometimes|integer|min:1']);
        $items = QuizAttempt::whereIn('status', ['submitted', 'expired'])->when($request->input('status'), fn ($q, $s) => $q->where('grade_status', $s))->latest('id')->paginate(20);

        return response()->json(['success' => true, 'data' => $items->map(fn ($a) => $a->only(['id', 'quiz_id', 'user_id', 'grade_status', 'grade_version', 'score']))->all(), 'meta' => ['page' => $items->currentPage(), 'last_page' => $items->lastPage()]]);
    }

    public function show(int $id, LmsGradingService $service): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $service->detail(QuizAttempt::findOrFail($id))])->header('Cache-Control', 'private, no-store');
    }

    public function draft(Request $request, int $id, LmsGradingService $service): JsonResponse
    {
        return $this->change($request, $id, $service, false);
    }

    public function publish(Request $request, int $id, LmsGradingService $service): JsonResponse
    {
        return $this->change($request, $id, $service, true);
    }

    private function change(Request $request, int $id, LmsGradingService $service, bool $publish): JsonResponse
    {
        $d = $request->validate(['request_id' => 'required|uuid', 'expected_version' => 'required|integer|min:0', 'reason' => 'required|string|min:5|max:2000',
            'grades' => $publish ? 'prohibited' : 'required|array|min:1|max:100', 'grades.*.question_id' => 'required|integer|min:1',
            'grades.*.points' => 'present|nullable|numeric|min:0|max:1000', 'grades.*.feedback' => 'sometimes|nullable|string|max:5000', 'grades.*.criteria' => 'sometimes|array|max:20']);
        $d['expected_version'] = (int) $d['expected_version'];

        return response()->json(['success' => true, 'data' => $service->change($request->user(), $id, $d, $publish)])->header('Cache-Control','private, no-store');
    }
}
