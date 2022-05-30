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

use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function count;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class ArrayMinItemsValidator extends AbstractArrayValidator
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $property, array $context = []): bool
    {
        return parent::supports($property, $context) && !Generator::isDefault($property->minItems);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Schema $property, string $propertyName, $value): void
    {
        if ($value === null) {
            return;
        }

        $value = $this->convertValueToArray($propertyName, $value, $property->collectionFormat);

        if (count($value) < $property->minItems) {
            $message = sprintf('Property "%s" should have %s items or more', $propertyName, $property->minItems);

            throw new InvalidOptionsException($message);
        }
    }
}
