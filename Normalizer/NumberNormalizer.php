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

use function is_numeric;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class NumberNormalizer implements OpenApiNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Property $propertySchema): bool
    {
        return $propertySchema->type === ParameterTypeEnum::NUMBER;
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

            if (is_numeric($value)) {
                return (float) $value;
            }

            throw new NormalizationFailedException($propertyName, (string) $value);
        };
    }
}
