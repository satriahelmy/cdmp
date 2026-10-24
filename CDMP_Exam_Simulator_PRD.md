# Product Requirements Document (PRD)
## CDMP Exam Simulator — Personal Laravel Web App

**Version:** 1.0 (MVP)  
**Status:** Ready for implementation  
**Primary user:** One person (private study)  
**Source material:** `Latihan Soal CDMP_.pdf` (474 pages) and extracted question-bank artifacts

## 1. Product summary
A private web-based multiple-choice examination simulator for CDMP preparation. Questions originate from the user's PDF, are imported once from JSON into MySQL during development, and are served by Laravel. The app supports timed full exams, shorter practice sessions, persistent answers, results, and review. It is **not** an official DAMA examination product.

## 2. Goals and non-goals
### Goals
- Start a 100-question timed mock exam from the available, eligible question bank.
- Offer 110-minute ESL and 90-minute standard presets.
- Support 20- or 50-question practice sessions with configurable timer or untimed mode.
- Allow navigating, answering, flagging, and revisiting questions without losing progress.
- Grade on the server and display scores, explanations, and question-source references after submission.
- Store exam attempts and responses in MySQL.
- Keep the question bank traceable to original PDF page and original question number.

### Out of scope (MVP)
- Public registration, payment, multi-user administration, social features.
- PDF upload and parsing from within the web app.
- Question authoring/admin CRUD UI.
- AI-generated questions, automatic correction of disputed keys, translation.
- Claims that mock exam questions or scores predict official CDMP outcomes.

## 3. Source and data constraints
The supplied extraction package reports **204 candidate records**, of which **193** have internally consistent PDF answer keys and **11** are held for review (9 conflicting keys, 2 provisional parsing recoveries). The 193 keys are **not independently verified against DAMA-DMBOK**. These counts are based on the extraction report, not a fresh independent audit.

- Import `questions_ready.json` only into the active scored question bank.
- Preserve `questions_review_queue.json` outside active exams until manually resolved.
- Do not silently change original question text, option text, answer key, or explanation.
- Store `id`, `original_number`, `source_page`, `source_type`, `review_status`, `key_verified_against_dmbok`, and `needs_key_review` as provenance/quality fields.
- Preserve source language (English). Do not invent missing explanations or domain labels.
- If domains are later classified, mark them as inferred and distinguish them from PDF-supplied metadata.
- Import must detect repeated IDs and reject malformed records, invalid `correct_index`, or fewer than two options.

## 4. Users and main journey
Single private user, optionally protected by one local login or HTTP authentication if deployed on a network.

1. Open dashboard and choose **Full Exam** or **Practice**.
2. Choose duration preset, then start an attempt.
3. Answer one question at a time; jump via question palette; flag items for later.
4. Responses are saved automatically and survive refresh.
5. Submit manually or auto-submit at time expiration.
6. See score, time used, completion rate, and per-question review.
7. Reopen prior attempt from history (read-only).

## 5. Functional requirements
| ID | Requirement | Priority | Acceptance criteria |
|---|---|---|---|
| FR-01 | JSON-to-MySQL development import | Must | Laravel seeder reads `database/data/questions_ready.json`, validates records, upserts by stable source ID, and reports inserted/updated/skipped counts. Re-running is idempotent. |
| FR-02 | Exam setup | Must | Full exam uses 100 distinct eligible questions, 110-minute ESL default or 90-minute standard. Practice allows 20/50 questions. |
| FR-03 | Randomization | Must | Each attempt freezes selected question IDs and question order; option order may be randomized while preserving original option identity and correct-answer mapping. |
| FR-04 | Exam interface | Must | Displays question, choices, current index, remaining time, answered count, flag status, navigation palette. No answer keys exposed before submission. |
| FR-05 | Autosave | Must | Choosing/changing an answer or flag updates server state. Refresh resumes the same attempt. |
| FR-06 | Timer enforcement | Must | Server stores `started_at` and `expires_at`; rejects late answer changes and finalizes expired attempts. Browser timer is display only. |
| FR-07 | Submit and grade | Must | One-way submission; server computes correct/incorrect/unanswered and percentage from stored question keys. Repeat submit is idempotent. |
| FR-08 | Results and review | Must | Shows score, time spent, unanswered items, chosen answer, correct answer, original explanation, PDF page and original question number. |
| FR-09 | Attempt history | Must | Lists completed and in-progress attempts; completed attempts cannot be modified. |
| FR-10 | Question quality gate | Must | Questions flagged for review are excluded from scored sessions by default. |
| FR-11 | Domain analytics | Later | Only show domain breakdown when reliable domain tags have been added; no fabricated tags. |

## 6. Exam rules
- Each question has exactly one scored correct option in the initial dataset.
- Unanswered questions receive zero points; no negative marking.
- Score = `correct_count / total_questions * 100`, rounded to two decimals.
- Show 60%, 70%, and 80% as **study reference thresholds**, not an assertion of certification eligibility.
- User can revise answers until submission or expiration.
- Starting an attempt stores a fixed snapshot/order of question IDs and options; subsequent bank imports must not retroactively change scoring for an existing attempt. At minimum snapshot the question content, option order, and correct-option identity per attempt, or version the question bank and pin the version.
- If fewer than 100 eligible questions exist, do not start a full exam; display a clear error.
- No explanation or correctness feedback is visible during Full Exam.

## 7. Data model (proposed)
- `questions`: id, source_id (unique), original_number, text, explanation (nullable), source_page, source_type, review_status, key_verified_against_dmbok, is_active, timestamps.
- `question_options`: id, question_id, source_index, text, is_correct, timestamps; unique(question_id, source_index).
- `exam_attempts`: id, mode, duration_minutes, started_at, expires_at, submitted_at, status, total_questions, correct_count, score_percent, timestamps.
- `exam_attempt_questions`: id, attempt_id, question_id, position, question_snapshot_json, options_snapshot_json, correct_option_source_index; unique(attempt_id, position).
- `exam_answers`: id, attempt_question_id (unique), selected_option_source_index (nullable), is_flagged, answered_at, timestamps.

No `users` table is required if truly localhost-only. If hosted remotely, use single-user authentication and associate attempts with that user.

## 8. Architecture and implementation
**Backend:** Laravel (project-supported version), MySQL, Eloquent, validation, transactions.  
**Frontend:** Blade + Tailwind CSS + Alpine.js.  
**Question import:** `database/data/questions_ready.json` -> `QuestionSeeder` -> MySQL, run during development with `php artisan db:seed --class=QuestionSeeder`. This is not a runtime JSON reader or custom import command.  
**Backend services:** `ExamService` for creating attempts, selecting/snapshotting questions, answer persistence, expiration, and grading.

Suggested routes:
- `GET /` dashboard
- `GET /exams/create` setup
- `POST /exams` create attempt
- `GET /exams/{attempt}` take/resume
- `PUT /exams/{attempt}/questions/{position}` save answer/flag
- `POST /exams/{attempt}/submit` finalize
- `GET /exams/{attempt}/result` result/review
- `GET /history` attempt history

Use CSRF protection, server-side validation, rate limits where appropriate, and transactions for submit/finalization. Never return `is_correct` or `correct_option_source_index` to active-exam browser payloads.

## 9. UI direction
Restrained editorial learning interface: strong typography, generous whitespace, readable English question text, understated palette, no excessive cards or dashboard decoration. Desktop-first, usable on mobile. Key screens: dashboard, setup, examination, results, question review.

## 10. Non-functional requirements
- Responsive layout and keyboard-accessible choice/navigation controls.
- Page reload does not lose saved answers.
- Consistent scoring even after JSON re-import.
- No browser access to answer keys during active attempts.
- Efficient queries for a small bank (~193 active questions initially).
- Clear errors for failed autosave, expired sessions, and invalid import records.

## 11. MVP acceptance test scenarios
1. Seed 193 ready records; seed twice without duplicate questions/options.
2. Start 100-question ESL exam; verify 100 unique questions and 110-minute server deadline.
3. Start standard exam; verify 90-minute deadline.
4. Select answer, flag, navigate, refresh; verify state persisted.
5. Verify correct answer is absent from active-exam responses and page source.
6. Submit with some unanswered; verify denominator remains total selected questions.
7. Expire attempt; verify no late edits and grading is performed once.
8. Open result; verify original PDF page and explanation appear when available.
9. Re-import altered bank; verify completed attempt review/score remains unchanged.
10. Verify 11 review-queue questions are excluded from scored attempts.

## 12. Delivery phases
**Phase 1 — Data foundation:** inspect JSON schema; implement migrations/models/seeder and import validation.  
**Phase 2 — Exam engine:** selection, snapshots, timer, autosave, submit, grading.  
**Phase 3 — UI:** dashboard, setup, exam, results, review, history.  
**Phase 4 — QA:** automated feature tests, end-to-end manual testing, usability refinement.

## 13. Open decisions
- Hosting: localhost only or remotely accessible with single-user login?
- Should Practice mode show correctness immediately, or only after practice submission? **MVP default: after submission.**
- Domain classification should be a separate audited task after the exam engine works.

---
**Source integrity note:** The PDF is a third-party practice-question compilation. The application must label it as a practice simulator and must not present its answer keys as independently verified official DAMA answers.
