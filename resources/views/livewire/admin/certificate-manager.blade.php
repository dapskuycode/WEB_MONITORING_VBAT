<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Certificate Manager & QR</h2>
        
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <div class="flex gap-4 mb-4">
            <input 
                type="text" 
                wire:model.live="search" 
                placeholder="Cari nomor sertifikat atau nama..."
                class="border rounded px-4 py-2 flex-1 max-w-md"
            >
            <select wire:model.live="status" class="border rounded px-4 py-2">
                <option value="all">Semua Status</option>
                <option value="valid">Valid</option>
                <option value="revoked">Revoked</option>
            </select>
            <button 
                wire:click="openIssueModal"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
                + Terbitkan Sertifikat
            </button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cert Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issued</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">QR</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($certificates as $cert)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">{{ $cert->certificate_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $cert->user->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded {{ $cert->status === 'valid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $cert->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $cert->issued_at?->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($cert->status === 'valid')
                            <a href="#" class="text-blue-600 hover:underline text-sm">View QR</a>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($cert->status === 'valid')
                            <button 
                                wire:click="openRevokeModal({{ $cert->id }})"
                                class="text-red-600 hover:text-red-900"
                            >
                                Revoke
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $certificates->links() }}
    </div>

    @if($showIssueModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Terbitkan Sertifikat Baru</h3>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Course ID</label>
                <input 
                    type="number" 
                    wire:model="courseId" 
                    placeholder="ID course..."
                    class="border rounded px-4 py-2 w-full"
                >
            </div>

            <div class="flex gap-2">
                <button 
                    wire:click="issueCertificate"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Terbitkan
                </button>
                <button 
                    wire:click="$set('showIssueModal', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($showRevokeModal && $selectedCertificate)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Batalkan Sertifikat</h3>
            <p class="text-sm text-gray-600 mb-4">Nomor: {{ $selectedCertificate->certificate_number }}</p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Alasan Pembatalan</label>
                <textarea 
                    wire:model="revokeReason" 
                    placeholder="Masukkan alasan..."
                    class="border rounded px-4 py-2 w-full"
                ></textarea>
            </div>

            <div class="flex gap-2">
                <button 
                    wire:click="revokeCertificate"
                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
                >
                    Batalkan
                </button>
                <button 
                    wire:click="$set('showRevokeModal', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
