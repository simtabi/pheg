<?php declare(strict_types=1);

namespace Simtabi\Pheg\Toolbox\Arr\Query\Exceptions;

class ConditionNotAllowedException extends \Exception
{
    public function __construct($message = "ConditionFactory not allowed exception", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
