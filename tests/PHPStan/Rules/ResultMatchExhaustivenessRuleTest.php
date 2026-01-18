<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\Rules;

use Jsoizo\Result\PHPStan\Rules\ResultMatchExhaustivenessRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ResultMatchExhaustivenessRule>
 */
final class ResultMatchExhaustivenessRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ResultMatchExhaustivenessRule();
    }

    public function testMissingFailure(): void
    {
        $this->analyse([__DIR__ . '/../data/result-match-invalid.php'], [
            [
                'Match expression on Result type is not exhaustive. Missing: Failure.',
                16,
            ],
            [
                'Match expression on Result type is not exhaustive. Missing: Success.',
                26,
            ],
            [
                'Match expression on Result type is not exhaustive. Missing: Failure.',
                46,
            ],
        ]);
    }

    public function testValidCases(): void
    {
        $this->analyse([__DIR__ . '/../data/result-match-valid.php'], []);
    }
}
