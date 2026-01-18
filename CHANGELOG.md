# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Type-safe Result type implementation (`Success<T, E>`, `Failure<T, E>`)
- PHPStan level max support
- Transformation methods: `map()`, `mapError()`, `flatMap()`
- Value extraction methods: `getOrElse()`, `getOrThrow()`
- Exception catching via `Result::catch()`
