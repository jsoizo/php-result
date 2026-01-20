<?php

declare(strict_types=1);

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\ResultException;

describe('Failure', function (): void {
    describe('isSuccess', function (): void {
        it('always returns false', function (): void {
            $result = Result::failure('error');

            // @phpstan-ignore method.impossibleType
            expect($result->isSuccess())->toBeFalse();
        });
    });

    describe('isFailure', function (): void {
        it('always returns true', function (): void {
            $result = Result::failure('error');

            // @phpstan-ignore method.alreadyNarrowedType
            expect($result->isFailure())->toBeTrue();
        });
    });

    describe('getOrElse', function (): void {
        it('returns default', function (): void {
            /** @var Failure<int, string> $result */
            $result = Result::failure('error');

            expect($result->getOrElse(42))->toBe(42);
        });

        it('returns null default', function (): void {
            /** @var Failure<null, string> $result */
            $result = Result::failure('error');

            expect($result->getOrElse(null))->toBeNull();
        });
    });

    describe('get', function (): void {
        it('throws Throwable error', function (): void {
            $exception = new RuntimeException('oops');
            $result = Result::failure($exception);

            expect(fn () => $result->get())->toThrow(RuntimeException::class, 'oops');
        });

        it('throws ResultException for non-Throwable error', function (): void {
            $result = Result::failure('error string');

            expect(fn () => $result->get())->toThrow(ResultException::class);
        });

        it('throws ResultException for array error', function (): void {
            $result = Result::failure(['code' => 404, 'message' => 'Not Found']);

            expect(fn () => $result->get())->toThrow(ResultException::class);
        });
    });

    describe('map', function (): void {
        it('does nothing', function (): void {
            $result = Result::failure('error')->map(fn ($x) => $x * 2);

            expect($result)->toBeInstanceOf(Failure::class);
        });

        it('callback is not called', function (): void {
            $called = false;
            // @phpstan-ignore method.resultUnused (Testing that callback is not called)
            Result::failure('error')->map(function ($x) use (&$called): int {
                $called = true;

                // @phpstan-ignore return.never (Callback is never called on Failure)
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
            /** @var Failure<null, string> $result */
            expect($result->getOrElse(null))->toBeNull();
        });

        it('changes error type', function (): void {
            $result = Result::failure('not found')
                ->mapError(fn ($e) => new RuntimeException($e));

            expect($result)->toBeInstanceOf(Failure::class);
            expect(fn () => $result->get())->toThrow(RuntimeException::class, 'not found');
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
            // @phpstan-ignore method.resultUnused (Testing that callback is not called)
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

            expect(fn () => $result->get())->toThrow(RuntimeException::class, 'original');
        });
    });

    describe('getErrorOrElse', function (): void {
        it('returns error', function (): void {
            $result = Result::failure('error message');

            expect($result->getErrorOrElse('default'))->toBe('error message');
        });

        it('ignores default for error value', function (): void {
            $result = Result::failure(['code' => 404]);

            expect($result->getErrorOrElse(['code' => 0]))->toBe(['code' => 404]);
        });

        it('returns null when error is null', function (): void {
            $result = Result::failure(null);

            expect($result->getErrorOrElse(null))->toBeNull();
        });
    });

    describe('getError', function (): void {
        it('returns error', function (): void {
            $result = Result::failure('error message');

            expect($result->getError())->toBe('error message');
        });

        it('returns exception error', function (): void {
            $exception = new RuntimeException('oops');
            $result = Result::failure($exception);

            expect($result->getError())->toBe($exception);
        });

        it('returns null when error is null', function (): void {
            $result = Result::failure(null);

            expect($result->getError())->toBeNull();
        });
    });

    describe('recover', function (): void {
        it('transforms error to value', function (): void {
            $result = Result::failure('error')->recover(fn ($e) => 42);

            expect($result)->toBeInstanceOf(\Jsoizo\Result\Success::class);
            expect($result->getOrElse(0))->toBe(42);
        });

        it('receives error value', function (): void {
            $result = Result::failure('error message')
                ->recover(fn ($e) => strlen($e));

            expect($result->getOrElse(0))->toBe(13);
        });

        it('can use error to compute recovery value', function (): void {
            $result = Result::failure(new RuntimeException('not found'))
                ->recover(fn ($e) => 'default value');

            expect($result)->toBeInstanceOf(\Jsoizo\Result\Success::class);
            expect($result->getOrElse(''))->toBe('default value');
        });
    });

    describe('recoverWith', function (): void {
        it('transforms error to Result', function (): void {
            $result = Result::failure('error')
                ->recoverWith(fn ($e) => Result::success(42));

            expect($result)->toBeInstanceOf(\Jsoizo\Result\Success::class);
            expect($result->getOrElse(0))->toBe(42);
        });

        it('can return Failure for different error', function (): void {
            $result = Result::failure('first error')
                ->recoverWith(fn ($e) => Result::failure('second error'));

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->getErrorOrElse(''))->toBe('second error');
        });

        it('chains multiple recoverWith', function (): void {
            /** @var Failure<string, string> $failure */
            $failure = Result::failure('error');
            $result = $failure
                ->recoverWith(function ($e): Result {
                    /** @var Failure<string, string> */
                    return Result::failure('still error');
                })
                ->recoverWith(fn ($e) => Result::success($e));

            expect($result)->toBeInstanceOf(\Jsoizo\Result\Success::class);
            expect($result->getOrElse(''))->toBe('still error');
        });

        it('stops at first Success', function (): void {
            $secondCalled = false;
            $result = Result::failure('error')
                ->recoverWith(fn ($e) => Result::success('recovered'))
                ->recoverWith(function ($e) use (&$secondCalled) {
                    $secondCalled = true;

                    return Result::success('not reached');
                });

            expect($result)->toBeInstanceOf(\Jsoizo\Result\Success::class);
            expect($result->getOrElse(''))->toBe('recovered');
            expect($secondCalled)->toBeFalse();
        });

        it('can change error type', function (): void {
            $result = Result::failure('string error')
                ->recoverWith(fn ($e) => Result::failure(new RuntimeException($e)));

            expect($result)->toBeInstanceOf(Failure::class);
            expect(fn () => $result->get())->toThrow(RuntimeException::class, 'string error');
        });
    });

    describe('tap', function (): void {
        it('does not execute callback and returns same Failure', function (): void {
            $called = false;
            $result = Result::failure('error')->tap(function ($v) use (&$called): void {
                $called = true;
            });

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->getErrorOrElse(''))->toBe('error');
            expect($called)->toBeFalse();
        });
    });

    describe('tapError', function (): void {
        it('executes callback with error and returns same Failure', function (): void {
            $captured = null;
            $result = Result::failure('error message')->tapError(function ($e) use (&$captured): void {
                $captured = $e;
            });

            expect($result)->toBeInstanceOf(Failure::class);
            expect($result->getErrorOrElse(''))->toBe('error message');
            expect($captured)->toBe('error message');
        });

        it('allows chaining', function (): void {
            $log = [];
            $result = Result::failure('error')
                ->tapError(function ($e) use (&$log): void {
                    $log[] = "error: $e";
                })
                ->mapError(fn ($e) => strtoupper($e))
                ->tapError(function ($e) use (&$log): void {
                    $log[] = "mapped: $e";
                });

            expect($result->getErrorOrElse(''))->toBe('ERROR');
            expect($log)->toBe(['error: error', 'mapped: ERROR']);
        });
    });

    describe('getOrNull', function (): void {
        it('returns null', function (): void {
            $result = Result::failure('error');

            expect($result->getOrNull())->toBeNull();
        });

        it('returns null regardless of error type', function (): void {
            $result = Result::failure(new RuntimeException('error'));

            expect($result->getOrNull())->toBeNull();
        });
    });

    describe('flatten', function (): void {
        it('returns same Failure unchanged', function (): void {
            $result = Result::failure('error');
            $flat = $result->flatten();

            expect($flat)->toBeInstanceOf(Failure::class);
            expect($flat->getErrorOrElse(''))->toBe('error');
        });

        it('preserves error type', function (): void {
            $exception = new RuntimeException('error');
            $result = Result::failure($exception);
            $flat = $result->flatten();

            expect($flat)->toBeInstanceOf(Failure::class);
            expect($flat->getErrorOrElse(new RuntimeException('')))->toBe($exception);
        });
    });
});
