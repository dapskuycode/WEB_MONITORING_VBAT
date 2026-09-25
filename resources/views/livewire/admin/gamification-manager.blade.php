<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Gamification & Notification Manager</h2>
        
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Badges -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Badges</h3>
                <button 
                    wire:click="openBadgeModal"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
                >
                    + Buat Badge
                </button>
            </div>

            <div class="mb-4">
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="Cari badge..."
                    class="border rounded px-4 py-2 w-full"
                >
            </div>

            <div class="space-y-4">
                @forelse($badges as $badge)
                <div class="border rounded p-4">
                    <div class="flex justify-between">
                        <div>
                            <h4 class="font-bold">{{ $badge->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $badge->description }}</p>
                            <div class="mt-2">
                                <span class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $badge->code }}</span>
                                <span class="text-xs bg-yellow-100 px-2 py-1 rounded ml-2">{{ $badge->xp_reward }} XP</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                wire:click="openBadgeModal({{ $badge->id }})"
                                class="text-blue-600 text-sm"
                            >
                                Edit
                            </button>
                            <button 
                                wire:click="deleteBadge({{ $badge->id }})"
                                class="text-red-600 text-sm"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500">Belum ada badge</p>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $badges->links() }}
            </div>
        </div>

        <!-- Achievements -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-4">
                <h3 class="text-lg font-bold mb-4">Achievements</h3>
                <input 
                    type="number" 
                    wire:model.live="userId" 
                    placeholder="Filter user ID (opsional)..."
                    class="border rounded px-4 py-2 w-full"
                >
            </div>

            <div class="space-y-4">
                @forelse($achievements as $ach)
                <div class="border rounded p-4">
                    <div class="flex justify-between">
                        <div>
                            <h4 class="font-bold">{{ $ach->badge->name }}</h4>
                            <p class="text-sm text-gray-600">User: {{ $ach->user->name }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $ach->earned_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <div>
                            <span class="text-xs bg-green-100 px-2 py-1 rounded">Earned</span>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500">Belum ada achievement</p>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $achievements->links() }}
            </div>
        </div>
    </div>

    <!-- Streak config -->
    <div class="bg-white shadow rounded-lg p-6 mt-6">
        <h3 class="text-lg font-bold mb-4">Streak Configuration</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2">Grace Period (days)</label>
                <input 
                    type="number" 
                    wire:model="graceDays" 
                    min="0" max="7"
                    class="border rounded px-4 py-2 w-full"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Timezone</label>
                <select wire:model="timezone" class="border rounded px-4 py-2 w-full">
                    <option value="Asia/Jakarta">Asia/Jakarta</option>
                    <option value="UTC">UTC</option>
                    <option value="Asia/Singapore">Singapore</option>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <button 
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                wire:click="$set('showStreakConfigModal', true)"
            >
                Simpan Konfigurasi
            </button>
        </div>
    </div>

    @if($showBadgeModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">{{ $badgeCode ? 'Edit' : 'Buat' }} Badge</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Kode (unik)</label>
                    <input 
                        type="text" 
                        wire:model="badgeCode" 
                        class="border rounded px-4 py-2 w-full"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nama</label>
                    <input 
                        type="text" 
                        wire:model="badgeName" 
                        class="border rounded px-4 py-2 w-full"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Deskripsi</label>
                    <textarea 
                        wire:model="badgeDescription" 
                        class="border rounded px-4 py-2 w-full"
                        rows="2"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Icon URL</label>
                    <input 
                        type="text" 
                        wire:model="badgeIconUrl" 
                        class="border rounded px-4 py-2 w-full"
                        placeholder="https://..."
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">XP Reward</label>
                    <input 
                        type="number" 
                        wire:model="badgeXpReward" 
                        min="0"
                        class="border rounded px-4 py-2 w-full"
                    >
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button 
                    wire:click="saveBadge"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex-1"
                >
                    Simpan
                </button>
                <button 
                    wire:click="$set('showBadgeModal', false)"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
