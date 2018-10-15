<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UnknownHashAlgorithmException
 * @package PHPSess\Exception
 * @deprecated Use the generic UnableToHashException with a custom message instead.
 */
class UnknownHashAlgorithmException extends \RuntimeException
{

    protected $message = 'The requested hashing algorithm is unknown';
}
