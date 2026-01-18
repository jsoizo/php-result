<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * @template T
 * @template E
 * @extends Result<T, E>
 */
final class Success extends Result
{
    /**
     * @param T $value
     */
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    public function isSuccess(): bool
    {
        return true;
    }

    public function isFailure(): bool
    {
        return false;
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @return T
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->value;
    }

    /**
     * @return T
     */
    public function getOrThrow(): mixed
    {
        return $this->value;
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return Success<U, E>
     */
    public function map(callable $fn): Success
    {
        /** @var Success<U, E> */
        return new Success($fn($this->value));
    }

    /**
     * @template F
     * @param callable(E): F $fn
     * @return Success<T, F>
     */
    public function mapError(callable $fn): Success
    {
        /** @var Success<T, F> $result */
        $result = $this; // @phpstan-ignore varTag.nativeType

        return $result;
    }

    /**
     * @template U
     * @param callable(T): Result<U, E> $fn
     * @return Result<U, E>
     */
    public function flatMap(callable $fn): Result
    {
        return $fn($this->value);
    }
}
