<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * Represents a failed Result containing an error.
 *
 * Failure is the concrete implementation of Result for failed computations.
 * It holds the error value and implements all Result operations to short-circuit
 * value transformations while allowing error transformations.
 *
 * @template T The type of the success value (unused, for type compatibility)
 * @template E The type of the error value
 * @extends Result<T, E>
 */
final class Failure extends Result
{
    /**
     * Creates a new Failure instance with the given error.
     *
     * @param E $error The error value to store
     */
    public function __construct(
        private readonly mixed $error,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Always returns false for Failure instances.
     */
    public function isSuccess(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * Always returns true for Failure instances.
     */
    public function isFailure(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * For Failure, always returns the default value since there is no success value.
     *
     * @template TDefault The type of the default value
     * @param TDefault $default The default value to return
     * @return TDefault The default value
     */
    public function getOrElse(mixed $default): mixed
    {
        return $default;
    }

    /**
     * {@inheritDoc}
     *
     * For Failure, always throws an exception. If the error is a Throwable,
     * it is thrown directly. Otherwise, a ResultException is thrown.
     *
     * @return never This method never returns normally
     * @throws \Throwable
     */
    public function getOrThrow(): never
    {
        if ($this->error instanceof \Throwable) {
            throw $this->error;
        }
        throw new ResultException('Result is a failure');
    }

    /**
     * {@inheritDoc}
     *
     * For Failure, returns this instance unchanged. The function is not called
     * since there is no success value to transform.
     *
     * @template U The type of the transformed value (unused)
     * @param callable(T): U $fn The transformation function (not called)
     * @return Failure<U, E> This Failure instance (type-widened for compatibility)
     */
    public function map(callable $fn): Failure
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Applies the function to the contained error and wraps the result in a new Failure.
     *
     * @template F The type of the transformed error
     * @param callable(E): F $fn The error transformation function
     * @return Failure<T, F> A new Failure containing the transformed error
     */
    public function mapError(callable $fn): Failure
    {
        /** @var Failure<T, F> */
        return new Failure($fn($this->error));
    }

    /**
     * {@inheritDoc}
     *
     * For Failure, returns this instance unchanged. The function is not called
     * since there is no success value to chain operations on.
     *
     * @template U The success type of the resulting Result (unused)
     * @param callable(T): Result<U, E> $fn The function (not called)
     * @return Failure<U, E> This Failure instance (type-widened for compatibility)
     */
    public function flatMap(callable $fn): Failure
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * For Failure, always returns the contained error, ignoring the default.
     *
     * @template TDefault The type of the default value (unused)
     * @param TDefault $default The default value (ignored)
     * @return E The contained error value
     */
    public function getErrorOrElse(mixed $default): mixed
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     *
     * For Failure, always returns the contained error without throwing.
     *
     * @return E The contained error value
     */
    public function getErrorOrThrow(): mixed
    {
        return $this->error;
    }
}
