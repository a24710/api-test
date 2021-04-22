<?php


namespace App\Tests\Controller;


use App\Entity\Customer;
use App\Entity\User;


use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerTest extends AuthenticatedWebTestCase
{

    public function testCreateValidCustomersWithAdmin(): void
    {
        //try with admin
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $this->testCreateValidUsers($client);
    }

    public function testCreateValidCustomersWithNonAdmin(): void
    {
        //try with non admin. it should be the same
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $this->testCreateValidUsers($client);
    }

    public function testCreateNonValidCustomersWithAdmin(): void
    {
        //try with admin
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $this->testCreateInvalidUsers($client);
    }

    public function testCreateNonValidCustomersWithNonAdmin(): void
    {
        //try with admin
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $this->testCreateInvalidUsers($client);
    }

    public function testListAllUsersInDBAdmin(): void
    {
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $this->testListAllUsers($client);
    }

    public function testListAllUsersInDBNonAdmin(): void
    {
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $this->testListAllUsers($client);
    }

    public function testUpdateUserWithAdmin(): void
    {
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $this->testUpdateUser($client);
    }

    public function testUpdateUserWithNonAdmin(): void
    {
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $this->testUpdateUser($client);
    }

    public function testDeleteUsersWithAdmin(): void
    {
        $client = $this->getAuthenticatedClient(self::_ADMIN_FIXTURE_EMAIL);
        $this->testDeleteUser($client);
    }

    public function testUpdateUsersWithNonAdmin(): void
    {
        $client = $this->getAuthenticatedClient(self::_NON_ADMIN_FIXTURE_EMAIL);
        $this->testDeleteUser($client);
    }

    private function testUpdateUser(KernelBrowser $client): void
    {
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customerRepo = $entityManager->getRepository(Customer::class);
        $dbCustomer = $customerRepo->findOneBy([]); //get a user from db

        self::assertNotNull($dbCustomer);

        //modify it
        $customerId = $dbCustomer->getId();
        $iri = 'api/customers/' . $dbCustomer->getUuid();
        $newName = 'newName';
        $normalizedCustomer = ['name' => $newName,
            'surname' => $dbCustomer->getSurname(),
            'email' => $dbCustomer->getEmail()];
        $customerJson = json_encode($normalizedCustomer);

        $client->request(Request::METHOD_PATCH, $iri, [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], $customerJson);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $dbModifiedCustomer = $customerRepo->find($customerId);
        self::assertEquals($newName, $dbModifiedCustomer->getName());
    }

    private function testDeleteUser(KernelBrowser $client): void
    {
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customerRepo = $entityManager->getRepository(Customer::class);
        $dbCustomer = $customerRepo->findOneBy([]); //get a user from db

        self::assertNotNull($dbCustomer);

        //delete it
        $customerId = $dbCustomer->getId();
        $iri = 'api/customers/' . $dbCustomer->getUuid();
        $client->request(Request::METHOD_DELETE, $iri, [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        //check that is no longer in the db
        $deletedCustomer = $customerRepo->find($customerId);
        self::assertNull($deletedCustomer);
    }



    private function testListAllUsers(KernelBrowser $client): void
    {
        //it's created, read it back from the dataBase
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customerRepo = $entityManager->getRepository(Customer::class);

        //custom method in customerRepository
        $customerCount = $customerRepo->recordCount();

        //get the first page
        $client->request(Request::METHOD_GET,'/api/customers', [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        //check that api-platform return the correct entity count
        $decodedContent = json_decode($response->getContent(), true);
        self::assertIsArray($decodedContent);
        self::assertArrayHasKey('hydra:totalItems', $decodedContent);
        self::assertEquals($customerCount, $decodedContent['hydra:totalItems']);

        //fetch all the pages and count all the entities
        self::assertArrayHasKey('hydra:member', $decodedContent);
        self::assertIsArray($decodedContent['hydra:member']);
        $accumulatedCustomerCount = count($decodedContent['hydra:member']);

        //check the pagination system is working, fetch all pages
        $nextPage = $decodedContent['hydra:view']['hydra:next'] ?? null;

        while ($nextPage !== null){
            $client->request(Request::METHOD_GET, $nextPage, [], [], ['CONTENT_TYPE' => 'application/json']);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $decodedContent = json_decode($response->getContent(), true);
            self::assertArrayHasKey('hydra:member', $decodedContent);
            self::assertIsArray($decodedContent['hydra:member']);
            $accumulatedCustomerCount += count($decodedContent['hydra:member']);
            $nextPage = $decodedContent['hydra:view']['hydra:next'] ?? null;
        }

        //check that the sum of all customer in pages is equal to the record count in the DB
        self::assertEquals($customerCount, $accumulatedCustomerCount);
    }

    private function testCreateInvalidUsers(KernelBrowser $client): void
    {
        $invalidUserJsons = $this->getInvalidCustomerJsons();

        foreach ($invalidUserJsons as $invalidUserJson){
            $client->request(Request::METHOD_POST,'/api/customers', [], [], ['CONTENT_TYPE' => 'application/json'], $invalidUserJson);
            $response = $client->getResponse();
            self::assertTrue((Response::HTTP_UNPROCESSABLE_ENTITY === $response->getStatusCode()) ||
                             (Response::HTTP_BAD_REQUEST === $response->getStatusCode()));
        }

        //create a valid one
        $validUserJsons = $this->getValidCustomerJsons();
        $client->request(Request::METHOD_POST,'/api/customers', [], [], ['CONTENT_TYPE' => 'application/json'], $validUserJsons[0]);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        //create it again, it should fail
        $client->request(Request::METHOD_POST,'/api/customers', [], [], ['CONTENT_TYPE' => 'application/json'], $validUserJsons[0]);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

    }

    private function testCreateValidUsers(KernelBrowser $client): void
    {
        $validCustomerJsons = $this->getValidCustomerJsons();

        //create valid users
        foreach ($validCustomerJsons as $validCustomerJson){
            $client->request(Request::METHOD_POST,'/api/customers', [], [], ['CONTENT_TYPE' => 'application/json'], $validCustomerJson);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        }

        //try to create them all again, it should fail
        foreach ($validCustomerJsons as $validCustomerJson){
            $client->request(Request::METHOD_POST,'/api/customers', [], [], ['CONTENT_TYPE' => 'application/json'], $validCustomerJson);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        }
    }

    private function getValidCustomerJsons(): array
    {
        $users = [];
        $users[] = ['email' => 'flashGordon@email.com', 'name' => 'Flash', 'surname' => 'Gordon'];
        $users[] = ['email' => 'mortadelo@email.com', 'name' => 'Mortadelo', 'surname' => 'Pi'];
        $outJSons = [];

        foreach ($users as $user){
            $outJSons[] = json_encode($user);
        }

        return $outJSons;
    }

    private function getInvalidCustomerJsons(): array
    {
        $users = [];
        $users[] = ['email' => 'flashGordon', 'name' => 'Flash', 'surname' => 'Gordon'];
        $users[] = ['email' => '', 'name' => 'Mortadelo', 'surname' => 'Pi'];
        $users[] = ['email' => null, 'name' => 'Mortadelo', 'surname' => 'Pi'];
        $users[] = ['name' => 'Mortadelo', 'surname' => 'Pi'];
        $users[] = ['email' => 'flashGordon@mail.com', 'name' => null, 'surname' => 'Gordon'];
        $users[] = ['email' => 'flashGordon@mail.com', 'name' => '', 'surname' => 'Gordon'];
        $users[] = ['email' => 'flashGordon@mail.com', 'surname' => 'Gordon'];
        $users[] = ['email' => 'flashGordon@mail.com', 'name' => 'name', 'surname' => ''];
        $users[] = ['email' => 'flashGordon@mail.com', 'name' => 'name', 'surname' => null];
        $users[] = ['email' => 'flashGordon@mail.com', 'name' => 'name'];
        $users[] = ['email' => 'flashGordon@mail.com'];
        $users[] = ['surname' => 'Pi'];
        $users[] = ['name' => 'Mortadelo'];

        $outJSons = [];

        foreach ($users as $user){
            $outJSons[] = json_encode($user);
        }

        return $outJSons;
    }



}