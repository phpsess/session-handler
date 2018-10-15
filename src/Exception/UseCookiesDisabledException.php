<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UseCookiesDisabledException
 * @package PHPSess\Exception
 * @deprecated Use the generic InsecureSettingsException with a custom message instead.
 */
class UseCookiesDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_cookies should be set to true';
}
