<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnableToSetupStorageException extends \RuntimeException
{

    protected $message = 'The Storage Driver was unable to setup.';
}
