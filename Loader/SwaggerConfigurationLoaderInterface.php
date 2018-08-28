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

namespace Linkin\Bundle\SwaggerResolverBundle\Loader;

use EXSyst\Component\Swagger\Swagger;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
interface SwaggerConfigurationLoaderInterface
{
    /**
     * Loads swagger configuration from the annotations.
     *
     * @return Swagger
     */
    public function loadConfiguration(): Swagger;
}