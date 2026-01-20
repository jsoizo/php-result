<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * Represents a successful Result containing a value.
 *
 * Success is the concrete implementation of Result for successful computations.
 * It holds the success value and implements all Result operations to work
 * with that value, passing it through transformations or returning it directly.
 *
 * @template T The type of the success value
 * @template E The type of the error value (unused, for type compatibility)
 * @extends Result<T, E>
 */
final class Success extends Result
{
    /**
     * Creates a new Success instance with the given value.
     *
     * @param T $value The success value to store
     */
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Always returns true for Success instances.
     */
    public function isSuccess(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * Always returns false for Success instances.
     */
    public function isFailure(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * For Success, always returns the contained value, ignoring the default.
     *
     * @param T $default The default value (ignored)
     * @return T The contained success value
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * For Success, always returns the contained value without throwing.
     *
     * @return T The contained success value
     */
    public function getOrThrow(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * Applies the function to the contained value and wraps the result in a new Success.
     *
     * @template U The type of the transformed value
     * @param callable(T): U $fn The transformation function
     * @return Success<U, E> A new Success containing the transformed value
     */
    public function map(callable $fn): Success
    {
        /** @var Success<U, E> */
        return new Success($fn($this->value));
    }

    /**
     * {@inheritDoc}
     *
     * For Success, returns this instance unchanged since there is no error to transform.
     *
     * @template F The type of the transformed error (unused)
     * @param callable(E): F $fn The error transformation function (not called)
     * @return Success<T, F> This Success instance (type-widened for compatibility)
     */
    public function mapError(callable $fn): Success
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Applies the function to the contained value and returns its Result directly.
     *
     * @template U The success type of the resulting Result
     * @template F The error type of the resulting Result
     * @param callable(T): Result<U, F> $fn The function returning a new Result
     * @return Result<U, F> The Result returned by the function
     */
    public function flatMap(callable $fn): Result
    {
        return $fn($this->value);
    }

    /**
     * {@inheritDoc}
     *
     * For Success, always returns the default value since there is no error.
     *
     * @param E $default The default value to return
     * @return E The default value
     */
    public function getErrorOrElse(mixed $default): mixed
    {
        return $default;
    }

    /**
     * {@inheritDoc}
     *
     * For Success, always throws a ResultException since there is no error to return.
     *
     * @return never This method never returns normally
     * @throws ResultException Always thrown for Success
     */
    public function getErrorOrThrow(): never
    {
        throw new ResultException('Result is a success');
    }

    /**
     * {@inheritDoc}
     *
     * For Success, applies the onSuccess function to the contained value.
     *
     * @template U The return type of both callbacks
     * @param callable(E): U $onFailure Function to apply if this is a Failure (not called)
     * @param callable(T): U $onSuccess Function to apply to the success value
     * @return U The result of applying onSuccess to the contained value
     */
    public function fold(callable $onFailure, callable $onSuccess): mixed
    {
        return $onSuccess($this->value);
    }

    /**
     * {@inheritDoc}
     *
     * For Success, returns this instance unchanged since there is no error to recover from.
     *
     * @template T2 The type of the recovered value (unused)
     * @param callable(E): T2 $fn The recovery function (not called)
     * @return Success<T, E> This Success instance
     */
    public function recover(callable $fn): Success
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * For Success, returns this instance unchanged since there is no error to recover from.
     *
     * @template T2 The success type of the resulting Result (unused)
     * @template F The error type of the resulting Result (unused)
     * @param callable(E): Result<T2, F> $fn The recovery function (not called)
     * @return Success<T, F> This Success instance (type-widened for compatibility)
     */
    public function recoverWith(callable $fn): Success
    {
        return $this;
    }
}
