<?php

declare(strict_types=1);

/**
 * This file is part of the Elastic OpenAPI PHP code generator.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elastic\OpenApi\Codegen\Endpoint;

use ADS\Util\ArrayUtil;
use ADS\ValueObjects\ValueObject;
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
    /** @var array<string, string>  */
    protected array $params = [];
    /** @var array<string, mixed>|null  */
    protected ?array $body = null;
    /** @var array<string, mixed>|null  */
    protected ?array $formData = null;
    protected bool $snakeCasedParams = false;
    protected bool $snakeCasedBody = false;
    protected bool $snakeCasedFormData = false;

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        $uri = $this->uri;

        foreach ($this->routeParams as $paramName) {
            $uri = str_replace(sprintf('{%s}', $paramName), strval($this->params[$paramName]), $uri);
        }

        return ltrim($uri, '/');
    }

    /**
     * @return array<string>
     */
    private function paramWhitelist(): array
    {
        return array_map(
            static fn (string $param) => str_ends_with($param, '[]') ? rtrim($param, '[]') : $param,
            $this->paramWhitelist
        );
    }

    /**
     * {@inheritdoc}
     */
    public function params(): array
    {
        $paramWhiteList = $this->paramWhitelist();

        /** @var array<string> $result */
        $result = ArrayUtil::rejectNullValues(
            array_filter(
                $this->params,
                static fn (string $paramName) => in_array($paramName, $paramWhiteList),
                ARRAY_FILTER_USE_KEY
            )
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function body(): ?array
    {
        return $this->body;
    }

    public function setBody(?array $body): static
    {
        $this->body = $this->transformData($body);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function formData(): ?array
    {
        return $this->formData;
    }

    public function setFormData(?array $formData): static
    {
        $this->formData = $this->transformData($formData);

        return $this;
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>|null
     */
    private function transformData(?array $data): ?array
    {
        if ($data === null) {
            return $data;
        }

        $data = ArrayUtil::rejectNullValues($data);
        $data = ArrayUtil::rejectEmptyArrayValues($data);
        $data = ArrayUtil::removePrefixFromKeys(
            $data,
            'prefixNumber'
        );

        if ($this->snakeCasedBody) {
            /** @var array<mixed> $data */
            $data = ArrayUtil::toSnakeCasedKeys($data);
        }

        return $data;
    }

    public function setParams(?array $params): static
    {
        if ($params === null) {
            return $this;
        }

        if ($this->snakeCasedParams) {
            /** @var array<string, string> $params */
            $params = ArrayUtil::toSnakeCasedKeys($params);
        }

        $this->checkParams($params);

        /** @var array<string> $params */
        $params = array_map(
            static fn ($paramValue) => $paramValue instanceof ValueObject ? $paramValue->toValue() : $paramValue,
            $params
        );

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
    private function checkParams(?array $params): void
    {
        if ($params === null) {
            return;
        }

        $whitelist = array_merge($this->paramWhitelist(), $this->routeParams);
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
     * @return static
     */
    public function setSnakeCasedParams(bool $snakeCasedParams): static
    {
        $this->snakeCasedParams = $snakeCasedParams;

        return $this;
    }

    /**
     * @return static
     */
    public function setSnakeCasedBody(bool $snakeCasedBody): static
    {
        $this->snakeCasedBody = $snakeCasedBody;

        return $this;
    }

    /**
     * @return static
     */
    public function setSnakeCasedFormData(bool $snakeCasedFormData): static
    {
        $this->snakeCasedFormData = $snakeCasedFormData;

        return $this;
    }
}
