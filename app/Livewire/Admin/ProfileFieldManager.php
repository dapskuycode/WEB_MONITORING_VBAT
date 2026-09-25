<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;

class ProfileFieldManager extends Component
{
    use WithPagination;

    public $showFieldModal = false;
    public $showKtaPreview = false;
    public $showCertPreview = false;

    // Field form
    public $fieldId;
    public $fieldName;
    public $fieldType = 'text';
    public $fieldLabel;
    public $fieldRequired = false;
    public $fieldOrder = 0;

    // Template data
    public $previewUser;

    public function mount()
    {
        $this->previewUser = auth()->user();
    }

    public function openFieldModal($id = null)
    {
        if ($id) {
            // Load from DB if needed
            $this->fieldId = $id;
        } else {
            $this->resetFieldForm();
        }
        $this->showFieldModal = true;
    }

    public function resetFieldForm()
    {
        $this->fieldId = null;
        $this->fieldName = null;
        $this->fieldType = 'text';
        $this->fieldLabel = null;
        $this->fieldRequired = false;
        $this->fieldOrder = 0;
    }

    public function saveField()
    {
        // Placeholder — would save to DB
        $this->showFieldModal = false;
        session()->flash('message', 'Profile field disimpan');
    }

    public function deleteField($id)
    {
        session()->flash('message', 'Profile field dihapus');
    }

    public function previewKta()
    {
        $this->showKtaPreview = true;
    }

    public function previewCert()
    {
        $this->showCertPreview = true;
    }

    public function render()
    {
        return view('livewire.admin.profile-field-manager');
    }
}
