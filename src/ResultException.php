<?php

declare(strict_types=1);

namespace Jsoizo\Result;

/**
 * Exception thrown when attempting to access a value that doesn't exist in a Result.
 *
 * This exception indicates a logic error: the programmer assumed the Result
 * was in a different state than it actually is.
 **/
final class ResultException extends \LogicException
{
}
