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

use EXSyst\Component\Swagger\Schema;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function mb_strlen;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class StringMaxLengthValidator implements SwaggerValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $property, array $context = []): bool
    {
        return ParameterTypeEnum::STRING === $property->getType() && null !== $property->getMaxLength();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Schema $property, string $propertyName, $value): void
    {
        if (mb_strlen($value) > $property->getMaxLength()) {
            throw new InvalidOptionsException(sprintf(
                'Property "%s" should have %s character or less',
                $propertyName,
                $property->getMaxLength()
            ));
        }
    }
}
