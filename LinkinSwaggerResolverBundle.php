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

namespace Linkin\Bundle\SwaggerResolverBundle;

use Linkin\Bundle\SwaggerResolverBundle\DependencyInjection\Compiler\CacheWarmerCompilerPass;
use Linkin\Bundle\SwaggerResolverBundle\DependencyInjection\Compiler\ModelDescriberCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class LinkinSwaggerResolverBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->addCompilerPass(new ModelDescriberCompilerPass())
            ->addCompilerPass(new CacheWarmerCompilerPass())
        ;
    }
}
