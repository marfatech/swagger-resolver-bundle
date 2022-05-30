<?php

declare(strict_types=1);

/*
 * This file is part of the SwaggerResolverBundle package.
 *
 * (c) Viktor Linkin <adrenalinkin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Bundle\SwaggerResolverBundle\Factory;

use Exception;
use Linkin\Bundle\SwaggerResolverBundle\Builder\OpenApiResolverBuilder;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfigurationInterface;
use Linkin\Bundle\SwaggerResolverBundle\Exception\SchemaNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

use function end;
use function explode;
use function strtolower;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class OpenApiResolverFactory
{
    private OpenApiResolverBuilder $openApiResolverBuilder;
    private OpenApiConfigurationInterface $openApiConfiguration;
    private RouterInterface $router;

    public function __construct(
        OpenApiResolverBuilder $openApiResolverBuilder,
        OpenApiConfigurationInterface $openApiConfiguration,
        RouterInterface $router
    ) {
        $this->openApiResolverBuilder = $openApiResolverBuilder;
        $this->openApiConfiguration = $openApiConfiguration;
        $this->router = $router;
    }

    /**
     * @throws Exception
     */
    public function createForRequest(Request $request): OptionsResolver
    {
        $pathInfo = $this->router->match($request->getPathInfo());
        $routeName = $pathInfo['_route'];
        $method = strtolower($request->getMethod());

        $mergedSchema = $this->openApiConfiguration->getMergedSchema($routeName, $method);

        return $this->openApiResolverBuilder->build($mergedSchema);
    }

    /**
     * @throws Exception
     */
    public function createForSchema(string $schemaName): OptionsResolver
    {
        $explodedName = explode('\\', $schemaName);
        $name = end($explodedName);

        if (!$name) {
            throw new SchemaNotFoundException($schemaName);
        }

        $schema = $this->openApiConfiguration->getSchema($name);

        return $this->openApiResolverBuilder->build($schema);
    }
}
