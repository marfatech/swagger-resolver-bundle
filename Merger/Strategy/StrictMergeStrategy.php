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
use RuntimeException;

use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class StrictMergeStrategy extends AbstractMergeStrategy
{
    /**
     * {@inheritdoc}
     */
    public function addParameter(string $parameterSource, Property $property, bool $required): void
    {
        $name = $property->property;

        if (isset($this->parameters[$name])) {
            $message = sprintf(
                'Parameter "%s" in "%s" has duplicate. Rename parameter or use another merger strategy',
                $name,
                $parameterSource
            );

            throw new RuntimeException($message);
        }

        if ($required === true) {
            $this->required[$name] = $name;
        }

        $this->parameters[$name] = $property;
    }
}
