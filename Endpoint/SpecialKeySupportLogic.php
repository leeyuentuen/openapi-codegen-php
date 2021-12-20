<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\Endpoint;

trait SpecialKeySupportLogic
{
    /**
     * @param array<string, string> $map
     */
    private function convertByMap(string $key, array $map): string
    {
        if (! array_key_exists($key, $map)) {
            return $key;
        }

        $result = $map[$key];

        if (str_ends_with($result, '[]')) {
            return rtrim($result, '[]');
        }

        return $result;
    }
}
