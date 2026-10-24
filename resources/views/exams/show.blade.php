@extends('layouts.app')

@section('title', 'Question '.$position.' · CDMP Exam Simulator')

@section('content')
<section class="exam-session" data-exam-session
    data-save-url="{{ route('exams.answers.update', [$attempt, '__POSITION__']) }}"
    data-submit-url="{{ route('exams.submit', $attempt) }}"
    data-result-url="{{ route('exams.result', $attempt) }}"
    data-expires-at="{{ $attempt->expires_at?->toIso8601String() }}"
    data-server-now="{{ now('UTC')->toIso8601String() }}"
    data-total="{{ $attempt->total_questions }}"
    data-position="{{ $position }}">
    <div class="exam-heading">
        <div>
            <a class="back-link" href="{{ route('dashboard') }}">← Exit to home</a>
            <p class="eyebrow">{{ $attempt->mode === 'full' ? 'Full exam' : 'Practice session' }} · {{ $attempt->total_questions }} questions</p>
        </div>
        <div class="timer-block" data-timer-block>
            <span class="small-label">Time remaining</span>
            <strong class="timer-value" data-timer role="timer" aria-live="off">{{ $attempt->expires_at ? '—:—' : 'Untimed practice' }}</strong>
            <span class="timer-warning-label" data-timer-warning aria-live="polite" hidden></span>
        </div>
    </div>

    <div class="exam-layout">
        <article class="question-panel">
            <div class="question-meta">
                <p class="question-count">Question <strong>{{ $position }}</strong> <span>of {{ $attempt->total_questions }}</span></p>
                <button type="button" class="flag-button {{ $currentQuestion['flagged'] ? 'is-flagged' : '' }}" data-flag-toggle aria-pressed="{{ $currentQuestion['flagged'] ? 'true' : 'false' }}">
                    <span class="flag-symbol" aria-hidden="true">⚑</span>
                    <span data-flag-label>{{ $currentQuestion['flagged'] ? 'Flagged for review' : 'Flag for review' }}</span>
                </button>
            </div>

            <h1 class="question-text">{{ $currentQuestion['text'] }}</h1>

            <fieldset class="option-list" aria-label="Answer choices">
                <legend class="sr-only">Choose one answer</legend>
                @foreach ($currentQuestion['options'] as $option)
                    <label class="answer-option {{ $currentQuestion['selected'] !== null && (int) $currentQuestion['selected'] === $option['source_index'] ? 'is-selected' : '' }}">
                        <input type="radio" name="selected_option_source_index" value="{{ $option['source_index'] }}" {{ $currentQuestion['selected'] !== null && (int) $currentQuestion['selected'] === $option['source_index'] ? 'checked' : '' }}>
                        <span class="option-letter">{{ chr(65 + $loop->index) }}</span>
                        <span class="option-copy">{{ $option['text'] }}</span>
                        <span class="option-check" aria-hidden="true"></span>
                    </label>
                @endforeach
            </fieldset>

            <div class="question-tools">
                <button type="button" class="text-button" data-clear-answer {{ $currentQuestion['selected'] === null ? 'disabled' : '' }}>Clear answer</button>
                <span class="save-status" data-save-status role="status" aria-live="polite">All changes saved</span>
                <button type="button" class="retry-save" data-retry-save hidden>Retry</button>
            </div>

            <div class="question-navigation">
                @if ($position > 1)
                    <a class="button button-secondary" data-navigate href="{{ route('exams.show', $attempt) }}?position={{ $position - 1 }}">← Previous</a>
                @else
                    <span></span>
                @endif
                @if ($position < $attempt->total_questions)
                    <a class="button button-primary" data-navigate href="{{ route('exams.show', $attempt) }}?position={{ $position + 1 }}">Save &amp; next <span aria-hidden="true">→</span></a>
                @else
                    <button type="button" class="button button-primary" data-open-submit>Review &amp; submit <span aria-hidden="true">→</span></button>
                @endif
            </div>
        </article>

        <aside class="navigator-panel" aria-label="Question navigator">
            <div class="navigator-heading">
                <div><p class="eyebrow">Your progress</p><h2>Question navigator</h2></div>
                <button type="button" class="navigator-toggle" data-navigator-toggle aria-expanded="true">Hide</button>
            </div>
            <div class="navigator-content" data-navigator-content>
                <div class="progress-counts">
                    <div><strong data-answered-count>{{ $answeredCount }}</strong><span>Answered</span></div>
                    <div><strong data-unanswered-count>{{ $attempt->total_questions - $answeredCount }}</strong><span>Unanswered</span></div>
                    <div><strong data-flagged-count>{{ $flaggedCount }}</strong><span>Flagged</span></div>
                </div>
                <div class="palette-grid">
                    @foreach ($palette as $item)
                        <a data-palette-item data-position="{{ $item['position'] }}" data-answered="{{ $item['answered'] ? 'true' : 'false' }}" data-flagged="{{ $item['flagged'] ? 'true' : 'false' }}"
                           class="palette-item {{ $item['position'] === $position ? 'is-current' : '' }} {{ $item['answered'] ? 'is-answered' : '' }} {{ $item['flagged'] ? 'is-flagged' : '' }}"
                           href="{{ route('exams.show', $attempt) }}?position={{ $item['position'] }}"
                           aria-label="Question {{ $item['position'] }}{{ $item['answered'] ? ', answered' : ', unanswered' }}{{ $item['flagged'] ? ', flagged' : '' }}{{ $item['position'] === $position ? ', current' : '' }}">
                            {{ str_pad((string) $item['position'], 2, '0', STR_PAD_LEFT) }}
                            @if ($item['flagged'])<span class="palette-flag" aria-hidden="true">⚑</span>@endif
                        </a>
                    @endforeach
                </div>
                <div class="palette-legend"><span><i class="legend-dot answered"></i>Answered</span><span><i class="legend-dot flagged"></i>Flagged</span><span><i class="legend-dot current"></i>Current</span></div>
                <button type="button" class="finish-link" data-open-submit>Finish session <span aria-hidden="true">→</span></button>
            </div>
        </aside>
    </div>
</section>

<dialog class="submit-dialog" data-submit-dialog>
    <div class="dialog-inner">
        <p class="eyebrow">Before you finish</p>
        <h2>Submit this session?</h2>
        <p>Your answers will be graded and can’t be changed after submission.</p>
        <div class="dialog-counts"><span><strong data-dialog-answered>{{ $answeredCount }}</strong> answered</span><span><strong data-dialog-open>{{ $attempt->total_questions - $answeredCount }}</strong> unanswered</span><span><strong data-dialog-flagged>{{ $flaggedCount }}</strong> flagged</span></div>
        <div class="dialog-actions">
            <button type="button" class="button button-secondary" data-close-submit>Continue reviewing</button>
            <form method="POST" action="{{ route('exams.submit', $attempt) }}" data-submit-form>@csrf<button class="button button-primary" type="submit">Submit session</button></form>
        </div>
    </div>
</dialog>
@endsection
