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
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\ModelDescriber\ObjectModelDescriber as NelmioObjectModelDescriber;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

use function class_exists;
use function sprintf;

class ObjectModelDescriber extends NelmioObjectModelDescriber
{
    use ModelRegistryAwareTrait;

    public function __construct(
        PropertyInfoExtractorInterface $propertyInfo,
        Reader $reader,
        iterable $propertyDescriberList
    ) {
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

        $extensionName = sprintf('x-%s', ParameterExtensionEnum::X_NULLABLE);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $reflectionPropertyType = $reflectionProperty->getType();
            $propertyName = $reflectionProperty->getName();
            $xNullableExtension[$extensionName] = true;

            if ($reflectionPropertyType) {
                $xNullableExtension[$extensionName] = $reflectionPropertyType->allowsNull();
            }

            if ($schema->getProperties()->has($propertyName)) {
                $schema->getProperties()->get($propertyName)->merge($xNullableExtension);
            }
        }
    }

    public function setModelRegistry(ModelRegistry $modelRegistry): void
    {
        $this->modelRegistry = $modelRegistry;

        parent::setModelRegistry($modelRegistry);
    }
}
