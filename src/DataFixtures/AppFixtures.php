<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use App\Services\CustomerManager;
use App\Services\UserManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    public const _ADMIN_FIXTURE_EMAIL = 'adminuser@email.com';
    public const _NOT_ADMIN_FIXTURE_EMAIL = 'regularuser@email.com';

    private const _CUSTOMER_COUNT = 100;
    private UserPasswordEncoderInterface $passwordEncoder;
    private UserManager $userManager;
    private CustomerManager $customerManager;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        UserManager $userManager,
        CustomerManager $customerManager)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->userManager = $userManager;
        $this->customerManager = $customerManager;
    }

    public function load(ObjectManager $manager)
    {
        $this->loadUsers();
        $this->loadCustomers();

        $manager->flush();
    }

    private function loadUsers()
    {
        //admin user
        $adminUser = new User();
        $adminUser->setRoles([User::_ROLE_ADMIN, User::_ROLE_USER]);

        //$adminUser->setPassword($encodedPassword);
        $adminUser->setEmail(self::_ADMIN_FIXTURE_EMAIL);
        $encodedPassword = $this->passwordEncoder->encodePassword($adminUser, 'mypassword');
        $adminUser->setPassword($encodedPassword);

        //regular user
        $regularUser = new User();
        $regularUser->setRoles([User::_ROLE_USER]);
        $regularUser->setPassword($encodedPassword);
        $regularUser->setEmail(self::_NOT_ADMIN_FIXTURE_EMAIL);
        $encodedPassword = $this->passwordEncoder->encodePassword($regularUser, 'mypassword');
        $adminUser->setPassword($encodedPassword);

        $this->userManager->store($adminUser);
        $this->userManager->store($regularUser);
    }

    private function loadCustomers()
    {
        $faker = Factory::create();

        for ($i = 0; $i<self::_CUSTOMER_COUNT; $i++){
            $fakeEmail = $faker->unique()->safeEmail;
            $fakeName = $faker->firstName;
            $fakeSurname = $faker->lastName;

            $customer = new Customer();
            $customer->setName($fakeName);
            $customer->setSurname($fakeSurname);
            $customer->setEmail($fakeEmail);

            $this->customerManager->store($customer);
        }
    }
}
