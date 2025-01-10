<?php

namespace App\Fixtures;

use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientFixture extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public const CLIENT_ARRAY = [
        ['name' => 'SFR', 'ref' => 'SFR', 'username' => 'sfr','password' => 'testSFR'],
        ['name' => 'Orange', 'ref' => 'Orange', 'username' => 'orange','password' => 'testORANGE'],
        // ['name' => 'NRJPhone', 'ref' => 'NRJPhone'],
        // ['name' => 'Mobile2000', 'ref' => 'Mobile2000'],
        // ['name' => 'Vodafone', 'ref' => 'Vodafone'],
        // ['name' => 'Free', 'ref' => 'Free'],
        // ['name' => 'LapostMobile', 'ref' => 'LapostMobile'],
        // ['name' => 'GeniusMobile', 'ref' => 'GeniusMobile'],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach (self::CLIENT_ARRAY as [
            'name' => $name,
            'ref' => $ref,
            'username' => $username,
            'password' => $password
        ]) {
            $client = new Client();
            $date = $faker->dateTime();

            $client->setName($name)
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($date))
                ->setUsername($username)
                ->setRoles(['ROLE_CLIENT'])
                ->setPassword($this->hasher->hashPassword($client, $password))
            ;

            $manager->persist($client);

            $this->addReference($ref, $client);
        }

        $manager->flush();
    }
}
