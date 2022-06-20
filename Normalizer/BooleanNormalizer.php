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
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use Linkin\Bundle\SwaggerResolverBundle\Exception\NormalizationFailedException;
use OpenApi\Annotations\Property;
use Symfony\Component\OptionsResolver\Options;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class BooleanNormalizer implements OpenApiNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Property $property): bool
    {
        return $property->type === ParameterTypeEnum::BOOLEAN;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizer(Property $property): Closure
    {
        $propertyName = $property->property;

        return static function (Options $options, $value) use ($propertyName) {
            if ($value === null) {
                return null;
            }

            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                return true;
            }

            if ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
                return false;
            }

            throw new NormalizationFailedException($propertyName, (string) $value);
        };
    }
}
