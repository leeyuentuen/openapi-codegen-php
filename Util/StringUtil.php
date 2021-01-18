<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\Util;

use Elastic\OpenApi\Codegen\Exception\StringUtilException;

final class StringUtil
{
    public static function camelize(string $string, string $delimiter = '_', bool $pascal = false): string
    {
        $result = str_replace($delimiter, '', ucwords($string, $delimiter));

        return $pascal ? $result : lcfirst($result);
    }

    public static function decamilize(string $string, string $delimiter = '_'): string
    {
        $regex = [
            '/([a-z\d])([A-Z])/',
            sprintf('/([^%s])([A-Z][a-z])/', $delimiter),
        ];

        $replaced = preg_replace($regex, '$1_$2', $string);

        if ($replaced === null) {
            throw StringUtilException::couldNotDecamilize($string);
        }

        return strtolower($replaced);
    }
}
