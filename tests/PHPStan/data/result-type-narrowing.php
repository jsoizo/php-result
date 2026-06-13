<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\Data;

use Jsoizo\Result\Result;

use function PHPStan\Testing\assertType;

final class ValidationError
{
}

final class DbError
{
}

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

/**
 * @param Result<string, ValidationError> $result
 */
function testFlatMapPreservesOriginalError(Result $result): void
{
    $mapped = $result->flatMap(fn (string $value) => Result::failure(new DbError()));

    assertType('Jsoizo\Result\Result<never, Jsoizo\Result\Tests\PHPStan\Data\DbError|Jsoizo\Result\Tests\PHPStan\Data\ValidationError>', $mapped);
}

/**
 * @param Result<int, string> $result
 */
function testRecoverCanChangeSuccessType(Result $result): void
{
    $recovered = $result->recover(fn (string $error) => false);

    assertType('Jsoizo\Result\Result<bool|int, never>', $recovered);
}

/**
 * @param Result<int, string> $result
 */
function testRecoverWithCanChangeSuccessAndErrorTypes(Result $result): void
{
    $recovered = $result->recoverWith(fn (string $error) => Result::failure(new \RuntimeException($error)));

    assertType('Jsoizo\Result\Result<int, RuntimeException>', $recovered);
}
