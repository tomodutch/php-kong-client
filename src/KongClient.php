<?php

declare(strict_types=1);

namespace TFarla\KongClient;

use Http\Client\HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TFarla\KongClient\Route\RouteTransformer;

/**
 * Class KongClient
 * @package TFarla\KongClient
 */
class KongClient
{
    /**
     * @var JsonClient
     */
    private $jsonClient;

    /**
     * KongClient constructor.
     * @param HttpClient $httpClient
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->jsonClient = new JsonClient(
            $httpClient,
            $requestFactory,
            $streamFactory
        );
    }

    /**
     * @param int|null $size
     * @return ServicePaginatedResult
     * @throws \Http\Client\Exception
     */
    public function getServices(?int $size = null, string $offset = null): ServicePaginatedResult
    {
        $queryParams = [];
        if (!is_null($size)) {
            $queryParams['size'] = $size;
        }

        if (!is_null($offset)) {
            $queryParams['offset'] = $offset;
        }


        $response = $this->jsonClient->get('/services', [], $queryParams);
        $body = $this->jsonClient->readBody($response);

        $next = $body['next'] ?? null;
        $offset = $body['offset'] ?? null;
        $data = [];
        foreach ($body['data'] as $rawService) {
            $data[] = ServiceTransformer::fromJson($rawService);
        }

        $result = new ServicePaginatedResult($data, $next, $offset);

        return $result;
    }

    /**
     * @param string $nameOrId
     * @return Service|null
     * @throws \Http\Client\Exception
     */
    public function getService(string $nameOrId): ?Service
    {
        $response = $this->jsonClient->get("/services/$nameOrId");
        $body = $this->jsonClient->readBody($response);

        return ServiceTransformer::fromJson($body);
    }

    /**
     * @param Service $service
     * @return Service
     * @throws \Http\Client\Exception
     */
    public function postService(Service $service): Service
    {
        $pairs = [
            ['name', $service->getName()],
            ['url', $service->getUrl()],
            ['protocol', $service->getProtocol()],
            ['host', $service->getHost()],
            ['port', $service->getPort()],
            ['path', $service->getPath()],
            ['retries', $service->getRetries()],
            ['read_timeout', $service->getReadTimeout()],
            ['write_timeout', $service->getWriteTimeout()],
            ['connect_timeout', $service->getConnectTimeout()]
        ];

        $requestBody = [];
        foreach ($pairs as list($key, $value)) {
            if (!is_null($value)) {
                $requestBody[$key] = $value;
            }
        }

        $response = $this->jsonClient->post('/services', [], [], $requestBody);
        $body = $this->jsonClient->readBody($response);

        return ServiceTransformer::fromJson($body);
    }

    /**
     * @param Service $service
     * @return Service
     * @throws \Http\Client\Exception
     */
    public function putService(Service $service): Service
    {
        $id = $service->getId();
        if (is_null($id)) {
            throw new \InvalidArgumentException('Can not update a service when it has no id');
        }

        $uri = "/services/$id";
        $rawService = ServiceTransformer::toArray($service);
        $response = $this->jsonClient->put($uri, [], [], $rawService);
        $body = $this->jsonClient->readBody($response);

        return ServiceTransformer::fromJson($body);
    }

    /**
     * @param string $id
     * @throws \Http\Client\Exception
     */
    public function deleteService(string $id): void
    {
        $uri = "/services/$id";
        $this->jsonClient->delete($uri);
    }

    /**
     * @param int|null $size
     * @param string|null $offset
     * @return RoutePaginatedResult
     * @throws \Http\Client\Exception
     */
    public function getRoutes(?int $size = null, ?string $offset = null): RoutePaginatedResult
    {
        $queryParams = [];
        if (!is_null($size)) {
            $queryParams['size'] = $size;
        }

        if (!is_null($offset)) {
            $queryParams['offset'] = $offset;
        }

        $response = $this->jsonClient->get('/routes', [], $queryParams);
        $body = $this->jsonClient->readBody($response);
        $next = $body['next'] ?? null;
        $offset = $body['offset'] ?? null;

        $routes = [];
        foreach (($body['data'] ?? []) as $rawRoute) {
            $route = RouteTransformer::fromArray($rawRoute);
            $routes[] = $route;
        }

        return new RoutePaginatedResult($routes, $next, $offset);
    }

    /**
     * @param string $id
     * @return Route
     * @throws \Http\Client\Exception
     */
    public function getRoute(string $id): Route
    {
        $response = $this->jsonClient->get("/routes/$id");
        $body = $this->jsonClient->readBody($response);

        return RouteTransformer::fromArray($body);
    }

    /**
     * @param Route $route
     * @return Route
     * @throws \Http\Client\Exception
     */
    public function postRoute(Route $route): Route
    {
        $requestBody = RouteTransformer::toArray($route);
        $response = $this->jsonClient->post('/routes', [], [], $requestBody);
        $body = $this->jsonClient->readBody($response);

        return RouteTransformer::fromArray($body);
    }

    /**
     * @param Route $route
     * @return Route
     * @throws \Http\Client\Exception
     */
    public function putRoute(Route $route): Route
    {
        $id = $route->getId();
        if (is_null($id)) {
            throw new \InvalidArgumentException('Can not update a route when it has no id');
        }

        $uri = "/routes/$id";
        $requestBody = RouteTransformer::toArray($route);
        $response = $this->jsonClient->put($uri, [], [], $requestBody);
        $body = $this->jsonClient->readBody($response);

        return RouteTransformer::fromArray($body);
    }

    /**
     * @param string $id
     * @throws \Http\Client\Exception
     */
    public function deleteRoute(string $id): void
    {
        $uri = "/routes/$id";
        $this->jsonClient->delete($uri);
    }
}
