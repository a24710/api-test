<?php


namespace App\Tests\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticatedWebTestCase extends WebTestCase
{
    protected const _ADMIN_FIXTURE_EMAIL = 'adminuser@email.com';
    protected const _NON_ADMIN_FIXTURE_EMAIL = 'regularuser@email.com';

    protected function getAuthenticatedClient(string $userEmail, ?KernelBrowser $client = null): ?KernelBrowser
    {
        $outClient = $client ?? static::createClient();
        $outClient->restart();
        $userRepository = static::$container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        self::assertNotNull($user);

        $outClient->loginUser($user, 'api');

        return $outClient;
    }

}