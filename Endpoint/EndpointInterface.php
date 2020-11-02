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
 * API endpoint interface.
 */
interface EndpointInterface
{
    /**
     * HTTP method for the current endpoint.
     */
    public function method() : string;

    /**
     * URI for the current endpoint.
     */
    public function uri() : string;

    /**
     * Params data for the current endpoint.
     *
     * @return array<string>
     */
    public function params() : array;

    /**
     * Body content for the current endpoint.
     *
     * @return array<mixed>|null
     */
    public function body() : ?array;

    /**
     * FormData content for the current endpoint.
     *
     * @return array<mixed>|null
     */
    public function formData() : ?array;

    /**
     * Set body data for the endpoint.
     *
     * @param array<mixed>|null $body
     *
     * @return static
     */
    public function setBody(?array $body);

    /**
     * Set body data for the endpoint.
     *
     * @param array<mixed>|null $formData
     *
     * @return static
     */
    public function setFormData(?array $formData);

    /**
     * Set params data for the endpoint.
     *
     * @param array<string, mixed>|null $params
     *
     * @return static
     */
    public function setParams(?array $params);
}
