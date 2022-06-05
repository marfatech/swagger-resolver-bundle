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

use Composer\InstalledVersions;
use Exception;
use IteratorAggregate;
use JsonException;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfigurationInterface;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use Linkin\Bundle\SwaggerResolverBundle\Matcher\ParameterTypeMatcher;
use Linkin\Bundle\SwaggerResolverBundle\Normalizer\OpenApiNormalizerInterface;
use Linkin\Bundle\SwaggerResolverBundle\Validator\OpenApiValidatorInterface;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use OpenApi\Serializer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function array_filter;
use function array_values;
use function class_exists;
use function implode;
use function in_array;
use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

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
    private ParameterTypeMatcher $parameterTypeMatcher;
    private OpenApiConfigurationInterface $openApiConfiguration;
    private Serializer $serializer;

    /**
     * @param array<int, OpenApiValidatorInterface>|IteratorAggregate
     * @param array<int, OpenApiNormalizerInterface>|IteratorAggregate
     * @param array $normalizationLocations
     * @param OpenApiConfigurationInterface $openApiConfiguration
     * @param ParameterTypeMatcher $parameterTypeMatcher
     * @param Serializer $serializer
     * @param ValidatorInterface|null $validator
     */
    public function __construct(
        IteratorAggregate $openApiNormalizers,
        IteratorAggregate $openApiValidators,
        array $normalizationLocations,
        OpenApiConfigurationInterface $openApiConfiguration,
        ParameterTypeMatcher $parameterTypeMatcher,
        Serializer $serializer,
        ?ValidatorInterface $validator = null
    ) {
        $this->openApiNormalizers = $openApiNormalizers;
        $this->openApiValidators = $openApiValidators;
        $this->normalizationLocations = $normalizationLocations;
        $this->openApiConfiguration = $openApiConfiguration;
        $this->parameterTypeMatcher = $parameterTypeMatcher;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @throws Exception
     */
    public function build(Schema $schema, ?OptionsResolver $optionsResolver = null): OptionsResolver
    {
        $optionsResolver = $optionsResolver ?: new OptionsResolver();

        $requiredProperties = Generator::isDefault($schema->required) ? [] : (array) $schema->required;

        if ($requiredProperties) {
            $optionsResolver->setRequired($requiredProperties);
        }

        if (Generator::isDefault($schema->properties)) {
            return $optionsResolver;
        }

        foreach ($schema->properties as $property) {
            $extensionPropertyList = Generator::isDefault($property->x) ? [] : $property->x;
            $optionResolve = $extensionPropertyList[ParameterExtensionEnum::X_OPTION_RESOLVE] ?? true;

            if ($optionResolve === false) {
                continue;
            }

            $name = $property->property;

            $optionsResolver->setDefined($name);
            $optionsResolver = $this->addDefault($optionsResolver, $name, $property);
            $optionsResolver = $this->addNestedResolver($optionsResolver, $name, $property);
            $optionsResolver = $this->addItemNestedResolver($optionsResolver, $name, $property);
            $optionsResolver = $this->addType($optionsResolver, $name, $property);
            $optionsResolver = $this->addNormalization($optionsResolver, $name, $property);
            $optionsResolver = $this->addValidator($optionsResolver, $name, $property);
            $optionsResolver = $this->addConstraint($optionsResolver, $name, $schema);

            if (!Generator::isDefault($property->enum)) {
                $optionsResolver->setAllowedValues($name, (array) $property->enum);
            }

            $info = [
                !Generator::isDefault($property->title) ? $property->title : '',
                !Generator::isDefault($property->description) ? $property->description : '',
            ];

            if (array_filter($info)) {
                $optionsResolver->setInfo($name, implode(' ', $info));
            }

            if (!Generator::isDefault($property->deprecated)) {
                $rootPackage = InstalledVersions::getRootPackage();

                $optionsResolver->setDeprecated($name, $rootPackage['name'], $rootPackage['version']);
            }
        }

        return $optionsResolver;
    }

    private function addDefault(OptionsResolver $resolver, string $name, Property $property): OptionsResolver
    {
        if (Generator::isDefault($property->default)) {
            return $resolver;
        }

        $resolver->setDefault($name, $property->default);

        return $resolver;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    private function addItemNestedResolver(OptionsResolver $resolver, string $name, Property $property): OptionsResolver
    {
        if (Generator::isDefault($property->items)) {
            return $resolver;
        }

        if (!Generator::isDefault($property->items->ref)) {
            $schema = $this->openApiConfiguration->getSchema($property->items->ref);
            $extensionReferenceSchemaExist = !Generator::isDefault($schema->x);
            $referenceClassSchemaExist = class_exists($schema->x[ParameterExtensionEnum::X_CLASS]);

            if ($extensionReferenceSchemaExist && $referenceClassSchemaExist) {
                $schemaClassName = $schema->x[ParameterExtensionEnum::X_CLASS];

                $resolver->addNormalizer($name, static function (Options $options, $value) use ($schemaClassName) {
                    $resultList = [];

                    foreach ($value as $key => $item) {
                        $resultList[$key] = is_array($value) ? new $schemaClassName($value) : $value;
                    }

                    return $resultList;
                });
            }
        } elseif ($property->items->type === 'object') {
            $schemaJson = json_encode(['properties' => $property->items->properties], JSON_THROW_ON_ERROR);
            $schema = $this->serializer->deserialize($schemaJson, Schema::class);
        } else {
            return $resolver;
        }

        $resolver->setDefault($name, function (OptionsResolver $nestedResolver) use ($schema) {
            $nestedResolver->setPrototype(true);

            $this->build($schema, $nestedResolver);
        });

        return $resolver;
    }

    private function addNestedResolver(OptionsResolver $resolver, string $name, Property $property): OptionsResolver
    {
        if ($property->type !== 'object' && Generator::isDefault($property->ref)) {
            return $resolver;
        }

        if (!Generator::isDefault($property->ref)) {
            $schema = $this->openApiConfiguration->getSchema($property->ref);
            $extensionReferenceSchemaExist = !Generator::isDefault($schema->x);
            $referenceClassSchemaExist = class_exists($schema->x[ParameterExtensionEnum::X_CLASS]);

            if ($extensionReferenceSchemaExist && $referenceClassSchemaExist) {
                $schemaClassName = $schema->x[ParameterExtensionEnum::X_CLASS];

                $resolver->addNormalizer($name, static function (Options $options, $value) use ($schemaClassName) {
                    return is_array($value) ? new $schemaClassName($value) : $value;
                });
            }
        } else {
            $schema = $property;
        }

        $resolver->setDefault($name, function (OptionsResolver $nestedResolver) use ($schema) {
            $this->build($schema, $nestedResolver);
        });

        return $resolver;
    }

    private function addType(OptionsResolver $resolver, string $name, Property $property): OptionsResolver
    {
        $isItemsSchema = !Generator::isDefault($property->items);

        if ($isItemsSchema && $property->items->type !== 'object' && !Generator::isDefault($property->items->type)) {
            $type = $property->items->type === 'number' ? 'float' : $property->items->type;

            $resolver->addAllowedTypes($name, $type . '[]');

            return $resolver;
        }

        $allowedTypes = [];

        $this->parameterTypeMatcher->matchTypes($property, $allowedTypes);

        if (!Generator::isDefault($property->oneOf)) {
            foreach ($property->oneOf as $schema) {
                $this->parameterTypeMatcher->matchTypes($schema, $allowedTypes);
            }
        }

        if ($property->nullable === true) {
            $allowedTypes['null'] = 'null';
        }

        if ($allowedTypes) {
            $resolver->setAllowedTypes($name, array_values($allowedTypes));
        }

        return $resolver;
    }

    private function addNormalization(OptionsResolver $resolver, string $name, Property $property): OptionsResolver
    {
        $extensionPropertyList = Generator::isDefault($property->x) ? [] : $property->x;
        $parameterLocation = $extensionPropertyList[ParameterExtensionEnum::X_PARAMETER_LOCATION] ?? null;

        if (!in_array($parameterLocation, $this->normalizationLocations, true)) {
            return $resolver;
        }

        foreach ($this->openApiNormalizers as $normalizer) {
            if (!$normalizer->supports($property)) {
                continue;
            }

            $closure = $normalizer->getNormalizer($property);

            return $resolver
                ->setNormalizer($name, $closure)
                ->addAllowedTypes($name, 'string')
            ;
        }

        return $resolver;
    }

    private function addValidator(OptionsResolver $resolver, string $name, Property $property): OptionsResolver
    {
        foreach ($this->openApiValidators as $openApiValidator) {
            if (!$openApiValidator->supports($property)) {
                continue;
            }

            $resolver->addAllowedValues($name, function ($value) use ($openApiValidator, $property) {
                $openApiValidator->validate($property, $value);

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
}
