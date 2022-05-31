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

namespace Linkin\Bundle\SwaggerResolverBundle\Describer;

use Deprecated;
use Doctrine\Common\Annotations\Reader;
use JetBrains\PhpStorm\Deprecated as JetBrainsAttributeDeprecated;
use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use MarfaTech\Bundle\EnumerBundle\Registry\EnumRegistryService;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\ObjectModelDescriber as NelmioObjectModelDescriber;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Null_;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

use function array_map;
use function class_exists;
use function get_class;
use function in_array;
use function is_array;

use const PHP_VERSION_ID;

class ObjectModelDescriber extends NelmioObjectModelDescriber
{
    private PropertyInfoExtractorInterface $propertyInfo;
    private DocBlockFactoryInterface $docBlockFactory;
    private ?EnumRegistryService $enumRegistryService;

    public function __construct(
        PropertyInfoExtractorInterface $propertyInfo,
        Reader $reader,
        iterable $propertyDescriberList,
        DocBlockFactoryInterface $docBlockFactory,
        array $mediaTypes,
        ?NameConverterInterface $nameConverter = null,
        ?EnumRegistryService $enumRegistryService = null
    ) {
        $this->propertyInfo = $propertyInfo;
        $this->docBlockFactory = $docBlockFactory;
        $this->enumRegistryService = $enumRegistryService;

        parent::__construct($propertyInfo, $reader, $propertyDescriberList, $mediaTypes, $nameConverter);
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

        $extSchema[ParameterExtensionEnum::X_CLASS] = $class;
        $schema->x = Generator::isDefault($schema->x) ? $extSchema : $schema->x + $extSchema;

        $propertyList = $this->propertyInfo->getProperties($class, []);

        foreach ($propertyList as $propertyName) {
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $property = Util::getProperty($schema, $propertyName);
            $docComment = $reflectionProperty->getDocComment();
            $docBlockProperty = $docComment ? $this->docBlockFactory->create($docComment) : null;

            $this->addDefault($property, $reflectionProperty);
            $this->addNullable($property, $reflectionProperty, $docBlockProperty);
            $this->addEnum($property);
            $this->addDeprecated($property, $reflectionProperty, $docBlockProperty);
        }
    }

    private function addDefault(Property $property, ReflectionProperty $reflectionProperty): void
    {
        if (!Generator::isDefault($property->default)) {
            return;
        }

        if (PHP_VERSION_ID >= 80000 && $reflectionProperty->hasDefaultValue()) {
            $defaultValue = $reflectionProperty->getDefaultValue();

            $property->default = $defaultValue;
        }
    }

    private function addNullable(
        Property $property,
        ReflectionProperty $reflectionProperty,
        ?DocBlock $docBlockProperty
    ): void {
        if (!Generator::isDefault($property->nullable)) {
            return;
        }

        $reflectionPropertyType = $reflectionProperty->getType();

        if ($reflectionPropertyType) {
            $property->nullable = $reflectionPropertyType->allowsNull();

            return;
        }

        if (!$docBlockProperty) {
            return;
        }

        /** @var Var_ $annotationVar */
        $annotationVar = $docBlockProperty->getTagsByName('var')[0] ?? null;

        if (!$annotationVar) {
            return;
        }

        $compoundTypes = $annotationVar->getType();

        if ($compoundTypes instanceof Compound) {
            $typeList = $compoundTypes->getIterator()->getArrayCopy();

            $typeClassList = array_map(static fn($type) => get_class($type), $typeList);
        } else {
            $typeClassList[] = get_class($compoundTypes);
        }

        if (in_array(Null_::class, $typeClassList, true)) {
            $property->nullable = true;
        } else {
            $property->nullable = false;
        }
    }

    private function addEnum(Property $property): void
    {
        if (is_array($property->enum)) {
            return;
        }

        $enumClass = $property->enum;

        if (Generator::isDefault($enumClass)) {
            return;
        }

        if (empty($enumClass)) {
            return;
        }

        if (!$this->enumRegistryService) {
            return;
        }

        if (class_exists($enumClass) && $this->enumRegistryService->hasEnum($enumClass)) {
            $enumList = $this->enumRegistryService->getOriginalList($enumClass);

            $property->enum = $enumList;
        }
    }

    private function addDeprecated(
        Property $property,
        ReflectionProperty $reflectionProperty,
        ?DocBlock $docBlockProperty
    ): void {
        if (!Generator::isDefault($property->deprecated)) {
            return;
        }

        if ($docBlockProperty && $docBlockProperty->hasTag('deprecated')) {
            $property->deprecated = true;

            return;
        }

        if (PHP_VERSION_ID >= 80000) {
            $jbAttributeDeprecated = $reflectionProperty->getAttributes(JetBrainsAttributeDeprecated::class);
            $phpAttributeDeprecated = $reflectionProperty->getAttributes(Deprecated::class);

            $property->deprecated = $jbAttributeDeprecated || $phpAttributeDeprecated ? true : Generator::UNDEFINED;
        }
    }
}
