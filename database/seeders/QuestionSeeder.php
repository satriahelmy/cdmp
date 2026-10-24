<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Support\QuestionImportValidator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/questions_ready.json');
        $validated = app(QuestionImportValidator::class)->validateFile($path);
        $counts = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0];

        DB::transaction(function () use ($validated, &$counts): void {
            foreach ($validated['records'] as $record) {
                $question = Question::where('source_id', $record['id'])->first();
                $inserted = $question === null;
                $question ??= new Question(['source_id' => $record['id']]);

                $attributes = [
                    'original_number' => $record['original_number'] ?? null,
                    'text' => $record['question'],
                    'explanation' => $record['explanation'] ?? null,
                    'source_page' => $record['source_page'] ?? null,
                    'source_type' => $record['source_type'] ?? null,
                    'review_status' => $record['review_status'],
                    'needs_key_review' => $record['needs_key_review'] ?? false,
                    'key_verified_against_dmbok' => $record['key_verified_against_dmbok'] ?? false,
                    'is_active' => true,
                ];
                $question->fill($attributes);
                $questionChanged = $question->isDirty();
                $question->save();

                $existingOptions = $question->options()->get()->keyBy('source_index');
                $sourceIndexes = [];
                $optionsChanged = false;

                foreach ($record['options'] as $sourceIndex => $text) {
                    $sourceIndexes[] = $sourceIndex;
                    $option = $existingOptions->get($sourceIndex);
                    $option ??= new QuestionOption(['question_id' => $question->id, 'source_index' => $sourceIndex]);
                    $option->fill(['text' => $text, 'is_correct' => $sourceIndex === $record['correct_index']]);
                    if ($option->isDirty()) {
                        $optionsChanged = true;
                        $option->save();
                    }
                }

                if ($existingOptions->keys()->diff($sourceIndexes)->isNotEmpty()) {
                    $question->options()->whereNotIn('source_index', $sourceIndexes)->delete();
                    $optionsChanged = true;
                }

                if ($inserted) {
                    $counts['inserted']++;
                } elseif ($questionChanged || $optionsChanged) {
                    $counts['updated']++;
                } else {
                    $counts['unchanged']++;
                }
            }
        });

        $total = count($validated['records']) + $validated['skipped'];
        $this->command?->info("Question import complete: input {$total}, inserted {$counts['inserted']}, updated {$counts['updated']}, unchanged {$counts['unchanged']}, skipped {$validated['skipped']}.");
    }
}
