<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UnableToCreateDirectoryException
 * @package PHPSess\Exception
 * @deprecated Use the generic UnableToSetupStorageException with a custom message instead.
 * @see UnableToSetupStorageException
 */
class UnableToCreateDirectoryException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to create the session directory.';
}
