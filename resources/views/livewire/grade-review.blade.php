<div class="space-y-6 p-6 text-zinc-900 dark:text-zinc-100">
    <h1 class="text-2xl font-semibold">Review nilai kuis &amp; tugas</h1><p>Periksa jawaban kuis, submission tugas praktik, dan gradebook kelas. Draft tidak mengganti hasil yang dilihat siswa sebelum dipublikasikan.</p>
    <nav class="flex flex-wrap gap-2" aria-label="Mode penilaian">
        <flux:button wire:click="$set('mode', 'quizzes')" :variant="$mode === 'quizzes' ? 'primary' : 'outline'">1. Review Kuis</flux:button>
        <flux:button wire:click="$set('mode', 'submissions')" :variant="$mode === 'submissions' ? 'primary' : 'outline'">2. Review Tugas Praktik</flux:button>
        <flux:button wire:click="$set('mode', 'gradebook')" :variant="$mode === 'gradebook' ? 'primary' : 'outline'">3. Gradebook Kelas</flux:button>
    </nav>
    <label for="grade-filter">Status penilaian</label><select id="grade-filter" wire:model.live="filter" class="rounded border p-2 dark:bg-zinc-900"><option value="pending_review">Menunggu review</option><option value="published">Sudah dipublikasikan</option><option value="returned">Dikembalikan untuk revisi</option><option value="all">Semua</option></select>
    @if(session('message'))<p role="status">{{ session('message') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($mode === 'quizzes')
    <ul class="space-y-2">@forelse($attempts as $attempt)<li class="flex flex-wrap items-center gap-3 rounded border border-zinc-300 p-3 dark:border-zinc-700"><span>{{ $attempt->snapshot['title'] }} · {{ $attempt->user?->name }} · {{ $attempt->grade_status }}</span><flux:button wire:click="open({{ $attempt->id }})">Buka review</flux:button></li>@empty<li>Tidak ada attempt pada filter ini.</li>@endforelse</ul>{{ $attempts->links() }}
    @if($review)
        <section class="space-y-5 rounded border border-zinc-300 p-4 dark:border-zinc-700"><h2 class="text-xl font-semibold">{{ $review['snapshot']['title'] }}</h2><p>Versi review {{ $gradeVersion }} · status siswa: {{ $review['grade_status'] }}</p>
        @foreach($review['snapshot']['questions'] as $index=>$question)
            <fieldset class="space-y-3 border-b border-zinc-200 pb-4 dark:border-zinc-700"><legend class="font-semibold">Soal {{ $index+1 }} · {{ $question['points'] }} poin</legend><p class="whitespace-pre-wrap break-words">{{ $question['question_text'] }}</p><p class="whitespace-pre-wrap break-words">Jawaban: {{ ((array)$review['answers'])[$question['id']]??'Belum dijawab' }}</p>
            @if(($question['question_type']??'mcq')==='mcq')<p>Kunci: {{ $question['correct_answer'] }}</p>@endif
            @if(($question['question_type']??'mcq')==='case_study')
                @foreach($question['rubric'] as $criterion)<label class="block">{{ $criterion['label'] }} (maks. {{ $criterion['max_points'] }})<input type="number" min="0" max="{{ $criterion['max_points'] }}" step="0.01" wire:model="grades.{{ $index }}.criteria.{{ $criterion['id'] }}" class="ml-2 max-w-full rounded border p-2 dark:bg-zinc-900" /></label>@endforeach
            @else<label class="block">Poin soal<input type="number" min="0" max="{{ $question['points'] }}" step="0.01" wire:model="grades.{{ $index }}.points" class="ml-2 max-w-full rounded border p-2 dark:bg-zinc-900" /></label>@endif
            <label class="block">Feedback untuk siswa<flux:textarea wire:model="grades.{{ $index }}.feedback" rows="3" /></label></fieldset>
        @endforeach
        <label class="block">Alasan penilaian/revisi<flux:textarea wire:model="reason" rows="2" /></label>
        <div class="flex flex-wrap gap-3"><flux:button wire:click="saveDraft" wire:loading.attr="disabled">Simpan draft review</flux:button><flux:button wire:click="publish" wire:loading.attr="disabled" variant="primary">Publikasikan draft terakhir</flux:button><flux:button wire:click="open({{ $attemptId }})">Muat ulang review</flux:button></div>
        <details><summary>Histori nilai</summary><ul class="space-y-2">@foreach($review['history'] as $version)<li>Versi {{ $version['revision'] }} · {{ $version['status'] }} · {{ $version['created_at'] }} · {{ $version['reason'] }}</li>@endforeach</ul></details>
        </section>
    @endif
    @elseif($mode === 'submissions')
    <ul class="space-y-2">@forelse($submissions as $sub)<li class="flex flex-wrap items-center gap-3 rounded border border-zinc-300 p-3 dark:border-zinc-700"><span>{{ $sub->assignment?->title }} · {{ $sub->user?->name }} · v{{ $sub->version }} · {{ $sub->receipt_code }} · {{ $sub->status }} ({{ $sub->grade_status }}) @if($sub->is_late)<strong class="text-amber-600">[Terlambat]</strong>@endif</span><flux:button wire:click="openSubmission({{ $sub->id }})">Buka submission</flux:button></li>@empty<li>Tidak ada submission tugas pada filter ini.</li>@endforelse</ul>{{ $submissions->links() }}
    @if($submissionReview)
        <section class="space-y-4 rounded border border-zinc-300 p-4 dark:border-zinc-700">
            <h2 class="text-xl font-semibold">{{ $submissionReview['assignment_snapshot']['title'] ?? 'Tugas' }} (v{{ $submissionReview['version'] }})</h2>
            <p>Bukti kirim: <code>{{ $submissionReview['receipt_code'] }}</code> · Versi nilai: {{ $submissionGradeVersion }} · Status: {{ $submissionReview['status'] }} ({{ $submissionReview['grade_status'] }})</p>
            @if(!empty($submissionReview['text_body']))<div class="rounded border p-3"><p class="font-medium">Jawaban Teks:</p><p class="whitespace-pre-wrap break-words">{{ $submissionReview['text_body'] }}</p></div>@endif
            @if(!empty($submissionReview['files']))<div><p class="font-medium">Lampiran ({{ count($submissionReview['files']) }}):</p><ul class="list-disc pl-5">@foreach($submissionReview['files'] as $f)<li><a href="{{ $f['download_url'] }}" target="_blank" class="underline">{{ $f['original_name'] }}</a> ({{ $f['mime_type'] }}, {{ number_format($f['size_bytes']/1024, 1) }} KB)</li>@endforeach</ul></div>@endif
            <label class="block">Skor (maks. {{ $submissionReview['assignment_snapshot']['max_score'] ?? 100 }})<input type="number" min="0" max="{{ $submissionReview['assignment_snapshot']['max_score'] ?? 100 }}" step="0.01" wire:model="submissionScore" class="ml-2 rounded border p-2 dark:bg-zinc-900" /></label>
            <label class="block">Feedback untuk siswa<flux:textarea wire:model="submissionFeedback" rows="3" /></label>
            <label class="block">Alasan penilaian / pengembalian<flux:textarea wire:model="submissionReason" rows="2" /></label>
            <label class="block">Tenggat revisi baru (opsional saat dikembalikan)<input type="datetime-local" wire:model="resubmitDeadline" class="rounded border p-2 dark:bg-zinc-900" /></label>
            <div class="flex flex-wrap gap-3">
                <flux:button wire:click="saveSubmissionDraft" wire:loading.attr="disabled">Simpan draft nilai</flux:button>
                <flux:button wire:click="publishSubmission" wire:loading.attr="disabled" variant="primary">Publikasikan nilai</flux:button>
                <flux:button wire:click="returnSubmission" wire:loading.attr="disabled">Kembalikan untuk revisi</flux:button>
            </div>
        </section>
    @endif
    @elseif($mode === 'gradebook')
    <div class="space-y-4">
        <label for="gb-course" class="block font-medium">Pilih kelas</label>
        <select id="gb-course" wire:model.live="gradebookCourseId" class="rounded border p-2 dark:bg-zinc-900"><option value="">-- Pilih Kelas --</option>@foreach($courses as $c)<option value="{{ $c->id }}">{{ $c->title }} ({{ $c->code }})</option>@endforeach</select>
        @if($gradebook)
        <div class="overflow-x-auto rounded border border-zinc-300 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-300 text-sm dark:divide-zinc-700">
                <thead><tr><th class="p-3 text-left">Siswa</th>@foreach($gradebook['columns'] as $col)<th class="p-3 text-left">{{ $col['title'] }} ({{ $col['kind'] }}, bobot {{ $col['weight'] }})</th>@endforeach<th class="p-3 text-left">Rata-rata Terpublikasi</th></tr></thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">@forelse($gradebook['rows'] as $row)<tr><td class="p-3 font-medium">{{ $row['student']['name'] }}<br><span class="text-xs text-zinc-500">{{ $row['student']['email'] }}</span></td>@foreach($gradebook['columns'] as $col)@php($cell = $row['cells'][$col['key']])<td class="p-3">{{ $cell['status'] }}@if($cell['score_percent'] !== null) · <strong>{{ $cell['score_percent'] }}%</strong>@endif @if(!empty($cell['has_draft_grade']))<span class="text-amber-600">(draft)</span>@endif</td>@endforeach<td class="p-3 font-semibold">{{ $row['summary']['weighted_score_percent'] !== null ? $row['summary']['weighted_score_percent'].'%' : 'Belum ada nilai' }}</td></tr>@empty<tr><td colspan="{{ count($gradebook['columns']) + 2 }}" class="p-3">Belum ada siswa pada kelas ini.</td></tr>@endforelse</tbody>
            </table>
        </div>
        @endif
    </div>
    @endif
</div>
