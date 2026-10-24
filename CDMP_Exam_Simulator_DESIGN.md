# DESIGN.md — CDMP Exam Simulator

**Version:** 1.0 · **Status:** Implementation-ready UI/UX specification  
**Companion:** `CDMP_Exam_Simulator_PRD.md`  
**Stack:** Laravel Blade + Tailwind CSS + Alpine.js · **Audience:** single private learner

## 1. Design intent
Build a focused, quiet examination environment—not a generic admin dashboard. The product should feel like a carefully typeset learning tool: confident hierarchy, generous whitespace, fast navigation, and minimal distractions during a 90–110 minute exam. Prioritize reading comfort and predictable interactions over decorative effects.

**Personality:** editorial, calm, precise, professional.  
**Avoid:** bright gradient hero panels, glassmorphism, decorative blobs, large KPI-card grids, unnecessary charts, excessive shadows, animated backgrounds, and icon-heavy navigation.

## 2. Design system

### Color tokens
Use CSS variables or Tailwind theme tokens so colors can change without rewriting components.

| Token | Suggested hex | Usage |
|---|---|---|
| `--canvas` | `#F8F9F7` | App background |
| `--surface` | `#FFFFFF` | Reading surfaces |
| `--ink` | `#182126` | Primary text |
| `--muted` | `#66737A` | Secondary text |
| `--line` | `#E3E8E7` | Dividers and borders |
| `--accent` | `#225C63` | Primary actions, selected answer |
| `--accent-soft` | `#EAF3F2` | Selected states |
| `--success` | `#24734D` | Correct after submission only |
| `--danger` | `#B44942` | Incorrect/error after submission |
| `--warning` | `#936820` | Unanswered/time warnings |

Ensure accessible contrast; do not rely on color alone to convey status. Keep the interface predominantly neutral, using accent sparingly.

### Typography
- Preferred: **Inter** or system sans for application UI; optional **Source Serif 4** for question text if it improves reading comfort. Avoid requiring external fonts to render basic UI.
- Question: 21–24px desktop, 18–20px mobile, line-height 1.5–1.65, max-width ~760px.
- Body/options: 15–17px, line-height 1.5.
- Small metadata: 12–13px, muted, never substitute for important instructions.
- Tabular numerals for timer, score, question index.

### Layout and spacing
- Content container: max-width 1240px; exam reading column 720–800px.
- Spacing scale: 4/8/12/16/24/32/48/64px.
- Border radius: 8–12px for controls; avoid excessive rounded-pill cards.
- Prefer fine borders over shadows. Use visible focus rings.
- Sticky elements must not obstruct question text on small screens.

## 3. Information architecture

```text
Dashboard (/)
├── New Exam (/exams/create)
│   └── Active Exam (/exams/{id})
│       └── Results (/exams/{id}/result)
│           └── Question Review (within results)
└── History (/history)
    ├── Resume in-progress attempt
    └── Open completed results
```

No admin interface, upload page, public account registration, or social features in MVP.

## 4. Screen specifications

### A. Dashboard
**Purpose:** start or resume studying in under 10 seconds.

**Desktop structure:** slim top bar (wordmark left, History right); main intro with heading “Prepare with focus.”; primary **Start exam** action; secondary **Resume exam** only if an active attempt exists; compact latest-attempt summary and a short history list. Do not create an analytics dashboard.

**Content:**
- “CDMP Exam Simulator” product identity.
- Supporting text: “Practice questions sourced from your PDF. Not an official DAMA exam.”
- Most recent score and date when available.
- In-progress attempt with remaining time, if available.
- Link to all attempts.

**Empty state:** “No attempts yet. Start your first practice exam.”

### B. Exam setup
**Purpose:** make the exam rules unmistakable before starting.

**Controls:**
- Mode: **Full exam (100 questions)** / **Practice (20 or 50 questions)**.
- Full exam duration: **ESL 110 minutes (default)** / **Standard 90 minutes**.
- Practice duration: timed or untimed (specific practice timing is configurable per PRD).
- Summary: number of questions, deadline behavior, ability to revisit/flag, answers reviewed only after submission.
- Primary CTA: **Begin exam**.

**Validation:** disable start and show a plain-language error if the eligible question pool is smaller than the requested count. Do not promise official DAMA domain weighting when source questions lack reliable domain tags.

### C. Exam session (highest priority)
**Desktop composition:**

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ CDMP Studio           Data Management Fundamentals          01:23:45 left │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Question 12 / 100                                     Flag for review      │
│  ────────────────────────────────────────────────────────────────────────   │
│  [Question text in large, comfortable reading width]                       │
│                                                                             │
│  ( ) A. Option text                                                         │
│  ( ) B. Option text                                                         │
│  (●) C. Option text                                                         │
│  ( ) D. Option text                                                         │
│  ( ) E. Option text                                                         │
│                                                                             │
│  Previous                                               Save & Next         │
│                                                                             │
│                                                  ┌─────────────────────┐    │
│                                                  │ Question navigator  │    │
│                                                  │ 01 02 03 04 05 ...  │    │
│                                                  │ Answered  / Flagged │    │
│                                                  │ Finish exam         │    │
│                                                  └─────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────────┘
```

Implementation can use a 2-column layout with a right-side 280px navigator and a flexible reading column. At narrower widths, navigator becomes a toggleable panel/drawer. Do not let the navigator squeeze options below a readable width.

**Question display:**
- Visible progress: “Question 12 of 100”; do not display original PDF question number during the exam unless helpful to debugging.
- Single-choice options with **radio semantics**, each a full-width clickable label with a letter marker.
- Selected state: subtle accent border + tinted background + clear radio check.
- Do not reveal correctness, explanation, or answer key before submission.
- Flag button with distinct text/icon state: “Flag for review” / “Flagged”.
- Previous / Next controls; on final question show “Review & submit”, not immediate destructive submit.
- Palette tiles: current (outlined), answered (filled dot), flagged (corner mark), unanswered (neutral). States must be distinguishable without color.
- Counters: Answered, Unanswered, Flagged.

**Timer:**
- Header remains visible; time uses `mm:ss` or `hh:mm:ss` and tabular numerals.
- Under 10 minutes: restrained warning; under 1 minute: strong warning, no flashing.
- Browser clock is visual only. Laravel's `expires_at` controls expiration and auto-finalization.
- Untimed practice displays “Untimed practice” instead of a fake countdown.

**Autosave feedback:**
- On answer/flag change: small “Saving…” then “Saved” status near navigation.
- If request fails: “Couldn’t save. Retry” with working retry action; do not silently navigate away and lose unsaved state.
- On refresh, resume at the saved question or first unanswered question; answer/flag state restored from server.
- Disable conflicting submit actions while save is pending; ensure latest response is persisted before final submit.

**Submission:**
- Clicking Finish opens a confirmation dialog: “Submit this exam?” with answered/unanswered/flagged counts and irreversible consequence.
- Buttons: “Continue reviewing” and “Submit exam”.
- On timeout, server finalizes and UI transitions to result with an explicit timeout message.

### D. Results
**Purpose:** clear, credible feedback without overstating certification readiness.

**Top section:** score as prominent typography (e.g., “76 / 100 · 76%”), attempt type, time spent, submitted/expired status. Below: correct, incorrect, unanswered. Small reference scale for 60%, 70%, 80% labeled “Study reference thresholds,” not certification outcome.

**Review list:**
- Filter chips/tabs: All / Incorrect / Unanswered / Correct / Flagged.
- Each item shows question, selected option, correct option, explanation when present, and source reference (PDF page + original question number).
- If explanation is absent, display “No explanation provided in source PDF.” Never invent one.
- Explicit provenance note: “Answers follow the supplied practice PDF and have not all been independently verified against DAMA-DMBOK.”
- Actions: Back to dashboard, Start another exam, View history.

**No premature answer exposure:** results data is requested only for completed/expired attempts.

### E. History
Simple chronological list/table with date, mode, score, completion status, and action (Resume / View results). Use readable rows rather than a dense dashboard. Show active attempts prominently.

## 5. Interaction and accessibility
- Use native buttons and radio inputs, not click-only `div`s.
- Every control reachable by keyboard, visible `:focus-visible` outline.
- Arrow keys may navigate radio choices by native browser behavior; do not override standard keyboard semantics.
- Shortcuts (optional later): number keys 1–5 to select choices, left/right to navigate, F to flag. Do not ship shortcuts without on-screen guidance and safeguards around text inputs.
- Provide ARIA live status for save state and timer warning, but do not announce every second.
- Respect `prefers-reduced-motion`; animations <=150ms and only for meaningful state changes.
- Touch targets >=44px, including palette cells.
- Modal must trap focus and return focus to trigger when closed.

## 6. Responsive rules
- **>=1024px:** 2-column exam layout, persistent navigator sidebar.
- **768–1023px:** reading area takes priority; navigator collapsible.
- **<768px:** single-column question/options; timer and progress remain visible; navigation palette opens via button; Previous/Next controls reachable without horizontal scroll.
- Long option text wraps naturally; no truncation.

## 7. Loading, empty, and error states
| Situation | Behavior |
|---|---|
| Initial loading | Stable skeleton for question content, no flashing spinners |
| No active attempts | Friendly empty state + Start exam |
| Insufficient eligible questions | Explain requested vs available count; disable Begin |
| Save request failed | Persistent retryable message; retain selection in local UI |
| Exam already submitted | Redirect to read-only results |
| Timer expired | Server grades once; redirect to results |
| Invalid/removed question | Prevent attempt creation and show a clear recoverable error |
| Explanation missing | State source has no explanation |

## 8. Content style and copy
- UI labels in **English** to match the original PDF and CDMP preparation context. (Can be localized later.)
- Keep source question text/options/explanations **verbatim** except safe whitespace normalization at import time.
- Avoid certification claims such as “You passed CDMP.” Use “Mock exam score” and “Study reference”.
- Use concise, action-oriented messages; never shame the learner for wrong answers.

## 9. Blade component map (suggested)
```text
resources/views/
├── layouts/app.blade.php
├── components/
│   ├── app-header.blade.php
│   ├── primary-button.blade.php
│   ├── status-label.blade.php
│   ├── question-option.blade.php
│   ├── question-palette.blade.php
│   ├── exam-timer.blade.php
│   └── confirm-submit.blade.php
├── dashboard.blade.php
├── exams/create.blade.php
├── exams/show.blade.php
├── exams/result.blade.php
└── history/index.blade.php
```
Alpine.js may manage the local exam view state and asynchronous save interactions; Laravel remains the source of truth for answers, time, grading, and eligibility.

## 10. Definition of design done
- [ ] Dashboard, setup, exam, results/review, and history implemented consistently.
- [ ] A 100-question, 110-minute exam is comfortable to use on desktop and mobile.
- [ ] Answer/flag/save states visually clear without relying only on color.
- [ ] No keys or explanations exposed in active exam UI or network payload.
- [ ] Submit confirmation accurately shows answered/unanswered/flagged counts.
- [ ] Errors, timeout, refresh, and missing explanations handled gracefully.
- [ ] Results explicitly distinguish mock exam score from official certification.
- [ ] Design avoids generic dashboard patterns and distracting ornamentation.

## 11. Scope boundaries for Codex
Implement this design **against the companion PRD**. Do not introduce new admin features, AI functionality, public auth, PDF upload, or speculative domain charts. Preserve existing Laravel project conventions where they do not conflict with the PRD. Prefer reusable Blade components and simple Alpine.js state over a separate SPA framework.
