<?php

namespace App\Attribute\Api\Interface;

use OpenApi\Attributes\Property;

interface IdInterface
{
    #[Property(property: 'id', type: 'integer', example: 22)]
    public function getId(): int;
}
