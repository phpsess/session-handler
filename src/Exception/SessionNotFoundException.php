<?php

namespace Ssess\Exception;

class SessionNotFoundException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to find the session.';

}