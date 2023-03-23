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

namespace Linkin\Bundle\SwaggerResolverBundle\Validator;

use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use OpenApi\Annotations\Property;
use OpenApi\Generator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function in_array;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class NumberMinimumValidator implements OpenApiValidatorInterface
{
    private bool $exclusiveMinimum = false;

    /**
     * {@inheritdoc}
     */
    public function supports(Property $property): bool
    {
        $isNumericType = in_array($property->type, [ParameterTypeEnum::NUMBER, ParameterTypeEnum::INTEGER], true);

        return $isNumericType && !Generator::isDefault($property->minimum);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Property $property, $value): void
    {
        if ($value === null) {
            return;
        }

        $propertyName = $property->property;

        $message = sprintf('Property "%s" value should be', $propertyName);
        $minimum = $property->minimum;
        $this->exclusiveMinimum = false;

        if (!Generator::isDefault($property->exclusiveMinimum)) {
            $this->exclusiveMinimum = $property->exclusiveMinimum;
        }

        if ($this->exclusiveMinimum === true && $value <= $minimum) {
            $message = sprintf('%s strictly greater than %s', $message, $minimum);

            throw new InvalidOptionsException($message);
        }

        if ($this->exclusiveMinimum === false && $value < $minimum) {
            $message = sprintf('%s greater than or equal to %s', $message, $minimum);

            throw new InvalidOptionsException($message);
        }
    }
}
