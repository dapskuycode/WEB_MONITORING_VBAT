<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Profile Fields & Template Preview</h2>
        
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Fields -->
        <div class="lg:col-span-2 bg-white shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">User Profile Fields</h3>
                <button 
                    wire:click="openFieldModal"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
                >
                    + Tambah Field
                </button>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Required</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse([
                        ['name' => 'first_name', 'type' => 'text', 'required' => true, 'order' => 1],
                        ['name' => 'last_name', 'type' => 'text', 'required' => true, 'order' => 2],
                        ['name' => 'phone', 'type' => 'tel', 'required' => true, 'order' => 3],
                        ['name' => 'address', 'type' => 'textarea', 'required' => true, 'order' => 4],
                        ['name' => 'province', 'type' => 'select', 'required' => true, 'order' => 5],
                        ['name' => 'city', 'type' => 'select', 'required' => true, 'order' => 6],
                    ] as $field)
                    <tr>
                        <td class="px-4 py-4">{{ $field['name'] }}</td>
                        <td class="px-4 py-4 text-sm text-gray-600">{{ $field['type'] }}</td>
                        <td class="px-4 py-4 text-sm">
                            @if($field['required'])
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Yes</span>
                            @else
                                <span class="text-xs bg-gray-100 px-2 py-1 rounded">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-sm">{{ $field['order'] }}</td>
                        <td class="px-4 py-4 text-sm">
                            <button 
                                wire:click="openFieldModal({{ $loop->index }})"
                                class="text-blue-600 hover:text-blue-900 mr-3"
                            >
                                Edit
                            </button>
                            <button 
                                wire:click="deleteField({{ $loop->index }})"
                                class="text-red-600 hover:text-red-900"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">Belum ada field</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Template Previews -->
        <div class="space-y-4">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">Template Preview</h3>
                
                <div class="space-y-2">
                    <button 
                        wire:click="previewKta"
                        class="w-full bg-purple-600 text-white px-4 py-3 rounded hover:bg-purple-700 text-sm font-medium"
                    >
                        👤 KTA Card Preview
                    </button>
                    <button 
                        wire:click="previewCert"
                        class="w-full bg-orange-600 text-white px-4 py-3 rounded hover:bg-orange-700 text-sm font-medium"
                    >
                        📜 Certificate Preview
                    </button>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-bold text-sm mb-2">Profile Completion</h4>
                <div class="space-y-1 text-xs text-gray-600">
                    <p>✓ Email</p>
                    <p>✓ Phone</p>
                    <p>✓ Address</p>
                    <p>✗ KTA Number</p>
                    <p>✗ Certificate</p>
                </div>
            </div>
        </div>
    </div>

    @if($showFieldModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">{{ $fieldId ? 'Edit' : 'Tambah' }} Profile Field</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Field Name</label>
                    <input 
                        type="text" 
                        wire:model="fieldName" 
                        class="border rounded px-4 py-2 w-full"
                        placeholder="e.g., phone"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select wire:model="fieldType" class="border rounded px-4 py-2 w-full">
                        <option value="text">Text</option>
                        <option value="email">Email</option>
                        <option value="tel">Telephone</option>
                        <option value="number">Number</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="date">Date</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Label</label>
                    <input 
                        type="text" 
                        wire:model="fieldLabel" 
                        class="border rounded px-4 py-2 w-full"
                        placeholder="Display label"
                    >
                </div>
                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="fieldRequired"
                            class="mr-2"
                        >
                        <span class="text-sm">Required</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Display Order</label>
                    <input 
                        type="number" 
                        wire:model="fieldOrder" 
                        min="0"
                        class="border rounded px-4 py-2 w-full"
                    >
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button 
                    wire:click="saveField"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex-1"
                >
                    Simpan
                </button>
                <button 
                    wire:click="$set('showFieldModal', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($showKtaPreview)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">KTA Card Preview</h3>
            
            <!-- KTA Card Mock -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-lg p-8 aspect-video flex flex-col justify-between" style="font-family: 'Courier New', monospace;">
                <div>
                    <div class="text-xs opacity-75">VBAT-INDONESIA</div>
                    <div class="text-xl font-bold mt-2">{{ $previewUser?->name ?? 'User Name' }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs">KTA No: <span class="font-mono">KTA-000001-2026</span></div>
                    <div class="text-xs">Tier: <span class="font-bold">GOLD</span></div>
                    <div class="text-xs">Valid Until: <span class="font-mono">2027-09-25</span></div>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button 
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm"
                >
                    Download PDF
                </button>
                <button 
                    wire:click="$set('showKtaPreview', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($showCertPreview)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <h3 class="text-lg font-bold mb-4">Certificate Preview</h3>
            
            <!-- Certificate Mock -->
            <div class="bg-gradient-to-br from-yellow-50 via-amber-50 to-orange-50 border-8 border-yellow-700 rounded-lg p-12 text-center aspect-video flex flex-col justify-center">
                <div class="text-3xl font-bold text-yellow-900 mb-4">CERTIFICATE</div>
                <div class="text-lg text-gray-700 mb-6">
                    This is to certify that<br>
                    <span class="text-2xl font-bold text-yellow-900">{{ $previewUser?->name ?? 'User Name' }}</span><br>
                    has successfully completed
                </div>
                <div class="text-xl font-bold text-yellow-900 mb-6">Advanced Training Course 2026</div>
                
                <div class="grid grid-cols-2 gap-4 mt-8 text-sm">
                    <div class="text-center">
                        <div class="border-t-2 border-gray-400 pt-2">Issued Date</div>
                        <div class="text-gray-600">25 September 2026</div>
                    </div>
                    <div class="text-center">
                        <div class="border-t-2 border-gray-400 pt-2">Certificate No</div>
                        <div class="text-gray-600 font-mono">CERT-000001-26</div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button 
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm"
                >
                    Download PDF
                </button>
                <button 
                    class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm"
                >
                    Download QR
                </button>
                <button 
                    wire:click="$set('showCertPreview', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
