<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UnknownEncryptionAlgorithmException
 * @package PHPSess\Exception
 * @deprecated Use the generic UnableToEncryptException with a custom message instead.
 * @see UnableToEncryptException
 */
class UnknownEncryptionAlgorithmException extends \RuntimeException
{

    protected $message = 'The requested encryption algorithm is unknown';
}
