@extends('layouts.app')

@section('title', 'Results · CDMP Exam Simulator')

@section('content')
<section class="result-page">
    @if ($attempt->status === 'expired')
        <div class="flash flash-warning" role="status">Time expired. Your saved answers were submitted and graded.</div>
    @endif

    <div class="result-heading">
        <a class="back-link" href="{{ route('dashboard') }}">← Home</a>
        <p class="eyebrow">{{ $attempt->status === 'expired' ? 'Time expired' : 'Session complete' }} · {{ $attempt->mode === 'full' ? 'Full exam' : 'Practice session' }}</p>
        <div class="score-line"><h1>{{ number_format((float) $attempt->score_percent, 2) }}<span>%</span></h1><span class="score-denominator">{{ $attempt->correct_count }} / {{ $attempt->total_questions }} correct</span></div>
        <p class="lead">Mock exam score · {{ $attempt->submitted_at?->timezone('Asia/Jakarta')->format('M j, Y · H:i') }}</p>
    </div>

    <section class="result-summary" aria-label="Score summary">
        <div><strong>{{ $attempt->correct_count }}</strong><span>Correct</span></div>
        <div><strong>{{ $attempt->incorrect_count }}</strong><span>Incorrect</span></div>
        <div><strong>{{ $attempt->unanswered_count }}</strong><span>Unanswered</span></div>
        <div><strong>{{ floor($timeUsedSeconds / 60) }}<small>m</small> {{ $timeUsedSeconds % 60 }}<small>s</small></strong><span>Time used</span></div>
    </section>

    <section class="reference-scale">
        <div><p class="eyebrow">Study reference thresholds</p><p>For your own study planning only. These are not certification outcomes.</p></div>
        <div class="thresholds"><span><strong>60%</strong><i></i></span><span><strong>70%</strong><i></i></span><span><strong>80%</strong><i></i></span></div>
    </section>

    <section class="review-section">
        <div class="section-heading review-heading">
            <div><p class="eyebrow">Answer review</p><h2>Question by question</h2></div>
            <div class="review-actions"><a class="button button-small" href="{{ route('exams.create') }}">Start another</a></div>
        </div>
        <div class="filter-row" role="group" aria-label="Filter question review">
            <button type="button" class="filter-chip is-active" data-review-filter="all" aria-pressed="true">All <span>{{ $reviews->count() }}</span></button>
            <button type="button" class="filter-chip" data-review-filter="incorrect" aria-pressed="false">Incorrect <span>{{ $reviews->where('outcome', 'incorrect')->count() }}</span></button>
            <button type="button" class="filter-chip" data-review-filter="unanswered" aria-pressed="false">Unanswered <span>{{ $reviews->where('outcome', 'unanswered')->count() }}</span></button>
            <button type="button" class="filter-chip" data-review-filter="correct" aria-pressed="false">Correct <span>{{ $reviews->where('outcome', 'correct')->count() }}</span></button>
            <button type="button" class="filter-chip" data-review-filter="flagged" aria-pressed="false">Flagged <span>{{ $reviews->where('is_flagged', true)->count() }}</span></button>
        </div>

        <div class="review-list">
            @foreach ($reviews as $review)
                <article class="review-item" data-review-item data-outcome="{{ $review['outcome'] }}" data-flagged="{{ $review['is_flagged'] ? 'true' : 'false' }}">
                    <div class="review-item-heading">
                        <span class="review-number">{{ str_pad((string) $review['position'], 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="status-pill {{ $review['outcome'] === 'correct' ? 'status-done' : ($review['outcome'] === 'incorrect' ? 'status-error' : 'status-muted') }}">{{ ucfirst($review['outcome']) }}</span>
                        @if ($review['is_flagged'])<span class="flagged-label">⚑ Flagged</span>@endif
                        <span class="source-ref">PDF question {{ $review['original_number'] ?? '—' }} · page {{ $review['source_page'] ?? '—' }}</span>
                    </div>
                    <h3>{{ $review['text'] }}</h3>
                    <div class="review-answers">
                        <p><span>Your answer</span><strong class="{{ $review['outcome'] === 'incorrect' ? 'answer-wrong' : '' }}">{{ $review['selected_text'] ?? 'No answer' }}</strong></p>
                        <p><span>Correct answer</span><strong class="answer-correct">{{ $review['correct_text'] }}</strong></p>
                    </div>
                    @if ($review['explanation'])
                        <div class="explanation"><span>Source explanation</span><p>{{ $review['explanation'] }}</p></div>
                    @else
                        <div class="explanation explanation-empty"><span>Source explanation</span><p>No explanation provided in the source PDF.</p></div>
                    @endif
                </article>
            @endforeach
        </div>
        <p class="provenance-note">Answers follow the supplied practice PDF and have not all been independently verified against DAMA-DMBOK.</p>
        <div class="result-footer-actions">
            <a class="button button-secondary" href="{{ route('dashboard') }}">Back to dashboard</a>
            <a class="button button-secondary" href="{{ route('history.index') }}">View history</a>
        </div>
    </section>
</section>
@endsection
