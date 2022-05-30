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
    public function supports(Schema $property, array $context = []): bool
    {
        return parent::supports($property, $context) && $property->uniqueItems === true;
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

        $itemsUnique = array_unique($value);

        if (count($itemsUnique) !== count($value)) {
            $message = sprintf('Property "%s" should contains unique items', $propertyName);

            throw new InvalidOptionsException($message);
        }
    }
}
