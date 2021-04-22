<?php


namespace App\Tests\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AuthenticatedWebTestCase
{
    public function testCreateUsersByAdmin()
    {
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $validUserJsons = $this->generateValidUserJsons();

        //create valid users
        foreach ($validUserJsons as $validUserJson){
            $client->request(Request::METHOD_POST,'/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $validUserJson);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        }

        //try to create another user with the same email again
        $client->request(Request::METHOD_POST,'/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $validUserJson[0]);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        //try to create users with invalid data
        $invalidUserJsons = $this->generateInvalidUserJsons();

        foreach ($invalidUserJsons as $invalidUserJson){
            $client->request(Request::METHOD_POST,'/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $invalidUserJson);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        }
    }

    public function testCreateUsersByRegularUser()
    {
        //not admin users should not be able to do this
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $validUserJsons = $this->generateValidUserJsons();

        //forbidden responses expected
        foreach ($validUserJsons as $validUserJson){
            $client->request(Request::METHOD_POST,'/api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $validUserJson);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        }
    }

    public function testUserGet()
    {
        //not admin user should not has access to this method
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $client->request(Request::METHOD_GET, 'api/users', [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        //admin user should receive a list of multiple users
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL, $client);
        $client->request(Request::METHOD_GET, 'api/users', [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseContent = $response->getContent();
        $responseArray = json_decode($responseContent, true);
        self::assertArrayHasKey('hydra:member', $responseArray);

        $users = $responseArray['hydra:member'];
        self::assertIsArray($users);
        self::assertTrue(count($users) > 0);
    }

    public function testUserUpdateAndDelete()
    {
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);

        //create a new user first
        $userMail = 'newUser@mail.com';
        $newUserJson = json_encode(['email' => $userMail, 'roles' => [User::_ROLE_USER]]);
        $client->request(Request::METHOD_POST, 'api/users', [], [], ['CONTENT_TYPE' => 'application/json'], $newUserJson);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        //it's created, read it back from the dataBase
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $entityManager->getRepository(User::class);
        $dbUser = $userRepo->findOneBy(['email' => $userMail]);
        self::assertNotNull($dbUser);

        //get it's uuid to form the iri
        $iri = 'api/users/' . $dbUser->getUuid();

        //modify the email and add an admin role
        $userModifiedEmail = 'modified@email.com';
        $newUserJson = json_encode(['email' => $userModifiedEmail, 'roles' => [User::_ROLE_USER, User::_ROLE_ADMIN]]);

        //reset client and launch PATCH
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL, $client);
        $client->request(Request::METHOD_PATCH, $iri, [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], $newUserJson);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        //read it back from the DB to check if the email changed
        $dbModifiedUser = $userRepo->findOneBy(['email' => $userModifiedEmail]);
        self::assertNotNull($dbModifiedUser);
        self::assertEquals($dbModifiedUser->getEmail(), $userModifiedEmail);

        //check if the role changed to admin
        self::assertContains(User::_ROLE_ADMIN, $dbModifiedUser->getRoles());

        //check if the regular user can modify it
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL, $client);
        $client->request(Request::METHOD_PATCH, $iri, [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], $newUserJson);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        //check if the regular user can delete it
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL, $client);
        $client->request(Request::METHOD_DELETE, $iri, [], [], ['CONTENT_TYPE' => 'application/json'], $newUserJson);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        //check if the admin can delete it
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL, $client);
        $client->request(Request::METHOD_DELETE, $iri, [], [], ['CONTENT_TYPE' => 'application/json'], $newUserJson);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        //check it's no longer in the database
        $dbUser = $userRepo->findOneBy(['email' => $userModifiedEmail]);
        self::assertNull($dbUser);
    }

    private function generateValidUserJsons(): array
    {
        $users = [];
        $users[] = ['email' => 'johnRegular@email.com', 'roles' => [User::_ROLE_USER]];
        $users[] = ['email' => 'johnAdmin@email.com', 'roles' => [User::_ROLE_USER, User::_ROLE_ADMIN]];
        $outJSons = [];

        foreach ($users as $user){
            $outJSons[] = json_encode($user);
        }

        return $outJSons;
    }

    private function generateInvalidUserJsons(): array
    {
        $users = [];

        //wrong emails
        $users[] = ['email' => 'wrongEmail@email', 'roles' => [User::_ROLE_USER]];
        $users[] = ['email' => '', 'roles' => [User::_ROLE_USER]];
        $users[] = ['roles' => [User::_ROLE_USER]];

        //wrong roles
        $users[] = ['email' => 'right@email.com', 'roles' => ['WRONG_ROLE']];

        $outJSons = [];

        foreach ($users as $user){
            $outJSons[] = json_encode($user);
        }

        return $outJSons;
    }
}

