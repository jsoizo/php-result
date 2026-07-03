<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\Data;

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;

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
 * @param Result<int, string> $result
 */
function testNarrowedAccessorsInferContainedTypes(Result $result): void
{
    if ($result->isSuccess()) {
        assertType('int', $result->get());
    }

    if ($result->isFailure()) {
        assertType('string', $result->getError());
    }
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
 * @param Result<string, ValidationError> $name
 * @param Result<int, ValidationError> $age
 */
function testBindingInfersReturnAndErrorTypes(Result $name, Result $age): void
{
    $bound = Result::binding(function () use ($name, $age): \Generator {
        /** @var string $unwrappedName */
        $unwrappedName = yield $name;

        /** @var int $unwrappedAge */
        $unwrappedAge = yield $age;

        return ['name' => $unwrappedName, 'age' => $unwrappedAge];
    });

    assertType('Jsoizo\Result\Result<array{name: string, age: int}, Jsoizo\Result\Tests\PHPStan\Data\ValidationError>', $bound);
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

/**
 * @param Result<int, string> $result
 */
function testGetErrorOrNullUnionsNull(Result $result): void
{
    assertType('string|null', $result->getErrorOrNull());

    if ($result->isFailure()) {
        assertType('string', $result->getErrorOrNull());
    } else {
        assertType('null', $result->getErrorOrNull());
    }
}

function testAccumulateCollectsValuesOrErrors(): void
{
    $accumulated = Result::accumulate([
        Result::success(1),
        Result::success(2),
        Result::failure('error'),
    ]);

    assertType('Jsoizo\Result\Result<list<int>, non-empty-list<string>>', $accumulated);
}

/**
 * @param Result<int, ValidationError> $validationResult
 * @param Result<int, DbError> $dbResult
 */
function testAccumulateUnionsErrorTypes(Result $validationResult, Result $dbResult): void
{
    $accumulated = Result::accumulate([$validationResult, $dbResult]);

    assertType('Jsoizo\Result\Result<list<int>, non-empty-list<Jsoizo\Result\Tests\PHPStan\Data\DbError|Jsoizo\Result\Tests\PHPStan\Data\ValidationError>>', $accumulated);
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

/**
 * @param Result<string, ValidationError> $r1
 * @param Result<int, DbError> $r2
 */
function testAccumulate2UnionsErrorTypes(Result $r1, Result $r2): void
{
    $accumulated = Result::accumulate2($r1, $r2, fn (string $a, int $b): string => $a);

    assertType('Jsoizo\Result\Result<string, non-empty-list<Jsoizo\Result\Tests\PHPStan\Data\DbError|Jsoizo\Result\Tests\PHPStan\Data\ValidationError>>', $accumulated);
}

/**
 * @param Result<string, ValidationError> $r1
 * @param Result<int, ValidationError> $r2
 * @param Result<bool, ValidationError> $r3
 * @param Result<float, ValidationError> $r4
 * @param Result<ValidationError, ValidationError> $r5
 * @param Result<DbError, ValidationError> $r6
 * @param Result<\DateTimeImmutable, ValidationError> $r7
 * @param Result<list<string>, ValidationError> $r8
 * @param Result<array{id: int}, ValidationError> $r9
 */
function testAccumulate9InfersTransformArgumentsAndReturnType(
    Result $r1,
    Result $r2,
    Result $r3,
    Result $r4,
    Result $r5,
    Result $r6,
    Result $r7,
    Result $r8,
    Result $r9,
): void {
    $accumulated = Result::accumulate9(
        $r1,
        $r2,
        $r3,
        $r4,
        $r5,
        $r6,
        $r7,
        $r8,
        $r9,
        fn (
            string $text,
            int $count,
            bool $enabled,
            float $ratio,
            ValidationError $validationError,
            DbError $dbError,
            \DateTimeImmutable $createdAt,
            array $tags,
            array $payload,
        ): array => [
            'text' => $text,
            'count' => $count,
            'enabled' => $enabled,
            'ratio' => $ratio,
        ],
    );

    assertType('Jsoizo\Result\Result<array{text: string, count: int, enabled: bool, ratio: float}, non-empty-list<Jsoizo\Result\Tests\PHPStan\Data\ValidationError>>', $accumulated);
}

/**
 * @param Success<int, string> $success
 * @param Failure<int, string> $failure
 * @param callable(int): Result<string, ValidationError> $fn
 */
function testConcreteClassMethodsInferSpecificTypes(Success $success, Failure $failure, callable $fn): void
{
    assertType('Jsoizo\Result\Success<string, string>', $success->map(fn (int $value): string => stringifyInt($value)));
    assertType('Jsoizo\Result\Success<int, RuntimeException>', $success->mapError(fn (string $error) => new \RuntimeException($error)));
    assertType('Jsoizo\Result\Result<string, Jsoizo\Result\Tests\PHPStan\Data\ValidationError>', $success->flatMap($fn));
    assertType('Jsoizo\Result\Success<int, never>', $success->recover(fn (string $error): bool => false));
    assertType('Jsoizo\Result\Success<int, never>', $success->recoverWith(fn (string $error) => Result::failure(new DbError())));

    assertType('Jsoizo\Result\Failure<string, string>', $failure->map(fn (int $value): string => stringifyInt($value)));
    assertType('Jsoizo\Result\Failure<int, RuntimeException>', $failure->mapError(fn (string $error) => new \RuntimeException($error)));
    assertType('Jsoizo\Result\Result<string, Jsoizo\Result\Tests\PHPStan\Data\ValidationError|string>', $failure->flatMap($fn));
    assertType('Jsoizo\Result\Success<bool, never>', $failure->recover(fn (string $error): bool => false));
    assertType('Jsoizo\Result\Result<never, Jsoizo\Result\Tests\PHPStan\Data\DbError>', $failure->recoverWith(fn (string $error) => Result::failure(new DbError())));
}

/**
 * @param Success<Result<int, string>, DbError> $nestedSuccess
 * @param Failure<Result<int, string>, DbError> $nestedFailure
 * @param Success<int, DbError> $plainSuccess
 * @param Failure<int, DbError> $plainFailure
 */
function testConcreteFlattenInfersSpecificTypes(
    Success $nestedSuccess,
    Failure $nestedFailure,
    Success $plainSuccess,
    Failure $plainFailure,
): void {
    assertType('Jsoizo\Result\Result<int, Jsoizo\Result\Tests\PHPStan\Data\DbError|string>', $nestedSuccess->flatten());
    assertType('Jsoizo\Result\Result<int, Jsoizo\Result\Tests\PHPStan\Data\DbError|string>', $nestedFailure->flatten());
    assertType('Jsoizo\Result\Result<int, Jsoizo\Result\Tests\PHPStan\Data\DbError>', $plainSuccess->flatten());
    assertType('Jsoizo\Result\Result<int, Jsoizo\Result\Tests\PHPStan\Data\DbError>', $plainFailure->flatten());
}

function testConcreteFlattenOnNeverReceiverStaysClean(): void
{
    $neverFailure = Result::failure(new DbError());

    assertType('Jsoizo\Result\Result<never, Jsoizo\Result\Tests\PHPStan\Data\DbError>', $neverFailure->flatten());
}
