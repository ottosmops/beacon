<?php

declare(strict_types=1);

namespace BeaconParser\Exception;

use Exception;

/**
 * Exception thrown when parsing BEACON files fails
 */
class BeaconParseException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
