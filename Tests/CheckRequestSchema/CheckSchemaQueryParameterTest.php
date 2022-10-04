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

namespace Linkin\Bundle\SwaggerResolverBundle\Tests\CheckRequestSchema;

use Exception;
use Linkin\Bundle\SwaggerResolverBundle\Builder\OpenApiResolverBuilder;
use Linkin\Bundle\SwaggerResolverBundle\Configuration\OpenApiConfiguration;
use Linkin\Bundle\SwaggerResolverBundle\Matcher\ParameterTypeMatcher;
use Linkin\Bundle\SwaggerResolverBundle\Validator\NumberMaximumValidator;
use Linkin\Bundle\SwaggerResolverBundle\Validator\NumberMinimumValidator;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Serializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CheckSchemaQueryParameterTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function testSchemaBuilderMissingOptions(): void
    {
        $fieldName = 'limitField';
        $originValue = 2000;

        $builder = new OpenApiResolverBuilder(
            new \ArrayObject(),
            new \ArrayObject([new NumberMinimumValidator(), new NumberMaximumValidator()]),
            [],
            $this->createMock(OpenApiConfiguration::class),
            new ParameterTypeMatcher(),
            new Serializer()
        );

        $schema = $this->createSchemaDefinitionQueryOptions();
        $optionsResolver = $builder->build($schema);
        $result = $optionsResolver->resolve(['limitField' => 2000]);

        self::assertSame($result[$fieldName], $originValue);
    }

    private function createSchemaDefinitionQueryOptions(): Schema
    {
        return new Schema([
            'required' =>
                [
                    'limitField' => 'limitField',
                ],
            'properties' =>
                [
                    'search' =>
                        new Property([
                            'title' => 'query',
                            'type' => 'string',
                            'example' => 'search',
                        ]),
                    'hah' =>
                        new Property([
                            'title' => 'query',
                            'type' => 'string',
                            'example' => 'cap',
                        ]),
                    'limitField' =>
                        new Property([
                            'title' => 'query',
                            'type' => 'integer',
                            'example' => 150,
                        ]),
                ],
            'type' => 'object',
        ]);
    }
}
