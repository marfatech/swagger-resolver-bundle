<?php

declare(strict_types=1);

namespace Linkin\Bundle\SwaggerResolverBundle\DependencyInjection\Compiler;

use Linkin\Bundle\SwaggerResolverBundle\Configuration\CacheWarmer;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\CacheWarmerSf4;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;

class CacheWarmerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (Kernel::MAJOR_VERSION === 4) {
            $cacheWarmer = new Definition(CacheWarmerSf4::class);
        } else {
            $cacheWarmer = new Definition(CacheWarmer::class);
        }

        $openapiConfiguration = $container->getDefinition('linkin_swagger_resolver.openapi_configuration');

        $cacheWarmer->addArgument($openapiConfiguration);
        $cacheWarmer->addTag('kernel.cache_warmer');

        $container->setDefinition('linkin_swagger_resolver.cache_warmer', $cacheWarmer);
    }
}
