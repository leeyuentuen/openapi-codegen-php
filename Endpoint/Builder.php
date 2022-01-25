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
    private bool $snakeCasedParams = false;
    private bool $snakeCasedBody = false;
    private bool $snakeCasedFormData = false;
    private string $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Create an endpoint from name.
     */
    public function __invoke(string $endpointName): EndpointInterface
    {
        $className = sprintf('%s\\%s', $this->namespace, $endpointName);

        /** @var AbstractEndpoint $endpoint */
        $endpoint = new $className();

        return $endpoint
            ->setSnakeCasedParams($this->snakeCasedParams)
            ->setSnakeCasedBody($this->snakeCasedBody)
            ->setSnakeCasedFormData($this->snakeCasedFormData);
    }

    public function setSnakeCasedParams(bool $snakeCasedParams): self
    {
        $this->snakeCasedParams = $snakeCasedParams;

        return $this;
    }

    public function setSnakeCasedBody(bool $snakeCasedBody): self
    {
        $this->snakeCasedBody = $snakeCasedBody;

        return $this;
    }

    public function setSnakeCasedFormData(bool $snakeCasedFormData): self
    {
        $this->snakeCasedFormData = $snakeCasedFormData;

        return $this;
    }
}
