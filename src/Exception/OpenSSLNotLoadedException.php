<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class OpenSSLNotLoadedException
 * @package PHPSess\Exception
 * @deprecated This extension is driver specific. Throw the proper exception when trying to use it instead.
 * @see UnableToEncryptException
 * @see UnableToDecryptException
 * @see UnableToHashException
 */
class OpenSSLNotLoadedException extends \RuntimeException
{

    protected $message = 'You need the OpenSSL extension to encrypt the session data';
}
