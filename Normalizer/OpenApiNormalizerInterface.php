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

namespace Linkin\Bundle\SwaggerResolverBundle\Normalizer;

use Closure;
use OpenApi\Annotations\Schema;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
interface OpenApiNormalizerInterface
{
    /**
     * Check is this normalizer supports received property
     */
    public function supports(Schema $propertySchema, string $propertyName, bool $isRequired, array $context = []): bool;

    /**
     * Returns closure for normalizing property
     */
    public function getNormalizer(Schema $propertySchema, string $propertyName, bool $isRequired): Closure;
}