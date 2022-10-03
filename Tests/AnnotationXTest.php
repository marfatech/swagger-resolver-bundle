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

use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnnotationXTest extends KernelTestCase
{
    public function testSchemaBuilder(): void
    {
        $key = 'option_resolve';
        $value = 'kek';

        $schema = $this->createSchemaDefinition($key, $value);
        $x = $schema->properties[0]->x;

        self::assertEquals($x, [$key => 'kek']);
    }

    private function createSchemaDefinition(string $key, string $value): Schema
    {
        return new Schema(
            [
                'type' => 'object',
                'required' => [],
                'properties' => [
                    new Property(
                        [
                            'property' => 'new',
                            'x' => [$key => $value],
                        ]
                    ),
                ],
            ]
        );
    }
}
