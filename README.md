# php-result

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jsoizo/php-result.svg)](https://packagist.org/packages/jsoizo/php-result)
[![CI](https://github.com/jsoizo/php-result/actions/workflows/ci.yml/badge.svg)](https://github.com/jsoizo/php-result/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/jsoizo/php-result.svg)](https://packagist.org/packages/jsoizo/php-result)

A type-safe Result type for PHP 8.1+ with first-class PHPStan support.

## Features

- Zero dependencies
- PHPStan level max support
- Rich composition functions (map, flatMap, mapError)
- Inspired by functional programming Result/Either types

## Installation

```bash
composer require jsoizo/php-result
```

## Basic Usage

```php
use Jsoizo\Result\Result;

// Create Success/Failure
$success = Result::success(42);
$failure = Result::failure('error message');

// Transform values
$doubled = $success->map(fn($x) => $x * 2); // Success(84)

// Chain operations
$result = $success
    ->flatMap(fn($x) => $x > 0
        ? Result::success($x * 2)
        : Result::failure('must be positive'));

// Get value with default
$value = $failure->getOrElse(0); // 0

// Catch exceptions
$result = Result::catch(fn() => riskyOperation());

// Handle both cases with fold
$message = $result->fold(
    onFailure: fn($error) => "Error: {$error->getMessage()}",
    onSuccess: fn($value) => "Got: {$value}"
);

// Compose validations with flatMap
$result = validateEmail($input['email'])
    ->flatMap(fn($email) => validatePassword($input['password'])
    ->flatMap(fn($password) => createUser($email, $password)));

// Recover from failure with default value
$recovered = $failure->recover(fn($e) => 'default'); // Success('default')

// Chain fallback operations
$result = fetchFromPrimaryDb()
    ->recoverWith(fn($e) => fetchFromSecondaryDb())
    ->recoverWith(fn($e) => Result::success('cached fallback'));

// Side effects for debugging/logging
$result = validateInput($data)
    ->tap(fn($v) => logger()->info("Valid: $v"))
    ->tapError(fn($e) => logger()->error("Invalid: $e"))
    ->flatMap(fn($v) => processData($v));

// Get value as nullable
$value = $result->getOrNull(); // T|null

// Flatten nested Results
$nested = Result::success(Result::success(42));
$flat = $nested->flatten(); // Success(42)
```

## API

### Factory Methods

| Method | Description |
|--------|-------------|
| `Result::success($value)` | Create a Success |
| `Result::failure($error)` | Create a Failure |
| `Result::catch(callable $fn)` | Wrap exception-throwing code |

### Instance Methods

| Method | Description |
|--------|-------------|
| `isSuccess()` | Returns true if Success |
| `isFailure()` | Returns true if Failure |
| `getOrElse($default)` | Get value or default |
| `getOrThrow()` | Get value or throw |
| `getOrNull()` | Get value or null |
| `getErrorOrElse($default)` | Get error or default |
| `getErrorOrThrow()` | Get error or throw LogicException |
| `map($fn)` | Transform success value |
| `mapError($fn)` | Transform error value |
| `flatMap($fn)` | Chain Result-returning operations |
| `flatten()` | Flatten nested Result |
| `fold($onFailure, $onSuccess)` | Handle both cases and return a value |
| `recover($fn)` | Recover from error with a value |
| `recoverWith($fn)` | Recover from error with a Result |
| `tap($fn)` | Execute side effect on success value |
| `tapError($fn)` | Execute side effect on error value |

## PHPStan Integration

### Sealed Class Support

Result is marked as a sealed class using `@phpstan-sealed`. This prevents creating custom subclasses of Result outside of Success and Failure.

```php
// PHPStan will report an error for unauthorized subclasses:
// "Type CustomResult is not allowed to be a subtype of Result"
class CustomResult extends Result { ... }
```

Requirements:
- PHPStan 2.1.18 or later
- No additional packages needed

See: [PHPStan Sealed Classes](https://phpstan.org/writing-php-code/phpdocs-basics#sealed-classes)

### Type Narrowing with isSuccess/isFailure

The `isSuccess()` and `isFailure()` methods support [PHPStan type narrowing](https://phpstan.org/writing-php-code/narrowing-types):

```php
/** @param Result<User, ValidationError> $result */
function handleResult(Result $result): void
{
    if ($result->isSuccess()) {
        // PHPStan knows $result is Success<User, ValidationError>
        $user = $result->getOrThrow();
    } else {
        // PHPStan knows $result is Failure<User, ValidationError>
        $error = $result->getErrorOrThrow();
    }
}

// Early return pattern
/** @param Result<int, string> $result */
function getValue(Result $result): int
{
    if ($result->isFailure()) {
        return -1;
    }
    // PHPStan knows $result is Success<int, string>
    return $result->getOrThrow();
}
```

### Match Exhaustiveness Check

This library includes a custom PHPStan rule that ensures match expressions on Result types are exhaustive.

**Setup:**

The rule is automatically enabled when you use PHPStan with this library (via `composer.json` extra config). Alternatively, add to your `phpstan.neon`:

```neon
includes:
    - vendor/jsoizo/php-result/extension.neon
```

**What it checks:**

```php
// Error: Match expression on Result type is not exhaustive. Missing: Failure.
match (true) {
    $result instanceof Success => 'success',
};

// OK: All cases covered
match (true) {
    $result instanceof Success => 'success',
    $result instanceof Failure => 'failure',
};

// OK: default covers remaining cases
match (true) {
    $result instanceof Success => 'success',
    default => 'failure',
};
```

**Note: PHPStan's `match.unhandled` error**

Even when all cases are covered, PHPStan may report `Match expression does not handle remaining value: true`. This is because PHPStan doesn't use sealed class information for match exhaustiveness.

To suppress this error, use the `@phpstan-ignore` comment:

```php
/** @phpstan-ignore match.unhandled (Result is sealed: Success|Failure) */
return match (true) {
    $result instanceof Success => 'success',
    $result instanceof Failure => 'failure',
};
```

The custom rule in this library ensures exhaustiveness, so it's safe to ignore `match.unhandled` for Result types.

