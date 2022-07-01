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

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use function method_exists;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class CacheWarmer implements CacheWarmerInterface
{
    private OpenApiConfigurationInterface $apiConfiguration;

    public function __construct(OpenApiConfigurationInterface $apiConfiguration)
    {
        $this->apiConfiguration = $apiConfiguration;
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function warmUp(string $cacheDir)
    {
        if (!method_exists($this->apiConfiguration, 'warmUp')) {
            return [];
        }

        return $this->apiConfiguration->warmUp();
    }
}
