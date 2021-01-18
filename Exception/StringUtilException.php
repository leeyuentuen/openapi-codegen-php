<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\Exception;

use RuntimeException;

final class StringUtilException
{
    public static function couldNotDecamilize(string $string): RuntimeException
    {
        return new RuntimeException(
            sprintf(
                'It\'s not possible to decamilize string \'%s\'.',
                $string
            )
        );
    }
}
