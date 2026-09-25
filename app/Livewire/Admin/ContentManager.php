<?php

namespace App\Livewire\Admin;

use App\Models\InfoContent;
use App\Models\LegalContent;
use Livewire\Component;
use Livewire\WithPagination;

class ContentManager extends Component
{
    use WithPagination;

    public $activeTab = 'info';
    public $showInfoModal = false;
    public $showLegalModal = false;

    // Info form
    public $infoId;
    public $infoSlug;
    public $infoTitle;
    public $infoContent;
    public $infoPremiumOnly = false;
    public $infoWhatsappNumber;

    // Legal form
    public $legalId;
    public $legalType;
    public $legalVersion;
    public $legalContent;

    public function openInfoModal($id = null)
    {
        if ($id) {
            $info = InfoContent::find($id);
            $this->infoId = $info->id;
            $this->infoSlug = $info->slug;
            $this->infoTitle = $info->title;
            $this->infoContent = $info->content;
            $this->infoPremiumOnly = $info->is_premium_only;
            $this->infoWhatsappNumber = $info->whatsapp_number;
        } else {
            $this->resetInfoForm();
        }
        $this->showInfoModal = true;
    }

    public function resetInfoForm()
    {
        $this->infoId = null;
        $this->infoSlug = null;
        $this->infoTitle = null;
        $this->infoContent = null;
        $this->infoPremiumOnly = false;
        $this->infoWhatsappNumber = null;
    }

    public function saveInfo()
    {
        InfoContent::updateOrCreate(
            ['id' => $this->infoId],
            [
                'slug' => $this->infoSlug,
                'title' => $this->infoTitle,
                'content' => $this->infoContent,
                'is_premium_only' => $this->infoPremiumOnly,
                'whatsapp_number' => $this->infoWhatsappNumber,
            ]
        );

        $this->showInfoModal = false;
        session()->flash('message', 'Info content disimpan');
    }

    public function deleteInfo($id)
    {
        InfoContent::find($id)->delete();
        session()->flash('message', 'Info content dihapus');
    }

    public function openLegalModal($id = null)
    {
        if ($id) {
            $legal = LegalContent::find($id);
            $this->legalId = $legal->id;
            $this->legalType = $legal->type;
            $this->legalVersion = $legal->version;
            $this->legalContent = $legal->content;
        } else {
            $this->resetLegalForm();
        }
        $this->showLegalModal = true;
    }

    public function resetLegalForm()
    {
        $this->legalId = null;
        $this->legalType = 'terms';
        $this->legalVersion = '1.0';
        $this->legalContent = null;
    }

    public function saveLegal()
    {
        LegalContent::create([
            'type' => $this->legalType,
            'version' => $this->legalVersion,
            'content' => $this->legalContent,
            'effective_at' => now(),
        ]);

        $this->showLegalModal = false;
        session()->flash('message', 'Legal version baru disimpan');
    }

    public function render()
    {
        $infoContents = InfoContent::latest()->paginate(10);
        $legalContents = LegalContent::latest()->paginate(10);

        return view('livewire.admin.content-manager', [
            'infoContents' => $infoContents,
            'legalContents' => $legalContents,
        ]);
    }
}
