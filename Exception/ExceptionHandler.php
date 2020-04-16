<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\Exception;

use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Throwable;

final class ExceptionHandler
{
    public static function fromGuzzleClientException(
        ClientException $exception,
        ?callable $exceptionHandler = null
    ) : Throwable {
        if ($exceptionHandler === null) {
            return $exception;
        }

        $response = $exception->getResponse();

        if ($response === null) {
            return new RuntimeException(
                'Client exception thrown without a response.'
            );
        }

        $exceptionData = json_decode($response->getBody()->getContents(), true);

        $throwable = $exceptionHandler($exceptionData);

        if (! $throwable instanceof Throwable) {
            return $exception;
        }

        return $throwable;
    }
}
