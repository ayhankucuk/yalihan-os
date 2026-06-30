<?php

namespace App\Services\AI\Copilot\Support;

use App\Exceptions\Copilot\OutputContractViolationException;

class OutputContractValidator
{
    public function parseAndValidate(string $rawOutput): array
    {
        $decoded = json_decode($rawOutput, true);

        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw OutputContractViolationException::invalidJson($rawOutput);
        }

        $this->validate($decoded);

        return $decoded;
    }

    public function validate(array $payload): void
    {
        $this->validateTopLevelKeys($payload);
        $this->validateStage($payload);
        $this->validateSummary($payload);
        $this->validateFindings($payload);
        $this->validateFixes($payload);
        $this->validateExecution($payload);
        $this->validateVerification($payload);
        $this->validateDecision($payload);
        $this->validateWarnings($payload);
        $this->validateMeta($payload);
    }

    protected function validateTopLevelKeys(array $payload): void
    {
        foreach (OutputContract::REQUIRED_TOP_LEVEL_KEYS as $key) {
            if (!array_key_exists($key, $payload)) {
                throw OutputContractViolationException::missingField($key);
            }
        }

        foreach (OutputContract::FORBIDDEN_TOP_LEVEL_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                throw OutputContractViolationException::forbiddenField($key);
            }
        }

        $allowed = array_merge(
            OutputContract::REQUIRED_TOP_LEVEL_KEYS,
            OutputContract::OPTIONAL_TOP_LEVEL_KEYS
        );

        foreach (array_keys($payload) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw OutputContractViolationException::invalidField($key, 'unexpected top-level field');
            }
        }
    }

    protected function validateStage(array $payload): void
    {
        if (!is_string($payload['stage'])) {
            throw OutputContractViolationException::invalidField('stage', 'must be a string');
        }

        if (!in_array($payload['stage'], OutputContract::ALLOWED_STAGES, true)) {
            throw OutputContractViolationException::invalidField(
                'stage',
                'must be one of: ' . implode(', ', OutputContract::ALLOWED_STAGES)
            );
        }
    }

    protected function validateSummary(array $payload): void
    {
        if (!array_key_exists('summary', $payload)) {
            return;
        }

        if (!is_string($payload['summary'])) {
            throw OutputContractViolationException::invalidField('summary', 'must be a string');
        }
    }

    protected function validateFindings(array $payload): void
    {
        if (!is_array($payload['findings'])) {
            throw OutputContractViolationException::invalidField('findings', 'must be an array');
        }

        foreach ($payload['findings'] as $index => $finding) {
            if (!is_array($finding)) {
                throw OutputContractViolationException::invalidField("findings.{$index}", 'must be an object');
            }

            foreach (['id', 'title', 'classification', 'type', 'evidence'] as $field) {
                if (!array_key_exists($field, $finding)) {
                    throw OutputContractViolationException::missingField("findings.{$index}.{$field}");
                }
            }

            if (!is_string($finding['id']) || $finding['id'] === '') {
                throw OutputContractViolationException::invalidField("findings.{$index}.id", 'must be a non-empty string');
            }

            if (!is_string($finding['title']) || $finding['title'] === '') {
                throw OutputContractViolationException::invalidField("findings.{$index}.title", 'must be a non-empty string');
            }

            if (!is_string($finding['classification']) || $finding['classification'] === '') {
                throw OutputContractViolationException::invalidField("findings.{$index}.classification", 'must be a non-empty string');
            }

            if (!is_string($finding['type']) || $finding['type'] === '') {
                throw OutputContractViolationException::invalidField("findings.{$index}.type", 'must be a non-empty string');
            }

            if (!is_array($finding['evidence'])) {
                throw OutputContractViolationException::invalidField("findings.{$index}.evidence", 'must be an array');
            }
        }
    }

    protected function validateFixes(array $payload): void
    {
        if (!is_array($payload['fixes'])) {
            throw OutputContractViolationException::invalidField('fixes', 'must be an array');
        }

        foreach ($payload['fixes'] as $index => $fix) {
            if (!is_array($fix)) {
                throw OutputContractViolationException::invalidField("fixes.{$index}", 'must be an object');
            }

            foreach (['finding_id', 'strategy', 'target_files', 'exact_change'] as $field) {
                if (!array_key_exists($field, $fix)) {
                    throw OutputContractViolationException::missingField("fixes.{$index}.{$field}");
                }
            }

            if (!is_string($fix['finding_id']) || $fix['finding_id'] === '') {
                throw OutputContractViolationException::invalidField("fixes.{$index}.finding_id", 'must be a non-empty string');
            }

            if (!is_string($fix['strategy']) || $fix['strategy'] === '') {
                throw OutputContractViolationException::invalidField("fixes.{$index}.strategy", 'must be a non-empty string');
            }

            if (!is_array($fix['target_files'])) {
                throw OutputContractViolationException::invalidField("fixes.{$index}.target_files", 'must be an array');
            }

            if (!is_string($fix['exact_change']) || $fix['exact_change'] === '') {
                throw OutputContractViolationException::invalidField("fixes.{$index}.exact_change", 'must be a non-empty string');
            }
        }
    }

    protected function validateExecution(array $payload): void
    {
        if (!is_array($payload['execution'])) {
            throw OutputContractViolationException::invalidField('execution', 'must be an array');
        }

        foreach ($payload['execution'] as $index => $step) {
            if (!is_array($step)) {
                throw OutputContractViolationException::invalidField("execution.{$index}", 'must be an object');
            }

            foreach (['file', 'action', 'instruction'] as $field) {
                if (!array_key_exists($field, $step)) {
                    throw OutputContractViolationException::missingField("execution.{$index}.{$field}");
                }
            }

            if (!is_string($step['file']) || $step['file'] === '') {
                throw OutputContractViolationException::invalidField("execution.{$index}.file", 'must be a non-empty string');
            }

            if (!is_string($step['action']) || $step['action'] === '') {
                throw OutputContractViolationException::invalidField("execution.{$index}.action", 'must be a non-empty string');
            }

            if (!is_string($step['instruction']) || $step['instruction'] === '') {
                throw OutputContractViolationException::invalidField("execution.{$index}.instruction", 'must be a non-empty string');
            }
        }
    }

    protected function validateVerification(array $payload): void
    {
        if (!is_array($payload['verification'])) {
            throw OutputContractViolationException::invalidField('verification', 'must be an array');
        }

        foreach ($payload['verification'] as $index => $item) {
            if (!is_array($item)) {
                throw OutputContractViolationException::invalidField("verification.{$index}", 'must be an object');
            }

            foreach (['type', 'result', 'proof'] as $field) {
                if (!array_key_exists($field, $item)) {
                    throw OutputContractViolationException::missingField("verification.{$index}.{$field}");
                }
            }

            if (!is_string($item['type']) || $item['type'] === '') {
                throw OutputContractViolationException::invalidField("verification.{$index}.type", 'must be a non-empty string');
            }

            if (!is_string($item['result']) || $item['result'] === '') {
                throw OutputContractViolationException::invalidField("verification.{$index}.result", 'must be a non-empty string');
            }

            if (!is_string($item['proof']) || $item['proof'] === '') {
                throw OutputContractViolationException::invalidField("verification.{$index}.proof", 'must be a non-empty string');
            }
        }
    }

    protected function validateDecision(array $payload): void
    {
        if (!is_array($payload['decision'])) {
            throw OutputContractViolationException::invalidField('decision', 'must be an object');
        }

        foreach (['action', 'reason'] as $field) {
            if (!array_key_exists($field, $payload['decision'])) {
                throw OutputContractViolationException::missingField("decision.{$field}");
            }
        }

        if (!is_string($payload['decision']['action'])) {
            throw OutputContractViolationException::invalidField('decision.action', 'must be a string');
        }

        if (!in_array($payload['decision']['action'], OutputContract::ALLOWED_ACTIONS, true)) {
            throw OutputContractViolationException::invalidField(
                'decision.action',
                'must be one of: ' . implode(', ', OutputContract::ALLOWED_ACTIONS)
            );
        }

        if (!is_string($payload['decision']['reason']) || $payload['decision']['reason'] === '') {
            throw OutputContractViolationException::invalidField('decision.reason', 'must be a non-empty string');
        }
    }

    protected function validateWarnings(array $payload): void
    {
        if (!array_key_exists('warnings', $payload)) {
            return;
        }

        if (!is_array($payload['warnings'])) {
            throw OutputContractViolationException::invalidField('warnings', 'must be an array');
        }

        foreach ($payload['warnings'] as $index => $warning) {
            if (!is_string($warning) || $warning === '') {
                throw OutputContractViolationException::invalidField("warnings.{$index}", 'must be a non-empty string');
            }
        }
    }

    protected function validateMeta(array $payload): void
    {
        if (!array_key_exists('meta', $payload)) {
            return;
        }

        if (!is_array($payload['meta'])) {
            throw OutputContractViolationException::invalidField('meta', 'must be an object');
        }
    }
}
