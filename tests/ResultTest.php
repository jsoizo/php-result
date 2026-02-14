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
        $result = Result::binding(function () {
            /** @var string $name */
            $name = yield Result::success('John');
            /** @var int $age */
            $age = yield Result::success(30);

            return "{$name} is {$age} years old";
        });

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(''))->toBe('John is 30 years old');
    });

    it('can use previous values in subsequent yields', function (): void {
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
        $result = Result::binding(function () {
            /** @var int $x */
            $x = yield Result::success(42);

            return $x;
        });

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(42);
    });
});

describe('Result::accumulate2', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate2(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn (int $a, int $b) => $a + $b
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(3);
    });

    it('returns Failure with single error when one fails', function (): void {
        $result = Result::accumulate2(
            fn () => Result::success(1),
            fn () => Result::failure('error2'),
            fn (int $a, int $b) => $a + $b
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['error2']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate2(
            fn () => Result::failure('error1'),
            fn () => Result::failure('error2'),
            fn (int $a, int $b) => $a + $b
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['error1', 'error2']);
    });

    it('does not call transform when any Result is Failure', function (): void {
        $called = false;
        $validate = fn (int $x): Result => $x > 0 ? Result::success($x) : Result::failure('must be positive');
        Result::accumulate2(
            fn () => Result::success(1),
            fn () => $validate(-1),
            function (int $a, int $b) use (&$called): int {
                $called = true;

                return $a + $b;
            }
        );

        expect($called)->toBeFalse();
    });

    it('works with different value types', function (): void {
        $result = Result::accumulate2(
            fn () => Result::success('hello'),
            fn () => Result::success(42),
            fn (string $s, int $n) => "{$s}: {$n}"
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(''))->toBe('hello: 42');
    });

    it('preserves error order matching argument order', function (): void {
        $result = Result::accumulate2(
            fn () => Result::failure('first'),
            fn () => Result::failure('second'),
            fn (int $a, int $b) => $a + $b
        );

        expect($result->getErrorOrElse([]))->toBe(['first', 'second']);
    });
});

describe('Result::accumulate3', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate3(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn (int $a, int $b, int $c) => $a + $b + $c
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(6);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate3(
            fn () => Result::success(1),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn (int $a, int $b, int $c) => $a + $b + $c
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e2', 'e3']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate3(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn (int $a, int $b, int $c) => $a + $b + $c
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3']);
    });
});

describe('Result::accumulate4', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate4(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn (int $a, int $b, int $c, int $d) => $a + $b + $c + $d
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(10);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate4(
            fn () => Result::success(1),
            fn () => Result::failure('e2'),
            fn () => Result::success(3),
            fn () => Result::failure('e4'),
            fn (int $a, int $b, int $c, int $d) => $a + $b + $c + $d
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e2', 'e4']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate4(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn () => Result::failure('e4'),
            fn (int $a, int $b, int $c, int $d) => $a + $b + $c + $d
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3', 'e4']);
    });
});

describe('Result::accumulate5', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate5(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn () => Result::success(5),
            fn (int $a, int $b, int $c, int $d, int $e) => $a + $b + $c + $d + $e
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(15);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate5(
            fn () => Result::failure('e1'),
            fn () => Result::success(2),
            fn () => Result::failure('e3'),
            fn () => Result::success(4),
            fn () => Result::failure('e5'),
            fn (int $a, int $b, int $c, int $d, int $e) => $a + $b + $c + $d + $e
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e3', 'e5']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate5(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn () => Result::failure('e4'),
            fn () => Result::failure('e5'),
            fn (int $a, int $b, int $c, int $d, int $e) => $a + $b + $c + $d + $e
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3', 'e4', 'e5']);
    });
});

describe('Result::accumulate6', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate6(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn () => Result::success(5),
            fn () => Result::success(6),
            fn (int $a, int $b, int $c, int $d, int $e, int $f) => $a + $b + $c + $d + $e + $f
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(21);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate6(
            fn () => Result::failure('e1'),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::failure('e4'),
            fn () => Result::success(5),
            fn () => Result::failure('e6'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f) => $a + $b + $c + $d + $e + $f
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e4', 'e6']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate6(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn () => Result::failure('e4'),
            fn () => Result::failure('e5'),
            fn () => Result::failure('e6'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f) => $a + $b + $c + $d + $e + $f
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3', 'e4', 'e5', 'e6']);
    });
});

describe('Result::accumulate7', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate7(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn () => Result::success(5),
            fn () => Result::success(6),
            fn () => Result::success(7),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g) => $a + $b + $c + $d + $e + $f + $g
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(28);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate7(
            fn () => Result::success(1),
            fn () => Result::failure('e2'),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn () => Result::failure('e5'),
            fn () => Result::success(6),
            fn () => Result::failure('e7'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g) => $a + $b + $c + $d + $e + $f + $g
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e2', 'e5', 'e7']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate7(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn () => Result::failure('e4'),
            fn () => Result::failure('e5'),
            fn () => Result::failure('e6'),
            fn () => Result::failure('e7'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g) => $a + $b + $c + $d + $e + $f + $g
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3', 'e4', 'e5', 'e6', 'e7']);
    });
});

describe('Result::accumulate8', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate8(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn () => Result::success(5),
            fn () => Result::success(6),
            fn () => Result::success(7),
            fn () => Result::success(8),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h) => $a + $b + $c + $d + $e + $f + $g + $h
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(36);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate8(
            fn () => Result::failure('e1'),
            fn () => Result::success(2),
            fn () => Result::failure('e3'),
            fn () => Result::success(4),
            fn () => Result::success(5),
            fn () => Result::failure('e6'),
            fn () => Result::success(7),
            fn () => Result::failure('e8'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h) => $a + $b + $c + $d + $e + $f + $g + $h
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e3', 'e6', 'e8']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate8(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn () => Result::failure('e4'),
            fn () => Result::failure('e5'),
            fn () => Result::failure('e6'),
            fn () => Result::failure('e7'),
            fn () => Result::failure('e8'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h) => $a + $b + $c + $d + $e + $f + $g + $h
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3', 'e4', 'e5', 'e6', 'e7', 'e8']);
    });
});

describe('Result::accumulate9', function (): void {
    it('returns Success with transformed value when all are Success', function (): void {
        $result = Result::accumulate9(
            fn () => Result::success(1),
            fn () => Result::success(2),
            fn () => Result::success(3),
            fn () => Result::success(4),
            fn () => Result::success(5),
            fn () => Result::success(6),
            fn () => Result::success(7),
            fn () => Result::success(8),
            fn () => Result::success(9),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h, int $i) => $a + $b + $c + $d + $e + $f + $g + $h + $i
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(0))->toBe(45);
    });

    it('collects errors from failed Results', function (): void {
        $result = Result::accumulate9(
            fn () => Result::success(1),
            fn () => Result::failure('e2'),
            fn () => Result::success(3),
            fn () => Result::failure('e4'),
            fn () => Result::success(5),
            fn () => Result::success(6),
            fn () => Result::failure('e7'),
            fn () => Result::success(8),
            fn () => Result::failure('e9'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h, int $i) => $a + $b + $c + $d + $e + $f + $g + $h + $i
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e2', 'e4', 'e7', 'e9']);
    });

    it('collects all errors when all fail', function (): void {
        $result = Result::accumulate9(
            fn () => Result::failure('e1'),
            fn () => Result::failure('e2'),
            fn () => Result::failure('e3'),
            fn () => Result::failure('e4'),
            fn () => Result::failure('e5'),
            fn () => Result::failure('e6'),
            fn () => Result::failure('e7'),
            fn () => Result::failure('e8'),
            fn () => Result::failure('e9'),
            fn (int $a, int $b, int $c, int $d, int $e, int $f, int $g, int $h, int $i) => $a + $b + $c + $d + $e + $f + $g + $h + $i
        );

        expect($result)->toBeInstanceOf(Failure::class);
        expect($result->getErrorOrElse([]))->toBe(['e1', 'e2', 'e3', 'e4', 'e5', 'e6', 'e7', 'e8', 'e9']);
    });

    it('passes all 9 arguments correctly to transform', function (): void {
        $result = Result::accumulate9(
            fn () => Result::success('a'),
            fn () => Result::success('b'),
            fn () => Result::success('c'),
            fn () => Result::success('d'),
            fn () => Result::success('e'),
            fn () => Result::success('f'),
            fn () => Result::success('g'),
            fn () => Result::success('h'),
            fn () => Result::success('i'),
            fn (string $a, string $b, string $c, string $d, string $e, string $f, string $g, string $h, string $i) => $a . $b . $c . $d . $e . $f . $g . $h . $i
        );

        expect($result)->toBeInstanceOf(Success::class);
        expect($result->getOrElse(''))->toBe('abcdefghi');
    });
});
