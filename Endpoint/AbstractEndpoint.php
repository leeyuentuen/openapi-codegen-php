<?php

declare(strict_types=1);

/**
 * This file is part of the Elastic OpenAPI PHP code generator.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elastic\OpenApi\Codegen\Endpoint;

use UnexpectedValueException;

/**
 * Abstract endpoint implementation.
 */
abstract class AbstractEndpoint implements EndpointInterface
{
    protected string $method;
    protected string $uri;
    /** @var array<string>  */
    protected array $routeParams = [];
    /** @var array<string>  */
    protected array $paramWhitelist = [];
    /** @var array<string, mixed>  */
    protected array $params = [];
    /** @var array<string>|null  */
    protected ?array $body = null;
    /** @var array<string>|null  */
    protected ?array $formData = null;

    public function method() : string
    {
        return $this->method;
    }

    public function uri() : string
    {
        $uri = $this->uri;

        foreach ($this->routeParams as $paramName) {
            $uri = str_replace(sprintf('{%s}', $paramName), $this->params[$paramName], $uri);
        }

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function params() : array
    {
        $params = [];

        foreach ($this->params as $paramName => $paramVal) {
            if (! in_array($paramName, $this->paramWhitelist)) {
                continue;
            }

            $params[$paramName] = $paramVal;
        }

        return $this->processParams($params);
    }

    /**
     * {@inheritdoc}
     */
    public function body() : ?array
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setBody(?array $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function formData() : ?array
    {
        return $this->formData;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormData(?array $formData)
    {
        $this->formData = $formData;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setParams(?array $params)
    {
        $this->checkParams($params);

        if ($params === null) {
            return $this;
        }

        $this->params = $params;

        return $this;
    }

    /**
     * Loop over the param to check all params are into the whitelist.
     *
     * @param array<string, mixed>|null $params
     *
     * @throws UnexpectedValueException
     */
    private function checkParams(?array $params) : void
    {
        if ($params === null) {
            return;
        }

        $whitelist = array_merge($this->paramWhitelist, $this->routeParams);
        $invalidParams = array_diff(array_keys($params), $whitelist);
        $countInvalid = count($invalidParams);

        if ($countInvalid <= 0) {
            return;
        }

        $whitelist = implode('", "', $whitelist);
        $invalidParams = implode('", "', $invalidParams);
        $message = '"%s" is not a valid parameter. Allowed parameters are "%s".';
        if ($countInvalid > 1) {
            $message = '"%s" are not valid parameters. Allowed parameters are "%s".';
        }

        throw new UnexpectedValueException(
            sprintf($message, $invalidParams, $whitelist)
        );
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>
     */
    private function processParams(array $params) : array
    {
        return array_filter(
            $params,
            static function ($param) {
                return $param !== null;
            }
        );
    }
}
