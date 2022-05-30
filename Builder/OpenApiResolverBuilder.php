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

use Exception;
use IteratorAggregate;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use Linkin\Bundle\SwaggerResolverBundle\Exception\UndefinedPropertyTypeException;
use Linkin\Bundle\SwaggerResolverBundle\Normalizer\OpenApiNormalizerInterface;
use Linkin\Bundle\SwaggerResolverBundle\Validator\OpenApiValidatorInterface;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function in_array;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class OpenApiResolverBuilder
{
    /**
     * @var array<int, OpenApiNormalizerInterface>|IteratorAggregate
     */
    private IteratorAggregate $openApiNormalizers;

    /**
     * @var array<int, OpenApiValidatorInterface>|IteratorAggregate
     */
    private IteratorAggregate $openApiValidators;

    private array $normalizationLocations;
    private ?ValidatorInterface $validator;

    /**
     * @param array<int, OpenApiValidatorInterface>|IteratorAggregate
     * @param array<int, OpenApiNormalizerInterface>|IteratorAggregate
     * @param array $normalizationLocations
     * @param ValidatorInterface|null $validator
     */
    public function __construct(
        IteratorAggregate $openApiNormalizers,
        IteratorAggregate $openApiValidators,
        array $normalizationLocations,
        ?ValidatorInterface $validator = null
    ) {
        $this->openApiNormalizers = $openApiNormalizers;
        $this->openApiValidators = $openApiValidators;
        $this->normalizationLocations = $normalizationLocations;
        $this->validator = $validator;
    }

    /**
     * @throws UndefinedPropertyTypeException
     * @throws Exception
     */
    public function build(Schema $schema): OptionsResolver
    {
        $optionsResolver = new OptionsResolver();

        $requiredProperties = Generator::isDefault($schema->required) ? [] : (array) $schema->required;

        if ($requiredProperties) {
            $optionsResolver->setRequired($requiredProperties);
        }

        if (Generator::isDefault($schema->properties)) {
            return $optionsResolver;
        }

        foreach ($schema->properties as $propertySchema) {
            $name = $propertySchema->property;

            $optionsResolver->setDefined($name);

            $allowedTypes = $this->getAllowedTypes($propertySchema);

            if ($allowedTypes === null) {
                $propertyType = $propertySchema->type ?? '';

                throw new UndefinedPropertyTypeException($schema->schema, $name, $propertyType);
            }

            $isNullable = $propertySchema->nullable;

            if ($isNullable === true) {
                $allowedTypes[] = 'null';
            }

            $optionsResolver->setAllowedTypes($name, $allowedTypes);

            $optionsResolver = $this->addNormalization($optionsResolver, $name, $propertySchema);
            $optionsResolver = $this->addValidator($optionsResolver, $name, $propertySchema);
            $optionsResolver = $this->addConstraint($optionsResolver, $name, $schema);

            if (!Generator::isDefault($propertySchema->default)) {
                $optionsResolver->setDefault($name, $propertySchema->default);
            }

            if (!Generator::isDefault($propertySchema->enum)) {
                $optionsResolver->setAllowedValues($name, (array) $propertySchema->enum);
            }
        }

        return $optionsResolver;
    }

    private function addNormalization(OptionsResolver $resolver, string $name, Schema $propertySchema): OptionsResolver
    {
        $extSchemaList = Generator::isDefault($propertySchema->x) ? [] : $propertySchema->x;
        $parameterLocation = $extSchemaList[ParameterExtensionEnum::X_PARAMETER_LOCATION] ?? null;

        if (!in_array($parameterLocation, $this->normalizationLocations, true)) {
            return $resolver;
        }

        $isRequired = $resolver->isRequired($name);

        foreach ($this->openApiNormalizers as $normalizer) {
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

    private function addValidator(OptionsResolver $resolver, string $name, Schema $propertySchema): OptionsResolver
    {
        foreach ($this->openApiValidators as $openApiValidator) {
            if (!$openApiValidator->supports($propertySchema)) {
                continue;
            }

            $resolver->addAllowedValues($name, function ($value) use ($openApiValidator, $propertySchema, $name) {
                $openApiValidator->validate($propertySchema, $name, $value);

                return true;
            });
        }

        return $resolver;
    }

    private function addConstraint(OptionsResolver $resolver, string $name, Schema $schema): OptionsResolver
    {
        if (!$this->validator) {
            return $resolver;
        }

        $extSchemaList = Generator::isDefault($schema->x) ? [] : (array) $schema->x;
        $schemaClass = $extSchemaList[ParameterExtensionEnum::X_CLASS] ?? null;

        if (!$schemaClass) {
            return $resolver;
        }

        if (!$this->validator->hasMetadataFor($schemaClass)) {
            return $resolver;
        }

        /** @var ClassMetadata $definitionMetadata */
        $definitionMetadata = $this->validator->getMetadataFor($schemaClass);
        $propertyMetadataList = $definitionMetadata->getPropertyMetadata($name);

        foreach ($propertyMetadataList as $propertyMetadata) {
            if (!$propertyMetadata->getConstraints()) {
                continue;
            }

            $resolver->addAllowedValues($name, function ($value) use ($schemaClass, $name) {
                $violations = $this->validator
                    ->startContext()
                    ->atPath($name)
                    ->validatePropertyValue($schemaClass, $name, $value)
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

    private function getAllowedTypes(Property $propertySchema): ?array
    {
        $propertyType = $propertySchema->type;
        $allowedTypes = [];

        if ($propertyType === ParameterTypeEnum::STRING) {
            $allowedTypes[] = 'string';

            return $allowedTypes;
        }

        if ($propertyType === ParameterTypeEnum::INTEGER) {
            $allowedTypes[] = 'integer';
            $allowedTypes[] = 'int';

            return $allowedTypes;
        }

        if ($propertyType === ParameterTypeEnum::BOOLEAN) {
            $allowedTypes[] = 'boolean';
            $allowedTypes[] = 'bool';

            return $allowedTypes;
        }

        if ($propertyType === ParameterTypeEnum::NUMBER) {
            $allowedTypes[] = 'double';
            $allowedTypes[] = 'float';

            return $allowedTypes;
        }

        if ($propertyType === ParameterTypeEnum::ARRAY) {
            $allowedTypes[] = Generator::isDefault($propertySchema->collectionFormat) ? 'array' : 'string';

            return $allowedTypes;
        }

        if ($propertyType === 'object') {
            $allowedTypes[] = 'object';
            $allowedTypes[] = 'array';

            return $allowedTypes;
        }

        if (Generator::isDefault($propertyType) && !Generator::isDefault($propertySchema->ref)) {
            $ref = $propertySchema->ref;

            $allowedTypes[] = 'object';
            $allowedTypes[] = 'array';
            $allowedTypes[] = $ref;

            return $allowedTypes;
        }

        return null;
    }
}
