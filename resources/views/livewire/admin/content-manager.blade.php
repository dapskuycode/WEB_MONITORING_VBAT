<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Info & Legal Content Manager</h2>
        
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <div class="border-b mb-6">
            <nav class="flex gap-4">
                <button 
                    wire:click="$set('activeTab', 'info')"
                    class="px-4 py-2 {{ $activeTab === 'info' ? 'border-b-2 border-blue-600 font-bold' : 'text-gray-600' }}"
                >
                    Info Content
                </button>
                <button 
                    wire:click="$set('activeTab', 'legal')"
                    class="px-4 py-2 {{ $activeTab === 'legal' ? 'border-b-2 border-blue-600 font-bold' : 'text-gray-600' }}"
                >
                    Legal Versioning
                </button>
            </nav>
        </div>
    </div>

    @if($activeTab === 'info')
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Info Content & WhatsApp Config</h3>
            <button 
                wire:click="openInfoModal"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
                + Buat Info
            </button>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Premium</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">WhatsApp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($infoContents as $info)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $info->slug }}</td>
                    <td class="px-6 py-4">{{ $info->title }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($info->is_premium_only)
                            <span class="text-xs bg-yellow-100 px-2 py-1 rounded">Premium</span>
                        @else
                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">Public</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        {{ $info->whatsapp_number ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button 
                            wire:click="openInfoModal({{ $info->id }})"
                            class="text-blue-600 hover:text-blue-900 mr-3"
                        >
                            Edit
                        </button>
                        <button 
                            wire:click="deleteInfo({{ $info->id }})"
                            class="text-red-600 hover:text-red-900"
                        >
                            Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada data</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $infoContents->links() }}
        </div>
    </div>
    @endif

    @if($activeTab === 'legal')
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Legal Content Versioning</h3>
            <button 
                wire:click="openLegalModal"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
                + Buat Versi Baru
            </button>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($legalContents as $legal)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-xs bg-blue-100 px-2 py-1 rounded">{{ $legal->type }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-mono">{{ $legal->version }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $legal->effective_at?->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $legal->created_at->format('Y-m-d H:i') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada data</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $legalContents->links() }}
        </div>
    </div>
    @endif

    @if($showInfoModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
            <h3 class="text-lg font-bold mb-4">{{ $infoId ? 'Edit' : 'Buat' }} Info Content</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Slug (URL)</label>
                    <input 
                        type="text" 
                        wire:model="infoSlug" 
                        class="border rounded px-4 py-2 w-full"
                        placeholder="about-us"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Title</label>
                    <input 
                        type="text" 
                        wire:model="infoTitle" 
                        class="border rounded px-4 py-2 w-full"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Content (HTML/Markdown)</label>
                    <textarea 
                        wire:model="infoContent" 
                        class="border rounded px-4 py-2 w-full"
                        rows="8"
                    ></textarea>
                </div>
                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="infoPremiumOnly"
                            class="mr-2"
                        >
                        <span class="text-sm">Premium Only</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">WhatsApp Number (opsional)</label>
                    <input 
                        type="text" 
                        wire:model="infoWhatsappNumber" 
                        class="border rounded px-4 py-2 w-full"
                        placeholder="62812345678"
                    >
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button 
                    wire:click="saveInfo"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex-1"
                >
                    Simpan
                </button>
                <button 
                    wire:click="$set('showInfoModal', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($showLegalModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
            <h3 class="text-lg font-bold mb-4">Buat Legal Content Version</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select wire:model="legalType" class="border rounded px-4 py-2 w-full">
                        <option value="terms">Terms of Service</option>
                        <option value="privacy">Privacy Policy</option>
                        <option value="about">About</option>
                        <option value="consent">Consent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Version</label>
                    <input 
                        type="text" 
                        wire:model="legalVersion" 
                        class="border rounded px-4 py-2 w-full"
                        placeholder="1.0"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Content (HTML/Markdown)</label>
                    <textarea 
                        wire:model="legalContent" 
                        class="border rounded px-4 py-2 w-full"
                        rows="10"
                    ></textarea>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button 
                    wire:click="saveLegal"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex-1"
                >
                    Simpan
                </button>
                <button 
                    wire:click="$set('showLegalModal', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
