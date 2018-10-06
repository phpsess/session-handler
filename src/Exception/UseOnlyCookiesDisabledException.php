<?php

namespace Ssess\Exception;

class UseOnlyCookiesDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_only_cookies should be set to true';

}