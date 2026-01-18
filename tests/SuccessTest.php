<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;

describe('Success', function (): void {
    describe('getOrElse', function (): void {
        test('returns value', function (): void {
            $result = Result::success(42);

            expect($result->getOrElse(0))->toBe(42);
        });

        test('ignores default for non-null value', function (): void {
            $result = Result::success('value');

            expect($result->getOrElse('default'))->toBe('value');
        });

        test('returns null when value is null', function (): void {
            $result = Result::success(null);

            expect($result->getOrElse('default'))->toBeNull();
        });
    });

    describe('getOrThrow', function (): void {
        test('returns value', function (): void {
            $result = Result::success(42);

            expect($result->getOrThrow())->toBe(42);
        });

        test('returns null when value is null', function (): void {
            $result = Result::success(null);

            expect($result->getOrThrow())->toBeNull();
        });
    });

    describe('map', function (): void {
        test('transforms value', function (): void {
            $result = Result::success(2)->map(fn ($x) => $x * 3);

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(6);
        });

        test('chains multiple maps', function (): void {
            $result = Result::success(2)
                ->map(fn ($x) => $x * 3)
                ->map(fn ($x) => $x + 1);

            expect($result->getOrElse(0))->toBe(7);
        });

        test('transforms type', function (): void {
            $result = Result::success(42)->map(fn ($x) => (string) $x);

            expect($result->getOrElse(''))->toBe('42');
        });
    });

    describe('mapError', function (): void {
        test('does nothing', function (): void {
            $result = Result::success(42)->mapError(fn ($e) => 'changed');

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(42);
        });

        test('callback is not called', function (): void {
            $called = false;
            Result::success(42)->mapError(function ($e) use (&$called): string {
                $called = true;

                return 'changed';
            });

            expect($called)->toBeFalse();
        });
    });

    describe('flatMap', function (): void {
        test('chains Results', function (): void {
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::success($x * 3));

            expect($result)->toBeInstanceOf(Success::class);
            expect($result->getOrElse(0))->toBe(6);
        });

        test('propagates Failure', function (): void {
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::failure('error'));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->isFailure())->toBeTrue();
        });

        test('chains multiple flatMaps', function (): void {
            $result = Result::success(2)
                ->flatMap(fn ($x) => Result::success($x * 3))
                ->flatMap(fn ($x) => Result::success($x + 1));

            expect($result->getOrElse(0))->toBe(7);
        });

        test('stops at first Failure', function (): void {
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
});
