<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\ImmutableRecords;

interface Discriminator
{
    /**
     * @return array<string, class-string>
     */
    public static function discriminatorMapping(): array;

    public static function discriminatorProperty(): string;
}
