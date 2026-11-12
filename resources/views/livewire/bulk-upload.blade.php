<div class="space-y-6 text-zinc-900 dark:text-zinc-100">
    <div><h2 class="text-xl font-semibold">Import materi LMS</h2><p class="mt-2">Template XLSX/CSV, maksimal 5 MB dan 1000 baris. Kelas dan modul harus sudah memiliki kode di CMS. Duplikat ditolak; semua hasil tetap Draft.</p></div>
    <div class="flex flex-wrap gap-3"><flux:button wire:click="downloadTemplate">Unduh template XLSX</flux:button><flux:button wire:click="$refresh">Perbarui status berkas</flux:button></div>
    <section class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="font-semibold">1. Unggah bacaan PDF jika diperlukan</h3><p>PDF/e-book berbentuk PDF maksimal 10 MB. Berkas masuk karantina hingga pemindai dan parser berhasil. Nama sama bisa ambigu; gunakan ID upload di Excel.</p>
        <label for="bulk-pdf">Berkas PDF</label><input id="bulk-pdf" type="file" accept="application/pdf" wire:model="pdf" class="block max-w-full" />@error('pdf')<p role="alert">{{ $message }}</p>@enderror
        <flux:button wire:click="uploadPdf" wire:loading.attr="disabled">Unggah untuk diperiksa</flux:button>
        <ul class="space-y-3">@forelse($uploads as $upload)<li class="break-all"><strong>{{ $upload->original_name }}</strong> — {{ $upload->status }} {{ $upload->reason }}<br />ID: <code>{{ $upload->id }}</code></li>@empty<li>Belum ada berkas milik akun ini.</li>@endforelse</ul>
    </section>
    <section class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="font-semibold">2. Periksa seluruh baris</h3><label for="bulk-file">Template terisi</label><input id="bulk-file" type="file" accept=".xlsx,.csv" wire:model="file" class="block max-w-full" />@error('file')<p role="alert">{{ $message }}</p>@enderror
        <flux:button wire:click="previewFile" wire:loading.attr="disabled">Buat preview</flux:button>
    </section>
    @if($summary)
        <section class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="font-semibold">3. Tinjau hasil pemeriksaan</h3><p>{{ $summary['ready_rows'] }} baris siap; {{ count($summary['errors']) }} masalah. @if($summary['status'] === 'applied')Preview sudah diterapkan; retry tidak membuat materi ganda.@else Preview berlaku hingga {{ $summary['expires_at'] }}. Belum ada materi yang dibuat.@endif</p>
            @if(count($summary['errors']))<flux:button wire:click="downloadErrors">Unduh laporan kesalahan XLSX</flux:button>@endif
            <ul class="space-y-2">@foreach($summary['errors'] as $error)<li class="break-words" role="alert">Baris {{ $error['row'] }}, {{ $error['field'] }}: {{ $error['message'] }} {{ $error['suggestion'] }}</li>@endforeach</ul>
            <ul class="space-y-1">@foreach($summary['preview'] as $row)<li class="break-words">Baris {{ $row['row'] }} · {{ $row['material_code'] }} · {{ $row['title'] }} · Draft</li>@endforeach</ul>
            <p>Kode kuis baru membuat kuis nonaktif tanpa soal. Lengkapi melalui editor kuis dan aktifkan sebelum menerbitkan materi. Apply tidak memperbarui materi existing.</p>
            @if($summary['is_valid'] && $summary['status'] !== 'applied')<flux:button wire:click="importData" wire:loading.attr="disabled" variant="primary">Terapkan seluruh baris sebagai Draft</flux:button>@endif
            @error('import')<p role="alert">{{ $message }}</p>@enderror
        </section>
    @endif
    @if($importResult)<p role="status">{{ $importResult['imported'] }} materi berhasil dibuat sebagai Draft. Gunakan CMS untuk pratinjau dan publikasi.</p>@endif
</div>
