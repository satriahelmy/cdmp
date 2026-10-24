@extends('layouts.app')

@section('title', 'New exam · CDMP Exam Simulator')

@section('content')
<section class="page-heading compact-heading">
    <a class="back-link" href="{{ route('dashboard') }}">← Home</a>
    <p class="eyebrow">Before you begin</p>
    <h1>Choose your session.</h1>
    <p class="lead">The question order is set when your attempt starts. You can revisit and flag any question.</p>
</section>

<form class="setup-form" method="POST" action="{{ route('exams.store') }}" data-setup-form data-eligible="{{ $eligibleCount }}">
    @csrf
    <fieldset class="mode-choice">
        <legend class="small-label">Session type</legend>
        <label class="mode-option">
            <input type="radio" name="mode" value="full" checked>
            <span class="mode-option-copy"><strong>Full exam</strong><span>100 questions · timed</span></span>
            <span class="mode-radio" aria-hidden="true"></span>
        </label>
        <label class="mode-option">
            <input type="radio" name="mode" value="practice">
            <span class="mode-option-copy"><strong>Practice</strong><span>20 or 50 questions · flexible timer</span></span>
            <span class="mode-radio" aria-hidden="true"></span>
        </label>
    </fieldset>

    <div class="settings-panel" data-mode-panel="full">
        <label class="field-label" for="duration-preset">Full exam duration</label>
        <select id="duration-preset" name="duration_preset">
            <option value="esl">ESL · 110 minutes</option>
            <option value="standard">Standard · 90 minutes</option>
        </select>
        <p class="field-help">Your deadline is enforced by the server. The browser timer is only a display.</p>
    </div>

    <div class="settings-panel" data-mode-panel="practice" hidden>
        <div class="form-grid">
            <div>
                <label class="field-label" for="question-count">Question count</label>
                <select id="question-count" name="question_count" disabled>
                    <option value="20">20 questions</option>
                    <option value="50">50 questions</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="practice-duration">Practice timer</label>
                <select id="practice-duration" name="practice_duration_minutes" disabled>
                    <option value="">Untimed</option>
                    <option value="20">20 minutes</option>
                    <option value="30">30 minutes</option>
                    <option value="45">45 minutes</option>
                    <option value="60">60 minutes</option>
                    <option value="90">90 minutes</option>
                    <option value="110">110 minutes</option>
                </select>
            </div>
        </div>
        <p class="field-help">You can review answers after submitting. Correctness is hidden during the session.</p>
    </div>

    <div class="rules-note">
        <span class="rules-icon" aria-hidden="true">i</span>
        <p><strong>Answer review comes after submission.</strong> Scores are study references from the supplied practice PDF; answer keys have not all been independently verified against DAMA-DMBOK.</p>
    </div>

    <div class="setup-footer">
        <p class="pool-note"><strong>{{ $eligibleCount }}</strong> eligible questions available <span>·</span> 193 ready records were supplied</p>
        <p class="pool-warning" data-pool-warning hidden></p>
        <button class="button button-primary" type="submit" data-start-button>Begin session <span aria-hidden="true">→</span></button>
    </div>
</form>
@endsection
