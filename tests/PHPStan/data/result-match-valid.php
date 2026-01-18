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

function nonResultMatch(): string
{
    $value = 'test';
    return match ($value) {
        'test' => 'matched',
        default => 'other',
    };
}

/**
 * @param Result<int, string> $result
 */
function withFqcn(Result $result): string
{
    return match (true) {
        $result instanceof \Jsoizo\Result\Success => 'success',
        $result instanceof \Jsoizo\Result\Failure => 'failure',
    };
}

/**
 * @param Result<int, string> $result
 */
function failureWithDefault(Result $result): string
{
    return match (true) {
        $result instanceof Failure => 'failure',
        default => 'other',
    };
}

/**
 * @param Result<int, string> $result
 */
function multipleConditionsInArm(Result $result): string
{
    return match (true) {
        $result instanceof Success, $result instanceof Failure => 'result',
    };
}

/**
 * @param Result<int, string> $result
 */
function defaultOnly(Result $result): string
{
    return match (true) {
        default => 'always',
    };
}

class ResultHolder
{
    /** @var Result<int, string> */
    public Result $result;

    public function __construct()
    {
        $this->result = Result::success(1);
    }

    /**
     * @return Result<int, string>
     */
    public function getResult(): Result
    {
        return $this->result;
    }

    public function propertyAccess(): string
    {
        return match (true) {
            $this->result instanceof Success => 'success',
            default => 'other',
        };
    }

    public function methodCall(): string
    {
        return match (true) {
            $this->getResult() instanceof Success => 'success',
            default => 'other',
        };
    }
}
