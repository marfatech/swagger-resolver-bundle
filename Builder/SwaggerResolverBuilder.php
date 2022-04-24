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

namespace Linkin\Bundle\SwaggerResolverBundle\Builder;

use EXSyst\Component\Swagger\Schema;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use Linkin\Bundle\SwaggerResolverBundle\Exception\UndefinedPropertyTypeException;
use Linkin\Bundle\SwaggerResolverBundle\Normalizer\SwaggerNormalizerInterface;
use Linkin\Bundle\SwaggerResolverBundle\Resolver\SwaggerResolver;
use Linkin\Bundle\SwaggerResolverBundle\Validator\SwaggerValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function in_array;
use function is_array;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class SwaggerResolverBuilder
{
    /**
     * @var array
     */
    private $normalizationLocations;

    /**
     * @var SwaggerNormalizerInterface[]
     */
    private $swaggerNormalizers;

    /**
     * @var SwaggerValidatorInterface[]
     */
    private $swaggerValidators;

    /**
     * @var ValidatorInterface|null
     */
    private $validator;

    /**
     * @param SwaggerValidatorInterface[] $swaggerValidators
     * @param SwaggerNormalizerInterface[] $swaggerNormalizers
     * @param array $normalizationLocations
     * @param ValidatorInterface|null $validator
     */
    public function __construct(
        array $swaggerValidators,
        array $swaggerNormalizers,
        array $normalizationLocations,
        ?ValidatorInterface $validator = null
    ) {
        $this->normalizationLocations = $normalizationLocations;
        $this->swaggerNormalizers = $swaggerNormalizers;
        $this->swaggerValidators = $swaggerValidators;
        $this->validator = $validator;
    }

    /**
     * @param Schema $definition
     * @param string $definitionName
     *
     * @return SwaggerResolver
     *
     * @throws UndefinedPropertyTypeException
     */
    public function build(Schema $definition, string $definitionName): SwaggerResolver
    {
        $swaggerResolver = new SwaggerResolver($definition);

        $requiredProperties = $definition->getRequired();

        if (is_array($requiredProperties)) {
            $swaggerResolver->setRequired($requiredProperties);
        }

        $propertiesCount = $definition->getProperties()->getIterator()->count();

        if (0 === $propertiesCount) {
            return $swaggerResolver;
        }

        /** @var Schema $propertySchema */
        foreach ($definition->getProperties() as $name => $propertySchema) {
            $swaggerResolver->setDefined($name);

            $allowedTypes = $this->getAllowedTypes($propertySchema);

            if (null === $allowedTypes) {
                $propertyType = $propertySchema->getType() ?? '';

                throw new UndefinedPropertyTypeException($definitionName, $name, $propertyType);
            }

            $isNullable = $propertySchema->getExtensions()[ParameterExtensionEnum::X_NULLABLE] ?? null;

            if ($isNullable === true) {
                $allowedTypes[] = 'null';
            }

            $swaggerResolver->setAllowedTypes($name, $allowedTypes);
            $swaggerResolver = $this->addNormalization($swaggerResolver, $name, $propertySchema);
            $swaggerResolver = $this->addConstraint($swaggerResolver, $name, $definition);

            if (null !== $propertySchema->getDefault()) {
                $swaggerResolver->setDefault($name, $propertySchema->getDefault());
            }

            if (!empty($propertySchema->getEnum())) {
                $swaggerResolver->setAllowedValues($name, (array) $propertySchema->getEnum());
            }
        }

        foreach ($this->swaggerValidators as $validator) {
            $swaggerResolver->addValidator($validator);
        }

        return $swaggerResolver;
    }

    /**
     * @param SwaggerResolver $resolver
     * @param string $name
     * @param Schema $definition
     *
     * @return SwaggerResolver
     */
    private function addConstraint(SwaggerResolver $resolver, string $name, Schema $definition): SwaggerResolver
    {
        if (!$this->validator) {
            return $resolver;
        }

        $definitionClass = $definition->getExtensions()[ParameterExtensionEnum::X_CLASS] ?? null;

        if (!$definitionClass) {
            return $resolver;
        }

        if (!$this->validator->hasMetadataFor($definitionClass)) {
            return $resolver;
        }

        /** @var ClassMetadata $definitionMetadata */
        $definitionMetadata = $this->validator->getMetadataFor($definitionClass);
        $propertyMetadataList = $definitionMetadata->getPropertyMetadata($name);

        foreach ($propertyMetadataList as $propertyMetadata) {
            if (!$propertyMetadata->getConstraints()) {
                continue;
            }

            $resolver->addAllowedValues($name, function ($value) use ($definitionClass, $name) {
                $violations = $this->validator
                    ->startContext()
                    ->atPath($name)
                    ->validatePropertyValue($definitionClass, $name, $value)
                    ->getViolations()
                ;

                if ($violations->count() > 0) {
                    throw new ValidationFailedException($value, $violations);
                }

                return true;
            });
        }

        return $resolver;
    }

    /**
     * @param SwaggerResolver $resolver
     * @param string $name
     * @param Schema $propertySchema
     *
     * @return SwaggerResolver
     */
    private function addNormalization(SwaggerResolver $resolver, string $name, Schema $propertySchema): SwaggerResolver
    {
        /** @see \Linkin\Bundle\SwaggerResolverBundle\Merger\OperationParameterMerger parameter location in title */
        if (!in_array($propertySchema->getTitle(), $this->normalizationLocations, true)) {
            return $resolver;
        }

        $isRequired = $resolver->isRequired($name);

        foreach ($this->swaggerNormalizers as $normalizer) {
            if (!$normalizer->supports($propertySchema, $name, $isRequired)) {
                continue;
            }

            $closure = $normalizer->getNormalizer($propertySchema, $name, $isRequired);

            return $resolver
                ->setNormalizer($name, $closure)
                ->addAllowedTypes($name, 'string')
            ;
        }

        return $resolver;
    }

    /**
     * @param Schema $propertySchema
     *
     * @return array
     */
    private function getAllowedTypes(Schema $propertySchema): ?array
    {
        $propertyType = $propertySchema->getType();
        $allowedTypes = [];

        if (ParameterTypeEnum::STRING === $propertyType) {
            $allowedTypes[] = 'string';

            return $allowedTypes;
        }

        if (ParameterTypeEnum::INTEGER === $propertyType) {
            $allowedTypes[] = 'integer';
            $allowedTypes[] = 'int';

            return $allowedTypes;
        }

        if (ParameterTypeEnum::BOOLEAN === $propertyType) {
            $allowedTypes[] = 'boolean';
            $allowedTypes[] = 'bool';

            return $allowedTypes;
        }

        if (ParameterTypeEnum::NUMBER === $propertyType) {
            $allowedTypes[] = 'double';
            $allowedTypes[] = 'float';

            return $allowedTypes;
        }

        if (ParameterTypeEnum::ARRAY === $propertyType) {
            $allowedTypes[] = null === $propertySchema->getCollectionFormat() ? 'array' : 'string';

            return $allowedTypes;
        }

        if ('object' === $propertyType) {
            $allowedTypes[] = 'object';
            $allowedTypes[] = 'array';

            return $allowedTypes;
        }

        if (null === $propertyType && $propertySchema->getRef()) {
            $ref = $propertySchema->getRef();

            $allowedTypes[] = 'object';
            $allowedTypes[] = 'array';
            $allowedTypes[] = $ref;

            return $allowedTypes;
        }

        return null;
    }
}
