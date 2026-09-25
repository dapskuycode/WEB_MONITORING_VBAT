<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Rule Configuration Manager</h1>
        <p class="text-gray-600 mt-1">Manage learning rules, pricing, and thresholds</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Completion Threshold</p>
            <p class="text-2xl font-bold">70%</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Attempt Limit</p>
            <p class="text-2xl font-bold">5</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Hardware Threshold</p>
            <p class="text-2xl font-bold">90%</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">
            {{ $editingId ? 'Edit Rule Config' : 'Add New Rule Config' }}
        </h2>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Key</label>
                <input type="text" wire:model="key" class="w-full px-3 py-2 border border-gray-300 rounded" {{ $editingId ? 'disabled' : '' }}>
                @error('key') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Type</label>
                <select wire:model="data_type" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="float">Float</option>
                    <option value="boolean">Boolean</option>
                    <option value="json">JSON</option>
                </select>
                @error('data_type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                <input type="text" wire:model="value" class="w-full px-3 py-2 border border-gray-300 rounded">
                @error('value') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea wire:model="description" class="w-full px-3 py-2 border border-gray-300 rounded" rows="2"></textarea>
            </div>
        </div>

        <div class="flex gap-2">
            <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                {{ $editingId ? 'Update' : 'Create' }}
            </button>
            @if ($editingId)
                <button wire:click="cancel" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                    Cancel
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Rule Configs</h2>

        <div class="mb-4">
            <input type="text" wire:model.live="search" placeholder="Search key or description..." class="w-full px-3 py-2 border border-gray-300 rounded">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Key</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Value</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Type</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Description</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($configs as $config)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $config->key }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $config->value }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $config->data_type }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $config->description }}</td>
                            <td class="px-4 py-2 text-center text-sm">
                                <button wire:click="edit({{ $config->id }})" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                                <button wire:click="delete({{ $config->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center text-gray-600">No rule configs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $configs->links() }}
        </div>
    </div>
</div>
