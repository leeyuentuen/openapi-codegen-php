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

    protected function endpoint(string $name) : EndpointInterface
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
        $uri = $endpoint->uri();

        $options = $this->buildOptions($endpoint);

        try {
            $response = $this->connection->request($method, $uri, $options);
        } catch (ClientException $exception) {
            throw ExceptionHandler::fromGuzzleClientException($exception, $this->exceptionHandler);
        }

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents, true);

        if ($body === null) {
            return [];
        }

        return $body;
    }

    /**
     * @return array<mixed>
     */
    protected function buildOptions(Endpoint\EndpointInterface $endpoint) : array
    {
        if ($this->optionBuilder) {
            $optionBuilder = $this->optionBuilder;

            $options = $optionBuilder($endpoint);

            if ($options !== null) {
                return $options;
            }
        }

        $params = $endpoint->params();
        $body = $endpoint->body();
        $formData = $endpoint->formData();

        $options = [];

        if (! empty($params)) {
            $options['query'] = $params;
        }

        if (! empty($body)) {
            $options['json'] = $body;
        }

        if (! empty($formData)) {
            $options['form_params'] = $formData;
        }

        return $options;
    }
}
