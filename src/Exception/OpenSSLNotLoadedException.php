<?php

declare(strict_types=1);

namespace Ssess\Exception;

class OpenSSLNotLoadedException extends \RuntimeException
{

    protected $message = 'You need the OpenSSL extension to encrypt the session data';

}