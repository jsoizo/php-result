<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;

describe('Failure', function (): void {
    describe('getOrElse', function (): void {
        it('returns default', function (): void {
            $result = Result::failure('error');

            expect($result->getOrElse(42))->toBe(42);
        });

        it('returns null default', function (): void {
            $result = Result::failure('error');

            expect($result->getOrElse(null))->toBeNull();
        });
    });

    describe('getOrThrow', function (): void {
        it('throws Throwable error', function (): void {
            $exception = new RuntimeException('oops');
            $result = Result::failure($exception);

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class, 'oops');
        });

        it('throws RuntimeException for non-Throwable error', function (): void {
            $result = Result::failure('error string');

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class);
        });

        it('throws RuntimeException with error details', function (): void {
            $result = Result::failure(['code' => 404, 'message' => 'Not Found']);

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class);
        });
    });

    describe('map', function (): void {
        it('does nothing', function (): void {
            $result = Result::failure('error')->map(fn ($x) => $x * 2);

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->isFailure())->toBeTrue();
        });

        it('callback is not called', function (): void {
            $called = false;
            Result::failure('error')->map(function ($x) use (&$called): int {
                $called = true;

                return $x * 2;
            });

            expect($called)->toBeFalse();
        });
    });

    describe('mapError', function (): void {
        it('transforms error', function (): void {
            $result = Result::failure('error')
                ->mapError(fn ($e) => strtoupper($e));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->getOrElse(null))->toBeNull();
        });

        it('changes error type', function (): void {
            $result = Result::failure('not found')
                ->mapError(fn ($e) => new RuntimeException($e));

            expect($result)->toBeInstanceOf(Failure::class);
            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class, 'not found');
        });
    });

    describe('flatMap', function (): void {
        it('does nothing', function (): void {
            $result = Result::failure('error')
                ->flatMap(fn ($x) => Result::success($x * 2));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->isFailure())->toBeTrue();
        });

        it('callback is not called', function (): void {
            $called = false;
            Result::failure('error')->flatMap(function ($x) use (&$called) {
                $called = true;

                return Result::success($x * 2);
            });

            expect($called)->toBeFalse();
        });

        it('preserves original Failure through chain', function (): void {
            $originalError = new RuntimeException('original');
            $result = Result::failure($originalError)
                ->flatMap(fn ($x) => Result::success($x))
                ->flatMap(fn ($x) => Result::failure('new error'));

            expect(fn () => $result->getOrThrow())->toThrow(RuntimeException::class, 'original');
        });
    });

    describe('getErrorOrElse', function (): void {
        it('returns error', function (): void {
            $result = Result::failure('error message');

            expect($result->getErrorOrElse('default'))->toBe('error message');
        });

        it('ignores default for error value', function (): void {
            $result = Result::failure(['code' => 404]);

            expect($result->getErrorOrElse('default'))->toBe(['code' => 404]);
        });

        it('returns null when error is null', function (): void {
            $result = Result::failure(null);

            expect($result->getErrorOrElse('default'))->toBeNull();
        });
    });

    describe('getErrorOrThrow', function (): void {
        it('returns error', function (): void {
            $result = Result::failure('error message');

            expect($result->getErrorOrThrow())->toBe('error message');
        });

        it('returns exception error', function (): void {
            $exception = new RuntimeException('oops');
            $result = Result::failure($exception);

            expect($result->getErrorOrThrow())->toBe($exception);
        });

        it('returns null when error is null', function (): void {
            $result = Result::failure(null);

            expect($result->getErrorOrThrow())->toBeNull();
        });
    });
});
