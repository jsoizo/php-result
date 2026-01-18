<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * @template T
 * @template E
 * @extends Result<T, E>
 */
final class Failure extends Result
{
    /**
     * @param E $error
     */
    public function __construct(
        private readonly mixed $error,
    ) {
    }

    public function isSuccess(): bool
    {
        return false;
    }

    public function isFailure(): bool
    {
        return true;
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @return TDefault
     */
    public function getOrElse(mixed $default): mixed
    {
        return $default;
    }

    /**
     * @return never
     * @throws \Throwable
     */
    public function getOrThrow(): never
    {
        if ($this->error instanceof \Throwable) {
            throw $this->error;
        }
        throw new \RuntimeException('Result is a failure: ' . print_r($this->error, true));
    }

    /**
     * @template U
     * @param callable(T): U $fn
     * @return Failure<U, E>
     */
    public function map(callable $fn): Failure
    {
        return $this;
    }

    /**
     * @template F
     * @param callable(E): F $fn
     * @return Failure<T, F>
     */
    public function mapError(callable $fn): Failure
    {
        /** @var Failure<T, F> */
        return new Failure($fn($this->error));
    }

    /**
     * @template U
     * @param callable(T): Result<U, E> $fn
     * @return Failure<U, E>
     */
    public function flatMap(callable $fn): Failure
    {
        return $this;
    }
}
