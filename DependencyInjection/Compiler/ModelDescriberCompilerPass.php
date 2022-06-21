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

namespace Linkin\Bundle\SwaggerResolverBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class ModelDescriberCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['NelmioApiDocBundle'])) {
            return;
        }

        $nelmioConfig = $container->getParameter('linkin_swagger_resolver.nelmio_config');
        $container->getParameterBag()->remove('linkin_swagger_resolver.nelmio_config');

        $objectModelDescriber = $container->getDefinition('linkin_swagger_resolver.model_describers.object');

        $objectModelDescriber->setArgument('$mediaTypes', $nelmioConfig['media_types']);
    }
}
