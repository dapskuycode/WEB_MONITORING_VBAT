<div class="space-y-6 p-6 text-zinc-900 dark:text-zinc-100">
    <h1 class="text-2xl font-semibold">Review nilai kuis</h1><p>Periksa jawaban dan rubrik, simpan draft, lalu publikasikan. Draft tidak mengganti hasil yang dilihat siswa.</p>
    <label for="grade-filter">Status penilaian</label><select id="grade-filter" wire:model.live="filter" class="rounded border p-2 dark:bg-zinc-900"><option value="pending_review">Menunggu review</option><option value="published">Sudah dipublikasikan</option><option value="all">Semua</option></select>
    @if(session('message'))<p role="status">{{ session('message') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
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
</div>
