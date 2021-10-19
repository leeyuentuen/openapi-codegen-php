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

        return rtrim($map[$key], '[]');
    }
}
