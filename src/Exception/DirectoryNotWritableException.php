<?php

namespace Ssess\Exception;

class DirectoryNotWritableException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to write to the session directory.';

}