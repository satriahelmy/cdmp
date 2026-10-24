import './bootstrap';

const setupForm = document.querySelector('[data-setup-form]');
if (setupForm) {
    const eligible = Number(setupForm.dataset.eligible || 0);
    const modeRadios = [...setupForm.querySelectorAll('input[name="mode"]')];
    const panels = [...setupForm.querySelectorAll('[data-mode-panel]')];
    const startButton = setupForm.querySelector('[data-start-button]');
    const warning = setupForm.querySelector('[data-pool-warning]');
    const countSelect = setupForm.querySelector('[name="question_count"]');

    const updateSetup = () => {
        let selectedMode = setupForm.querySelector('input[name="mode"]:checked')?.value || 'full';
        const fullRadio = setupForm.querySelector('input[name="mode"][value="full"]');
        const practiceRadio = setupForm.querySelector('input[name="mode"][value="practice"]');
        fullRadio.disabled = eligible < 100;
        practiceRadio.disabled = eligible < 20;
        if (selectedMode === 'full' && fullRadio.disabled && !practiceRadio.disabled) {
            practiceRadio.checked = true;
            selectedMode = 'practice';
        }

        panels.forEach((panel) => {
            const active = panel.dataset.modePanel === selectedMode;
            panel.hidden = !active;
            panel.querySelectorAll('select, input').forEach((field) => { field.disabled = !active; });
        });

        const needed = selectedMode === 'full' ? 100 : Number(countSelect.value || 20);
        startButton.disabled = eligible < needed;
        if (eligible < needed) {
            warning.textContent = `This session needs ${needed} eligible questions, but only ${eligible} are available.`;
            warning.hidden = false;
        } else {
            warning.textContent = '';
            warning.hidden = true;
        }
    };

    modeRadios.forEach((radio) => radio.addEventListener('change', updateSetup));
    countSelect.addEventListener('change', updateSetup);
    updateSetup();
}

const examSession = document.querySelector('[data-exam-session]');
if (examSession) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const position = Number(examSession.dataset.position);
    const answerInputs = [...examSession.querySelectorAll('input[name="selected_option_source_index"]')];
    const flagButton = examSession.querySelector('[data-flag-toggle]');
    const clearButton = examSession.querySelector('[data-clear-answer]');
    const status = examSession.querySelector('[data-save-status]');
    const retryButton = examSession.querySelector('[data-retry-save]');
    const answeredCountEl = examSession.querySelector('[data-answered-count]');
    const unansweredCountEl = examSession.querySelector('[data-unanswered-count]');
    const flaggedCountEl = examSession.querySelector('[data-flagged-count]');
    const dialog = document.querySelector('[data-submit-dialog]');
    const submitForm = document.querySelector('[data-submit-form]');
    const total = Number(examSession.dataset.total);
    let answeredCount = Number(answeredCountEl.textContent || 0);
    let flaggedCount = Number(flaggedCountEl.textContent || 0);
    let wasAnswered = answerInputs.some((input) => input.checked);
    let wasFlagged = flagButton.getAttribute('aria-pressed') === 'true';
    let saving = false;
    let dirty = false;
    let failed = false;
    let savePromise = Promise.resolve(true);

    const selectedValue = () => answerInputs.find((input) => input.checked)?.value ?? null;
    const setStatus = (text, state = '') => {
        status.textContent = text;
        status.classList.toggle('is-saving', state === 'saving');
        status.classList.toggle('is-error', state === 'error');
    };

    const updateLocalProgress = () => {
        const selected = selectedValue() !== null;
        const flagged = flagButton.getAttribute('aria-pressed') === 'true';
        if (selected !== wasAnswered) {
            answeredCount += selected ? 1 : -1;
            wasAnswered = selected;
        }
        if (flagged !== wasFlagged) {
            flaggedCount += flagged ? 1 : -1;
            wasFlagged = flagged;
        }
        answeredCount = Math.max(0, Math.min(total, answeredCount));
        flaggedCount = Math.max(0, Math.min(total, flaggedCount));
        answeredCountEl.textContent = String(answeredCount);
        unansweredCountEl.textContent = String(total - answeredCount);
        flaggedCountEl.textContent = String(flaggedCount);
        document.querySelector('[data-dialog-answered]').textContent = String(answeredCount);
        document.querySelector('[data-dialog-open]').textContent = String(total - answeredCount);
        document.querySelector('[data-dialog-flagged]').textContent = String(flaggedCount);

        const tile = examSession.querySelector(`[data-palette-item][data-position="${position}"]`);
        if (tile) {
            tile.dataset.answered = String(selected);
            tile.dataset.flagged = String(flagged);
            tile.classList.toggle('is-answered', selected);
            tile.classList.toggle('is-flagged', flagged);
            tile.setAttribute('aria-label', `Question ${position}${selected ? ', answered' : ', unanswered'}${flagged ? ', flagged' : ''}, current`);
            const mark = tile.querySelector('.palette-flag');
            if (flagged && !mark) {
                const flag = document.createElement('span');
                flag.className = 'palette-flag';
                flag.setAttribute('aria-hidden', 'true');
                flag.textContent = '⚑';
                tile.append(flag);
            } else if (!flagged && mark) {
                mark.remove();
            }
        }
        clearButton.classList.toggle('disabled', !selected);
        answerInputs.forEach((input) => input.closest('.answer-option')?.classList.toggle('is-selected', input.checked));
    };

    const postSave = async () => {
        const url = examSession.dataset.saveUrl.replace('__POSITION__', String(position));
        const payload = {
            selected_option_source_index: selectedValue() === null ? null : Number(selectedValue()),
            is_flagged: flagButton.getAttribute('aria-pressed') === 'true',
        };
        const response = await fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (response.status === 410 || response.status === 409) {
            window.location.assign(data.redirect_url || examSession.dataset.resultUrl);
            return false;
        }
        if (!response.ok) throw new Error(data.message || 'Could not save your changes.');

        if (data.server_now) {
            serverOffset = Date.parse(data.server_now) - Date.now();
            updateTimer();
        }
        setStatus('Saved');
        failed = false;
        retryButton.hidden = true;
        return true;
    };

    const queueSave = () => {
        dirty = true;
        failed = false;
        retryButton.hidden = true;
        setStatus('Saving…', 'saving');
        if (saving) return savePromise;

        saving = true;
        savePromise = (async () => {
            try {
                while (dirty) {
                    dirty = false;
                    await postSave();
                }
                return !failed;
            } catch (error) {
                dirty = false;
                failed = true;
                setStatus('Couldn’t save. Your choice is still here.', 'error');
                retryButton.hidden = false;
                return false;
            } finally {
                saving = false;
            }
        })();
        return savePromise;
    };

    const flushSave = async () => {
        if (dirty && !saving) return queueSave();
        if (saving) {
            const result = await savePromise;
            if (dirty) return flushSave();
            return result;
        }
        return !failed;
    };

    answerInputs.forEach((input) => input.addEventListener('change', () => {
        updateLocalProgress();
        queueSave();
    }));
    flagButton.addEventListener('click', () => {
        const flagged = flagButton.getAttribute('aria-pressed') === 'true';
        flagButton.setAttribute('aria-pressed', String(!flagged));
        flagButton.classList.toggle('is-flagged', !flagged);
        flagButton.querySelector('[data-flag-label]').textContent = flagged ? 'Flag for review' : 'Flagged for review';
        updateLocalProgress();
        queueSave();
    });
    clearButton.addEventListener('click', () => {
        answerInputs.forEach((input) => { input.checked = false; });
        updateLocalProgress();
        queueSave();
    });
    retryButton.addEventListener('click', () => queueSave());

    examSession.querySelectorAll('[data-navigate], [data-palette-item]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            if (await flushSave()) window.location.assign(link.href);
        });
    });

    const openSubmit = async () => {
        if (!(await flushSave())) return;
        dialog?.showModal();
    };
    examSession.querySelectorAll('[data-open-submit]').forEach((button) => button.addEventListener('click', openSubmit));
    document.querySelector('[data-close-submit]')?.addEventListener('click', () => dialog?.close());
    submitForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!(await flushSave())) return;
        submitForm.submit();
    });

    const navigator = document.querySelector('.navigator-panel');
    const navigatorToggle = document.querySelector('[data-navigator-toggle]');
    navigatorToggle?.addEventListener('click', () => {
        const collapsed = navigator.classList.toggle('is-collapsed');
        navigatorToggle.setAttribute('aria-expanded', String(!collapsed));
        navigatorToggle.textContent = collapsed ? 'Show' : 'Hide';
    });

    const timer = document.querySelector('[data-timer]');
    const timerBlock = document.querySelector('[data-timer-block]');
    const timerWarning = document.querySelector('[data-timer-warning]');
    const deadline = Date.parse(examSession.dataset.expiresAt || '');
    let serverOffset = Date.parse(examSession.dataset.serverNow || '') - Date.now();
    let timeoutSent = false;
    const formatTime = (seconds) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainder = seconds % 60;
        return hours > 0
            ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
            : `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
    };
    const updateTimer = () => {
        if (!Number.isFinite(deadline) || !timer) return;
        const seconds = Math.max(0, Math.ceil((deadline - (Date.now() + serverOffset)) / 1000));
        timer.textContent = formatTime(seconds);
        timerBlock.classList.toggle('is-warning', seconds < 600 && seconds >= 60);
        timerBlock.classList.toggle('is-urgent', seconds < 60);
        timerWarning.hidden = seconds >= 600;
        timerWarning.textContent = seconds < 60 ? 'Under 1 minute remaining' : 'Under 10 minutes remaining';
        if (seconds === 0 && !timeoutSent) {
            timeoutSent = true;
            flushSave().finally(() => submitForm?.submit());
        }
    };
    updateTimer();
    if (Number.isFinite(deadline)) window.setInterval(updateTimer, 1000);
}

const reviewFilters = document.querySelectorAll('[data-review-filter]');
if (reviewFilters.length) {
    const items = [...document.querySelectorAll('[data-review-item]')];
    reviewFilters.forEach((filter) => filter.addEventListener('click', () => {
        reviewFilters.forEach((button) => {
            const active = button === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        const value = filter.dataset.reviewFilter;
        items.forEach((item) => {
            item.hidden = value === 'all'
                ? false
                : value === 'flagged'
                    ? item.dataset.flagged !== 'true'
                    : item.dataset.outcome !== value;
        });
    }));
}
