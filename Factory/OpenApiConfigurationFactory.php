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

namespace Linkin\Bundle\SwaggerResolverBundle\Factory;

use Exception;
use Generator;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfiguration;
use Linkin\Bundle\SwaggerResolverBundle\Exception\LoadConfigurationFailedException;
use Linkin\Bundle\SwaggerResolverBundle\Merger\OperationParameterMerger;
use Nelmio\ApiDocBundle\ApiDocGenerator;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use OpenApi\Generator as OpenApiGenerator;
use OpenApi\Serializer;
use OpenApi\Util;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function class_exists;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class OpenApiConfigurationFactory
{
    private RouterInterface $router;
    private OperationParameterMerger $operationParameterMerger;
    private array $oaAnnotationConfig;
    private array $oaFileConfig;
    private Serializer $serializer;
    private ?CacheInterface $cache;
    private ?ServiceProviderInterface $nelmioApiDocAreaLocator;

    public function __construct(
        RouterInterface $router,
        OperationParameterMerger $operationParameterMerger,
        array $oaAnnotationConfig,
        array $oaFileConfig,
        Serializer $serializer,
        ?CacheInterface $cache = null,
        ?ServiceProviderInterface $nelmioApiDocLoaderLocator = null
    ) {
        $this->router = $router;
        $this->operationParameterMerger = $operationParameterMerger;
        $this->oaAnnotationConfig = $oaAnnotationConfig;
        $this->oaFileConfig = $oaFileConfig;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->nelmioApiDocAreaLocator = $nelmioApiDocLoaderLocator;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function __invoke(): OpenApiConfiguration
    {
        if (class_exists(NelmioApiDocBundle::class)) {
            return $this->createInstanceByNelmioApiDoc();
        }

        if ($this->oaAnnotationConfig) {
            return $this->createInstanceByOpenApiAnnotation();
        }

        if ($this->oaFileConfig) {
            return $this->createInstanceBySerializedConfig();
        }

        $message = 'You have to install "nelmio/api-doc-bundle" or add config to linkin_swagger_resolver package';

        throw new LoadConfigurationFailedException($message);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function createInstanceByNelmioApiDoc(): OpenApiConfiguration
    {
        return new OpenApiConfiguration(
            $this->getOpenApiGeneratorByNelmioApiDoc(),
            $this->router,
            $this->operationParameterMerger,
            $this->serializer,
            $this->cache
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function getOpenApiGeneratorByNelmioApiDoc(): Generator
    {
        $apiDocGeneratorList = $this->nelmioApiDocAreaLocator->getProvidedServices();

        foreach ($apiDocGeneratorList as $area => $_) {
            /** @var ApiDocGenerator $openApi */
            $openApi = $this->nelmioApiDocAreaLocator->get($area);
            yield $area => $openApi->generate();
        }
    }

    private function createInstanceByOpenApiAnnotation(): OpenApiConfiguration
    {
        return new OpenApiConfiguration(
            $this->getOpenApiGeneratorByOpenApiAnnotation(),
            $this->router,
            $this->operationParameterMerger,
            $this->serializer,
            $this->cache
        );
    }

    private function getOpenApiGeneratorByOpenApiAnnotation(): Generator
    {
        foreach ($this->oaAnnotationConfig as $area => $openapiAnnotation) {
            $scan = $openapiAnnotation['scan'];
            $exclude = $openapiAnnotation['exclude'];

            $finder = Util::finder($scan, $exclude);

            yield $area => OpenApiGenerator::scan($finder);
        }
    }

    /**
     * @throws Exception
     */
    private function createInstanceBySerializedConfig(): OpenApiConfiguration
    {
        return new OpenApiConfiguration(
            $this->getOpenApiGeneratorBySerializedConfig(),
            $this->router,
            $this->operationParameterMerger,
            $this->serializer,
            $this->cache
        );
    }

    /**
     * @throws Exception
     */
    private function getOpenApiGeneratorBySerializedConfig(): Generator
    {
        foreach ($this->oaFileConfig as $area => $openapiSerializedConfig) {
            $file = $openapiSerializedConfig['file'];

            yield $area => $this->serializer->deserializeFile($file);
        }
    }
}
