<?php

declare(strict_types=1);

/**
 * This file is part of the Elastic OpenAPI PHP code generator.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elastic\OpenApi\Codegen\Endpoint;

/**
 * Endpoint builder implementation.
 */
class Builder
{
    private string $namespace;
    private bool $snakeCasedParams;

    public function __construct(string $namespace, bool $snakeCasedParams = false)
    {
        $this->namespace = $namespace;
        $this->snakeCasedParams = $snakeCasedParams;
    }

    /**
     * Create an endpoint from name.
     */
    public function __invoke(string $endpointName): EndpointInterface
    {
        $className = sprintf('%s\\%s', $this->namespace, $endpointName);

        return (new $className())
            ->setSnakeCasedParams($this->snakeCasedParams);
    }
}
