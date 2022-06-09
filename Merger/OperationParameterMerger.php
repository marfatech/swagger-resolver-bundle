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

namespace Linkin\Bundle\SwaggerResolverBundle\Merger;

use Exception;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfigurationInterface;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterLocationEnum;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use OpenApi\Serializer;

use function in_array;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class OperationParameterMerger
{
    private MergeStrategyInterface $mergeStrategy;
    private Serializer $serializer;

    public function __construct(MergeStrategyInterface $defaultMergeStrategy, Serializer $serializer)
    {
        $this->mergeStrategy = $defaultMergeStrategy;
        $this->serializer = $serializer;
    }

    /**
     * @throws Exception
     */
    public function merge(Operation $operation, OpenApiConfigurationInterface $apiConfiguration): Schema
    {
        $parameterList = Generator::isDefault($operation->parameters) ? [] : $operation->parameters;
        $contentList = Generator::isDefault($operation->requestBody) ? [] : $operation->requestBody->content;

        foreach ($parameterList as $parameter) {
            $extensionParameterList[ParameterExtensionEnum::X_PARAMETER_LOCATION] = $parameter->in;
            $parameter->x = Generator::isDefault($parameter->x)
                ? $extensionParameterList
                : $parameter->x + $extensionParameterList
            ;
            $property = $this->toProperty($parameter);
            $required = !Generator::isDefault($parameter->required);

            $this->mergeStrategy->addParameter($parameter->in, $property, $required);
        }

        $extSchemaList = [];

        foreach ($contentList as $content) {
            $bodySchema = $content->schema;
            $mediaType = $content->mediaType;

            $reference = Generator::isDefault($bodySchema->ref) ? null : $bodySchema->ref;

            if ($reference) {
                $schema = $apiConfiguration->getSchema($reference);
                $schemaName = $schema->schema;

                $propertyList = Generator::isDefault($schema->properties) ? [] : $schema->properties;
                $requiredList = Generator::isDefault($schema->required) ? [] : $schema->required;
                $extSchemaList += Generator::isDefault($schema->x) ? [] : $schema->x;

                foreach ($propertyList as $property) {
                    $parameterSource = sprintf('%s_%s', $mediaType, ParameterLocationEnum::IN_BODY);
                    $required = in_array($property->property, $requiredList, true);

                    $extensionParameterList[ParameterExtensionEnum::X_PARAMETER_LOCATION] =
                        ParameterLocationEnum::IN_BODY
                    ;
                    $property->x = Generator::isDefault($property->x)
                        ? $extensionParameterList
                        : $property->x + $extensionParameterList
                    ;

                    $this->mergeStrategy->addParameter($parameterSource, $property, $required);
                }
            } elseif ($bodySchema->type === 'object') {
                $propertyList = Generator::isDefault($bodySchema->properties) ? [] : $bodySchema->properties;
                $requiredList = Generator::isDefault($bodySchema->required) ? [] : $bodySchema->required;
                $extSchemaList += Generator::isDefault($bodySchema->x) ? [] : $bodySchema->x;

                foreach ($propertyList as $property) {
                    $parameterSource = sprintf('%s_%s', $mediaType, ParameterLocationEnum::IN_BODY);
                    $required = in_array($property->property, $requiredList, true);

                    $extensionParameterList[ParameterExtensionEnum::X_PARAMETER_LOCATION] =
                        ParameterLocationEnum::IN_BODY
                    ;
                    $property->x = Generator::isDefault($property->x)
                        ? $extensionParameterList
                        : $property->x + $extensionParameterList
                    ;

                    $this->mergeStrategy->addParameter($parameterSource, $property, $required);
                }
            } else {
                $extSchemaList += Generator::isDefault($bodySchema->x) ? [] : $bodySchema->x;
                $parameterSource = sprintf('%s_%s', $mediaType, ParameterLocationEnum::IN_BODY);
                $requiredList = Generator::isDefault($bodySchema->required) ? [] : $bodySchema->required;

                $extensionParameterList[ParameterExtensionEnum::X_PARAMETER_LOCATION] = ParameterLocationEnum::IN_BODY;
                $bodySchema->x = Generator::isDefault($bodySchema->x)
                    ? $extensionParameterList
                    : $bodySchema->x + $extensionParameterList
                ;
                $required = in_array($bodySchema->property, $requiredList, true);

                $property = new Property([
                    'property', ParameterLocationEnum::IN_BODY,
                    'schema' => $bodySchema,
                ]);

                $this->mergeStrategy->addParameter($parameterSource, $property, $required);
            }
        }

        $mergedSchema = new Schema([
            'type' => 'object',
            'schema' => $schemaName ?? Generator::UNDEFINED,
            'properties' => $this->mergeStrategy->getParameters(),
            'required' => $this->mergeStrategy->getRequired(),
            'x' => $extSchemaList,
        ]);

        $this->mergeStrategy->clean();

        return $mergedSchema;
    }

    /**
     * @throws Exception
     */
    private function toProperty(Parameter $parameter): Property
    {
        $propertyOptions = [
            'property' => $parameter->name,
            'example' => $parameter->example,
            'description' => $parameter->description,
            'deprecated' => $parameter->deprecated,
            'x-' . ParameterExtensionEnum::X_PARAMETER_LOCATION => $parameter->in,
        ];

        $parameterExtensionList = Generator::isDefault($parameter->x) ? [] : $parameter->x;

        foreach ($parameterExtensionList as $parameterExtensionKey => $parameterExtensionValue) {
            $propertyOptions['x-' . $parameterExtensionKey] = $parameterExtensionValue;
        }

        if (!Generator::isDefault($parameter->schema)) {
            if (!Generator::isDefault($parameter->schema->properties)) {
                $this->setParameterLocation($parameter->schema->properties, $parameter->in);
            }

            $propertyOptions = (array) $parameter->schema->jsonSerialize() + $propertyOptions;
        }

        $propertyJson = json_encode($propertyOptions, JSON_THROW_ON_ERROR);

        return $this->serializer->deserialize($propertyJson, Property::class);
    }

    /**
     * @param Property[] $properties
     * @param string $in
     *
     * @return void
     */
    private function setParameterLocation(array $properties, string $in): void
    {
        foreach ($properties as $property) {
            $extensionParameterList[ParameterExtensionEnum::X_PARAMETER_LOCATION] = $in;
            $property->x = Generator::isDefault($property->x)
                ? $extensionParameterList
                : $property->x + $extensionParameterList
            ;

            if (!Generator::isDefault($property->properties)) {
                $this->setParameterLocation($property->properties, $in);
            }
        }
    }
}
