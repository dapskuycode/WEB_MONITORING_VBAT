<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentQuizController extends Controller
{
    public function __construct(private QuizAttemptService $attempts) {}

    public function show(Request $request, int $id): JsonResponse
    {
        $this->identity($request);

        return $this->respond($this->attempts->overview($request->user(), $id));
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $this->identity($request);
        $data = $request->validate(['request_id' => 'required|uuid']);

        return $this->respond($this->attempts->start($request->user(), $id, $data['request_id']));
    }

    public function read(Request $request, int $id): JsonResponse
    {
        $this->identity($request);

        return $this->respond($this->attempts->read($request->user(), $id));
    }

    public function save(Request $request, int $id): JsonResponse
    {
        return $this->change($request, $id, false);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        return $this->change($request, $id, true);
    }

    private function change(Request $request, int $id, bool $submit): JsonResponse
    {
        $this->identity($request);
        $data = $request->validate(['request_id' => 'required|uuid', 'expected_version' => 'required|integer|min:0',
            'answers' => $submit ? 'prohibited' : 'present|array|max:100', 'answers.*.question_id' => 'required|integer|min:1',
            'answers.*.value' => 'present|nullable|string|max:5000',
            'flagged_question_ids' => $submit ? 'prohibited' : 'sometimes|array|max:100', 'flagged_question_ids.*' => 'integer|min:1|distinct', 'score' => 'prohibited', 'is_passed' => 'prohibited']);
        $data['expected_version'] = (int) $data['expected_version'];

        return $this->respond($this->attempts->mutate($request->user(), $id, $data, $submit));
    }

    private function identity(Request $request): void
    {
        $request->validate(['user_id' => 'prohibited', 'email' => 'prohibited']);
    }

    /** @param array<string, mixed> $data */
    private function respond(array $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data])->header('Cache-Control', 'private, no-store');
    }
}
