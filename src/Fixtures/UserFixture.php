<?php

namespace App\Fixtures;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class UserFixture extends Fixture implements DependentFixtureInterface
{
    public const USER_ARRAY = [
        ['ref' => 'SFR'],
        ['ref' => 'SFR'],
        ['ref' => 'SFR'],
        ['ref' => 'Orange'],
        ['ref' => 'Orange'],
        ['ref' => 'NRJPhone'],
        ['ref' => 'NRJPhone'],
        ['ref' => 'NRJPhone'],
        ['ref' => 'NRJPhone'],
        ['ref' => 'Mobile2000'],
        ['ref' => 'Mobile2000'],
        ['ref' => 'Mobile2000'],
        ['ref' => 'Vodafone'],
        ['ref' => 'Vodafone'],
        ['ref' => 'Vodafone'],
        ['ref' => 'Free'],
        ['ref' => 'Free'],
        ['ref' => 'Free'],
        ['ref' => 'LapostMobile'],
        ['ref' => 'LapostMobile'],
        ['ref' => 'LapostMobile'],
        ['ref' => 'LapostMobile'],
        ['ref' => 'LapostMobile'],
        ['ref' => 'LapostMobile'],
        ['ref' => 'GeniusMobile'],
        ['ref' => 'GeniusMobile'],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach (self::USER_ARRAY as $index => ['ref' => $ref]) {
            $user = new User();
            $date = $faker->dateTime();

            $role = ['ROLE_USER'];
            if ($index === 0) {
                $role = ['ROLE_ADMIN'];
            }

            $user->setFirstname($faker->firstName())
            ->setEmail($faker->email())
            ->setRoles($role)
            ->setPassword($faker->password())
            ->setLastname($faker->lastName())
            ->setClient($this->getReference($ref, Client::class))
            ->setCreatedAt(\DateTimeImmutable::createFromMutable($date))
            ;



            $manager->persist($user);

        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [ClientFixture::class];
    }
}
