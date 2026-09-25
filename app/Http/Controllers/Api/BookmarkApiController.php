<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningBookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkApiController extends Controller
{
    /**
     * List user's learning bookmarks.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'bookmarkable_type' => ['nullable', 'in:course,learning_material,lesson'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = LearningBookmark::where('user_id', $userId)
            ->with(['bookmarkable'])
            ->orderBy('created_at', 'desc');

        if (!empty($validated['bookmarkable_type'])) {
            $modelMap = [
                'course' => \App\Models\Course::class,
                'learning_material' => \App\Models\LearningMaterial::class,
                'lesson' => \App\Models\Lesson::class,
            ];
            $query->where('bookmarkable_type', $modelMap[$validated['bookmarkable_type']]);
        }

        $bookmarks = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $bookmarks->items(),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'per_page' => $bookmarks->perPage(),
                'total' => $bookmarks->total(),
                'last_page' => $bookmarks->lastPage(),
            ],
            'message' => 'Bookmarks retrieved successfully',
        ]);
    }

    /**
     * Toggle bookmark (add or remove).
     */
    public function toggle(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'bookmarkable_type' => ['required', 'in:course,learning_material,lesson'],
            'bookmarkable_id' => ['required', 'integer'],
        ]);

        $modelMap = [
            'course' => \App\Models\Course::class,
            'learning_material' => \App\Models\LearningMaterial::class,
            'lesson' => \App\Models\Lesson::class,
        ];

        $bookmarkableType = $modelMap[$validated['bookmarkable_type']];
        $bookmarkableId = $validated['bookmarkable_id'];

        // Check if exists
        $existing = LearningBookmark::where('user_id', $userId)
            ->where('bookmarkable_type', $bookmarkableType)
            ->where('bookmarkable_id', $bookmarkableId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'data' => ['bookmarked' => false],
                'message' => 'Bookmark removed',
            ]);
        }

        $bookmark = LearningBookmark::create([
            'user_id' => $userId,
            'bookmarkable_type' => $bookmarkableType,
            'bookmarkable_id' => $bookmarkableId,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['bookmarked' => true, 'bookmark' => $bookmark],
            'message' => 'Bookmark added',
        ], 201);
    }

    /**
     * Check if item is bookmarked.
     */
    public function check(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'bookmarkable_type' => ['required', 'in:course,learning_material,lesson'],
            'bookmarkable_id' => ['required', 'integer'],
        ]);

        $modelMap = [
            'course' => \App\Models\Course::class,
            'learning_material' => \App\Models\LearningMaterial::class,
            'lesson' => \App\Models\Lesson::class,
        ];

        $exists = LearningBookmark::where('user_id', $userId)
            ->where('bookmarkable_type', $modelMap[$validated['bookmarkable_type']])
            ->where('bookmarkable_id', $validated['bookmarkable_id'])
            ->exists();

        return response()->json([
            'success' => true,
            'data' => ['bookmarked' => $exists],
            'message' => 'Bookmark status checked',
        ]);
    }
}
