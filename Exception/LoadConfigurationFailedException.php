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

namespace Linkin\Bundle\SwaggerResolverBundle\Exception;

use RuntimeException;

use function sprintf;

/**
 * @author MarfaTech <https://marfa-tech.com>
 */
class LoadConfigurationFailedException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct(sprintf('Load Open API configuration is failed: "%s"', $message));
    }
}
