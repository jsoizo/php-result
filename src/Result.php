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
 * @template-covariant T The type of the success value
 * @template-covariant E The type of the error value
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
     * Passing $exceptionClass captures only that class (and its subclasses)
     * and narrows the error type accordingly; any other Throwable is rethrown.
     *
     * @template TValue The return type of the callable
     * @template TException of \Throwable = \Throwable The exception class to capture
     * @param callable(): TValue $fn The callable to execute
     * @param class-string<TException> $exceptionClass The exception class to capture as a Failure
     * @return Result<TValue, TException> Success with the return value, or Failure with the exception
     * @throws \Throwable If the thrown exception is not an instance of $exceptionClass
     */
    public static function catch(callable $fn, string $exceptionClass = \Throwable::class): Result
    {
        try {
            return self::success($fn());
        } catch (\Throwable $e) {
            if (!$e instanceof $exceptionClass) {
                throw $e;
            }

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
     * @throws ResultException If $fn does not return a Generator, or the generator yields a non-Result value
     */
    public static function binding(callable $fn): Result
    {
        $generator = $fn();

        // @phpstan-ignore instanceof.alwaysTrue (guards callables that violate the PHPDoc return type at runtime)
        if (!$generator instanceof \Generator) {
            throw new ResultException(
                'binding() callable must return a Generator, got: ' . get_debug_type($generator)
            );
        }

        while ($generator->valid()) {
            $result = $generator->current();

            if ($result instanceof Failure) {
                return $result;
            }

            if ($result instanceof Success) {
                $generator->send($result->get());
            } else {
                throw new ResultException(
                    'binding() generator must yield Result instances, got: '
                    . get_debug_type($result)
                );
            }
        }

        return self::success($generator->getReturn());
    }

    /**
     * Converts a list of Results into a Result containing a list of values.
     *
     * Collects every error instead of short-circuiting. If every Result is a
     * Success, returns a Success with values in input order. If any Result is a
     * Failure, returns a Failure with all errors in input order.
     *
     * @template T1
     * @template E1
     * @param list<Result<T1, E1>> $results
     * @return Result<list<T1>, non-empty-list<E1>>
     * @throws ResultException If any element of $results is not a Result instance
     */
    public static function accumulate(array $results): Result
    {
        $values = [];
        $errors = [];

        foreach ($results as $result) {
            // @phpstan-ignore instanceof.alwaysTrue (guards list elements that violate the PHPDoc param type at runtime)
            if (!$result instanceof Result) {
                throw new ResultException(
                    'accumulate() expects Result instances, got: ' . get_debug_type($result)
                );
            }

            if ($result->isFailure()) {
                $errors[] = $result->getError();

                continue;
            }

            $values[] = $result->get();
        }

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($values);
    }

    /**
     * Converts a list of Results into a Result containing a list of values, failing fast.
     *
     * Iterates in input order and short-circuits on the first Failure, returning
     * its error unwrapped. If every Result is a Success, returns a Success with
     * values in input order. An empty list yields a Success with an empty list.
     *
     * @template T1
     * @template E1
     * @param list<Result<T1, E1>> $results
     * @return Result<list<T1>, E1>
     * @throws ResultException If any element of $results is not a Result instance
     */
    public static function sequence(array $results): Result
    {
        $values = [];

        foreach ($results as $result) {
            // @phpstan-ignore instanceof.alwaysTrue (guards list elements that violate the PHPDoc param type at runtime)
            if (!$result instanceof Result) {
                throw new ResultException(
                    'sequence() expects Result instances, got: ' . get_debug_type($result)
                );
            }

            if ($result->isFailure()) {
                return self::failure($result->getError());
            }

            $values[] = $result->get();
        }

        return self::success($values);
    }

    /**
     * Combines two Results, collecting all errors on failure.
     *
     * Evaluates all Results without short-circuiting. If all are Success,
     * applies the transform function to their values and returns a Success.
     * If any are Failure, collects all errors into a non-empty list.
     *
     * @template T1
     * @template T2
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param callable(T1, T2): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate2(Result $r1, Result $r2, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get()));
    }

    /**
     * Combines three Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param callable(T1, T2, T3): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate3(Result $r1, Result $r2, Result $r3, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get()));
    }

    /**
     * Combines four Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param Result<T4, E1> $r4
     * @param callable(T1, T2, T3, T4): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate4(Result $r1, Result $r2, Result $r3, Result $r4, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3, $r4);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get(), $r4->get()));
    }

    /**
     * Combines five Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param Result<T4, E1> $r4
     * @param Result<T5, E1> $r5
     * @param callable(T1, T2, T3, T4, T5): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate5(Result $r1, Result $r2, Result $r3, Result $r4, Result $r5, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3, $r4, $r5);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get(), $r4->get(), $r5->get()));
    }

    /**
     * Combines six Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template T6
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param Result<T4, E1> $r4
     * @param Result<T5, E1> $r5
     * @param Result<T6, E1> $r6
     * @param callable(T1, T2, T3, T4, T5, T6): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate6(Result $r1, Result $r2, Result $r3, Result $r4, Result $r5, Result $r6, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3, $r4, $r5, $r6);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get(), $r4->get(), $r5->get(), $r6->get()));
    }

    /**
     * Combines seven Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template T6
     * @template T7
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param Result<T4, E1> $r4
     * @param Result<T5, E1> $r5
     * @param Result<T6, E1> $r6
     * @param Result<T7, E1> $r7
     * @param callable(T1, T2, T3, T4, T5, T6, T7): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate7(Result $r1, Result $r2, Result $r3, Result $r4, Result $r5, Result $r6, Result $r7, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3, $r4, $r5, $r6, $r7);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get(), $r4->get(), $r5->get(), $r6->get(), $r7->get()));
    }

    /**
     * Combines eight Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template T6
     * @template T7
     * @template T8
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param Result<T4, E1> $r4
     * @param Result<T5, E1> $r5
     * @param Result<T6, E1> $r6
     * @param Result<T7, E1> $r7
     * @param Result<T8, E1> $r8
     * @param callable(T1, T2, T3, T4, T5, T6, T7, T8): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate8(Result $r1, Result $r2, Result $r3, Result $r4, Result $r5, Result $r6, Result $r7, Result $r8, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3, $r4, $r5, $r6, $r7, $r8);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get(), $r4->get(), $r5->get(), $r6->get(), $r7->get(), $r8->get()));
    }

    /**
     * Combines nine Results, collecting all errors on failure.
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template T6
     * @template T7
     * @template T8
     * @template T9
     * @template E1
     * @template U
     * @param Result<T1, E1> $r1
     * @param Result<T2, E1> $r2
     * @param Result<T3, E1> $r3
     * @param Result<T4, E1> $r4
     * @param Result<T5, E1> $r5
     * @param Result<T6, E1> $r6
     * @param Result<T7, E1> $r7
     * @param Result<T8, E1> $r8
     * @param Result<T9, E1> $r9
     * @param callable(T1, T2, T3, T4, T5, T6, T7, T8, T9): U $transform
     * @return Result<U, non-empty-list<E1>>
     */
    public static function accumulate9(Result $r1, Result $r2, Result $r3, Result $r4, Result $r5, Result $r6, Result $r7, Result $r8, Result $r9, callable $transform): Result
    {
        $errors = self::collectErrors($r1, $r2, $r3, $r4, $r5, $r6, $r7, $r8, $r9);

        if ($errors !== []) {
            return self::failure($errors);
        }

        return self::success($transform($r1->get(), $r2->get(), $r3->get(), $r4->get(), $r5->get(), $r6->get(), $r7->get(), $r8->get(), $r9->get()));
    }

    /**
     * @template E1
     * @param Result<mixed, E1> ...$results
     * @return list<E1>
     */
    private static function collectErrors(Result ...$results): array
    {
        $errors = [];

        foreach ($results as $result) {
            if ($result instanceof Failure) {
                $errors[] = $result->getError();
            }
        }

        return $errors;
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
     * @template D
     * @param D $default The value to return if this is a Failure
     * @return T|D The success value or the default
     */
    abstract public function getOrElse(mixed $default): mixed;

    /**
     * Returns the success value, or throws an exception if this is a Failure.
     *
     * For Success, returns the contained value. For Failure, throws the error
     * if it is a Throwable, or wraps it in a ResultException otherwise.
     *
     * @return T The success value
     * @throws \Throwable
     */
    abstract public function get(): mixed;

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
     * @return Result<T1, E|E1> The Result from the function, or the original Failure
     */
    abstract public function flatMap(callable $fn): Result;

    /**
     * Returns the error value, or the given default if this is a Success.
     *
     * Provides a safe way to extract the error without risking exceptions.
     * For Failure, returns the contained error. For Success, returns the default.
     *
     * @template D
     * @param D $default The value to return if this is a Success
     * @return E|D The error value or the default
     */
    abstract public function getErrorOrElse(mixed $default): mixed;

    /**
     * Returns the error value, or throws an exception if this is a Success.
     *
     * For Failure, returns the contained error. For Success, throws a ResultException
     * since attempting to get an error from a successful result is a programming error.
     *
     * @return E The error value
     * @throws ResultException If this is a Success
     */
    abstract public function getError(): mixed;

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
     * @template T1 The type of the recovery value
     * @param callable(E): T1 $fn The recovery function
     * @return Result<T|T1, never> A new Success with the recovered value, or the original Success
     */
    abstract public function recover(callable $fn): Result;

    /**
     * Recovers from a Failure by transforming the error into a new Result.
     *
     * If this is a Failure, applies the function to the error and returns the
     * resulting Result directly. If this is a Success, returns the Success unchanged.
     * This allows chaining fallback operations or changing the error type.
     *
     * @template T1 The success type of the resulting Result
     * @template E1 The error type of the resulting Result
     * @param callable(E): Result<T1, E1> $fn The recovery function returning a new Result
     * @return Result<T|T1, E1> The Result from the function, or the original Success
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
     * Returns the error value, or null if this is a Success.
     *
     * Provides a convenient way to extract the error with null as the default.
     * For Failure, returns the contained error. For Success, returns null.
     *
     * @return E|null The error value or null
     */
    abstract public function getErrorOrNull(): mixed;

    /**
     * Flattens a nested Result into a single Result.
     *
     * If the success value is itself a Result, unwraps it and returns the inner Result.
     * If the success value is not a Result, returns this Result unchanged.
     * For Failure, returns the Failure unchanged.
     *
     * @return (T is never
     *     ? Result<never, E>
     *     : (T is \Jsoizo\Result\Result<*, *>
     *         ? \Jsoizo\Result\Result<template-type<T, \Jsoizo\Result\Result, 'T'>, E|template-type<T, \Jsoizo\Result\Result, 'E'>>
     *         : Result<T, E>))
     * @phpstan-ignore conditionalType.alwaysFalse (never is a subtype of every type, so the outer branch must stay reachable for narrowed T)
     */
    abstract public function flatten(): Result;
}
