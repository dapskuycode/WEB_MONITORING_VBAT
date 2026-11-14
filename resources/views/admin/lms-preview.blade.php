<x-layouts::admin :title="'Pratinjau kelas VBAT'">
<div class="space-y-6 text-zinc-900 dark:text-zinc-100">
    <header><flux:heading level="1" size="xl">Pratinjau admin: {{ $course->title }}</flux:heading><p class="mt-2">Mode baca saja. Draft dapat terlihat di sini. Tidak membuat enrollment, progres, jawaban atau percobaan kuis siswa.</p><p>Status kelas: {{ $course->status }} · Paket: {{ $course->type }} · Revisi: {{ $course->revision }}</p></header>
    <p class="rounded border border-zinc-500 p-3">Simulasi siswa tanpa paket: {{ $course->status !== 'published' ? 'terkunci karena kelas belum terbit' : ($course->type === 'free_class' ? 'Free Class dapat diakses setelah login' : 'terkunci sampai entitlement dan prasyarat akun terpenuhi') }}. Media di bawah dibuka dengan izin admin, bukan akun siswa.</p>
    <flux:button href="{{ route('admin.content') }}">Kembali ke pengelolaan konten</flux:button>
    @forelse($course->lessons as $lesson)<section class="space-y-4 border-t border-zinc-400 pt-4"><flux:heading level="2">{{ $lesson->sort_order }}. {{ $lesson->title }} ({{ $lesson->status }})</flux:heading>
        @forelse($lesson->learningMaterials as $material)<article class="space-y-3"><flux:heading level="3">{{ $material->title }}</flux:heading><p>{{ $material->status }} · {{ $material->is_required ? 'wajib' : 'opsional' }} · {{ $material->is_preview ? 'kandidat preview' : 'materi kelas' }}</p>
            <p>{{ $material->description }}</p>
            @if($material->material_type === 'youtube_video' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $material->youtube_video_id ?? ''))
            <iframe class="aspect-video w-full max-w-3xl" title="Pratinjau video {{ $material->title }}" src="https://www.youtube.com/embed/{{ $material->youtube_video_id }}" allow="fullscreen" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe><p>Periksa pemutaran dan pesan error provider. Pratinjau ini belum membuktikan pemutaran Android/iOS.</p>
            @elseif($material->material_type === 'pdf_document')<flux:button href="{{ $mediaUrls[$material->id] }}" target="_blank">Buka PDF privat (tab baru, izin 5 menit)</flux:button>
            @elseif($material->material_type === 'text_content')<p class="whitespace-pre-wrap break-words">{{ $material->content_text }}</p>
            @elseif($material->material_type === 'external_link' && str_starts_with($material->external_url ?? '', 'https://'))<a class="inline-flex min-h-11 items-center underline" href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer">Buka tautan materi (tab baru)</a>
            @else<p>Sumber materi belum tersedia atau belum valid.</p>@endif
            @if($material->quiz_required)<p>Kuis wajib terhubung: {{ $material->quiz_id ?? 'belum dipilih' }}. Preview tidak memulai attempt kuis.</p>@endif
        </article>@empty<p>Modul belum memiliki materi.</p>@endforelse
    </section>@empty<p>Kelas belum memiliki modul.</p>@endforelse
</div>
</x-layouts::admin>
