<?php

namespace Ssess\Exception;

class DirectoryNotReadableException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to read the session directory.';

}