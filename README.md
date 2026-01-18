# php-result

Type-safe Result type library for PHP 8.2+

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
| `getErrorOrElse($default)` | Get error or default |
| `getErrorOrThrow()` | Get error or throw LogicException |
| `map($fn)` | Transform success value |
| `mapError($fn)` | Transform error value |
| `flatMap($fn)` | Chain Result-returning operations |

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

Note: Exhaustive checking in match expressions is not yet supported by PHPStan. Use `fold()` method (when available) or handle all cases manually.

See: [PHPStan Sealed Classes](https://phpstan.org/writing-php-code/phpdocs-basics#sealed-classes)
