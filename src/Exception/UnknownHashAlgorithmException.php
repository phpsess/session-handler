<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnknownHashAlgorithmException extends \RuntimeException
{

    protected $message = 'The requested hashing algorithm is unknown';
}
