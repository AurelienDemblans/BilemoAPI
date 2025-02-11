<?php

namespace App\Attribute\Api\Interface;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes\Property;

interface UserInterface extends DateInterface, IdInterface
{
    #[Property(property: 'email', type: 'string', example: 'email@email.com')]
    public function getEmail(): string;

    #[Property(property: 'firstname', type: 'string', example: 'Firstname')]
    public function getFirstname(): string;

    #[Property(property: 'lastname', type: 'string', example: 'Lastname')]
    public function getLastname(): string;

    #[Property(property: 'client', ref: new Model(type: ClientInterface::class))]
    public function getClient(): ClientInterface;
}
