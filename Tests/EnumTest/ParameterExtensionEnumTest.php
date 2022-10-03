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

use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterExtensionEnum;
use PHPUnit\Framework\TestCase;

class ParameterExtensionEnumTest extends TestCase
{
    public function testGetAll(): void
    {
        self::assertSame(ParameterExtensionEnum::getAll(), [
            'parameter_location',
            'option_resolve',
            'class',
        ]);
    }
}