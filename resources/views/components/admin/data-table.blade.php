@props([
    'headers' => [],          // [['key' => 'name', 'label' => 'Nama', 'sortable' => true], ...]
    'rows' => [],              // Collection / array
    'empty' => 'Tidak ada data untuk ditampilkan.',
    'loading' => false,
    'error' => null,
    'paginated' => false,
    'actions' => false,        // show last action column
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden']) }}>
    {{-- Loading overlay --}}
    @if($loading)
    <x-admin.loading />
    @endif

    {{-- Error state --}}
    @if($error)
    <x-admin.error-state :message="$error" />
    @endif

    {{-- Empty state --}}
    @if(!$loading && !$error && $rows->isEmpty())
        <x-admin.empty-state :message="$empty" />
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-xs font-semibold text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-800">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-6 py-4" {{ isset($header['class']) ? "class={$header['class']}" : '' }}>
                            @if(isset($header['sortable']) && $header['sortable'])
                                <button type="button" wire:click="sort('{{ $header['key'] }}')" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white transition">
                                    {{ $header['label'] }}
                                    <flux:icon icon="chevron-up-down" variant="micro" />
                                </button>
                            @else
                                {{ $header['label'] }}
                            @endif
                        </th>
                    @endforeach
                    @if($actions)
                    <th class="px-6 py-4 text-right">{{ __('Aksi') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($paginated && method_exists($rows, 'links'))
    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
        {{ $rows->links() }}
    </div>
    @endif
    @endif
</div>