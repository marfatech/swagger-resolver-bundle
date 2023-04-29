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

use Exception;
use Linkin\Bundle\SwaggerResolverBundle\Builder\OpenApiResolverBuilder;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfiguration;
use Linkin\Bundle\SwaggerResolverBundle\Matcher\ParameterTypeMatcher;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Serializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class OptionResolverExceptionTest extends KernelTestCase
{
    public function testSchemaBuilderMissingOptions(): void
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

        $schema = $this->createSchemaDefinitionOptions($fieldName, $fieldType);
        $optionsResolver = $builder->build($schema);

        $this->expectException(MissingOptionsException::class);
        $result = $optionsResolver->resolve([]);

        self::assertSame($result[$fieldName], $originValue);
    }

    /**
     * @throws Exception
     */
    public function testSchemaBuilderUndefinedOption(): void
    {
        $fieldName = 'a';
        $fieldType = 'string';

        $builder = new OpenApiResolverBuilder(
            new \ArrayObject(),
            new \ArrayObject(),
            [],
            $this->createMock(OpenApiConfiguration::class),
            new ParameterTypeMatcher(),
            new Serializer()
        );

        $schema = $this->createSchemaInvalidOption($fieldName, $fieldType);
        $optionsResolver = $builder->build($schema);

        $this->expectException(InvalidOptionsException::class);
        $optionsResolver->resolve(['a' => 123]);
    }

    private function createSchemaDefinitionOptions(string $fieldName, string $type): Schema
    {
        return new Schema(
            [
                'type' => 'object',
                'required' => [$fieldName],
                'properties' => [
                    new Property(
                        [
                            'property' => $fieldName,
                            'type' => $type,
                            'required' => true
                        ]
                    ),
                ],
            ]
        );
    }

    private function createSchemaInvalidOption(string $fieldName, string $type): Schema
    {
        return new Schema(
            [
                'type' => 'object',
                'required' => [$fieldName],
                'properties' => [
                    $fieldName => new Property(
                        [
                            'example' => 'primer',
                            'property' => $fieldName,
                            'type' => $type,
                            'required' => true
                        ]
                    ),
                ],
            ]
        );
    }
}
