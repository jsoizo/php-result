<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\TypeInference;

use PHPStan\Testing\TypeInferenceTestCase;

final class ResultTypeNarrowingTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/../data/result-type-narrowing.php');
    }

    /**
     * @dataProvider dataFileAsserts
     */
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../../phpstan.neon'];
    }
}
