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
use OpenApi\Annotations\Schema;
use Symfony\Component\OptionsResolver\Options;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class BooleanNormalizer implements OpenApiNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $propertySchema, string $propertyName, bool $isRequired, array $context = []): bool
    {
        return $propertySchema->type === ParameterTypeEnum::BOOLEAN;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizer(Schema $propertySchema, string $propertyName, bool $isRequired): Closure
    {
        return static function (Options $options, $value) use ($isRequired, $propertyName) {
            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                return true;
            }

            if ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
                return false;
            }

            if (!$isRequired && $value === null) {
                return null;
            }

            throw new NormalizationFailedException($propertyName, (string) $value);
        };
    }
}
