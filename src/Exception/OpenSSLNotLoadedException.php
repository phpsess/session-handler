<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class OpenSSLNotLoadedException extends \RuntimeException
{

    protected $message = 'You need the OpenSSL extension to encrypt the session data';
}
