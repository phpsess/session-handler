<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UseOnlyCookiesDisabledException
 * @package PHPSess\Exception
 * @deprecated Use the generic InsecureSettingsException with a custom message instead.
 * @see InsecureSettingsException
 */
class UseOnlyCookiesDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_only_cookies should be set to true';
}
