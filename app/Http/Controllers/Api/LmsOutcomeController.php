<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Services\LmsCredentialService;
use App\Services\LmsFileService;
use App\Services\LmsOutcomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LmsOutcomeController extends Controller
{
    private function identity(Request $r): void
    {
        $r->validate(['user_id' => 'prohibited', 'email' => 'prohibited']);
    }

    /** @param array<string, mixed> $data */
    private function result(array $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data])->header('Cache-Control', 'private, no-store');
    }

    public function course(Request $r, int $id, LmsOutcomeService $s): JsonResponse
    {
        $this->identity($r);

        return $this->result($s->course($r->user(), Course::findOrFail($id)));
    }

    public function dashboard(Request $r, LmsOutcomeService $s): JsonResponse
    {
        $this->identity($r);
        $r->validate(['page' => 'sometimes|integer|min:1']);
        $owned = $r->user()->entitlements()->whereIn('package_type', ['android', 'iphone', 'bundling'])->pluck('package_type')->all();
        $types = in_array('bundling', $owned, true) ? ['android', 'iphone'] : array_values(array_intersect($owned, ['android', 'iphone']));
        $courses = Course::published()->where(function ($q) use ($types, $r) {
            $q->whereIn('type', $types)->orWhere(fn ($q) => $q->where('type', 'free_class')->whereIn('id', DB::table('lms_playback_progress as p')->join('learning_materials as m', 'm.id', '=', 'p.material_id')->join('lessons as l', 'l.id', '=', 'm.lesson_id')->where('p.user_id', $r->user()->id)->select('l.course_id')->union(DB::table('lms_reading_progress as p')->join('learning_materials as m', 'm.id', '=', 'p.material_id')->join('lessons as l', 'l.id', '=', 'm.lesson_id')->where('p.user_id', $r->user()->id)->select('l.course_id'))));
        })->orderBy('id')->paginate(20);

        return $this->result(['courses' => $courses->map(fn ($c) => $s->course($r->user(), $c))->all(), 'page' => $courses->currentPage(), 'last_page' => $courses->lastPage()]);
    }

    public function history(Request $r, LmsOutcomeService $s): JsonResponse
    {
        $this->identity($r);
        $r->validate(['page' => 'sometimes|integer|min:1']);

        return $this->result($s->history($r->user(), $r->integer('page', 1)));
    }

    public function credentials(Request $r, LmsCredentialService $s): JsonResponse
    {
        $this->identity($r);
        $items = $r->user()->certificates()->latest('id')->paginate(20);

        return $this->result(['items' => $items->map(fn ($c) => $s->data($c))->all(), 'page' => $items->currentPage(), 'last_page' => $items->lastPage()]);
    }

    public function issue(Request $r, int $id, LmsCredentialService $s): JsonResponse
    {
        $this->identity($r);

        return $this->result($s->data($s->issue($r->user(), Course::findOrFail($id))));
    }

    public function visibility(Request $r, int $id, LmsCredentialService $s): JsonResponse
    {
        $d = $r->validate(['is_public' => 'required|boolean']);

        return $this->result($s->data($s->visibility($r->user(), $id, (bool) $d['is_public'])));
    }

    public function document(Request $r, int $id): BinaryFileResponse
    {
        $c = Certificate::whereKey($id)->where('user_id', $r->user()->id)->firstOrFail();
        abort_unless($c->document_path !== null, 404);
        $path = app(LmsFileService::class)->path('lms', $c->document_path);
        abort_unless($path !== null, 404);

        return response()->file($path, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function verify(string $token, LmsCredentialService $s): JsonResponse
    {
        $c = Certificate::where('verification_token', $token)->where('is_public', true)->firstOrFail();

        return $this->result($s->data($c, true));
    }

    public function revoke(Request $r, int $id, LmsCredentialService $s): JsonResponse
    {
        $d = $r->validate(['reason' => 'required|string|min:5|max:2000']);

        return $this->result($s->data($s->revoke($r->user(),$id,$d['reason'])));
    }
}
