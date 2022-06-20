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

namespace Linkin\Bundle\SwaggerResolverBundle\Matcher;

use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class ParameterTypeMatcher
{
    public function matchTypes(Schema $schema, array &$types): void
    {
        $propertyType = $schema->type;

        switch (true) {
            case $propertyType === ParameterTypeEnum::STRING:
                $types['string'] = 'string';
                break;

            case $propertyType === ParameterTypeEnum::INTEGER:
                $types['integer'] = 'integer';
                $types['int'] = 'int';
                break;

            case $propertyType === ParameterTypeEnum::BOOLEAN:
                $types['boolean'] = 'boolean';
                $types['bool'] = 'bool';
                break;

            case $propertyType === ParameterTypeEnum::NUMBER:
                $types['double'] = 'double';
                $types['float'] = 'float';
                break;

            case $propertyType === ParameterTypeEnum::ARRAY:
                $type = Generator::isDefault($schema->collectionFormat) ? 'array' : 'string';
                $types[$type] = $type;
                break;

            case !Generator::isDefault($schema->ref):
            case $propertyType === 'object':
                $types['object'] = 'object';
                $types['array'] = 'array';
                break;

            default:
        }
    }
}
