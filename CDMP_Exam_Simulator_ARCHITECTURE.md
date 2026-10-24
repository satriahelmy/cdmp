# ARCHITECTURE.md — CDMP Exam Simulator

**Version:** 1.0 · **Status:** Implementation specification  
**Companion documents:** `CDMP_Exam_Simulator_PRD.md`, `CDMP_Exam_Simulator_DESIGN.md`  
**Stack:** Laravel + MySQL + Blade + Tailwind CSS + Alpine.js  
**Scope:** private, single-user practice application; no runtime PDF parsing, no admin CRUD.

## 1. Architecture decisions

- MySQL is the application's operational database. JSON is an **import artifact only**.
- A standard Laravel `QuestionSeeder` reads `database/data/questions_ready.json` during development. **No custom Artisan import command**, upload screen, or runtime JSON reads.
- Questions, choices, and scoring keys live server-side. Active-exam responses must never include keys or correctness flags.
- An exam attempt **snapshots** question text, option text/order, correct option identity, explanation, and source metadata at creation. Re-importing the bank cannot alter existing attempt scores or reviews.
- Use server-authoritative expiration and scoring. Alpine.js manages lightweight interaction and timer display, not business rules.
- Practice answers are revealed only after submission in MVP.

## 2. High-level data flow

```text
PDF (offline source)
  -> extraction / manual QA (outside Laravel)
  -> database/data/questions_ready.json
  -> QuestionSeeder (development-time, validated + idempotent)
  -> MySQL: questions, question_options

Browser (Blade + Alpine.js)
  -> web routes + CSRF + optional single-user authentication
  -> ExamController / AttemptController
  -> ExamService (transactional lifecycle + server timer + grading)
  -> MySQL: exam_attempts, exam_attempt_questions, exam_answers
  -> Blade results / read-only review
```

## 3. Source JSON contract (verified against supplied `questions_ready.json`)

The provided JSON is a **top-level array of 193 records**. Each record has fields such as:

```json
{
  "id": "cdmp-188-1",
  "original_number": 188,
  "question": "Which of the following is NOT a primary Master Data Management area of focus?",
  "options": [
    "Generating a golden record/best version of the truth",
    "Producing clear data definitions for Master Data",
    "Producing read only versions of key data items",
    "Identifying duplicate records",
    "Providing access to golden data records"
  ],
  "correct_index": 2,
  "correct_answer": "Producing read only versions of key data items",
  "explanation": "Producing read only versions of key data items",
  "source_page": 1,
  "needs_key_review": false,
  "review_status": "ready_source_key",
  "key_verified_against_dmbok": false,
  "source_type": "Google Forms answer review PDF",
  "answer_variants": []
}
```

**Important:** `correct_index` is **zero-based** and references the original JSON `options` array; the ID is a **string**, not an integer. `correct_answer` is redundant human-readable source data: verify that it matches `options[correct_index]` (after safe whitespace normalization) rather than using it to guess a new key. Preserve original wording. `answer_variants` is extraction metadata, not additional scored answers.

Seeder acceptance rules:

1. JSON parses to a nonempty array; `id` is nonblank and unique within file.
2. `question` is nonblank; `options` contains at least two nonblank strings; `correct_index` is an integer in `[0, count(options)-1]`.
3. Only `review_status = ready_source_key` and `needs_key_review = false` are eligible; never seed the review queue as active questions.
4. Validate optional fields by type, preserve nullable explanations and source references. Flag mismatches between `correct_answer` and the indexed option rather than silently correcting them.
5. On validation failure, report row/source ID and abort the import transaction. Do not partially import a malformed file.
6. Re-run safely using `source_id` as the stable unique key. Upsert the question and synchronize its options by `(question_id, source_index)`; remove obsolete options only inside a transaction, after verifying no historical dependency (snapshots must remain self-contained).
7. Seeder summary: input, inserted, updated, unchanged, rejected. Never treat source answer consistency as independent DAMA-DMBOK verification.

## 4. Database schema

### `questions`
| Column | Type / rule |
|---|---|
| `id` | BIGINT PK |
| `source_id` | VARCHAR(100) UNIQUE; e.g. `cdmp-188-1` |
| `original_number` | INT NULL |
| `text` | LONGTEXT NOT NULL |
| `explanation` | LONGTEXT NULL |
| `source_page` | INT NULL |
| `source_type` | VARCHAR(255) NULL |
| `review_status` | VARCHAR(50) NOT NULL |
| `needs_key_review` | BOOLEAN DEFAULT false |
| `key_verified_against_dmbok` | BOOLEAN DEFAULT false |
| `is_active` | BOOLEAN DEFAULT true |
| `created_at`, `updated_at` | TIMESTAMPS |

### `question_options`
| Column | Type / rule |
|---|---|
| `id` | BIGINT PK |
| `question_id` | FK -> questions, cascade on delete for bank data |
| `source_index` | SMALLINT UNSIGNED (0-based) |
| `text` | TEXT NOT NULL |
| `is_correct` | BOOLEAN NOT NULL |
| `created_at`, `updated_at` | TIMESTAMPS |

Unique `(question_id, source_index)`; index `(question_id, is_correct)`. Enforce exactly one correct option per question in import/service validation.

### `exam_attempts`
| Column | Type / rule |
|---|---|
| `id` | BIGINT PK |
| `mode` | ENUM/string: `full`, `practice` |
| `duration_minutes` | SMALLINT NULL; null means untimed practice |
| `started_at` | DATETIME NOT NULL (UTC) |
| `expires_at` | DATETIME NULL (UTC) |
| `submitted_at` | DATETIME NULL (UTC) |
| `status` | ENUM/string: `in_progress`, `submitted`, `expired` |
| `total_questions` | SMALLINT NOT NULL |
| `correct_count` | SMALLINT NULL |
| `incorrect_count` | SMALLINT NULL |
| `unanswered_count` | SMALLINT NULL |
| `score_percent` | DECIMAL(5,2) NULL |
| `created_at`, `updated_at` | TIMESTAMPS |

### `exam_attempt_questions`
| Column | Type / rule |
|---|---|
| `id` | BIGINT PK |
| `exam_attempt_id` | FK -> exam_attempts, cascade on delete |
| `question_id` | FK -> questions, nullable + `nullOnDelete` for provenance |
| `position` | SMALLINT UNSIGNED (1-based display order) |
| `question_snapshot` | JSON: text, explanation, original_number, source_page, source_id, source_type |
| `options_snapshot` | JSON array of `{source_index, text}` in frozen display order |
| `correct_option_source_index` | SMALLINT UNSIGNED, **server-only** |
| `created_at`, `updated_at` | TIMESTAMPS |

Unique `(exam_attempt_id, position)` and `(exam_attempt_id, question_id)` for non-null IDs. Snapshots remain readable if question bank entries are later edited or deleted.

### `exam_answers`
| Column | Type / rule |
|---|---|
| `id` | BIGINT PK |
| `exam_attempt_question_id` | FK -> exam_attempt_questions UNIQUE, cascade on delete |
| `selected_option_source_index` | SMALLINT UNSIGNED NULL |
| `is_flagged` | BOOLEAN DEFAULT false |
| `answered_at` | DATETIME NULL |
| `created_at`, `updated_at` | TIMESTAMPS |

**No users table** if truly localhost-only. If hosted on a public network, add authentication **before deployment** and scope attempts by authenticated owner. Do not expose an unauthenticated remote simulator with personal history.

## 5. Application modules and files

```text
app/
  Http/Controllers/
    DashboardController.php
    ExamController.php
    ExamAnswerController.php
    ExamResultController.php
    HistoryController.php
  Http/Requests/
    StartExamRequest.php
    SaveExamAnswerRequest.php
  Models/
    Question.php
    QuestionOption.php
    ExamAttempt.php
    ExamAttemptQuestion.php
    ExamAnswer.php
  Services/
    ExamService.php
  Support/
    QuestionImportValidator.php

database/
  data/questions_ready.json
  migrations/*_create_questions_table.php
  migrations/*_create_question_options_table.php
  migrations/*_create_exam_attempts_table.php
  migrations/*_create_exam_attempt_questions_table.php
  migrations/*_create_exam_answers_table.php
  seeders/QuestionSeeder.php

resources/views/
  layouts/app.blade.php
  dashboard.blade.php
  exams/create.blade.php
  exams/show.blade.php
  exams/result.blade.php
  history/index.blade.php

routes/web.php
tests/Feature/{QuestionSeederTest,ExamLifecycleTest,ExamSecurityTest}.php
```

Names may be adjusted to Laravel conventions, but maintain the service boundaries.

## 6. Routes and HTTP contracts

| Method | Path | Action | Notes |
|---|---|---|---|
| GET | `/` | dashboard | Recent attempt + resume |
| GET | `/exams/create` | setup | Pool count + available modes |
| POST | `/exams` | start | `mode`, `question_count` for practice, `duration_preset` for full, practice timer option |
| GET | `/exams/{attempt}` | show/resume | Render only safe question/option data; reconcile expiration first |
| PUT | `/exams/{attempt}/questions/{position}` | save | `selected_option_source_index` nullable; `is_flagged` boolean; JSON response with saved state |
| POST | `/exams/{attempt}/submit` | submit | Transactional; idempotent; redirect to results |
| GET | `/exams/{attempt}/result` | result | Read-only; unavailable while active |
| GET | `/history` | history | In-progress + completed |

Use Laravel named routes, CSRF middleware, Form Requests, and route model binding. JSON autosave responses should use correct status codes: 200 saved, 422 invalid, 409 already finalized, and 410 expired (after server finalizes). No correctness fields in any active-exam payload, including inline HTML, Alpine state, or API responses.

## 7. Exam lifecycle

### Start
1. Validate mode/count: `full` = 100; `practice` = 20 or 50. Full timer presets: 110 ESL / 90 standard. Untimed practice has `expires_at = null`.
2. Query active, source-ready, non-review questions that each have exactly one correct option. If eligible pool is too small, return validation error.
3. Sample **distinct** questions; randomize order. Option display order may be shuffled, but retain original `source_index` values.
4. Within a database transaction create attempt, snapshot each selected question + options + correct source index, and create empty answer rows. Set UTC `started_at`, `expires_at` server-side.
5. Redirect to the first question.

### Save answer / flag
1. Find attempt + position, validate selected index belongs to that question's **snapshot**; `null` clears an answer.
2. Lock attempt row in a transaction; check status and current **server time**. If expired, finalize and reject edit.
3. Update answer and flag; set `answered_at` when selected (clear when unanswered); respond with saved state and server deadline.
4. UI should show saving/saved/error and allow retry. Never claim saved before server confirms.

### Submit / expiration
1. Lock attempt row; if already finalized, return existing result unchanged.
2. If deadline passed, finalize as `expired`; otherwise manual submit sets `submitted`.
3. Compare each stored answer's `selected_option_source_index` with snapshot `correct_option_source_index` (not current question-bank keys).
4. Calculate correct, incorrect, unanswered, and `round(correct / total_questions * 100, 2)`; persist counts, `submitted_at`, status, and score in the **same transaction**.
5. Render result using snapshots only. Flag state is retained for review.

**Expiration strategy:** reconcile expiration on GET show, PUT save, POST submit, dashboard/history reads, and result access as needed. A browser countdown reaching zero triggers a submit request; server-side reconciliation remains authoritative if the tab is closed. Optional scheduler is unnecessary for MVP unless background finalization is explicitly required.

**Race safety:** use `DB::transaction()` + `lockForUpdate()` on the attempt for save/finalize; make submit idempotent. For overlapping autosave requests, serialize updates and prefer client-side request sequencing so older requests do not overwrite newer choices; optionally add monotonic `answer_revision` for robust multi-tab handling.

## 8. Frontend boundaries

- Blade renders current question text, safe options in display order, selected option index, flag, palette status, `expires_at`/server timestamp, and CSRF token.
- Alpine.js manages local selection, loading state, navigation, keyboard focus, and countdown based on server deadline. Use a server-time offset if device clocks differ.
- Auto-save on answer change and flag toggle. Disable navigation until save acknowledgment **or** queue pending writes and visibly report unsaved state.
- Provide explicit submit confirmation with unanswered count. Prevent accidental double-submit.
- On expired response, disable inputs and navigate to result.
- Review shows correct/incorrect/selected, explanation only when supplied, and source-page metadata. Avoid invented domain tags.
- Follow `DESIGN.md` for spacing, color tokens, responsive palette, focus rings, and minimal visual treatment.

## 9. Security, privacy, and integrity

- Do not publish JSON question bank under `public/` or `resources/` served as static files; keep it under `database/data/` and never expose it through an asset build.
- Restrict database credentials to `.env`; commit `.env.example` only.
- Ensure `APP_DEBUG=false` in any deployed environment.
- Never send `is_correct`, `correct_index`, or snapshot key to an active-exam page or browser response. Avoid serializing full Eloquent models directly.
- Validate attempt state and selected option against attempt snapshot on every write; ignore client-provided scores or deadlines.
- If accessed outside localhost, require a single-user login and authorization on all attempt routes.
- Use CSRF, output escaping (Blade `{{ }}`), and rate limiting on writes if remotely deployed.

## 10. Seeder implementation guidance

A minimal implementation pattern:

```php
$path = database_path('data/questions_ready.json');
$records = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

// Validate ALL records and duplicate IDs before opening the transaction.
// Reject invalid correct_index or mismatch with correct_answer.
DB::transaction(function () use ($records) {
    foreach ($records as $item) {
        $question = Question::updateOrCreate(
            ['source_id' => $item['id']],
            [
                'original_number' => $item['original_number'] ?? null,
                'text' => $item['question'],
                'explanation' => $item['explanation'] ?? null,
                'source_page' => $item['source_page'] ?? null,
                'source_type' => $item['source_type'] ?? null,
                'review_status' => $item['review_status'],
                'needs_key_review' => false,
                'key_verified_against_dmbok' => $item['key_verified_against_dmbok'] ?? false,
                'is_active' => true,
            ]
        );
        foreach ($item['options'] as $index => $optionText) {
            $question->options()->updateOrCreate(
                ['source_index' => $index],
                ['text' => $optionText, 'is_correct' => $index === $item['correct_index']]
            );
        }
        // Remove obsolete bank options if present, inside this transaction.
    }
});
```

This is **illustrative**, not the complete seeder: implement validation, sync obsolete options, and counters. Standard development commands:

```bash
php artisan migrate
php artisan db:seed --class=QuestionSeeder
php artisan test
```

## 11. Automated tests / acceptance criteria

1. Seeder imports 193 active questions from the current supplied JSON; repeated seeding creates no duplicates.
2. Invalid JSON, repeated source IDs, invalid answer index, and mismatched answer text fail safely and do not partially write.
3. 100-question full exam creates 100 distinct snapshot rows; ESL 110 minutes and standard 90 minutes are server-set.
4. Practice supports 20/50; timed and untimed behavior is correct.
5. Option shuffle does not change scoring; selected source index is checked against snapshot.
6. Answer + flag persist after refresh; clearing an answer works.
7. Active-exam HTML/JSON does not contain answer keys or explanations.
8. Late autosave is rejected; expired attempt is finalized only once.
9. Duplicate submit requests return the same persisted result; counts sum to total questions.
10. Re-seeding changed question text/key leaves historical review and score unchanged.
11. Review-queue records never enter active scored exams.
12. Results/history are read-only; remote deployment requires authentication and authorization.

## 12. Delivery order for Codex

**Milestone A — Foundation:** create Laravel app, configure MySQL, migrations, Eloquent relationships, source JSON validation and idempotent seeder. Confirm imported counts and provenance.

**Milestone B — Exam engine:** `ExamService`, routes, form validation, snapshots, timer enforcement, autosave, grading, tests.

**Milestone C — UI:** implement Blade/Alpine screens per `DESIGN.md`, with focus on exam session usability and mobile navigation.

**Milestone D — QA:** run feature tests, inspect active-exam payload for key leakage, manually test expiration/refresh/reimport, and polish accessibility.

### Implementation handoff
Provide Codex these four inputs:

- `CDMP_Exam_Simulator_PRD.md`
- `CDMP_Exam_Simulator_DESIGN.md`
- `CDMP_Exam_Simulator_ARCHITECTURE.md`
- `questions_ready.json` (place at `database/data/questions_ready.json`)

**Do not silently expand scope.** Build MVP first, keep source keys unverified against DAMA-DMBOK unless independently audited, and prioritize correctness of question import and exam lifecycle over decoration.
