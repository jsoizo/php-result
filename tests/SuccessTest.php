<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\ResultException;
use Jsoizo\Result\Success;

describe('Success', function (): void {
    describe('isSuccess', function (): void {
        it('always returns true', function (): void {
            $result = Result::success('value');

            // @phpstan-ignore method.alreadyNarrowedType
            expect($result->isSuccess())->toBeTrue();
        });
    });

    describe('isFailure', function (): void {
        it('always returns false', function (): void {
            $result = Result::success('value');

            // @phpstan-ignore method.impossibleType
            expect($result->isFailure())->toBeFalse();
        });
    });

    describe('getOrElse', function (): void {
        it('returns value', function (): void {
            $result = Result::success(42);

            expect($result->getOrElse(0))->toBe(42);
        });

        it('ignores default for non-null value', function (): void {
            $result = Result::success('value');

            expect($result->getOrElse('default'))->toBe('value');
        });

        it('returns null when value is null', function (): void {
            $result = Result::success(null);

            expect($result->getOrElse('default'))->toBeNull();
        });
    });

    describe('getOrThrow', function (): void {
        it('returns value', function (): void {
            $result = Result::success(42);

            expect($result->getOrThrow())->toBe(42);
        });

        it('returns null when value is null', function (): void {
            $result = Result::success(null);

            expect($result->getOrThrow())->toBeNull();
        });
    });

    describe('map', function (): void {
        it('transforms value', function (): void {
            $result = Result::success(2)->map(fn ($x) => $x * 3);

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(6);
        });

        it('chains multiple maps', function (): void {
            $result = Result::success(2)
                ->map(fn ($x) => $x * 3)
                ->map(fn ($x) => $x + 1);

            expect($result->getOrElse(0))->toBe(7);
        });

        it('transforms type', function (): void {
            $result = Result::success(42)->map(fn ($x) => (string) $x);

            expect($result->getOrElse(''))->toBe('42');
        });
    });

    describe('mapError', function (): void {
        it('does nothing', function (): void {
            $result = Result::success(42)->mapError(fn ($e) => 'changed');

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(42);
        });

        it('callback is not called', function (): void {
            $called = false;
            // @phpstan-ignore method.resultUnused (Testing that callback is not called)
            Result::success(42)->mapError(function ($e) use (&$called): string {
                $called = true;

                return 'changed';
            });

            expect($called)->toBeFalse();
        });
    });

    describe('flatMap', function (): void {
        it('chains Results', function (): void {
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::success($x * 3));

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(6);
        });

        it('propagates Failure', function (): void {
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::failure('error'));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->isFailure())->toBeTrue();
        });

        it('chains multiple flatMaps', function (): void {
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::success($x * 3))
                ->flatMap(fn ($x) => Result::success($x + 1));

            expect($result->getOrElse(0))->toBe(7);
        });

        it('stops at first Failure', function (): void {
            $secondCalled = false;
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::failure('first error'))
                ->flatMap(function ($x) use (&$secondCalled) {
                    $secondCalled = true;

                    return Result::success($x * 3);
                });

            expect($result->isFailure())->toBeTrue();
            expect($secondCalled)->toBeFalse();
        });
    });

    describe('getErrorOrElse', function (): void {
        it('returns default', function (): void {
            $result = Result::success(42);

            expect($result->getErrorOrElse('default error'))->toBe('default error');
        });

        it('returns null default', function (): void {
            $result = Result::success(42);

            expect($result->getErrorOrElse(null))->toBeNull();
        });
    });

    describe('getErrorOrThrow', function (): void {
        it('throws ResultException', function (): void {
            $result = Result::success(42);

            expect(fn () => $result->getErrorOrThrow())->toThrow(ResultException::class);
        });

        it('throws ResultException with message', function (): void {
            $result = Result::success('value');

            expect(fn () => $result->getErrorOrThrow())
                ->toThrow(ResultException::class, 'Result is a success');
        });
    });

    describe('recover', function (): void {
        it('does nothing', function (): void {
            $result = Result::success(42)->recover(fn ($e) => 0);

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(42);
        });

        it('callback is not called', function (): void {
            $called = false;
            // @phpstan-ignore method.resultUnused (Testing that callback is not called)
            Result::success(42)->recover(function ($e) use (&$called): int {
                $called = true;

                return 0;
            });

            expect($called)->toBeFalse();
        });
    });

    describe('recoverWith', function (): void {
        it('does nothing', function (): void {
            $result = Result::success(42)->recoverWith(fn ($e) => Result::success(0));

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(42);
        });

        it('callback is not called', function (): void {
            $called = false;
            // @phpstan-ignore method.resultUnused (Testing that callback is not called)
            Result::success(42)->recoverWith(function ($e) use (&$called) {
                $called = true;

                return Result::success(0);
            });

            expect($called)->toBeFalse();
        });
    });
});
