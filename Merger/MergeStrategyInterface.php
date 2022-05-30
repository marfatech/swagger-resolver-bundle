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

namespace Linkin\Bundle\SwaggerResolverBundle\Merger;

use OpenApi\Attributes\Property;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
interface MergeStrategyInterface
{
    /**
     * Add parameter into collection
     */
    public function addParameter(string $parameterSource, string $name, array $data, bool $isRequired);

    /**
     * Returns list of collected parameters
     *
     * @return array<int, Property>
     */
    public function getParameters(): array;

    /**
     * Returns list of names of the required parameters
     *
     * @return array<int, string>
     */
    public function getRequired(): array;

    /**
     * Clean all collected data
     */
    public function clean();
}
