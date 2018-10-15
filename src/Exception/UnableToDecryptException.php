<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnableToDecryptException extends \RuntimeException
{

    protected $message = 'The session encryption driver was unable to decrypt the session.';
}
