<?php

declare(strict_types=1);

/**
 * This file is part of the Elastic OpenAPI PHP code generator.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elastic\OpenApi\Codegen;

use ADS\Util\ArrayUtil;
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
    /** @var array<string, mixed> */
    private array $configs;

    /**
     * @param array<string, mixed> $configs
     */
    public function __construct(callable $endpointBuilder, Client $connection, array $configs = [])
    {
        $this->endpointBuilder = $endpointBuilder;
        $this->connection = $connection;
        $this->configs = $configs;
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

        if ($this->configs['transformHal'] ?? false) {
            return $this->transformHal($body);
        }

        return $body;
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    private function transformHal(array $body): array
    {
        if (isset($body['_embedded'])) {
            $body = array_shift($body['_embedded']);
        }

        unset($body['_links']);

        if (! ArrayUtil::isAssociative($body)) {
            foreach ($body as &$bodyPart) {
                unset($bodyPart['_links']);
            }
        }

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
        $options = $this->optionBuilder ? ($this->optionBuilder)($endpoint) : [];
        $params = $endpoint->params();
        $body = $endpoint->body();
        $formData = $endpoint->formData();

        if (! array_key_exists('query', $options) && count($params) > 0) {
            $options['query'] = $params;
        }

        if (! array_key_exists('json', $options) && is_array($body)) {
            $options['json'] = $body;
        }

        if (! array_key_exists('form_params', $options) && is_array($formData)) {
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
