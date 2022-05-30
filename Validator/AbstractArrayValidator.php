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

use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterCollectionFormatEnum;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use OpenApi\Annotations\Schema;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function explode;
use function is_array;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
abstract class AbstractArrayValidator implements OpenApiValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $property, array $context = []): bool
    {
        return $property->type === ParameterTypeEnum::ARRAY;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function validate(Schema $property, string $propertyName, $value): void;

    protected function convertValueToArray(string $propertyName, $value, ?string $collectionFormat): array
    {
        if ($value === null) {
            return [];
        }

        if ($collectionFormat === null) {
            if (is_array($value)) {
                return $value;
            }

            throw new InvalidOptionsException(sprintf('Property "%s" should contain valid json array', $propertyName));
        }

        if (is_array($value)) {
            $message = sprintf('Property "%s" should contain valid "%s" string', $propertyName, $collectionFormat);

            throw new InvalidOptionsException($message);
        }

        $delimiter = ParameterCollectionFormatEnum::getDelimiter($collectionFormat);
        $arrayValue = explode($delimiter, $value);

        if (ParameterCollectionFormatEnum::MULTI === $delimiter) {
            foreach ($arrayValue as &$item) {
                $exploded = explode('=', $item);
                $item = $exploded[1];
            }
        }

        return $arrayValue;
    }
}
