<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Services\LmsFileService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LmsPreviewController extends Controller
{
    public function show(Request $request, int $id): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $course = Course::with('lessons.learningMaterials')->findOrFail($id);
        $mediaUrls = [];
        foreach ($course->lessons as $lesson) {
            foreach ($lesson->learningMaterials as $material) {
                if ($material->material_type === 'pdf_document') {
                    $mediaUrls[$material->id] = URL::temporarySignedRoute('admin.lms.preview-media', now()->addMinutes(5), [
                        'id' => $material->id, 'user' => $request->user()->id,
                    ]);
                }
            }
        }

        return response()->view('admin.lms-preview', compact('course', 'mediaUrls'))->header('Cache-Control', 'private, no-store');
    }

    public function media(Request $request, int $id): BinaryFileResponse
    {
        abort_unless($request->user()?->isSuperAdmin() && $request->hasValidSignature()
            && (string) $request->query('user') === (string) $request->user()->id, 403);
        $material = LearningMaterial::findOrFail($id);
        abort_unless($material->material_type === 'pdf_document', 404);
        $path = app(LmsFileService::class)->path('lms', $material->pdf_path);
        abort_unless($path !== null, 404);

        return response()->file($path, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
