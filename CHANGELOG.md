# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.0] - 2026-07-03

### Added
- `getErrorOrNull()` method for extracting error as nullable (symmetric with `getOrNull()`)
- `Result::sequence()` for converting a list of Results into one Result with fail-fast semantics, returning the first error unwrapped; throws `ResultException` if any element is not a Result instance
- Optional `$exceptionClass` parameter for `Result::catch()` to capture only the given exception class (others are rethrown) and narrow the error type
- `Result::fromNullable()` for converting nullable values into Results with a lazily produced error
- `getOr()` and `getErrorOr()` for lazy fallbacks computed from the opposite side's value, only invoked when needed
- `Result::accumulate2()` through `Result::accumulate9()` for combining multiple Result instances with error accumulation
- `Result::accumulate()` for converting a list of Results into one Result while collecting all errors; throws `ResultException` if any element is not a Result instance

### Changed
- Changed `flatMap()` type signature to preserve the original error type
  - Before: `@return Result<T1, E1>`
  - After: `@return Result<T1, E|E1>`
- Changed `recover()` type signature to allow a different recovery success type and return `never` as the error type
  - Before: `@param callable(E): T $fn` / `@return Result<T, E>`
  - After: `@param callable(E): T1 $fn` / `@return Result<T|T1, never>`
- Changed `recoverWith()` type signature to allow a different recovery success type
  - Before: `@param callable(E): Result<T, E1> $fn` / `@return Result<T, E1>`
  - After: `@param callable(E): Result<T1, E1> $fn` / `@return Result<T|T1, E1>`
- Changed `flatten()` type signature to preserve nested success and error types instead of widening to `mixed`
  - Before: `@return Result<mixed, mixed>`
  - After: `@return Result<T, E>` unchanged, or `Result<TInner, E|EInner>` if the success value is a `Result<TInner, EInner>`
- Improved `Failure::get()` (non-Throwable errors) and `Success::getError()` exception messages by including the value's type

### Fixed
- Fixed `Result::binding()` hanging forever when a generator yields a non-Result value — it now throws `ResultException` instead
- Fixed `Result::binding()` throwing an unclear PHP error when the callable does not return a `Generator` — it now throws `ResultException` with a descriptive message

## [0.2.1] - 2026-02-06

### Fixed
- Fixed type definitions for `recover` and `recoverWith` in `Success` and `Failure` to align with abstract class signatures
  - Changed from `@template T2` to using `T` for consistent type preservation

## [0.2.0] - 2025-01-21

### Added
- `Result::binding()` for monad comprehension using generators (requires manual type annotations for yielded values)
- PHPStan type narrowing support for `isSuccess()` and `isFailure()` methods
- `tap()` and `tapError()` methods for side effects without modifying the Result
- `getOrNull()` method for extracting value as nullable
- `flatten()` method for flattening nested Results

### Changed
- Renamed `getOrThrow()` to `get()` for simpler API
- Renamed `getErrorOrThrow()` to `getError()` for simpler API
- Minimum PHP version lowered to 8.1 (tests still require PHP 8.2+)
- Changed `getOrElse` type signature to require same type as success value
  - Before: `@param TDefault $default` / `@return T|TDefault`
  - After: `@param T $default` / `@return T`
- Changed `getErrorOrElse` type signature to require same type as error value (symmetric with `getOrElse`)
  - Before: `@param TDefault $default` / `@return E|TDefault`
  - After: `@param E $default` / `@return E`
- Changed `flatMap` type signature to allow callbacks returning Result with different error types
  - Before: `@param callable(T): Result<U, E> $fn` / `@return Result<U, E>`
  - After: `@param callable(T): Result<T1, E1> $fn` / `@return Result<T1, E1>`
- Changed `recover` type signature to preserve original T type
  - Before: `@param callable(E): T2 $fn` / `@return Result<T|T2, E>`
  - After: `@param callable(E): T $fn` / `@return Result<T, E>`
- Changed `recoverWith` type signature to preserve T but allow different error types (symmetric with `flatMap`)
  - Before: `@param callable(E): Result<T2, F> $fn` / `@return Result<T|T2, F>`
  - After: `@param callable(E): Result<T, E1> $fn` / `@return Result<T, E1>`

## [0.1.0] - 2025-01-19

### Added
- Type-safe Result type implementation (`Success<T, E>`, `Failure<T, E>`)
- PHPStan level max support
- Transformation methods: `map()`, `mapError()`, `flatMap()`, `fold()`, `recover()`, `recoverWith()`
- Value extraction methods: `getOrElse()`, `getOrThrow()`
- Error extraction methods: `getErrorOrElse()`, `getErrorOrThrow()`
- `ResultException` for unwrap operations (`getOrThrow()`, `getErrorOrThrow()`)
- Exception catching via `Result::catch()`
- PHPStan rule for match expression exhaustiveness check on Result types
