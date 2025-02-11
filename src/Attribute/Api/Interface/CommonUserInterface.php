<?php

namespace App\Attribute\Api\Interface;

use OpenApi\Attributes\Property;

interface CommonUserInterface
{
    #[Property(property: 'email', type: 'string', example: 'email@email.com')]
    public function getEmail(): string;

    #[Property(property: 'firstname', type: 'string', example: 'Firstname')]
    public function getFirstname(): string;

    #[Property(property: 'lastname', type: 'string', example: 'Lastname')]
    public function getLastname(): string;
}
