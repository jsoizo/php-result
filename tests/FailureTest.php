<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;

describe('Failure', function (): void {
    describe('getOrElse', function (): void {
        test('returns default', function (): void {
            $result = Result::failure('error');

            expect($result->getOrElse(42))->toBe(42);
        });

        test('returns null default', function (): void {
            $result = Result::failure('error');

            expect($result->getOrElse(null))->toBeNull();
        });
    });

    describe('getOrThrow', function (): void {
        test('throws Throwable error', function (): void {
            $exception = new RuntimeException('oops');
            $result = Result::failure($exception);

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class, 'oops');
        });

        test('throws RuntimeException for non-Throwable error', function (): void {
            $result = Result::failure('error string');

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class);
        });

        test('throws RuntimeException with error details', function (): void {
            $result = Result::failure(['code' => 404, 'message' => 'Not Found']);

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class);
        });
    });

    describe('map', function (): void {
        test('does nothing', function (): void {
            $result = Result::failure('error')->map(fn ($x) => $x * 2);

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->isFailure())->toBeTrue();
        });

        test('callback is not called', function (): void {
            $called = false;
            Result::failure('error')->map(function ($x) use (&$called): int {
                $called = true;

                return $x * 2;
            });

            expect($called)->toBeFalse();
        });
    });

    describe('mapError', function (): void {
        test('transforms error', function (): void {
            $result = Result::failure('error')
                ->mapError(fn ($e) => strtoupper($e));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->getOrElse(null))->toBeNull();
        });

        test('changes error type', function (): void {
            $result = Result::failure('not found')
                ->mapError(fn ($e) => new RuntimeException($e));

            expect($result)->toBeInstanceOf(Failure::class);
            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class, 'not found');
        });
    });

    describe('flatMap', function (): void {
        test('does nothing', function (): void {
            $result = Result::failure('error')
                ->flatMap(fn ($x) => Result::success($x * 2));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->isFailure())->toBeTrue();
        });

        test('callback is not called', function (): void {
            $called = false;
            Result::failure('error')->flatMap(function ($x) use (&$called) {
                $called = true;

                return Result::success($x * 2);
            });

            expect($called)->toBeFalse();
        });

        test('preserves original Failure through chain', function (): void {
            $originalError = new RuntimeException('original');
            $result = Result::failure($originalError)
                ->flatMap(fn ($x) => Result::success($x))
                ->flatMap(fn ($x) => Result::failure('new error'));

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class, 'original');
        });
    });
});
