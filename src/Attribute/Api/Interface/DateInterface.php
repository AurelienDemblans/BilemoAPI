<?php

namespace App\Attribute\Api\Interface;

use DateTimeInterface;
use OpenApi\Attributes\Property;

interface DateInterface
{
    #[Property(type: 'string', example: '2024-05-20T15:50:39.414Z')]
    public function getCreatedAt(): DateTimeInterface;
}
