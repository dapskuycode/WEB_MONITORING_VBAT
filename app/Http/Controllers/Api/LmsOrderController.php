<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\LmsAuthoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsOrderController extends Controller
{
    public function update(Request $request, string $kind, int $id, LmsAuthoringService $service): JsonResponse
    {
        $data = $request->validate([
            'expected_revision' => 'required|integer|min:1', 'items' => 'required|array|min:1|max:500',
            'items.*.id' => 'required|integer|distinct', 'items.*.revision' => 'required|integer|min:1',
        ]);
        $parent = match ($kind) {
            'courses' => Course::findOrFail($id), 'lessons' => Lesson::findOrFail($id), default => abort(404)
        };
        $service->reorder($parent, $data['items'], $data['expected_revision']);

        return response()->json(['success' => true, 'data' => $parent->fresh()]);
    }
}
