<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UnableToDecryptException extends \RuntimeException
{

    protected $message = 'The session encryption driver was unable to decrypt the session. This may be caused by a wrong app key or session id.';
}
