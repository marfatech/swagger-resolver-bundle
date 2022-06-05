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

use OpenApi\Annotations\Property;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function array_unique;
use function count;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class ArrayUniqueItemsValidator extends AbstractArrayValidator
{
    /**
     * {@inheritdoc}
     */
    public function supports(Property $property): bool
    {
        return parent::supports($property) && $property->uniqueItems === true;
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

        $value = $this->convertValueToArray($propertyName, $value, $property->collectionFormat);

        $itemsUnique = array_unique($value);

        if (count($itemsUnique) !== count($value)) {
            $message = sprintf('Property "%s" should contains unique items', $propertyName);

            throw new InvalidOptionsException($message);
        }
    }
}
