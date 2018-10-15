<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class UseTransSidEnabledException
 * @package PHPSess\Exception
 * @deprecated Use the generic InsecureSettingsException with a custom message instead.
 */
class UseTransSidEnabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_trans_id should be set to false';
}
