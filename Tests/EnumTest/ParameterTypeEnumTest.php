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

namespace Linkin\Bundle\SwaggerResolverBundle\Tests\EnumTest;

use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterTypeEnum;
use PHPUnit\Framework\TestCase;

class ParameterTypeEnumTest extends TestCase
{
    public function testGetAll(): void
    {
        self::assertSame(ParameterTypeEnum::getAll(), [
            'array',
            'boolean',
            'file',
            'integer',
            'number',
            'string',
        ]);
    }
}