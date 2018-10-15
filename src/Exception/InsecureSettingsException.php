<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class InsecureSettingsException extends \UnexpectedValueException
{

    protected $message = 'The session handler detected insecure settings in your ini config.';
}
