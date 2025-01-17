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

namespace Linkin\Bundle\SwaggerResolverBundle\Tests;

use Linkin\Bundle\SwaggerResolverBundle\Builder\OpenApiResolverBuilder;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfiguration;
use Linkin\Bundle\SwaggerResolverBundle\Matcher\ParameterTypeMatcher;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Serializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResolverTest extends KernelTestCase
{
    public function testSchemaBuilder(): void
    {
        $fieldName = 'testString';
        $fieldType = 'string';
        $originValue = 'test';

        $builder = new OpenApiResolverBuilder(
            new \ArrayObject(),
            new \ArrayObject(),
            [],
            $this->createMock(OpenApiConfiguration::class),
            new ParameterTypeMatcher(),
            new Serializer()
        );

        $schema = $this->createSchemaDefinition($fieldName, $fieldType);

        $optionsResolver = $builder->build($schema);

        $result = $optionsResolver->resolve([$fieldName => $originValue]);

        self::assertSame($result[$fieldName], $originValue);
    }

    private function createSchemaDefinition(string $fieldName, string $type): Schema
    {
        return new Schema(
            [
                'type' => 'object',
                'required' => [],
                'properties' => [
                    new Property(
                        [
                            'property' => $fieldName,
                            'type' => $type,
                        ]
                    ),
                ],
            ]
        );
    }
}
