<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Bulk Upload Materials</h1>
        <p class="text-gray-600 mt-1">Import learning materials via XLSX template</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Step 1: Download Template</h2>
        <button wire:click="downloadTemplate" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Download XLSX Template
        </button>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Step 2: Upload File</h2>
        <input type="file" wire:model="file" accept=".xlsx,.xls" class="mb-4">
        @error('file') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        <div>
            <button wire:click="previewFile" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" wire:loading.attr="disabled">
                Preview
            </button>
        </div>
    </div>

    @if (count($preview) > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Step 3: Preview ({{ count($preview) }} rows)</h2>
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lesson</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($preview as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $item['row'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['course_slug'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['lesson_slug'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['unit_code'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['material_type'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['title'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button wire:click="importData" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" wire:loading.attr="disabled">
                Commit Import
            </button>
        </div>
    @endif

    @if ($importResult)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Import Result</h2>
            <div class="space-y-2">
                <p><strong>Created:</strong> {{ $importResult['created'] }}</p>
                <p><strong>Updated:</strong> {{ $importResult['updated'] }}</p>
                <p><strong>Skipped:</strong> {{ $importResult['skipped'] }}</p>
                @if (count($importResult['errors']) > 0)
                    <div class="mt-4">
                        <p class="font-semibold text-red-600">Errors:</p>
                        <ul class="list-disc pl-5 text-sm text-red-600">
                            @foreach ($importResult['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
