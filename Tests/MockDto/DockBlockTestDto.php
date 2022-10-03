<?php

declare(strict_types=1);

namespace Linkin\Bundle\SwaggerResolverBundle\Tests\MockDto;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     propertyNames="abc"
 *     required="a"
 * )
 */
class DockBlockTestDto
{
    /**
     * @OA\Property(
     *     required="true",
     *     type="string"
     * )
     */
    public $a;
}
