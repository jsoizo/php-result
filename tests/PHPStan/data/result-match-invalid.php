<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\Data;

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;

/**
 * @param Result<int, string> $result
 */
function missingFailure(Result $result): string
{
    return match (true) {
        $result instanceof Success => 'success',
    };
}

/**
 * @param Result<int, string> $result
 */
function missingSuccess(Result $result): string
{
    return match (true) {
        $result instanceof Failure => 'failure',
    };
}

/**
 * @param Result<int, string> $result
 */
function missingBoth(Result $result): string
{
    return match (true) {
        true => 'always',
    };
}

/**
 * @param Result<int, string> $result
 */
function missingFailureDirectMatch(Result $result): string
{
    return match ($result) {
        $result instanceof Success => 'success',
    };
}
