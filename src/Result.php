<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * A type-safe Result monad representing either a success value or an error.
 *
 * Result is an abstract base class that encapsulates the outcome of an operation
 * that may fail. It provides a functional approach to error handling, avoiding
 * exceptions for expected failures while maintaining full type safety with PHPStan.
 *
 * @template T The type of the success value
 * @template E The type of the error value
 *
 * @see Success
 * @see Failure
 */
abstract class Result
{
    /**
     * Creates a Success instance containing the given value.
     *
     * Use this factory method to wrap a successful computation result.
     * The error type is set to 'never' since a Success can never contain an error.
     *
     * @template TValue The type of the success value
     * @param TValue $value The success value to wrap
     * @return Success<TValue, never> A Success instance containing the value
     */
    public static function success(mixed $value): Success
    {
        /** @var Success<TValue, never> */
        return new Success($value);
    }

    /**
     * Creates a Failure instance containing the given error.
     *
     * Use this factory method to represent a failed computation.
     * The success type is set to 'never' since a Failure can never contain a value.
     *
     * @template TError The type of the error value
     * @param TError $error The error value to wrap
     * @return Failure<never, TError> A Failure instance containing the error
     */
    public static function failure(mixed $error): Failure
    {
        /** @var Failure<never, TError> */
        return new Failure($error);
    }

    /**
     * Executes a callable and captures any thrown exception as a Failure.
     *
     * This method wraps potentially throwing code in a try-catch block,
     * returning a Success with the result if no exception is thrown,
     * or a Failure containing the caught Throwable otherwise.
     *
     * @template TValue The return type of the callable
     * @param callable(): TValue $fn The callable to execute
     * @return Result<TValue, \Throwable> Success with the return value, or Failure with the exception
     */
    public static function catch(callable $fn): Result
    {
        try {
            return self::success($fn());
        } catch (\Throwable $e) {
            return self::failure($e);
        }
    }

    /**
     * Checks whether this Result is a Success.
     *
     * @return bool True if this is a Success, false if this is a Failure
     */
    abstract public function isSuccess(): bool;

    /**
     * Checks whether this Result is a Failure.
     *
     * @return bool True if this is a Failure, false if this is a Success
     */
    abstract public function isFailure(): bool;

    /**
     * Returns the success value, or the given default if this is a Failure.
     *
     * Provides a safe way to extract the value without risking exceptions.
     * For Success, returns the contained value. For Failure, returns the default.
     *
     * @template TDefault The type of the default value
     * @param TDefault $default The value to return if this is a Failure
     * @return T|TDefault The success value or the default
     */
    abstract public function getOrElse(mixed $default): mixed;

    /**
     * Returns the success value, or throws an exception if this is a Failure.
     *
     * For Success, returns the contained value. For Failure, throws the error
     * if it is a Throwable, or wraps it in a RuntimeException otherwise.
     *
     * @return T The success value
     * @throws \Throwable
     */
    abstract public function getOrThrow(): mixed;

    /**
     * Transforms the success value using the given function.
     *
     * If this is a Success, applies the function to the value and wraps the result
     * in a new Success. If this is a Failure, returns the Failure unchanged.
     *
     * @template U The type of the transformed value
     * @param callable(T): U $fn The transformation function
     * @return Result<U, E> A new Result with the transformed value, or the original Failure
     */
    abstract public function map(callable $fn): Result;

    /**
     * Transforms the error value using the given function.
     *
     * If this is a Failure, applies the function to the error and wraps the result
     * in a new Failure. If this is a Success, returns the Success unchanged.
     *
     * @template F The type of the transformed error
     * @param callable(E): F $fn The error transformation function
     * @return Result<T, F> A new Result with the transformed error, or the original Success
     */
    abstract public function mapError(callable $fn): Result;

    /**
     * Chains a Result-returning operation on the success value.
     *
     * If this is a Success, applies the function to the value and returns the
     * resulting Result directly (without wrapping). If this is a Failure,
     * returns the Failure unchanged.
     *
     * @template U The success type of the resulting Result
     * @param callable(T): Result<U, E> $fn The function returning a new Result
     * @return Result<U, E> The Result from the function, or the original Failure
     */
    abstract public function flatMap(callable $fn): Result;

    /**
     * Returns the error value, or the given default if this is a Success.
     *
     * Provides a safe way to extract the error without risking exceptions.
     * For Failure, returns the contained error. For Success, returns the default.
     *
     * @template TDefault The type of the default value
     * @param TDefault $default The value to return if this is a Success
     * @return E|TDefault The error value or the default
     */
    abstract public function getErrorOrElse(mixed $default): mixed;

    /**
     * Returns the error value, or throws an exception if this is a Success.
     *
     * For Failure, returns the contained error. For Success, throws a LogicException
     * since attempting to get an error from a successful result is a programming error.
     *
     * @return E The error value
     * @throws \LogicException If this is a Success
     */
    abstract public function getErrorOrThrow(): mixed;
}
