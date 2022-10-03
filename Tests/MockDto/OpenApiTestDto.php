<?php

declare(strict_types=1);

namespace Linkin\Bundle\SwaggerResolverBundle\Tests\MockDto;

use OpenApi\Annotations as OA;

class OpenApiTestDto
{
    /**
     * @OA\Property(type="string")
     */
    private $a;
}
