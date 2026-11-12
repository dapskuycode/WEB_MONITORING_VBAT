<?php

use App\Models\LearningMaterial;
use App\Models\LmsUpload;
use App\Models\QuizAttempt;
use App\Services\LmsFileService;
use App\Services\LmsUploadService;
use App\Services\QuizAttemptService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lms:privatize-pdfs {--apply : Copy, verify SHA-256, then remove the public original}', function () {
    $paths = LearningMaterial::withTrashed()->whereNotNull('pdf_path')->pluck('pdf_path')
        ->merge(Storage::disk('public')->allFiles('learning/pdfs'))->unique();
    $failures = 0;
    $moved = 0;
    foreach ($paths as $path) {
        $source = app(LmsFileService::class)->path('public', $path);
        if (! $source) {
            if (! app(LmsFileService::class)->path('lms', $path)) {
                $failures++;
            }

            continue;
        }
        if (! $this->option('apply')) {
            $moved++;

            continue;
        }
        $disk = Storage::disk('lms');
        try {
            $hash = hash_file('sha256', $source);
            if (! $disk->exists($path)) {
                $stream = fopen($source, 'rb');
                try {
                    $disk->put($path, $stream);
                } finally {
                    fclose($stream);
                }
            }
            $target = app(LmsFileService::class)->path('lms', $path);
            if (! $target || hash_file('sha256', $target) !== $hash || hash_file('sha256', $source) !== $hash) {
                $failures++;

                continue;
            }
            if (! Storage::disk('public')->delete($path)) {
                $failures++;

                continue;
            }
            $moved++;
        } catch (Throwable $e) {
            report($e);
            $failures++;
        }
    }
    $this->info(($this->option('apply') ? 'Privatized' : 'Dry run; would privatize').": {$moved}; missing/failed: {$failures}");
    $this->comment('Audit webserver/CDN caches, backups, and alternate public copies before release.');

    return $failures ? 1 : 0;
})->purpose('Move legacy LMS PDFs out of public storage; dry run by default');

Artisan::command('lms:expire-quiz-attempts', function () {
    $count = 0;
    QuizAttempt::where('status', 'active')->where('deadline_at', '<=', now())->select('id')->chunkById(100, function ($attempts) use (&$count) {
        foreach ($attempts as $attempt) {
            app(QuizAttemptService::class)->expireById($attempt->id);
            $count++;
        }
    });
    $this->info("Expired attempts processed: {$count}");
})->purpose('Finalize timed-out LMS attempts using their saved answer snapshots');

Schedule::command('lms:expire-quiz-attempts')->everyMinute()->withoutOverlapping();

Artisan::command('lms:scan-uploads {--id=}', function () {
    $query = LmsUpload::where('status', 'quarantine');
    if ($this->option('id')) {
        $query->whereKey($this->option('id'));
    }
    foreach ($query->oldest()->limit(25)->get() as $upload) {
        $result = app(LmsUploadService::class)->scan($upload->id);
        $this->line($result->id.': '.$result->status.' '.($result->reason ?? ''));
    }
});
Schedule::command('lms:scan-uploads')->everyMinute()->withoutOverlapping();
