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

function stringifyInt(int $value): string
{
    return (string) $value;
}

/**
 * @param int $value
 * @param ValidationError $error
 */
function testFactoriesInferNeverSides(int $value, ValidationError $error): void
{
    assertType('Jsoizo\Result\Success<int, never>', Result::success($value));
    assertType('Jsoizo\Result\Failure<never, Jsoizo\Result\Tests\PHPStan\Data\ValidationError>', Result::failure($error));
}

function testCatchInfersThrowableError(): void
{
    $caught = Result::catch(fn (): int => 1);

    assertType('Jsoizo\Result\Result<int, Throwable>', $caught);
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
 * @param Result<int, string> $result
 */
function testMapChangesSuccessType(Result $result): void
{
    $mapped = $result->map(fn (int $value): string => stringifyInt($value));

    assertType('Jsoizo\Result\Result<string, string>', $mapped);
}

/**
 * @param Result<int, string> $result
 */
function testMapErrorChangesErrorType(Result $result): void
{
    $mapped = $result->mapError(fn (string $error) => new \RuntimeException($error));

    assertType('Jsoizo\Result\Result<int, RuntimeException>', $mapped);
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
 * @param callable(int): Result<string, never> $fn
 */
function testFlatMapCanChangeSuccessType(Result $result, callable $fn): void
{
    $mapped = $result->flatMap($fn);

    assertType('Jsoizo\Result\Result<string, string>', $mapped);
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

/**
 * @param Result<int, string> $result
 */
function testRecoverWithCanAddSuccessType(Result $result): void
{
    $recovered = $result->recoverWith(fn (string $error) => Result::success($error));

    assertType('Jsoizo\Result\Result<int|string, never>', $recovered);
}

/**
 * @param Result<Result<int, string>, string> $result
 */
function testFlattenPreservesNestedTypes(Result $result): void
{
    $flattened = $result->flatten();

    assertType('Jsoizo\Result\Result<int, string>', $flattened);
}

/**
 * @param Result<Result<int, string>, int> $result
 */
function testFlattenUnionsOuterAndInnerErrorTypes(Result $result): void
{
    $flattened = $result->flatten();

    assertType('Jsoizo\Result\Result<int, int|string>', $flattened);
}

/**
 * @param Result<int, string> $result
 */
function testFlattenKeepsNonNestedTypes(Result $result): void
{
    $flattened = $result->flatten();

    assertType('Jsoizo\Result\Result<int, string>', $flattened);
}

/**
 * @param Result<int, string> $result
 */
function testFallbackMethodsUnionDefaults(Result $result): void
{
    assertType('int|false', $result->getOrElse(false));
    assertType('RuntimeException|string', $result->getErrorOrElse(new \RuntimeException()));
}

/**
 * @param Result<int, string> $result
 */
function testFoldUnionsCallbackReturnTypes(Result $result): void
{
    $folded = $result->fold(
        fn (string $error) => false,
        fn (int $value) => $value,
    );

    assertType('int|false', $folded);
}

/**
 * @param Result<int, string> $result
 */
function testTapMethodsPreserveResultType(Result $result): void
{
    assertType('Jsoizo\Result\Result<int, string>', $result->tap(fn (int $value): null => null));
    assertType('Jsoizo\Result\Result<int, string>', $result->tapError(fn (string $error): null => null));
}

/**
 * @param Result<int, string> $result
 */
function testGetOrNullUnionsNull(Result $result): void
{
    assertType('int|null', $result->getOrNull());
}

function testSequenceCollectsValuesOrErrors(): void
{
    $sequenced = Result::sequence([
        Result::success(1),
        Result::success(2),
        Result::failure('error'),
    ]);

    assertType('Jsoizo\Result\Result<list<int>, non-empty-list<string>>', $sequenced);
}

/**
 * @param Result<string, ValidationError> $name
 * @param Result<int, ValidationError> $age
 */
function testAccumulate2InfersTransformAndErrors(Result $name, Result $age): void
{
    $accumulated = Result::accumulate2(
        $name,
        $age,
        fn (string $name, int $age): array => ['name' => $name, 'age' => $age],
    );

    assertType('Jsoizo\Result\Result<array{name: string, age: int}, non-empty-list<Jsoizo\Result\Tests\PHPStan\Data\ValidationError>>', $accumulated);
}
