<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\Util;

use Closure;
use stdClass;

final class ArrayUtil
{
    /**
     * @param array<mixed> $array
     *
     * @return array<mixed>
     */
    public static function noNullItems(array $array, bool $recursive = true): array
    {
        $filter = static fn ($value) => $value !== null;

        return array_filter(
            self::process(
                $array,
                null,
                static function ($array) use ($filter) {
                    return array_filter(
                        $array,
                        $filter
                    );
                },
                $recursive
            ),
            $filter
        );
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function camelCasedKeys(array $array, bool $recursive = false): array
    {
        return self::process(
            $array,
            static fn ($key) => is_int($key) ? $key : StringUtil::camelize($key),
            null,
            $recursive
        );
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function snakeCasedKeys(array $array, bool $recursive = false): array
    {
        return self::process(
            $array,
            static fn ($key) => is_int($key) ? $key : StringUtil::decamilize($key),
            null,
            $recursive
        );
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    private static function process(
        array $array,
        ?Closure $keyClosure = null,
        ?Closure $valueClosure = null,
        bool $recursive = false
    ): array {
        $processedArray = [];

        foreach ($array as $key => $value) {
            if ($recursive) {
                $isStdClass = $value instanceof stdClass;

                if ($isStdClass) {
                    $value = (array) $value;
                }

                if (is_array($value)) {
                    $value = self::process($value, $keyClosure, $valueClosure, $recursive);
                }

                $value = $isStdClass ? (object) $value : $value;
            }

            $processedArray[$keyClosure ? $keyClosure($key) : $key] = $valueClosure ? $valueClosure($value) : $value;
        }

        return $processedArray;
    }
}
