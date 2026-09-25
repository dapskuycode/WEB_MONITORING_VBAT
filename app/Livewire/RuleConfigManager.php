<?php

namespace App\Livewire;

use App\Models\RuleConfig;
use Livewire\Component;
use Livewire\WithPagination;

class RuleConfigManager extends Component
{
    use WithPagination;

    public $key;
    public $value;
    public $data_type = 'string';
    public $description;
    public $editingId = null;
    public $search = '';

    protected $rules = [
        'key' => 'required|string|unique:rule_configs,key',
        'value' => 'required',
        'data_type' => 'required|in:string,integer,float,boolean,json',
        'description' => 'nullable|string',
    ];

    public function mount()
    {
        $this->seedDefaults();
    }

    public function seedDefaults()
    {
        $defaults = [
            ['key' => 'completion_threshold', 'value' => '70', 'data_type' => 'integer', 'description' => 'Video completion % threshold'],
            ['key' => 'attempt_limit', 'value' => '5', 'data_type' => 'integer', 'description' => 'Quiz attempt limit'],
            ['key' => 'hardware_threshold', 'value' => '90', 'data_type' => 'integer', 'description' => 'Hardware Solution unlock threshold %'],
            ['key' => 'package_android_price', 'value' => '800000', 'data_type' => 'integer', 'description' => 'Android package price (IDR)'],
            ['key' => 'package_iphone_price', 'value' => '2000000', 'data_type' => 'integer', 'description' => 'iPhone package price (IDR)'],
            ['key' => 'package_bundling_price', 'value' => '2500000', 'data_type' => 'integer', 'description' => 'Bundling package price (IDR)'],
            ['key' => 'alumni_cutoff_date', 'value' => '2027-01-01 00:00:00', 'data_type' => 'string', 'description' => 'Alumni subscription cutoff date'],
        ];

        foreach ($defaults as $default) {
            RuleConfig::firstOrCreate(
                ['key' => $default['key']],
                $default
            );
        }
    }

    public function edit($id)
    {
        $rule = RuleConfig::find($id);
        $this->editingId = $id;
        $this->key = $rule->key;
        $this->value = $rule->value;
        $this->data_type = $rule->data_type;
        $this->description = $rule->description;
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->editingId) {
            unset($rules['key']);
        }

        $this->validate($rules);

        if ($this->editingId) {
            $rule = RuleConfig::find($this->editingId);
            $rule->update([
                'value' => $this->value,
                'data_type' => $this->data_type,
                'description' => $this->description,
            ]);
            session()->flash('message', 'Rule config updated.');
        } else {
            RuleConfig::create([
                'key' => $this->key,
                'value' => $this->value,
                'data_type' => $this->data_type,
                'description' => $this->description,
                'is_active' => true,
            ]);
            session()->flash('message', 'Rule config created.');
        }

        $this->reset(['key', 'value', 'data_type', 'description', 'editingId']);
    }

    public function delete($id)
    {
        RuleConfig::find($id)->delete();
        session()->flash('message', 'Rule config deleted.');
    }

    public function cancel()
    {
        $this->reset(['key', 'value', 'data_type', 'description', 'editingId']);
    }

    public function render()
    {
        $query = RuleConfig::query();
        
        if ($this->search) {
            $query->where('key', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
        }

        $configs = $query->paginate(10);

        return view('livewire.rule-config-manager', compact('configs'));
    }
}
