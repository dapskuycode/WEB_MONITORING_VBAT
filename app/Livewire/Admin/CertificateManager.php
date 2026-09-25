<?php

namespace App\Livewire\Admin;

use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificateService;
use Livewire\Component;
use Livewire\WithPagination;

class CertificateManager extends Component
{
    use WithPagination;

    public $search = '';
    public $status = 'all';
    public $selectedCertificate;
    public $showIssueModal = false;
    public $showRevokeModal = false;
    public $courseId;
    public $revokeReason = '';

    public function searchCertificates()
    {
        $this->resetPage();
    }

    public function openIssueModal()
    {
        $this->showIssueModal = true;
    }

    public function issueCertificate()
    {
        // Validate course exists
        $certificate = CertificateService::issueCertificate(
            auth()->user(),
            ['course_id' => $this->courseId]
        );

        $this->showIssueModal = false;
        $this->courseId = null;
        session()->flash('message', 'Sertifikat berhasil diterbitkan: ' . $certificate->certificate_number);
    }

    public function openRevokeModal($certificateId)
    {
        $this->selectedCertificate = Certificate::find($certificateId);
        $this->showRevokeModal = true;
    }

    public function revokeCertificate()
    {
        if ($this->selectedCertificate) {
            CertificateService::revokeCertificate(
                $this->selectedCertificate,
                $this->revokeReason
            );

            $this->showRevokeModal = false;
            $this->revokeReason = '';
            session()->flash('message', 'Sertifikat berhasil dibatalkan');
        }
    }

    public function render()
    {
        $certificates = Certificate::query()
            ->when($this->search, fn($q) => $q->where('certificate_number', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")))
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('livewire.admin.certificate-manager', [
            'certificates' => $certificates,
        ]);
    }
}
