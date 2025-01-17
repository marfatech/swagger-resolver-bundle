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

namespace Linkin\Bundle\SwaggerResolverBundle\Merger\Strategy;

use OpenApi\Annotations\Property;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class ReplaceFirstWinMergeStrategy extends AbstractMergeStrategy
{
    /**
     * {@inheritdoc}
     */
    public function addParameter(string $parameterSource, Property $property, bool $required): void
    {
        $name = $property->property;

        if (isset($this->parameters[$name])) {
            return;
        }

        if ($required === true) {
            $this->required[$name] = $name;
        }

        $this->parameters[$name] = $property;
    }
}
