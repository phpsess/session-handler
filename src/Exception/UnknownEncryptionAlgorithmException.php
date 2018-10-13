<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnknownEncryptionAlgorithmException extends \RuntimeException
{

    protected $message = 'The requested encryption algorithm is unknown';
}
