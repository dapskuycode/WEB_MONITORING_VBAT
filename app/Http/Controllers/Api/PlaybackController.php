<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaybackController extends Controller
{
    public function __construct(private PlaybackService $playback) {}

    public function start(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['request_id' => 'required|uuid', 'user_id' => 'prohibited', 'email' => 'prohibited', 'progress_percent' => 'prohibited']);

        return $this->respond($this->playback->start($request->user(), LearningMaterial::findOrFail($id), $data['request_id']));
    }

    public function event(Request $request, string $session): JsonResponse
    {
        $data = $request->validate(['event_id' => 'required|uuid', 'sequence' => 'required|integer|min:1|max:2147483647',
            'position_seconds' => 'required|numeric|min:0|max:86400', 'state' => 'required|in:playing,paused,ended',
            'user_id' => 'prohibited', 'email' => 'prohibited', 'material_id' => 'prohibited', 'progress_percent' => 'prohibited']);
        $data['sequence'] = (int) $data['sequence'];
        $data['position_seconds'] = (float) $data['position_seconds'];

        return $this->respond($this->playback->event($request->user(), $session, $data));
    }

    /** @param array<string, mixed> $data */
    private function respond(array $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data])->header('Cache-Control', 'private, no-store');
    }
}
