# php-result

Type-safe Result type library for PHP 8.2+ with PHPStan support.

## Commands

```bash
composer test           # Run Pest tests
composer test:coverage  # Run tests with coverage (100% required)
composer analyse        # Run PHPStan static analysis
composer cs-check       # Check code style
composer cs-fix         # Fix code style
```

## Development Workflow

After changes: `composer cs-fix` → `composer analyse` → `composer test`

## Adding New Methods

### PHPDoc Template

```php
/**
 * Brief description.
 *
 * @template T
 * @param callable(TValue): T $fn
 * @return Result<T, TError>
 */
```

### README Update Required

- New public methods
- Usage changes

Not required for internal refactoring or bug fixes.
