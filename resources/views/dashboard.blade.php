@extends('layouts.app')

@section('title', 'Prepare with focus · CDMP Exam Simulator')

@section('content')
<section class="hero-block hero-home">
    <div class="hero-copy">
        <p class="hero-badge"><span class="badge-dot" aria-hidden="true"></span> Your personal CDMP study space</p>
        <h1>Build confidence<br>before <span class="hero-highlight">exam day.</span></h1>
        <p class="lead">A focused place to practice CDMP questions, track your progress, and learn from every attempt.</p>
        <div class="hero-actions">
            <a class="button button-primary hero-primary" href="{{ route('exams.create') }}">Start an exam <span aria-hidden="true">→</span></a>
            @if ($activeAttempt)
                <a class="button button-secondary hero-secondary" href="{{ route('exams.show', $activeAttempt) }}">Resume your session</a>
            @else
                <a class="button button-secondary hero-secondary" href="{{ route('history.index') }}">View study history</a>
            @endif
        </div>
        <div class="hero-facts" aria-label="Exam format highlights">
            <div><strong>100</strong><span>questions per full exam</span></div>
            <i aria-hidden="true"></i>
            <div><strong>90–110 <small>min</small></strong><span>timed exam options</span></div>
            <i aria-hidden="true"></i>
            <div><strong>Review</strong><span>answers after submission</span></div>
        </div>
    </div>

    <div class="hero-visual" aria-label="Preview of an exam session">
        <div class="visual-glow" aria-hidden="true"></div>
        <div class="visual-orbit visual-orbit-one" aria-hidden="true"></div>
        <div class="visual-orbit visual-orbit-two" aria-hidden="true"></div>
        <article class="session-preview">
            <div class="preview-topline"><span class="preview-mark" aria-hidden="true">C</span><span>SESSION PREVIEW</span><span class="preview-status"><i aria-hidden="true"></i> Ready when you are</span></div>
            <div class="preview-title-row"><div><p class="preview-kicker">A steady pace. A clear next step.</p><h2>Your next<br>study session.</h2></div><span class="preview-spark" aria-hidden="true">✳</span></div>
            <div class="preview-stat-row">
                <div class="preview-stat"><span>FULL EXAM</span><strong>100 <small>questions</small></strong></div>
                <div class="preview-stat"><span>ESL TIMER</span><strong>110 <small>minutes</small></strong></div>
            </div>
            <div class="preview-progress" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
            <div class="preview-bottom"><span class="preview-lock" aria-hidden="true">✓</span><span>Answers stay hidden until you submit.</span><span class="preview-arrow" aria-hidden="true">↗</span></div>
        </article>
        <div class="visual-chip"><span class="chip-icon" aria-hidden="true">✦</span><span><strong>One question at a time</strong><small>Your progress saves as you go</small></span></div>
        <div class="visual-stamp" aria-hidden="true"><span>CDMP</span><strong>STUDY<br>STUDIO</strong></div>
    </div>
</section>

<section class="study-options" id="study-options">
    <div class="section-heading options-heading">
        <div><p class="eyebrow">Find your rhythm</p><h2>Choose a way to practice.</h2></div>
        <p class="section-intro">Start with a full mock exam or fit a shorter session into your day.</p>
    </div>
    <div class="format-grid">
        <a class="format-card format-full" href="{{ route('exams.create') }}">
            <span class="format-icon format-icon-dark" aria-hidden="true">↗</span>
            <span class="format-tag">BUILD EXAM CONFIDENCE</span>
            <strong>Full exam</strong>
            <span class="format-description">A complete timed session with 100 questions and review after submission.</span>
            <span class="format-bottom"><span>ESL 110 min · Standard 90 min</span><b aria-hidden="true">→</b></span>
        </a>
        <a class="format-card format-practice" href="{{ route('exams.create') }}">
            <span class="format-icon format-icon-mint" aria-hidden="true">✳</span>
            <span class="format-tag">PRACTICE AT YOUR PACE</span>
            <strong>Practice session</strong>
            <span class="format-description">Choose 20 or 50 questions. Set a timer or take an untimed session.</span>
            <span class="format-bottom"><span>Flexible · 20 or 50 questions</span><b aria-hidden="true">→</b></span>
        </a>
        <aside class="source-note">
            <span class="source-note-icon" aria-hidden="true">i</span>
            <div><strong>Practice material, clearly labeled.</strong><p>Questions come from your supplied PDF. This simulator is not an official DAMA examination product, and answer keys have not all been independently verified.</p></div>
        </aside>
    </div>
</section>

<section class="dashboard-lower">
    <div class="section-heading">
        <div><p class="eyebrow">A little progress adds up</p><h2>Your study history.</h2></div>
        <a class="text-link" href="{{ route('history.index') }}">View all attempts <span aria-hidden="true">→</span></a>
    </div>

    @if ($latestCompleted)
        <article class="latest-result">
            <div class="latest-score-wrap"><span class="small-label">LATEST RESULT</span><div class="latest-score">{{ number_format((float) $latestCompleted->score_percent, 2) }}<span>%</span></div></div>
            <div class="latest-meta">
                <strong>{{ ucfirst($latestCompleted->mode) }} session · {{ $latestCompleted->total_questions }} questions</strong>
                <span>Completed {{ $latestCompleted->submitted_at?->timezone('Asia/Jakarta')->format('M j, Y · H:i') }}</span>
            </div>
            <a class="button button-secondary" href="{{ route('exams.result', $latestCompleted) }}">Review result <span aria-hidden="true">→</span></a>
        </article>
    @endif

    @if ($recentAttempts->isEmpty())
        <div class="empty-state history-empty"><span class="empty-illustration" aria-hidden="true">✳</span><strong>Your first session starts here.</strong><p>Take a practice run and your progress will show up here.</p><a class="text-link" href="{{ route('exams.create') }}">Choose a session <span aria-hidden="true">→</span></a></div>
    @else
        <div class="attempt-list">
            @foreach ($recentAttempts as $attempt)
                <article class="attempt-row">
                    <div class="attempt-date">{{ $attempt->started_at->timezone('Asia/Jakarta')->format('M j') }}<span>{{ $attempt->started_at->timezone('Asia/Jakarta')->format('Y') }}</span></div>
                    <div class="attempt-description">
                        <strong>{{ $attempt->mode === 'full' ? 'Full exam' : 'Practice session' }}</strong>
                        <span>{{ $attempt->total_questions }} questions · {{ $attempt->started_at->timezone('Asia/Jakarta')->format('H:i') }}</span>
                    </div>
                    <div class="attempt-status">
                        @if ($attempt->status === 'in_progress')
                            <span class="status-pill status-active">In progress</span>
                            <a href="{{ route('exams.show', $attempt) }}">Resume <span aria-hidden="true">→</span></a>
                        @else
                            <span class="status-pill {{ $attempt->status === 'expired' ? 'status-muted' : 'status-done' }}">{{ $attempt->status === 'expired' ? 'Timed out' : 'Completed' }}</span>
                            <a href="{{ route('exams.result', $attempt) }}">{{ number_format((float) $attempt->score_percent, 2) }}% · Review</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
