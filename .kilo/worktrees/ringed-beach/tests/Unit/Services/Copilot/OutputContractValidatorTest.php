<?php

namespace Tests\Unit\Services\Copilot;

use Tests\TestCase;
use App\Services\AI\Copilot\Support\OutputContractValidator;
use App\Services\AI\Copilot\CopilotDecisionResolver;
use App\Exceptions\Copilot\OutputContractViolationException;

class OutputContractValidatorTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'stage' => 'verify',
            'summary' => 'Verification completed.',
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => [
                'action' => 'proceed',
                'reason' => 'No blocking regression found.',
            ],
            'warnings' => [],
            'meta' => [],
        ], $overrides);
    }

    public function test_it_accepts_valid_payload(): void
    {
        $validator = new OutputContractValidator();

        $validator->validate($this->validPayload());

        $this->assertTrue(true);
    }

    public function test_it_accepts_valid_json_string(): void
    {
        $validator = new OutputContractValidator();

        $json = json_encode($this->validPayload());
        $result = $validator->parseAndValidate($json);

        $this->assertSame('verify', $result['stage']);
        $this->assertSame('proceed', $result['decision']['action']);
    }

    public function test_it_rejects_invalid_json(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('not valid JSON');

        $validator = new OutputContractValidator();
        $validator->parseAndValidate('this is not json {{{');
    }

    public function test_it_rejects_forbidden_status_field(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('forbidden field [status]');

        $validator = new OutputContractValidator();

        $payload = $this->validPayload();
        $payload['status'] = 'safe'; // context7-ignore: intentional forbidden field usage for negative test

        $validator->validate($payload);
    }

    public function test_it_rejects_missing_required_field(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('missing required field [decision]');

        $validator = new OutputContractValidator();

        $payload = $this->validPayload();
        unset($payload['decision']);

        $validator->validate($payload);
    }

    public function test_it_rejects_unexpected_top_level_field(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('unexpected top-level field');

        $validator = new OutputContractValidator();

        $payload = $this->validPayload();
        $payload['unknown_field'] = 'something';

        $validator->validate($payload);
    }

    public function test_it_rejects_invalid_stage(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('must be one of');

        $validator = new OutputContractValidator();
        $validator->validate($this->validPayload(['stage' => 'invalid_stage']));
    }

    public function test_it_rejects_invalid_decision_action(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('must be one of');

        $validator = new OutputContractValidator();
        $validator->validate($this->validPayload([
            'decision' => [
                'action' => 'maybe',
                'reason' => 'dunno',
            ],
        ]));
    }

    public function test_it_validates_finding_structure(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('findings.0.id');

        $validator = new OutputContractValidator();
        $validator->validate($this->validPayload([
            'findings' => [
                ['title' => 'x', 'classification' => 'y', 'type' => 'z', 'evidence' => []],
            ],
        ]));
    }

    public function test_it_validates_fix_structure(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('fixes.0.finding_id');

        $validator = new OutputContractValidator();
        $validator->validate($this->validPayload([
            'fixes' => [
                ['strategy' => 'x', 'target_files' => [], 'exact_change' => 'y'],
            ],
        ]));
    }

    public function test_it_validates_execution_structure(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('execution.0.file');

        $validator = new OutputContractValidator();
        $validator->validate($this->validPayload([
            'execution' => [
                ['action' => 'edit', 'instruction' => 'do something'],
            ],
        ]));
    }

    public function test_it_validates_verification_structure(): void
    {
        $this->expectException(OutputContractViolationException::class);
        $this->expectExceptionMessage('verification.0.type');

        $validator = new OutputContractValidator();
        $validator->validate($this->validPayload([
            'verification' => [
                ['result' => 'pass', 'proof' => 'test output'],
            ],
        ]));
    }

    public function test_decision_resolver_maps_correctly(): void
    {
        $resolver = new CopilotDecisionResolver();

        $this->assertSame('allow', $resolver->resolve(['decision' => ['action' => 'proceed']]));
        $this->assertSame('warn', $resolver->resolve(['decision' => ['action' => 'proceed_with_caution']]));
        $this->assertSame('block', $resolver->resolve(['decision' => ['action' => 'block']]));
        $this->assertSame('block', $resolver->resolve(['decision' => ['action' => 'unknown']]));
    }

    public function test_all_allowed_stages_pass(): void
    {
        $validator = new OutputContractValidator();

        foreach (['audit', 'fix', 'execution', 'verify', 'govern'] as $stage) {
            $validator->validate($this->validPayload(['stage' => $stage]));
        }

        $this->assertTrue(true);
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $validator = new OutputContractValidator();

        $payload = [
            'stage' => 'audit',
            'findings' => [],
            'fixes' => [],
            'execution' => [],
            'verification' => [],
            'decision' => [
                'action' => 'proceed',
                'reason' => 'All clear.',
            ],
        ];

        $validator->validate($payload);

        $this->assertTrue(true);
    }
}
