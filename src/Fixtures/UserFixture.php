<?php

namespace App\Fixtures;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public const USER_ARRAY = [
        ['email' => 'client1@test.com', 'ref' => 'SFR','password' => 'client1'],
        ['email' => 'client2@test.com', 'ref' => 'SFR','password' => 'client2'],
        ['email' => 'client3@test.com', 'ref' => 'SFR','password' => 'client3'],
        ['email' => 'client4@test.com', 'ref' => 'Orange','password' => 'client4'],
        ['email' => 'client5@test.com', 'ref' => 'Orange','password' => 'client5'],
        // ['ref' => 'NRJPhone'],
        // ['ref' => 'NRJPhone'],
        // ['ref' => 'NRJPhone'],
        // ['ref' => 'NRJPhone'],
        // ['ref' => 'Mobile2000'],
        // ['ref' => 'Mobile2000'],
        // ['ref' => 'Mobile2000'],
        // ['ref' => 'Vodafone'],
        // ['ref' => 'Vodafone'],
        // ['ref' => 'Vodafone'],
        // ['ref' => 'Free'],
        // ['ref' => 'Free'],
        // ['ref' => 'Free'],
        // ['ref' => 'LapostMobile'],
        // ['ref' => 'LapostMobile'],
        // ['ref' => 'LapostMobile'],
        // ['ref' => 'LapostMobile'],
        // ['ref' => 'LapostMobile'],
        // ['ref' => 'LapostMobile'],
        // ['ref' => 'GeniusMobile'],
        // ['ref' => 'GeniusMobile'],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach (
            self::USER_ARRAY as $index =>
            [
                'ref' => $ref,
                'email' => $email,
                'password' => $password
            ]
        ) {
            $user = new User();
            $date = $faker->dateTime();

            $role = ['ROLE_USER'];
            if ($index === 0) {
                $role = ['ROLE_ADMIN'];
            }

            $user->setFirstname($faker->firstName())
                ->setEmail($email)
                ->setLastname($faker->lastName())
                ->setClient($this->getReference($ref, Client::class))
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($date))
                ->setRoles($role)
                ->setPassword($this->hasher->hashPassword($user, $password))
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
