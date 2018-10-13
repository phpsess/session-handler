<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnableToCreateDirectoryException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to create the session directory.';
}
