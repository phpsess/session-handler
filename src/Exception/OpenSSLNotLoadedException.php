<?php

namespace Ssess\Exception;

class OpenSSLNotLoadedException extends \RuntimeException
{

    protected $message = 'You need the OpenSSL extension to encrypt the session data';

}