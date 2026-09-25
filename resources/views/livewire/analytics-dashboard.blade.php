<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Analytics & Audit Console</h1>
        <p class="text-gray-600 mt-1">System observability and change history</p>
    </div>

    <div class="grid grid-cols-4 gap-6 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Total Users</p>
            <p class="text-2xl font-bold">{{ $totalUsers }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Active Sponsors</p>
            <p class="text-2xl font-bold">{{ $totalSponsors }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Published Materials</p>
            <p class="text-2xl font-bold">{{ $totalMaterials }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Quiz Attempts</p>
            <p class="text-2xl font-bold">{{ $totalAttempts }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Top Materials</h2>
            <div class="space-y-2">
                @forelse ($topMaterials as $material)
                    <div class="flex justify-between items-center py-2 border-b">
                        <span class="text-sm text-gray-900">{{ $material->title }}</span>
                        <span class="text-sm text-gray-600">{{ $material->views_count ?? 0 }} views</span>
                    </div>
                @empty
                    <p class="text-gray-600 text-sm">No data available</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Recent Users</h2>
            <div class="space-y-2">
                @forelse ($recentUsers as $user)
                    <div class="flex justify-between items-center py-2 border-b">
                        <div>
                            <p class="text-sm text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-600">{{ $user->email }}</p>
                        </div>
                        <span class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-gray-600 text-sm">No users yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Filters</h2>
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Start</label>
                <input type="date" wire:model.live="dateStart" class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date End</label>
                <input type="date" wire:model.live="dateEnd" class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sponsor</label>
                <input type="text" wire:model.live="sponsorFilter" placeholder="Sponsor name..." class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Placement</label>
                <input type="text" wire:model.live="placementFilter" placeholder="Placement..." class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
        </div>
        <p class="text-sm text-gray-600">Export/report functionality requires event tracking integration (TASK-ANA-BE-01)</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h2 class="text-lg font-semibold mb-4">Audit Log</h2>
        <p class="text-sm text-gray-600">Audit trail functionality requires TASK-M1-BE-05 (Event tracking & analytics backend)</p>
        <div class="mt-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Timestamp</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Actor</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Action</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Target</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-center text-gray-600">No audit logs available yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
