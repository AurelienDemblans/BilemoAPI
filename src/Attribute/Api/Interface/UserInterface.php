<?php

namespace App\Attribute\Api\Interface;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes\Property;

interface UserInterface extends DateInterface, IdInterface, CommonUserInterface
{
    #[Property(property: 'client', ref: new Model(type: ClientInterface::class))]
    public function getClient(): ClientInterface;
}
