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

use function preg_match;
use function sprintf;
use function trim;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class StringPatternValidator implements OpenApiValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $property, array $context = []): bool
    {
        return $property->type === ParameterTypeEnum::STRING && !Generator::isDefault($property->pattern);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Schema $property, string $propertyName, $value): void
    {
        if ($value === null) {
            return;
        }

        $pattern = sprintf('/%s/', trim($property->pattern, '/'));

        if (!preg_match($pattern, $value)) {
            $message = sprintf('Property "%s" should match the pattern "%s"', $propertyName, $pattern);

            throw new InvalidOptionsException($message);
        }
    }
}
