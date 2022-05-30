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

use DateTime;
use Exception;
use OpenApi\Annotations\Schema;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Throwable;

use function preg_match;
use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
abstract class AbstractFormatDateValidator implements OpenApiValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Schema $property, array $context = []): bool
    {
        return $property->format === $this->getSupportedFormatName();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Schema $property, string $propertyName, $value): void
    {
        if (empty($value)) {
            return;
        }

        if ($property->pattern === null) {
            $this->validateDatePattern($propertyName, $value);
        }

        try {
            $this->createDateFromValue($value);
        } catch (Throwable $exception) {
            $supportedFormatName = $this->getSupportedFormatName();
            $message = sprintf('Property "%s" contains invalid %s value', $propertyName, $supportedFormatName);

            throw new InvalidOptionsException($message);
        }
    }

    /**
     * @return string
     */
    abstract protected function getDefaultPattern(): string;

    /**
     * @return string
     */
    abstract protected function getSupportedFormatName(): string;

    /**
     * @throws Exception
     */
    protected function createDateFromValue($value): DateTime
    {
        return new DateTime($value);
    }

    protected function validateDatePattern(string $propertyName, $value): void
    {
        $pattern = sprintf('/%s/', $this->getDefaultPattern());

        if (!preg_match($pattern, $value)) {
            $message = sprintf(
                'Property "%s" should match the pattern "%s". Set pattern explicitly to avoid this exception',
                $propertyName,
                $this->getDefaultPattern()
            );

            throw new InvalidOptionsException($message);
        }
    }
}
