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
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function in_array;
use function is_int;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class NumberMultipleOfValidator implements OpenApiValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $property, array $context = []): bool
    {
        $isNumericType = in_array($property->type, [ParameterTypeEnum::NUMBER, ParameterTypeEnum::INTEGER], true);

        return $isNumericType && !Generator::isDefault($property->multipleOf);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Schema $property, string $propertyName, $value): void
    {
        if ($value === null) {
            return;
        }

        $divisionResult = $value / $property->multipleOf;

        if (!is_int($divisionResult)) {
            $message = sprintf(
                'Property "%s" should be an integer after division by %s',
                $propertyName,
                $property->multipleOf
            );

            throw new InvalidOptionsException($message);
        }
    }
}
