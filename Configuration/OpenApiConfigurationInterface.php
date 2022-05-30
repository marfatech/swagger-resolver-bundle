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

namespace Linkin\Bundle\SwaggerResolverBundle\Configuration;

use Linkin\Bundle\SwaggerResolverBundle\Merger\MergeStrategyInterface;
use Linkin\Bundle\SwaggerResolverBundle\Merger\OperationParameterMerger;
use OpenApi\Annotations\Schema;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
interface OpenApiConfigurationInterface
{
    /**
     * Return Open API schema object
     */
    public function getSchema(string $needleSchemaName): Schema;

    /**
     * Returns merged Open API path operation by @see OperationParameterMerger
     * according to specific @see MergeStrategyInterface
     */
    public function getMergedSchema(string $routeName, string $method): Schema;
}
