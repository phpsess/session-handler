<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnableToEncryptException extends \RuntimeException
{

    protected $message = 'The Encryption Driver is unable to encrypt';
}
