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

use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfigurationInterface;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterLocationEnum;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

use function array_flip;
use function sprintf;
use function str_replace;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class OperationParameterMerger
{
    private MergeStrategyInterface $mergeStrategy;

    public function __construct(MergeStrategyInterface $defaultMergeStrategy)
    {
        $this->mergeStrategy = $defaultMergeStrategy;
    }

    public function merge(Operation $operation, OpenApiConfigurationInterface $apiConfiguration): Schema
    {
        $parameterList = Generator::isDefault($operation->parameters) ? [] : $operation->parameters;
        $contentList = Generator::isDefault($operation->requestBody) ? [] : $operation->requestBody->content;

        foreach ($parameterList as $parameter) {
            $extParam[ParameterExtensionEnum::X_PARAMETER_LOCATION] = $parameter->in;
            $parameter->x = Generator::isDefault($parameter->x) ? $extParam : $parameter->x + $extParam;

            $this->mergeStrategy->addParameter(
                $parameter->in,
                $parameter->name,
                (array) $parameter->jsonSerialize(),
                $parameter->required === true,
            );
        }

        $extSchemaList = [];

        foreach ($contentList as $content) {
            $bodySchema = $content->schema;
            $mediaType = $content->mediaType;

            $reference = Generator::isDefault($bodySchema->ref) ? null : $bodySchema->ref;

            if ($reference) {
                $definitionName = str_replace(Components::SCHEMA_REF, '', $reference);
                $definition = $apiConfiguration->getSchema($definitionName);

                $propertyList = Generator::isDefault($definition->properties) ? [] : $definition->properties;
                $requiredList = Generator::isDefault($definition->required) ? [] : array_flip($definition->required);
                $extSchemaList += Generator::isDefault($definition->x) ? [] : $definition->x;

                foreach ($propertyList as $property) {
                    $propertyName = $property->property;
                    $parameterSource = sprintf('%s_%s', $mediaType, ParameterLocationEnum::IN_BODY);

                    $extParam[ParameterExtensionEnum::X_PARAMETER_LOCATION] = ParameterLocationEnum::IN_BODY;
                    $property->x = Generator::isDefault($property->x) ? $extParam : $property->x + $extParam;

                    $this->mergeStrategy->addParameter(
                        $parameterSource,
                        $propertyName,
                        (array) $property->jsonSerialize(),
                        isset($requiredList[$propertyName])
                    );
                }
            } elseif ($bodySchema->type === 'object') {
                $propertyList = Generator::isDefault($bodySchema->properties) ? [] : $bodySchema->properties;
                $requiredList = Generator::isDefault($bodySchema->required) ? [] : array_flip($bodySchema->required);
                $extSchemaList += Generator::isDefault($bodySchema->x) ? [] : $bodySchema->x;

                foreach ($propertyList as $property) {
                    $propertyName = $property->property;
                    $parameterSource = sprintf('%s_%s', $mediaType, ParameterLocationEnum::IN_BODY);

                    $extParam[ParameterExtensionEnum::X_PARAMETER_LOCATION] = ParameterLocationEnum::IN_BODY;
                    $property->x = Generator::isDefault($property->x) ? $extParam : $property->x + $extParam;

                    $this->mergeStrategy->addParameter(
                        $parameterSource,
                        $propertyName,
                        (array) $property->jsonSerialize(),
                        isset($requiredList[$propertyName])
                    );
                }
            } else {
                $required = Generator::isDefault($bodySchema->required) ? false : $bodySchema->required;
                $extSchemaList += Generator::isDefault($bodySchema->x) ? [] : $bodySchema->x;
                $parameterSource = sprintf('%s_%s', $mediaType, ParameterLocationEnum::IN_BODY);

                $extParam[ParameterExtensionEnum::X_PARAMETER_LOCATION] = ParameterLocationEnum::IN_BODY;
                $bodySchema->x = Generator::isDefault($bodySchema->x) ? $extParam : $bodySchema->x + $extParam;

                $this->mergeStrategy->addParameter(
                    $parameterSource,
                    ParameterLocationEnum::IN_BODY,
                    (array) $bodySchema->jsonSerialize(),
                    $required
                );
            }
        }

        $mergedSchema = new Schema([
            'type' => 'object',
            'schema' => $definitionName ?? Generator::UNDEFINED,
            'properties' => $this->mergeStrategy->getParameters(),
            'required' => $this->mergeStrategy->getRequired(),
            'x' => $extSchemaList,
        ]);

        $this->mergeStrategy->clean();

        return $mergedSchema;
    }
}
