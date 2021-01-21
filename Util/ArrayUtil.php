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
        return self::recursiveFilterValues($array, static fn ($value) => $value !== null, $recursive);
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function camelCasedKeys(array $array, bool $recursive = false): array
    {
        return self::recursiveMapKeysAndValues(
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
        return self::recursiveMapKeysAndValues(
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
    private static function recursiveMapKeysAndValues(
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
                    $value = self::recursiveMapKeysAndValues($value, $keyClosure, $valueClosure, $recursive);
                }

                $value = $isStdClass ? (object) $value : $value;
            }

            $processedArray[$keyClosure ? $keyClosure($key) : $key] = $valueClosure ? $valueClosure($value) : $value;
        }

        return $processedArray;
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    private static function recursiveFilterValues(
        array $array,
        Closure $filter,
        bool $recursive
    ): array {
        if (! $recursive) {
            return array_filter($array, $filter);
        }

        return array_filter(
            self::recursiveMapKeysAndValues(
                $array,
                null,
                static function ($value) use ($filter) {
                    if (is_array($value)) {
                        $value = array_filter(
                            $value,
                            $filter
                        );
                    }

                    return $value;
                },
                $recursive
            ),
            $filter
        );
    }
}
