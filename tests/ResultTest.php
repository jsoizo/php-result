<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;

describe('Result::success', function (): void {
    it('creates Success instance', function (): void {
        $result = Result::success(42);

        expect($result)->toBeInstanceOf(Success::class);
    });

    it('handles null value', function (): void {
        $result = Result::success(null);

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(null))->toBeNull();
    });
});

describe('Result::failure', function (): void {
    it('creates Failure instance', function (): void {
        $result = Result::failure('error');

        expect($result)->toBeInstanceOf(Failure::class);
    });

    it('handles null error', function (): void {
        $result = Result::failure(null);

        expect($result)->toBeInstanceOf(Failure::class);
    });
});

describe('Result::catch', function (): void {
    it('returns Success on normal execution', function (): void {
        $result = Result::catch(fn () => 42);

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(42);
    });

    it('catches exception and returns Failure', function (): void {
        $exception = new RuntimeException('oops');
        $result = Result::catch(fn () => throw $exception);

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->isFailure())->toBeTrue();
    });

    it('catches Error and returns Failure', function (): void {
        $result = Result::catch(fn () => throw new Error('fatal'));

        expect($result)->toBeInstanceOf(Failure::class);
    });
});

describe('Result::fold', function (): void {
    it('applies onSuccess for Success', function (): void {
        $result = Result::success(42);

        $value = $result->fold(
            fn ($error) => "Error: {$error}",
            fn ($value) => "Value: {$value}"
        );

        expect($value)->toBe('Value: 42');
    });

    it('applies onFailure for Failure', function (): void {
        $result = Result::failure('oops');

        $value = $result->fold(
            fn ($error) => "Error: {$error}",
            fn ($value) => "Value: {$value}"
        );

        expect($value)->toBe('Error: oops');
    });

    it('works with named arguments', function (): void {
        $success = Result::success(10);
        $failure = Result::failure('err');

        $successValue = $success->fold(
            onFailure: fn ($e) => 0,
            onSuccess: fn ($v) => $v * 2
        );
        $failureValue = $failure->fold(
            onSuccess: fn ($v) => $v * 2,
            onFailure: fn ($e) => -1
        );

        expect($successValue)->toBe(20);
        expect($failureValue)->toBe(-1);
    });

    it('works with positional arguments', function (): void {
        $success = Result::success(5);
        $failure = Result::failure('error');

        $successValue = $success->fold(fn ($e) => 0, fn ($v) => $v + 1);
        $failureValue = $failure->fold(fn ($e) => -1, fn ($v) => $v + 1);

        expect($successValue)->toBe(6);
        expect($failureValue)->toBe(-1);
    });
});

describe('Result::binding', function (): void {
    it('chains successful Results', function (): void {
        // @phpstan-ignore argument.type (Generator type inference is complex)
        $result = Result::binding(function () {
            /** @var int $x */
            $x = yield Result::success(10);
            /** @var int $y */
            $y = yield Result::success(20);
            /** @var int $z */
            $z = yield Result::success(12);

            return $x + $y + $z;
        });

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(42);
    });

    it('short-circuits on first Failure', function (): void {
        $secondCalled = false;
        // @phpstan-ignore argument.type (Generator type inference is complex)
        $result = Result::binding(function () use (&$secondCalled) {
            /** @var int $x */
            $x = yield Result::success(10);
            /** @var int $y */
            $y = yield Result::failure('error');
            $secondCalled = true;
            /** @var int $z */
            $z = yield Result::success(12);

            return $x + $y + $z;
        });

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse(''))->toBe('error');
        expect($secondCalled)->toBeFalse();
    });

    it('returns Failure when first Result fails', function (): void {
        // @phpstan-ignore argument.type (Generator type inference is complex)
        $result = Result::binding(function () {
            /** @var int $x */
            $x = yield Result::failure('first error');
            /** @var int $y */
            $y = yield Result::success(20);

            return $x + $y;
        });

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse(''))->toBe('first error');
    });

    it('works with different types', function (): void {
        // @phpstan-ignore argument.type (Generator type inference is complex)
        $result = Result::binding(function () {
            /** @var string $name */
            $name = yield Result::success('John');
            /** @var int $age */
            $age = yield Result::success(30);

            return "{$name} is {$age} years old";
        });

        expect($result)->toBeInstanceOf(Success::class);
        // @phpstan-ignore argument.type (Test uses empty string as default)
        expect($result->getOrElse(''))->toBe('John is 30 years old');
    });

    it('can use previous values in subsequent yields', function (): void {
        // @phpstan-ignore argument.type (Generator type inference is complex)
        $result = Result::binding(function () {
            /** @var int $x */
            $x = yield Result::success(5);
            /** @var int $y */
            $y = yield Result::success($x * 2);
            /** @var int $z */
            $z = yield Result::success($x + $y);

            return $z;
        });

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(15);
    });

    it('works with single yield', function (): void {
        // @phpstan-ignore argument.type (Generator type inference is complex)
        $result = Result::binding(function () {
            /** @var int $x */
            $x = yield Result::success(42);

            return $x;
        });

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(42);
    });
});
