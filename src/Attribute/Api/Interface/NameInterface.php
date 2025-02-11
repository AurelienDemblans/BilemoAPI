<?php

namespace App\Attribute\Api\Interface;

use OpenApi\Attributes\Property;

interface NameInterface
{
    #[Property(property: 'name', type: 'string', example: 'string')]
    public function getName(): string;
}
