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

namespace Linkin\Bundle\SwaggerResolverBundle\Tests;

use EXSyst\Component\Swagger\Schema;
use Linkin\Bundle\SwaggerResolverBundle\Resolver\SwaggerResolver;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    public function testSwaggerResolver(): void
    {
        $fieldName = 'testString';
        $fieldType = 'string';
        $originValue = 'test';

        $schemaDefinition = $this->createSchemaDefinition($fieldName, $fieldType);

        $resolver = new SwaggerResolver($schemaDefinition);
        $resolver->setDefined($fieldName);

        $result = $resolver->resolve([$fieldName => $originValue]);
        self::assertSame($result[$fieldName], $originValue);
    }

    private function createSchemaDefinition(string $fieldName, string $type): Schema
    {
        return new Schema(
            [
                'type' => 'object',
                'required' => [],
                'properties' => [
                    $fieldName => [
                        'type' => $type,
                    ],
                ],
            ]
        );
    }
}
