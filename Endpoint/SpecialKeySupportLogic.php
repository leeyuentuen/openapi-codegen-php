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
        return $this->removeArrayBrackets(
            array_key_exists($key, $map)
                ? $map[$key]
                : $key
        );
    }

    private function removeArrayBrackets(string $key): string
    {
        if (substr_compare($key, '[]', -strlen('[]')) === 0) {
            return rtrim($key, '[]');
        }

        return $key;
    }
}
