<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * @template T
 * @template E
 */
abstract class Result
{
    /**
     * @template TValue
     * @param TValue $value
     * @return Success<TValue, never>
     */
    public static function success(mixed $value): Success
    {
        /** @var Success<TValue, never> */
        return new Success($value);
    }

    /**
     * @template TError
     * @param TError $error
     * @return Failure<never, TError>
     */
    public static function failure(mixed $error): Failure
    {
        /** @var Failure<never, TError> */
        return new Failure($error);
    }

    /**
     * @template TValue
     * @param callable(): TValue $fn
     * @return Result<TValue, \Throwable>
     */
    public static function catch(callable $fn): Result
    {
        try {
            return self::success($fn());
        } catch (\Throwable $e) {
            return self::failure($e);
        }
    }

    abstract public function isSuccess(): bool;

    abstract public function isFailure(): bool;

    /**
     * @template TDefault
     * @param TDefault $default
     * @return T|TDefault
     */
    abstract public function getOrElse(mixed $default): mixed;

    /**
     * @return T
     * @throws \Throwable
     */
    abstract public function getOrThrow(): mixed;

    /**
     * @template U
     * @param callable(T): U $fn
     * @return Result<U, E>
     */
    abstract public function map(callable $fn): Result;

    /**
     * @template F
     * @param callable(E): F $fn
     * @return Result<T, F>
     */
    abstract public function mapError(callable $fn): Result;

    /**
     * @template U
     * @param callable(T): Result<U, E> $fn
     * @return Result<U, E>
     */
    abstract public function flatMap(callable $fn): Result;
}
