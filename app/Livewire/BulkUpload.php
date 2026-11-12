<?php

namespace App\Livewire;

use App\Http\Controllers\Api\LmsImportController;
use App\Models\LmsImport;
use App\Models\LmsUpload;
use App\Services\BulkImportService;
use App\Services\LmsUploadService;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class BulkUpload extends Component
{
    use WithFileUploads;

    public $file;

    public $pdf;

    #[Locked]
    public ?string $previewId = null;

    #[Locked]
    public ?array $importResult = null;

    public function downloadTemplate()
    {
        $this->assertAdmin();

        return app(LmsImportController::class)->template(app(BulkImportService::class));
    }

    public function updatedFile(): void
    {
        $this->previewId = null;
        $this->importResult = null;
        $this->resetValidation();
    }

    public function previewFile(): void
    {
        $this->assertAdmin();
        $this->validate(['file' => 'required|file|max:5120']);
        $this->previewId = null;
        $this->importResult = null;
        $preview = app(BulkImportService::class)->preview($this->file->getRealPath(), auth()->user(), strtolower($this->file->getClientOriginalExtension()));
        $this->previewId = $preview->id;
    }

    public function importData(): void
    {
        $this->assertAdmin();
        abort_unless($this->previewId, 422);
        try {
            $this->importResult = app(BulkImportService::class)->apply($this->previewId, auth()->user());
        } catch (HttpExceptionInterface $e) {
            $this->addError('import', $e->getMessage());
        }
    }

    public function uploadPdf(): void
    {
        $this->assertAdmin();
        $this->validate(['pdf' => 'required|file|mimes:pdf|max:10240']);
        app(LmsUploadService::class)->receive(auth()->user(), $this->pdf, 'course_material');
        $this->pdf = null;
    }

    public function downloadErrors()
    {
        $this->assertAdmin();
        $preview = LmsImport::whereKey($this->previewId)->where('user_id', auth()->id())->firstOrFail();

        return LmsImportController::errorDownload($preview);
    }

    private function assertAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function render()
    {
        $this->assertAdmin();

        return view('livewire.bulk-upload', ['summary' => $this->previewId ? app(BulkImportService::class)->data(LmsImport::whereKey($this->previewId)->where('user_id', auth()->id())->firstOrFail()) : null,
            'uploads' => LmsUpload::where('user_id', auth()->id())->latest()->limit(30)->get()]);
    }
}
