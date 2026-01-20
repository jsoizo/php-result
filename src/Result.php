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
 * @phpstan-sealed Success<T, E>|Failure<T, E>
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
     * Enables monad comprehension syntax using generators.
     *
     * This method allows chaining multiple Result-returning operations in an
     * imperative style, avoiding deeply nested flatMap calls. Each `yield`
     * unwraps a Result value, and if any Result is a Failure, the entire
     * binding short-circuits and returns that Failure.
     *
     * Usage:
     * ```php
     * $result = Result::binding(function() {
     *     $x = yield $resultA; // unwraps Success or short-circuits on Failure
     *     $y = yield $resultB;
     *     return $x + $y;
     * });
     * ```
     *
     * @template TValue The return type of the generator
     * @template TError The error type
     * @param callable(): \Generator<int, Result<mixed, TError>, mixed, TValue> $fn
     * @return Result<TValue, TError>
     */
    public static function binding(callable $fn): Result
    {
        $generator = $fn();

        while ($generator->valid()) {
            $result = $generator->current();

            if ($result instanceof Failure) {
                return $result;
            }

            if ($result instanceof Success) {
                $generator->send($result->getOrThrow());
            } else {
                $generator->next();
            }
        }

        return self::success($generator->getReturn());
    }

    /**
     * Checks whether this Result is a Success.
     *
     * @phpstan-assert-if-true Success<T, E> $this
     * @phpstan-assert-if-false Failure<T, E> $this
     * @return bool True if this is a Success, false if this is a Failure
     */
    abstract public function isSuccess(): bool;

    /**
     * Checks whether this Result is a Failure.
     *
     * @phpstan-assert-if-true Failure<T, E> $this
     * @phpstan-assert-if-false Success<T, E> $this
     * @return bool True if this is a Failure, false if this is a Success
     */
    abstract public function isFailure(): bool;

    /**
     * Returns the success value, or the given default if this is a Failure.
     *
     * Provides a safe way to extract the value without risking exceptions.
     * For Success, returns the contained value. For Failure, returns the default.
     *
     * @param T $default The value to return if this is a Failure
     * @return T The success value or the default
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
     * @template E1 The type of the transformed error
     * @param callable(E): E1 $fn The error transformation function
     * @return Result<T, E1> A new Result with the transformed error, or the original Success
     */
    abstract public function mapError(callable $fn): Result;

    /**
     * Chains a Result-returning operation on the success value.
     *
     * If this is a Success, applies the function to the value and returns the
     * resulting Result directly (without wrapping). If this is a Failure,
     * returns the Failure unchanged.
     *
     * @template T1 The success type of the resulting Result
     * @template E1 The error type of the resulting Result
     * @param callable(T): Result<T1, E1> $fn The function returning a new Result
     * @return Result<T1, E1> The Result from the function, or the original Failure
     */
    abstract public function flatMap(callable $fn): Result;

    /**
     * Returns the error value, or the given default if this is a Success.
     *
     * Provides a safe way to extract the error without risking exceptions.
     * For Failure, returns the contained error. For Success, returns the default.
     *
     * @param E $default The value to return if this is a Success
     * @return E The error value or the default
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

    /**
     * Applies one of two functions depending on whether this is a Success or Failure.
     *
     * This method allows handling both cases in a single expression, transforming
     * the Result into a single value of type U. Both callbacks must return the same type.
     *
     * @template U The return type of both callbacks
     * @param callable(E): U $onFailure Function to apply if this is a Failure
     * @param callable(T): U $onSuccess Function to apply if this is a Success
     * @return U The result of applying the appropriate function
     */
    abstract public function fold(callable $onFailure, callable $onSuccess): mixed;

    /**
     * Recovers from a Failure by transforming the error into a success value.
     *
     * If this is a Failure, applies the function to the error and wraps the result
     * in a new Success. If this is a Success, returns the Success unchanged.
     *
     * @param callable(E): T $fn The recovery function
     * @return Result<T, E> A new Success with the recovered value, or the original Success
     */
    abstract public function recover(callable $fn): Result;

    /**
     * Recovers from a Failure by transforming the error into a new Result.
     *
     * If this is a Failure, applies the function to the error and returns the
     * resulting Result directly. If this is a Success, returns the Success unchanged.
     * This allows chaining fallback operations or changing the error type.
     *
     * @template E1 The error type of the resulting Result
     * @param callable(E): Result<T, E1> $fn The recovery function returning a new Result
     * @return Result<T, E1> The Result from the function, or the original Success
     */
    abstract public function recoverWith(callable $fn): Result;

    /**
     * Executes a side effect with the success value without modifying the Result.
     *
     * If this is a Success, calls the function with the value and returns the
     * same Success. If this is a Failure, returns the Failure unchanged.
     * Useful for logging, debugging, or other side effects in a chain.
     *
     * @param callable(T): void $fn The function to execute with the success value
     * @return Result<T, E> The same Result unchanged
     */
    abstract public function tap(callable $fn): Result;

    /**
     * Executes a side effect with the error value without modifying the Result.
     *
     * If this is a Failure, calls the function with the error and returns the
     * same Failure. If this is a Success, returns the Success unchanged.
     * Useful for logging, debugging, or other side effects in a chain.
     *
     * @param callable(E): void $fn The function to execute with the error value
     * @return Result<T, E> The same Result unchanged
     */
    abstract public function tapError(callable $fn): Result;

    /**
     * Returns the success value, or null if this is a Failure.
     *
     * Provides a convenient way to extract the value with null as the default.
     * For Success, returns the contained value. For Failure, returns null.
     *
     * @return T|null The success value or null
     */
    abstract public function getOrNull(): mixed;

    /**
     * Flattens a nested Result into a single Result.
     *
     * If the success value is itself a Result, unwraps it and returns the inner Result.
     * If the success value is not a Result, returns this Result unchanged.
     * For Failure, returns the Failure unchanged.
     *
     * @return Result<mixed, mixed> The flattened Result
     */
    abstract public function flatten(): Result;
}
