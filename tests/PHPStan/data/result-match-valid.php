<?php

declare(strict_types=1);

namespace Jsoizo\Result\Tests\PHPStan\Data;

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;

/**
 * @param Result<int, string> $result
 */
function exhaustiveMatch(Result $result): string
{
    return match (true) {
        $result instanceof Success => 'success',
        $result instanceof Failure => 'failure',
    };
}

/**
 * @param Result<int, string> $result
 */
function withDefault(Result $result): string
{
    return match (true) {
        $result instanceof Success => 'success',
        default => 'failure',
    };
}

/**
 * @param Result<int, string> $result
 */
function exhaustiveDirectMatch(Result $result): string
{
    return match ($result) {
        $result instanceof Success => 'success',
        $result instanceof Failure => 'failure',
    };
}

/**
 * @param Result<int, string> $result
 */
function directMatchWithDefault(Result $result): string
{
    return match ($result) {
        $result instanceof Success => 'success',
        default => 'other',
    };
}

function nonResultMatch(): string
{
    $value = 'test';
    return match ($value) {
        'test' => 'matched',
        default => 'other',
    };
}
