<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">User Entitlement Manager</h1>
        <p class="text-gray-600 mt-1">Grant, revoke, and manage user course access</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'grant')"
                class="@if($activeTab === 'grant') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Grant Access
            </button>
            <button wire:click="$set('activeTab', 'entitlements')"
                class="@if($activeTab === 'entitlements') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Manage Entitlements
            </button>
            <button wire:click="$set('activeTab', 'batch')"
                class="@if($activeTab === 'batch') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Batch Grant
            </button>
        </nav>
    </div>

    {{-- Grant Access Tab --}}
    @if($activeTab === 'grant')
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Grant Course Access</h2>
            <form wire:submit.prevent="saveGrant" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">User *</label>
                    <select wire:model="grantUserId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- Select User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('grantUserId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Course *</label>
                    <select wire:model="grantCourseId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- Select Course --</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('grantCourseId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Expires At (optional)</label>
                    <input type="date" wire:model="grantExpiresAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for lifetime access</p>
                    @error('grantExpiresAt') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model="grantNotes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g., Sponsored by X, Promo code Y"></textarea>
                    @error('grantNotes') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Grant Access
                </button>
            </form>
        </div>
    @endif

    {{-- Manage Entitlements Tab --}}
    @if($activeTab === 'entitlements')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Edit Form (shown when selectedEntitlementId) --}}
            @if($selectedEntitlementId)
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-lg font-semibold mb-4">Edit Entitlement</h2>
                    <form wire:submit.prevent="saveEntitlement" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Expires At</label>
                            <input type="date" wire:model="editExpiresAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('editExpiresAt') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="editStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="revoked">Revoked</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md">Save</button>
                        <button type="button" wire:click="resetEntitlementForm" class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                    </form>
                </div>
            @endif

            {{-- Entitlement List --}}
            <div class="@if($selectedEntitlementId) lg:col-span-2 @else lg:col-span-3 @endif bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">All Entitlements</h2>
                
                {{-- Filters --}}
                <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-2">
                    <input type="text" wire:model.live="searchTerm" placeholder="Search user..." class="block w-full rounded-md border-gray-300 shadow-sm">
                    <select wire:model.live="userFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Users --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="courseFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Courses --</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($entitlements as $entitlement)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">
                                        <div>{{ $entitlement->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $entitlement->user->email }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ $entitlement->course->title }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        @if($entitlement->expires_at)
                                            {{ $entitlement->expires_at->format('M d, Y') }}
                                        @else
                                            <span class="text-gray-500 italic">Lifetime</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="inline-block px-2 py-1 text-xs rounded
                                            @if($entitlement->status === 'active') bg-green-200 text-green-800
                                            @elseif($entitlement->status === 'expired') bg-gray-200 text-gray-800
                                            @else bg-red-200 text-red-800 @endif">
                                            {{ ucfirst($entitlement->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <button wire:click="editEntitlement({{ $entitlement->id }})" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                                        <button wire:click="deleteEntitlement({{ $entitlement->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No entitlements found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $entitlements->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Batch Grant Tab --}}
    @if($activeTab === 'batch')
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Batch Grant Courses</h2>
            <form wire:submit.prevent="saveBatch" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">User *</label>
                    <select wire:model="batchUserId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- Select User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('batchUserId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Courses * (multiple)</label>
                    <div class="mt-1 border rounded-md p-3 max-h-48 overflow-y-auto space-y-2">
                        @foreach($courses as $course)
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="batchCourseIds" value="{{ $course->id }}" class="rounded border-gray-300">
                                <span class="ml-2 text-sm">{{ $course->title }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('batchCourseIds') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Expires At (optional)</label>
                    <input type="date" wire:model="batchExpiresAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for lifetime access</p>
                    @error('batchExpiresAt') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model="batchNotes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g., Sponsored package"></textarea>
                    @error('batchNotes') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Grant All Selected Courses
                </button>
            </form>
        </div>
    @endif
</div>
