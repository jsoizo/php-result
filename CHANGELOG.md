# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- PHPStan type narrowing support for `isSuccess()` and `isFailure()` methods

### Changed
- Changed `getOrElse` type signature to require same type as success value (Rust-style)
  - Before: `@param TDefault $default` / `@return T|TDefault`
  - After: `@param T $default` / `@return T`
- Changed `flatMap` type signature to allow callbacks returning Result with different error types
  - Before: `@param callable(T): Result<U, E> $fn` / `@return Result<U, E>`
  - After: `@param callable(T): Result<U, F> $fn` / `@return Result<U, F>`

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
