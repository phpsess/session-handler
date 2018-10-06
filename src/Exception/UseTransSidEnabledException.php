<?php

namespace Ssess\Exception;

class UseTransSidEnabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_trans_id should be set to false';

}