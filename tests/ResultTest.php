<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;

describe('Result::success', function (): void {
    test('creates Success instance', function (): void {
        $result = Result::success(42);

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->isSuccess())->toBeTrue();
        expect($result->isFailure())->toBeFalse();
    });

    test('handles null value', function (): void {
        $result = Result::success(null);

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse('default'))->toBeNull();
    });
});

describe('Result::failure', function (): void {
    test('creates Failure instance', function (): void {
        $result = Result::failure('error');

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->isSuccess())->toBeFalse();
        expect($result->isFailure())->toBeTrue();
    });

    test('handles null error', function (): void {
        $result = Result::failure(null);

        expect($result)->toBeInstanceOf(Failure::class);
    });
});

describe('Result::try', function (): void {
    test('returns Success on normal execution', function (): void {
        $result = Result::try(fn () => 42);

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(42);
    });

    test('catches exception and returns Failure', function (): void {
        $exception = new RuntimeException('oops');
        $result = Result::try(fn () => throw $exception);

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->isFailure())->toBeTrue();
    });

    test('catches Error and returns Failure', function (): void {
        $result = Result::try(fn () => throw new Error('fatal'));

        expect($result)->toBeInstanceOf(Failure::class);
    });
});
