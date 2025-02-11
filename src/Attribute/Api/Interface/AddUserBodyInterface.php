<?php

namespace App\Attribute\Api\Interface;

use OpenApi\Attributes\Property;

interface AddUserBodyInterface extends CommonUserInterface
{
    #[Property(property: 'client', type: 'string', example: 'SFR')]
    public function getClient(): string;

    #[Property(property: 'password', type: 'string', example: 'password1234')]
    public function getPassword(): string;
}
