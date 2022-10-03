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

namespace Linkin\Bundle\SwaggerResolverBundle\Enum;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class ParameterExtensionEnum
{
    public const X_PARAMETER_LOCATION = 'parameter_location';
    public const X_OPTION_RESOLVE = 'option_resolve';
    public const X_CLASS = 'class';

    public static function getAll(): array
    {
        return [self::X_PARAMETER_LOCATION, self::X_OPTION_RESOLVE, self::X_CLASS];
    }
}
