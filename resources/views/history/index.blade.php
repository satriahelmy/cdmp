@extends('layouts.app')

@section('title', 'History · CDMP Exam Simulator')

@section('content')
<section class="page-heading history-heading">
    <a class="back-link" href="{{ route('dashboard') }}">← Home</a>
    <p class="eyebrow">Every attempt, in one place</p>
    <h1>Study history.</h1>
    <p class="lead">Open an active session to continue, or revisit a completed result.</p>
</section>

@if ($attempts->isEmpty())
    <div class="empty-state history-empty-panel">
        <span class="empty-illustration" aria-hidden="true">✳</span>
        <div class="history-empty-copy">
            <strong>Your first session starts here.</strong>
            <p>Take a practice run and your attempts will appear here.</p>
        </div>
        <a class="button button-primary" href="{{ route('exams.create') }}">Start an exam <span aria-hidden="true">→</span></a>
    </div>
@else
    <div class="history-table-wrap">
        <table class="history-table">
            <thead><tr><th>Started</th><th>Session</th><th>Questions</th><th>Result</th><th>Status</th><th aria-label="Action"></th></tr></thead>
            <tbody>
            @foreach ($attempts as $attempt)
                @php
                    $statusLabel = match ($attempt->status) {
                        'in_progress' => 'In progress',
                        'expired' => 'Timed out',
                        default => 'Completed',
                    };
                    $statusClass = match ($attempt->status) {
                        'in_progress' => 'status-active',
                        'expired' => 'status-muted',
                        default => 'status-done',
                    };
                    $actionLabel = $attempt->status === 'in_progress' ? 'Resume' : 'Review';
                    $actionUrl = $attempt->status === 'in_progress'
                        ? route('exams.show', $attempt)
                        : route('exams.result', $attempt);
                @endphp
                <tr>
                    <td>{{ $attempt->started_at->timezone('Asia/Jakarta')->format('M j, Y') }}<span>{{ $attempt->started_at->timezone('Asia/Jakarta')->format('H:i') }}</span></td>
                    <td>{{ $attempt->mode === 'full' ? 'Full exam' : 'Practice' }}</td>
                    <td>{{ $attempt->total_questions }}</td>
                    <td>{{ $attempt->score_percent !== null ? number_format((float) $attempt->score_percent, 2).'%' : '—' }}</td>
                    <td><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td class="history-action"><a href="{{ $actionUrl }}">{{ $actionLabel }} →</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="history-cards" aria-label="Study history">
        @foreach ($attempts as $attempt)
            @php
                $statusLabel = match ($attempt->status) {
                    'in_progress' => 'In progress',
                    'expired' => 'Timed out',
                    default => 'Completed',
                };
                $statusClass = match ($attempt->status) {
                    'in_progress' => 'status-active',
                    'expired' => 'status-muted',
                    default => 'status-done',
                };
                $actionLabel = $attempt->status === 'in_progress' ? 'Resume session' : 'Review result';
                $actionUrl = $attempt->status === 'in_progress'
                    ? route('exams.show', $attempt)
                    : route('exams.result', $attempt);
            @endphp
            <article class="history-card">
                <div class="history-card-heading">
                    <span>Started</span>
                    <strong>{{ $attempt->started_at->timezone('Asia/Jakarta')->format('M j, Y') }}</strong>
                    <time datetime="{{ $attempt->started_at->toIso8601String() }}">{{ $attempt->started_at->timezone('Asia/Jakarta')->format('H:i') }}</time>
                </div>
                <dl class="history-card-details">
                    <div><dt>Session</dt><dd>{{ $attempt->mode === 'full' ? 'Full exam' : 'Practice' }}</dd></div>
                    <div><dt>Questions</dt><dd>{{ $attempt->total_questions }}</dd></div>
                    <div><dt>Result</dt><dd>{{ $attempt->score_percent !== null ? number_format((float) $attempt->score_percent, 2).'%' : '—' }}</dd></div>
                    <div><dt>Status</dt><dd><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></dd></div>
                </dl>
                <a class="history-card-action" href="{{ $actionUrl }}">{{ $actionLabel }} <span aria-hidden="true">→</span></a>
            </article>
        @endforeach
    </div>
@endif
@endsection
