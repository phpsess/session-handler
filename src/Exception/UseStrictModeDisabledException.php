<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UseStrictModeDisabledException
 * @package PHPSess\Exception
 * @deprecated Use the generic InsecureSettingsException with a custom message instead.
 */
class UseStrictModeDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_strict_mode should be set to true';
}
