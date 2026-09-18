<?php

namespace Tests\Unit\UseCases;

use App\UseCases\N8n\ProcessAIIlanTaslagiUseCase;
use App\UseCases\N8n\ProcessAIMesajTaslagiUseCase;
use App\UseCases\N8n\ProcessAIContractDraftUseCase;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for N8N-AI-USECASES-UNQUALIFIED-MODEL-CRASH.
 *
 * Design: Reflection-based namespace contract test.
 *
 * Each UseCase's handle() method declares the affected AI model as its return type.
 * ReflectionMethod::getReturnType()->getName() resolves the alias to its FQCN.
 * We assert that FQCN matches the canonical App\Models\AI\* namespace.
 *
 * Canonical models (confirmed via repository inspection):
 *   App\Models\AI\AIIlanTaslagi
 *   App\Models\AI\AIMessage
 *   App\Models\AI\AIContractDraft
 *
 * Pre-fix UseCase imports (WRONG — classes do not exist):
 *   use App\Models\AIIlanTaslagi;       → return type resolves to App\Models\AIIlanTaslagi
 *   use App\Models\AIMessage;           → return type resolves to App\Models\AIMessage
 *   use App\Models\AIContractDraft;      → return type resolves to App\Models\AIContractDraft
 *
 * Post-fix UseCase imports (CORRECT):
 *   use App\Models\AI\AIIlanTaslagi;   → return type resolves to App\Models\AI\AIIlanTaslagi
 *   use App\Models\AI\AIMessage;       → return type resolves to App\Models\AI\AIMessage
 *   use App\Models\AI\AIContractDraft; → return type resolves to App\Models\AI\AIContractDraft
 *
 * The test derives the FQCN from the UseCase method reflection — NOT from the test's own imports.
 * This means it is sensitive to the UseCase's use statement, not the test's.
 *
 * NO persistence, NO DB, NO handle() execution, NO schema required.
 * Tests only the static type declaration contract between UseCase and model.
 */
class N8nUseCasesModelImportTest extends TestCase
{
    private const EXPECTED_FQCNS = [
        ProcessAIIlanTaslagiUseCase::class => 'App\Models\AI\AIIlanTaslagi',
        ProcessAIMesajTaslagiUseCase::class => 'App\Models\AI\AIMessage',
        ProcessAIContractDraftUseCase::class => 'App\Models\AI\AIContractDraft',
    ];

    /**
     * @dataProvider useCaseReturnTypeProvider
     */
    public function test_usecase_handle_return_type_resolves_to_canonical_model(
        string $useCaseClass,
        string $expectedFqcn
    ): void {
        $method = new \ReflectionMethod($useCaseClass, 'handle');

        // 1. Return type must exist on handle()
        $returnType = $method->getReturnType();
        $this->assertNotNull(
            $returnType,
            "handle() on {$useCaseClass} must declare a return type"
        );

        // 2. Return type must be a named type (not union/void)
        $this->assertInstanceOf(
            \ReflectionNamedType::class,
            $returnType,
            "handle() return type on {$useCaseClass} must be a single named type"
        );

        /** @var ReflectionNamedType $returnType */
        $resolvedFqcn = $returnType->getName();

        // 3. FQCN must match the canonical App\Models\AI\* namespace exactly
        $this->assertEquals(
            $expectedFqcn,
            $resolvedFqcn,
            "{$useCaseClass}::handle() resolves to '{$resolvedFqcn}' but canonical is '{$expectedFqcn}'"
        );

        // 4. The resolved FQCN must be an existing class
        $this->assertTrue(
            class_exists($resolvedFqcn),
            "Resolved FQCN '{$resolvedFqcn}' must exist as a class"
        );
    }

    public static function useCaseReturnTypeProvider(): array
    {
        return [
            'ProcessAIIlanTaslagiUseCase → App\\Models\\AI\\AIIlanTaslagi' => [
                ProcessAIIlanTaslagiUseCase::class,
                'App\Models\AI\AIIlanTaslagi',
            ],
            'ProcessAIMesajTaslagiUseCase → App\\Models\\AI\\AIMessage' => [
                ProcessAIMesajTaslagiUseCase::class,
                'App\Models\AI\AIMessage',
            ],
            'ProcessAIContractDraftUseCase → App\\Models\\AI\\AIContractDraft' => [
                ProcessAIContractDraftUseCase::class,
                'App\Models\AI\AIContractDraft',
            ],
        ];
    }
}
