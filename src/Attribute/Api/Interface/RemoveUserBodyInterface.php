<?php

namespace App\Attribute\Api\Interface;

use App\Entity\User;
use OpenApi\Attributes\Property;

interface RemoveUserBodyInterface
{
    #[Property(property: 'user', type: 'integer', example: 22)]
    public function getUser(): User;
}
