<?php

namespace App\Support;

use JsonException;
use RuntimeException;

class QuestionImportValidator
{
    /** @return array{records: array<int, array<string, mixed>>, skipped: int} */
    public function validateFile(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Question file is missing or unreadable: {$path}");
        }

        try {
            $records = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Question JSON is invalid: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($records) || $records === [] || ! array_is_list($records)) {
            throw new RuntimeException('Question JSON must contain a non-empty top-level array.');
        }

        $seenIds = [];
        $eligible = [];
        $skipped = 0;

        foreach ($records as $row => $record) {
            $label = 'Row '.($row + 1);
            if (! is_array($record)) {
                throw new RuntimeException("{$label} must be an object.");
            }

            $id = $record['id'] ?? null;
            if (! is_string($id) || trim($id) === '') {
                throw new RuntimeException("{$label} has a missing or blank id.");
            }
            $label = "Row ".($row + 1)." ({$id})";
            if (isset($seenIds[$id])) {
                throw new RuntimeException("{$label} repeats a source id.");
            }
            $seenIds[$id] = true;

            if (! is_string($record['question'] ?? null) || trim($record['question']) === '') {
                throw new RuntimeException("{$label} has a blank question.");
            }
            if (! is_array($record['options'] ?? null) || ! array_is_list($record['options']) || count($record['options']) < 2) {
                throw new RuntimeException("{$label} must have at least two options.");
            }
            foreach ($record['options'] as $optionIndex => $option) {
                if (! is_string($option) || trim($option) === '') {
                    throw new RuntimeException("{$label} option ".($optionIndex + 1).' is blank or is not text.');
                }
            }

            $correctIndex = $record['correct_index'] ?? null;
            if (! is_int($correctIndex) || $correctIndex < 0 || $correctIndex >= count($record['options'])) {
                throw new RuntimeException("{$label} has an invalid zero-based correct_index.");
            }
            if (! is_string($record['review_status'] ?? null) || trim($record['review_status']) === '') {
                throw new RuntimeException("{$label} has a missing review_status.");
            }
            if (! is_bool($record['needs_key_review'] ?? null)) {
                throw new RuntimeException("{$label} has a missing or invalid needs_key_review flag.");
            }
            if (isset($record['correct_answer'])) {
                if (! is_string($record['correct_answer']) || $this->normalize($record['correct_answer']) !== $this->normalize($record['options'][$correctIndex])) {
                    throw new RuntimeException("{$label} correct_answer does not match options[correct_index].");
                }
            }

            $this->validateOptional($record, $label);

            if (($record['review_status'] ?? '') !== 'ready_source_key' || ($record['needs_key_review'] ?? false) !== false) {
                $skipped++;
                continue;
            }

            $eligible[] = $record;
        }

        return ['records' => $eligible, 'skipped' => $skipped];
    }

    private function validateOptional(array $record, string $label): void
    {
        foreach (['original_number', 'source_page'] as $field) {
            if (array_key_exists($field, $record) && $record[$field] !== null && (! is_int($record[$field]) || $record[$field] < 0)) {
                throw new RuntimeException("{$label} field {$field} must be a non-negative integer or null.");
            }
        }

        foreach (['explanation', 'source_type', 'review_status'] as $field) {
            if (array_key_exists($field, $record) && $record[$field] !== null && ! is_string($record[$field])) {
                throw new RuntimeException("{$label} field {$field} must be text or null.");
            }
        }

        foreach (['needs_key_review', 'key_verified_against_dmbok'] as $field) {
            if (array_key_exists($field, $record) && ! is_bool($record[$field])) {
                throw new RuntimeException("{$label} field {$field} must be a boolean.");
            }
        }
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }
}
