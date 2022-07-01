<?php

declare(strict_types=1);

/*
 * This file is part of the SwaggerResolverBundle package.
 *
 * (c) MarfaTech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Bundle\SwaggerResolverBundle\Configuration;

use ArrayIterator;
use Exception;
use IteratorAggregate;
use Linkin\Bundle\SwaggerResolverBundle\Exception\LoadConfigurationFailedException;
use Linkin\Bundle\SwaggerResolverBundle\Exception\SchemaNotFoundException;
use Linkin\Bundle\SwaggerResolverBundle\Merger\OperationParameterMerger;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Schema;
use OpenApi\Generator as OAGenerator;
use OpenApi\Serializer;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

use function sprintf;
use function str_replace;
use function strtoupper;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class OpenApiConfiguration implements IteratorAggregate, OpenApiConfigurationInterface
{
    private const CACHE_KEY = 'linkin_swagger_resolver';
    private const METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    /**
     * @var Schema[][]
     */
    private ?array $mergedSchemaList = null;

    /**
     * @var Schema[]
     */
    private ?array $schemaList = null;

    /**
     * @var array<string, OpenApi>
     */
    private array $openApiList;

    private RouterInterface $router;
    private OperationParameterMerger $operationParameterMerger;
    private Serializer $serializer;
    private ?CacheInterface $cache;

    /**
     * @param array<string, OpenApi> $openApiList
     */
    public function __construct(
        array $openApiList,
        RouterInterface $router,
        OperationParameterMerger $operationParameterMerger,
        Serializer $serializer,
        ?CacheInterface $cache = null
    ) {
        $this->openApiList = $openApiList;
        $this->router = $router;
        $this->operationParameterMerger = $operationParameterMerger;
        $this->serializer = $serializer;
        $this->cache = $cache;
    }

    /**
     * @return array<string, Schema>|ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        $schemaList = [];

        foreach ($this->openApiList as $openApi) {
            foreach ($openApi->components->schemas as $schema) {
                $schemaList[$schema->schema] = $schema;
            }
        }

        return new ArrayIterator($schemaList);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getSchema(string $needleSchemaName): Schema
    {
        $schemaName = str_replace(Components::SCHEMA_REF, '', $needleSchemaName);

        if ($this->cache) {
            $cacheKey = sprintf('%s.schema.%s', self::CACHE_KEY, $schemaName);

            $serializedSchema = $this->cache->get($cacheKey, function () use ($schemaName) {
                return $this->serializeSchema($schemaName);
            });
        } else {
            $serializedSchema = $this->serializeSchema($schemaName);
        }

        return $this->serializer->deserialize($serializedSchema, Schema::class);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getMergedSchema(string $routeName, string $method): Schema
    {
        $method = strtoupper($method);

        if ($this->cache) {
            $cacheKey = sprintf('%s.path.%s.%s', self::CACHE_KEY, $routeName, $method);

            $serializedMergedSchema = $this->cache->get($cacheKey, function () use ($routeName, $method) {
                return $this->serializeMergedSchema($routeName, $method);
            });
        } else {
            $serializedMergedSchema = $this->serializeMergedSchema($routeName, $method);
        }

        return $this->serializer->deserialize($serializedMergedSchema, Schema::class);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function warmUp(): array
    {
        if (!$this->cache) {
            return [];
        }

        $mergedRouteSchemaList = $this->buildMergedRouteSchemaList();
        $schemaList = $this->buildSchemaList();

        /** @var Route $route */
        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            foreach ($route->getMethods() as $method) {
                if (!isset($mergedRouteSchemaList[$method][$routeName])) {
                    continue;
                }

                $this->getMergedSchema($method, $routeName);
            }
        }

        foreach ($schemaList as $schemaName => $_) {
            $this->getSchema($schemaName);
        }

        return [];
    }

    private function serializeSchema(string $schemaName): string
    {
        $schemaList = $this->buildSchemaList();

        $schema = $schemaList[$schemaName] ?? null;

        if ($schema) {
            return $schema->toJson();
        }

        throw new SchemaNotFoundException($schemaName);
    }

    /**
     * @throws Exception
     */
    private function serializeMergedSchema(string $routeName, string $method): string
    {
        $mergedRouteSchemaList = $this->buildMergedRouteSchemaList();

        $mergedSchema = $mergedRouteSchemaList[$routeName][$method] ?? null;

        if ($mergedSchema) {
            return $mergedSchema->toJson();
        }

        throw new SchemaNotFoundException(sprintf('%s %s', $method, $routeName));
    }

    /**
     * @return Schema[]
     */
    private function buildSchemaList(): array
    {
        if ($this->schemaList) {
            return $this->schemaList;
        }

        $buildSchemaList = [];

        foreach ($this as $schema) {
            $schemaName = $schema->schema;

            $buildSchemaList[$schemaName] = $schema;
        }

        return $this->schemaList = $buildSchemaList;
    }

    /**
     * @return Schema[][]
     *
     * @throws Exception
     */
    private function buildMergedRouteSchemaList(): array
    {
        if ($this->mergedSchemaList) {
            return $this->mergedSchemaList;
        }

        $routeList = [];

        /** @var Route $route */
        foreach ($this->router->getRouteCollection() as $name => $route) {
            foreach ($route->getMethods() as $method) {
                $routeList[$route->getPath()][$method] = $name;
            }
        }

        $mergedSchemaList = [];

        foreach ($this->openApiList as $openApi) {
            foreach ($openApi->paths as $path) {
                foreach (self::METHODS as $method) {
                    if (OAGenerator::isDefault($path->$method)) {
                        continue;
                    }

                    /** @var Operation $operation */
                    $operation = $path->$method;
                    $operationPath = OAGenerator::isDefault($path->path) ? $operation->path : $path->path;

                    if (OAGenerator::isDefault($operationPath)) {
                        $message = sprintf('Cannot find Open API operation path in "%s"', $path->toJson());

                        throw new LoadConfigurationFailedException($message);
                    }

                    $method = strtoupper($method);
                    $routeName = $routeList[$operationPath][$method] ?? null;

                    if (!$routeName) {
                        $message = sprintf('Cannot find route name by Open API operation path "%s"', $operationPath);

                        throw new LoadConfigurationFailedException($message);
                    }

                    $mergedSchema = $this->operationParameterMerger->merge($operation, $this);

                    $mergedSchemaList[$routeName][$method] = $mergedSchema;
                }
            }
        }

        return $this->mergedSchemaList = $mergedSchemaList;
    }
}
