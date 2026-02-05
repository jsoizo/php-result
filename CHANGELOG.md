# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
