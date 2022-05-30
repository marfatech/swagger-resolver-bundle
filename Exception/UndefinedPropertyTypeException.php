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

namespace Linkin\Bundle\SwaggerResolverBundle\Exception;

use RuntimeException;

use function sprintf;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class UndefinedPropertyTypeException extends RuntimeException
{
    public function __construct(string $schemaName, string $propertyName, string $type)
    {
        $message = sprintf(
            'Property "%s" of the Open API schema "%s" contains undefined type "%s"',
            $propertyName,
            $schemaName,
            $type
        );

        parent::__construct($message);
    }
}
