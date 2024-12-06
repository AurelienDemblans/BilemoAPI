<?php

namespace App\Fixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixture extends Fixture // implements DependentFixtureInterface
{
    public const PRODUCT_ARRAY = [
        ['name' => 'Iphone 12'],
        ['name' => 'Blackberry MXS'],
        ['name' => 'galaxy pro'],
        ['name' => 'smart HX'],
        ['name' => 'iphone X'],
        ['name' => 'SMR123'],
        ['name' => 'Bilemo 666'],
        ['name' => 'Booknote 12'],
        ['name' => 'Ipad'],
        ['name' => 'A22'],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach (self::PRODUCT_ARRAY as ['name' => $name]) {
            $product = new Product();
            $date = $faker->dateTime();

            $product->setName($name)
                // ->setSlug($slug)
                ->setBrand($faker->word())
                ->setPrice($faker->randomFloat())
                ->setMemory($faker->randomNumber())
                ->setColor($faker->word())
                ->setProductionYear($faker->numberBetween(2015, 2025))
                ->setHeight($faker->randomFloat())
                ->setLenght($faker->randomFloat())
                ->setThickness($faker->randomFloat())
                ->setProductionYear($faker->numberBetween(2015, 2025))
                ->setOs($faker->word())
                ->setPhotoResolution($faker->word())
                ->setZoom($faker->randomDigit())
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($date))
            ;



            $manager->persist($product);

            // $this->addReference($ref, $trick);
        }

        $manager->flush();
    }

    // /**
    //  * @return string[]
    //  */
    // public function getDependencies(): array
    // {
    //     return [];
    // }
}
