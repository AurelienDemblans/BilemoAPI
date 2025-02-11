<?php

namespace App\Attribute\Api\Interface;

use OpenApi\Attributes\Property;

interface ClientInterface extends IdInterface, NameInterface, DateInterface
{
    #[Property(property: 'username', type: 'string', example: 'sfr')]
    public function getUsername(): string;
}
