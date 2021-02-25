<?php

declare(strict_types=1);

namespace App\Common\Infrastructure\Request\ParamConverter;

use Throwable;

final class RequestBodyParamConversionFailedException extends RuntimeException
{
    protected $message = 'request.param_converter.request_body.exception.request_body_param_conversion_failed';

    public function __construct(Throwable $e)
    {
        parent::__construct($this->message, $e->getCode(), $e);
    }
}
