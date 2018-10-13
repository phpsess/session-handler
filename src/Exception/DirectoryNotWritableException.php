<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class DirectoryNotWritableException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to write to the session directory.';
}
