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

use function mb_strlen;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class StringMaxLengthValidator implements OpenApiValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Property $property): bool
    {
        return $property->type === ParameterTypeEnum::STRING && !Generator::isDefault($property->maxLength);
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

        if (mb_strlen($value) > $property->maxLength) {
            $message = sprintf('Property "%s" should have %s character or less', $propertyName, $property->maxLength);

            throw new InvalidOptionsException($message);
        }
    }
}
