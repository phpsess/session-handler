<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class OpenSSLNotLoadedException
 * @package PHPSess\Exception
 * @deprecated
 */
class OpenSSLNotLoadedException extends \RuntimeException
{

    protected $message = 'You need the OpenSSL extension to encrypt the session data';
}
