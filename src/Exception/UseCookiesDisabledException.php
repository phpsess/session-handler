<?php

namespace Ssess\Exception;

class UseCookiesDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_cookies should be set to true';

}