<?php

declare(strict_types=1);

/**
 * This file is part of the Elastic OpenAPI PHP code generator.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elastic\OpenApi\Codegen;

use Elastic\OpenApi\Codegen\Endpoint\EndpointInterface;
use Elastic\OpenApi\Codegen\Exception\ExceptionHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * A base client implementation implemented by the generator.
 */
abstract class AbstractClient
{
    private Client $connection;

    /** @var callable */
    private $endpointBuilder;

    /** @var callable|null */
    protected $exceptionHandler = null;

    /** @var callable|null */
    protected $optionBuilder = null;

    private ?string $prependPath = null;
    private ?ResponseInterface $lastResponse = null;

    public function __construct(callable $endpointBuilder, Client $connection)
    {
        $this->endpointBuilder = $endpointBuilder;
        $this->connection = $connection;
    }

    /**
     * @return static
     */
    public function setExceptionHandler(callable $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;

        return $this;
    }

    /**
     * @return static
     */
    public function setOptionBuilder(callable $optionBuilder)
    {
        $this->optionBuilder = $optionBuilder;

        return $this;
    }

    /**
     * @return static
     */
    public function setPrependPath(string $prependPath)
    {
        $this->prependPath = $prependPath;

        return $this;
    }

    protected function endpoint(string $name): EndpointInterface
    {
        $endpointBuilder = $this->endpointBuilder;

        return $endpointBuilder($name);
    }

    /**
     * @return mixed
     */
    protected function performRequest(Endpoint\EndpointInterface $endpoint)
    {
        $method = $endpoint->method();
        $uri = $this->uriWithPrependPath($endpoint);

        $options = $this->buildOptions($endpoint);

        try {
            $response = $this->connection->request($method, $uri, $options);
        } catch (ClientException $exception) {
            throw ExceptionHandler::fromGuzzleClientException($exception, $this->exceptionHandler);
        }

        $this->lastResponse = $response;

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents, true);

        if ($body === null) {
            return [];
        }

        // Remove HAL metadata
        unset($body['_links']);

        return $body;
    }

    protected function uriWithPrependPath(EndpointInterface $endpoint): string
    {
        $uri = $endpoint->uri();
        if ($this->prependPath !== null) {
            return $this->prependPath . $uri;
        }

        return $uri;
    }

    /**
     * @return array<mixed>
     */
    protected function buildOptions(Endpoint\EndpointInterface $endpoint): array
    {
        $options = [];
        if ($this->optionBuilder) {
            $optionBuilder = $this->optionBuilder;

            $options = $optionBuilder($endpoint);
        }

        $params = $endpoint->params();
        $body = $endpoint->body();
        $formData = $endpoint->formData();

        if (! array_key_exists('query', $options) && count($params) > 0) {
            $options['query'] = $params;
        }

        if (! array_key_exists('json', $options) && is_array($body) && count($body) > 0) {
            $options['json'] = $body;
        }

        if (! array_key_exists('form_params', $options) && is_array($formData) && count($formData) > 0) {
            $options['form_params'] = $formData;
        }

        return $options;
    }

    public function lastResponse(): ResponseInterface
    {
        if ($this->lastResponse === null) {
            throw new RuntimeException('No last response found.');
        }

        return $this->lastResponse;
    }

    public function lastStatusCode(): int
    {
        $lastResponse = $this->lastResponse();

        return $lastResponse->getStatusCode();
    }
}
