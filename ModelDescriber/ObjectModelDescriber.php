<?php

declare(strict_types=1);

/*
 * This file is part of the SwaggerResolverBundle package.
 *
 * (c) MarfaTech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Bundle\SwaggerResolverBundle\ModelDescriber;

use Doctrine\Common\Annotations\Reader;
use EXSyst\Component\Swagger\Schema;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use MarfaTech\Bundle\EnumerBundle\Registry\EnumRegistryService;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\ModelDescriber\ObjectModelDescriber as NelmioObjectModelDescriber;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

use function class_exists;
use function is_array;
use function is_string;
use function sprintf;

class ObjectModelDescriber extends NelmioObjectModelDescriber
{
    use ModelRegistryAwareTrait;

    private ?EnumRegistryService $enumRegistryService;

    public function __construct(
        PropertyInfoExtractorInterface $propertyInfo,
        Reader $reader,
        iterable $propertyDescriberList,
        ?EnumRegistryService $enumRegistryService = null
    ) {
        $this->enumRegistryService = $enumRegistryService;

        parent::__construct($propertyInfo, $reader, $propertyDescriberList);
    }

    public function supports(Model $model): bool
    {
        $isObjectType = Type::BUILTIN_TYPE_OBJECT === $model->getType()->getBuiltinType();
        $isClassExists = class_exists($model->getType()->getClassName());

        return $isObjectType && $isClassExists;
    }

    /**
     * @throws ReflectionException
     */
    public function describe(Model $model, Schema $schema): void
    {
        parent::describe($model, $schema);

        $class = $model->getType()->getClassName();
        $reflectionClass = new ReflectionClass($class);

        $extensionNullableName = sprintf('x-%s', ParameterExtensionEnum::X_NULLABLE);
        $extensionClassName = sprintf('x-%s', ParameterExtensionEnum::X_CLASS);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionPropertyType = $reflectionProperty->getType();
            $propertyName = $reflectionProperty->getName();
            $propertyExtension[$extensionNullableName] = true;

            if ($reflectionPropertyType) {
                $propertyExtension[$extensionNullableName] = $reflectionPropertyType->allowsNull();
            }

            if ($schema->getProperties()->has($propertyName)) {
                $property = $schema->getProperties()->get($propertyName);

                $this->addEnum($property);

                $property->merge($propertyExtension);
            }
        }

        $classExtension[$extensionClassName] = $class;
        $schema->merge($classExtension);
    }

    public function setModelRegistry(ModelRegistry $modelRegistry): void
    {
        $this->modelRegistry = $modelRegistry;

        parent::setModelRegistry($modelRegistry);
    }

    private function addEnum(Schema $propertySchema): void
    {
        $enum = $propertySchema->getEnum();

        if (empty($enum)) {
            return;
        }

        if (is_array($enum)) {
            return;
        }

        if (!$this->enumRegistryService) {
            return;
        }

        if (is_string($enum) && $this->enumRegistryService->hasEnum($enum)) {
            $enumList = $this->enumRegistryService->getOriginalList($enum);

            $propertySchema->setEnum($enumList);
        }
    }
}