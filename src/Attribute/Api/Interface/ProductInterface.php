<?php

namespace App\Attribute\Api\Interface;

use DateTimeInterface;
use OpenApi\Attributes\Property;

interface ProductInterface extends DateInterface, IdInterface, NameInterface
{
    #[Property(property: 'brand', type: 'string', example: 'Samsung')]
    public function getBrand(): string;

    #[Property(property: 'price', type: 'number', example: 345.65)]
    public function getPrice(): float;

    #[Property(property: 'memory', type: 'integer', example: '248')]
    public function getMemory(): string;

    #[Property(property: 'color', type: 'string', example: 'black')]
    public function getColor(): string;

    #[Property(property: 'productionYear', type: 'integer', example: 2023)]
    public function getProductionYear(): int;

    #[Property(property: 'height', type: 'number', example: 45.23)]
    public function getHeight(): float;

    #[Property(property: 'Lenght', type: 'number', example: 45.23)]
    public function getLenght(): float;

    #[Property(property: 'OS', type: 'string', example: 'Android')]
    public function getOs(): string;

    #[Property(property: 'thickness', type: 'number', example: 45.23)]
    public function getThickness(): float;

    #[Property(property: 'photoResolution', type: 'string')]
    public function getPhotoResolution(): string;

    #[Property(property: 'zoom', type: 'integer', example:2)]
    public function getZoom(): string;
}
