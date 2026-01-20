<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\Data;

use Jsoizo\Result\Result;

use function PHPStan\Testing\assertType;

/**
 * @param Result<int, string> $result
 */
function testIsSuccessNarrowing(Result $result): void
{
    if ($result->isSuccess()) {
        assertType('Jsoizo\Result\Success<int, string>', $result);
    } else {
        assertType('Jsoizo\Result\Failure<int, string>', $result);
    }
}

/**
 * @param Result<int, string> $result
 */
function testIsFailureNarrowing(Result $result): void
{
    if ($result->isFailure()) {
        assertType('Jsoizo\Result\Failure<int, string>', $result);
    } else {
        assertType('Jsoizo\Result\Success<int, string>', $result);
    }
}

/**
 * @param Result<int, string> $result
 */
function testNegatedIsSuccessNarrowing(Result $result): void
{
    if (!$result->isSuccess()) {
        assertType('Jsoizo\Result\Failure<int, string>', $result);
    } else {
        assertType('Jsoizo\Result\Success<int, string>', $result);
    }
}

/**
 * @param Result<int, string> $result
 */
function testEarlyReturn(Result $result): int
{
    if ($result->isFailure()) {
        assertType('Jsoizo\Result\Failure<int, string>', $result);

        return -1;
    }

    assertType('Jsoizo\Result\Success<int, string>', $result);

    return $result->get();
}

/**
 * @param Result<array{name: string}, \Exception> $result
 */
function testComplexTypes(Result $result): void
{
    if ($result->isSuccess()) {
        assertType('Jsoizo\Result\Success<array{name: string}, Exception>', $result);
    } else {
        assertType('Jsoizo\Result\Failure<array{name: string}, Exception>', $result);
    }
}
