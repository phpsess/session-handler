<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class DirectoryNotWritableException
 * @package PHPSess\Exception
 * @deprecated Use the generic UnableToSaveException with a custom message instead.
 */
class DirectoryNotWritableException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to write to the session directory.';
}
