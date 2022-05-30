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

namespace Linkin\Bundle\SwaggerResolverBundle\DependencyInjection;

use Exception;
use Linkin\Bundle\SwaggerResolverBundle\Normalizer\OpenApiNormalizerInterface;
use Linkin\Bundle\SwaggerResolverBundle\Validator\OpenApiValidatorInterface;
use Nelmio\ApiDocBundle\DependencyInjection\Configuration as NelmioApiDocConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class LinkinSwaggerResolverExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('linkin_swagger_resolver.enable_normalization', $config['enable_normalization']);
        $container->setParameter('linkin_swagger_resolver.oa_annotation_config', $config['openapi_annotation']);
        $container->setParameter('linkin_swagger_resolver.oa_file_config', $config['configuration_file']);

        $pathMergeStrategy = $container->getDefinition($config['path_merge_strategy']);
        $container->setDefinition('linkin_swagger_resolver.merge_strategy', $pathMergeStrategy);

        $container
            ->registerForAutoconfiguration(OpenApiValidatorInterface::class)
            ->addTag('linkin_swagger_resolver.validator')
        ;

        $container
            ->registerForAutoconfiguration(OpenApiNormalizerInterface::class)
            ->addTag('linkin_swagger_resolver.normalizer')
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('nelmio_api_doc')) {
            return;
        }

        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['NelmioApiDocBundle'])) {
            $nelmioConfigs = $container->getExtensionConfig('nelmio_api_doc');
            $configuration = new NelmioApiDocConfiguration();
            $nelmioConfig = $this->processConfiguration($configuration, $nelmioConfigs);
        } else {
            $nelmioConfig = [];
        }

        $container->setParameter('linkin_swagger_resolver.nelmio_config', $nelmioConfig);
    }
}
